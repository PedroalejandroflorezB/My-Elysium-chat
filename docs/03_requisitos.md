# 03. Requisitos — Elysium Ito

## Requisitos Funcionales

- **RF-01**: El sistema debe permitir el inicio de sesión seguro con email y contraseña, con soporte para autenticación Google OAuth2.
- **RF-02**: El sistema debe permitir el envío de mensajes de texto en tiempo real con indicadores de presencia (online/offline).
- **RF-03**: El sistema debe soportar transferencia P2P directa entre navegadores via WebRTC DataChannel, sin almacenar bytes en el servidor.
- **RF-04**: Los administradores deben poder gestionar (CRUD) las cuentas de usuario con control de roles jerárquico.
- **RF-05**: El sistema debe soportar la subida de archivos de hasta 1 GB por fragmentos (chunks de 10 MB, 3 en paralelo).
- **RF-06**: El sistema debe sincronizar archivos automáticamente con la cuenta de Google Drive del usuario (backup dual: emisor y receptor).
- **RF-07**: El sistema debe permitir la eliminación física de mensajes y conversaciones para todos los participantes.
- **RF-08**: El formulario de contacto del home debe enrutar los mensajes al email del Super Usuario registrado en base de datos.
- **RF-09**: El Super Usuario debe poder ser creado o reemplazado desde CLI mediante `php artisan super:setup`, usando credenciales definidas en `.env` como mecanismo de recuperación de acceso.

## Requisitos No Funcionales

- **RNF-01 (Seguridad)**: Los tokens OAuth2 de Google deben almacenarse encriptados en base de datos. Campos sensibles (`google_access_token`, `google_refresh_token`) ocultos en respuestas JSON.
- **RNF-02 (Rendimiento)**: Las transferencias a Google Drive deben ejecutarse en background mediante Jobs y Queues, sin bloquear el hilo principal del servidor.
- **RNF-03 (Escalabilidad)**: El almacenamiento de archivos debe delegarse a Google Drive personal para mantener el uso de disco del servidor en $0.
- **RNF-04 (Usabilidad)**: La interfaz debe ser responsive y soportar Modo Oscuro (Tailwind CSS v4 + clase `dark` en `<html>`).
- **RNF-05 (Cumplimiento)**: Los registros de aceptación de términos deben cumplir con la Ley 1581 (IP y fecha de consentimiento almacenadas).
- **RNF-06 (Resiliencia)**: Los Jobs de subida a Drive deben reintentar hasta 3 veces. El disco del servidor se monitorea cada hora; al superar el 90% de uso se bloquean nuevas subidas.
- **RNF-07 (Recuperación)**: El sistema debe garantizar que el Super Usuario siempre pueda ser restaurado desde CLI sin acceso a la interfaz web, usando variables de entorno como credenciales de emergencia.

## Casos de Uso

### CU-01: Transferencia de Archivo Pesado (Chunked Upload)

- **Actor**: Usuario Emisor.
- **Precondición**: El usuario está autenticado y tiene una conversación abierta.
- **Flujo Principal**:
    1. El emisor selecciona un archivo (hasta 1 GB).
    2. El frontend divide el archivo en fragmentos de 10 MB con `File.slice()` y genera un `uploadId` UUID v4.
    3. Se envían hasta 3 chunks en paralelo vía `multipart/form-data`.
    4. El servidor ensambla los chunks con `stream_copy_to_stream`, valida Magic Bytes (MIME real) y crea el mensaje en BD.
    5. Se despachan dos Jobs independientes: uno sube al Drive del emisor, otro al del receptor.
    6. Al finalizar, el archivo temporal se elimina del servidor y se notifica al receptor con el link de Drive.
- **Poscondición**: El archivo es accesible desde Google Drive. El disco del servidor queda vacío.

### CU-02: Transferencia P2P (WebRTC DataChannel)

- **Actor**: Usuarios del Chat.
- **Flujo Principal**:
    1. El emisor inicia `p2pSendFile()` — el servidor retransmite un `transfer-request` al receptor via Pusher.
    2. El receptor acepta — se intercambian SDP offer/answer e ICE candidates via Pusher como servidor de señalización.
    3. Se establece el DataChannel directo entre navegadores.
    4. El archivo se transfiere en chunks de 64 KB directamente entre navegadores.
    5. Al completar, el servidor solo guarda los metadatos del mensaje (nombre, tamaño, tipo) — nunca los bytes.
- **Poscondición**: El archivo llega al receptor. El servidor no almacena ningún byte del archivo.

### CU-03: Sincronización en Tiempo Real

- **Actor**: Usuarios del Chat.
- **Flujo Principal**:
    1. Un usuario envía un mensaje.
    2. El servidor procesa el mensaje y emite un evento vía Pusher al canal `private-chatify.{userId}`.
    3. El receptor recibe el evento y actualiza su UI instantáneamente sin recargar la página.
    4. El estado de presencia (online/offline) se gestiona via canal `presence-activeStatus`.

### CU-04: Recuperación del Super Usuario

- **Actor**: Operador del sistema (acceso al servidor).
- **Precondición**: Se ha perdido el acceso a la cuenta del Super Usuario.
- **Flujo Principal**:
    1. El operador accede al servidor via SSH o terminal local.
    2. Ejecuta `php artisan super:setup` (usa `SUPER_USER_EMAIL` y `SUPER_USER_PASSWORD` del `.env`).
    3. O bien ejecuta `php artisan super:setup --email=nuevo@correo.com --password=nuevaClave123` para credenciales distintas.
    4. El comando desactiva el flag `is_super` del usuario anterior y crea/actualiza el nuevo.
- **Poscondición**: El Super Usuario queda restaurado. Solo existe uno en el sistema.
