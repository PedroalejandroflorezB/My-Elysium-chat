# 04. Historias de Usuario — Elysium Ito

## HU-01: Gestión de Usuarios (Admin)

- **Descripción**: Como Administrador, quiero poder crear, editar, activar o desactivar cuentas de usuario para controlar el acceso a la plataforma.
- **Prioridad**: Alta
- **Criterios de Aceptación**:
    - El panel muestra el estado actual (Activo/Bloqueado) y el rol de cada usuario.
    - Un usuario desactivado pierde el acceso al sistema en su siguiente petición (sesión revocada).
    - El Super Usuario no aparece en el listado ni puede ser modificado por un Admin.
    - Los campos sensibles (`google_access_token`, `google_refresh_token`) no se exponen en respuestas JSON.

## HU-02: Mensajería en Tiempo Real

- **Descripción**: Como Usuario, quiero enviar mensajes de texto y ver si el receptor está en línea para tener una comunicación fluida.
- **Prioridad**: Crítica
- **Criterios de Aceptación**:
    - Los mensajes aparecen instantáneamente sin recargar la página (canal `private-chatify.{userId}`).
    - El indicador de presencia (punto verde/gris) se actualiza en tiempo real via canal `presence-activeStatus`.
    - El input de escritura se oculta cuando no hay chat activo.

## HU-03: Respaldo en la Nube (Google Drive)

- **Descripción**: Como Usuario, quiero que mis archivos se guarden automáticamente en mi Google Drive para no depender del almacenamiento del servidor.
- **Prioridad**: Alta
- **Criterios de Aceptación**:
    - La subida a Drive ocurre en background (Job en cola `drive-uploads`) — el chat no se bloquea.
    - El estado de sincronización se muestra en la burbuja del mensaje: `processing` (spinner) → `synced` (link) → `failed` (botón "Reintentar") → `error_authorization` (enlace "Vincular Drive").
    - El backup es dual: se sube una copia al Drive del emisor y otra al del receptor de forma independiente.
    - El disco del servidor queda vacío tras completar la subida.

## HU-04: Transferencia de Archivos Pesados

- **Descripción**: Como Usuario, quiero enviar archivos de hasta 1 GB sin que el chat se congele ni la subida falle por timeout.
- **Prioridad**: Crítica
- **Criterios de Aceptación**:
    - El archivo se divide en chunks de 10 MB y se envían 3 en paralelo.
    - Una barra de progreso muestra el porcentaje, velocidad (MB/s) y tiempo estimado restante.
    - El botón de envío se deshabilita durante la subida para prevenir doble envío.
    - El servidor valida los Magic Bytes del archivo ensamblado (MIME real vs extensión declarada).

## HU-05: Transferencia P2P (Privacidad Absoluta)

- **Descripción**: Como Usuario, quiero poder enviar archivos directamente al receptor sin que pasen por el servidor para garantizar la máxima privacidad.
- **Prioridad**: Media
- **Criterios de Aceptación**:
    - El receptor recibe una notificación con nombre y tamaño del archivo y puede aceptar o rechazar.
    - La transferencia ocurre directamente entre navegadores via WebRTC DataChannel.
    - El servidor solo guarda los metadatos del mensaje (nombre, tamaño, tipo) — nunca los bytes del archivo.
    - Al completar, el archivo se descarga automáticamente en el navegador del receptor.

## HU-06: Formulario de Contacto (Super Usuario)

- **Descripción**: Como visitante del sitio, quiero poder enviar un mensaje de contacto desde el home para comunicarme con el administrador de la plataforma.
- **Prioridad**: Media
- **Criterios de Aceptación**:
    - El formulario valida que el email esté registrado en la plataforma antes de enviar.
    - El mensaje llega al email del Super Usuario registrado en base de datos.
    - Si no hay Super Usuario en BD, el sistema usa `MAIL_ADMIN_ADDRESS` del `.env` como fallback.
    - Rate limiting: máx 3 mensajes por IP cada 24 horas.

## HU-07: Recuperación del Super Usuario

- **Descripción**: Como operador del sistema, quiero poder restaurar el acceso del Super Usuario desde la línea de comandos en caso de pérdida de credenciales.
- **Prioridad**: Crítica
- **Criterios de Aceptación**:
    - El comando `php artisan super:setup` crea o reemplaza el Super Usuario usando `SUPER_USER_EMAIL` y `SUPER_USER_PASSWORD` del `.env`.
    - Se puede especificar email y contraseña directamente con `--email=` y `--password=` para emergencias sin acceso al `.env`.
    - El comando garantiza que solo exista un Super Usuario en todo momento.
    - Pide confirmación antes de reemplazar un Super Usuario existente (salvo `--force`).
