# Arquitectura de Transferencia y Persistencia — Elysium Ito

## Resumen ejecutivo

Elysium Ito resuelve el problema de transferir archivos de hasta 1GB en un chat en tiempo real con **costo de almacenamiento $0**, usando Google Drive personal de cada usuario como capa de persistencia.

---

## Capas del sistema

### Capa 1 — Transporte (Frontend)

Se implementó un sistema de **Chunked Upload** mediante JavaScript nativo (`File.slice()`).

- El archivo se fragmenta en bloques de **10MB** para evitar el desbordamiento de memoria RAM del servidor.
- Se envían hasta **3 chunks en paralelo** para maximizar el uso del ancho de banda.
- Cada chunk se envía con `fetch` + `async/await` — nunca con `<form>` submit tradicional.
- El estado de la subida (progreso, `uploadId`, chunks enviados) se guarda en variables JS del módulo, no en el DOM.
- Si el WebSocket se desconecta durante la subida, la subida continúa — son requests HTTP independientes.
- El `uploadId` (UUID v4) identifica la sesión de subida y permite reanudar si se implementa retry.

### Capa 2 — Procesamiento (Backend)

Laravel 12 actúa como **puente orquestador**. Nunca almacena archivos permanentemente.

- `ChunkedUploadController` recibe cada chunk y lo escribe en `storage/app/private/chunks/{uploadId}/`.
- Al llegar el último chunk, ensambla el archivo con `stream_copy_to_stream` (más eficiente que `fread/fwrite`).
- Lock atómico con `rename()` para evitar race conditions en el ensamblado.
- Límite de 3 uploads concurrentes por usuario via `Cache`.
- Rate limiting: 200 chunks/minuto por usuario.
- Al completar el ensamblaje, copia el archivo a `storage/app/private/drive_tmp/` y despacha `UploadFileToDrive::dispatch()` a la cola `drive-uploads`.

### Capa 3 — Almacenamiento (Cloud Bridge)

Mediante la **API de Google Drive (OAuth2 + scope `drive.file`)**, el Job sube el archivo al Drive personal del usuario.

- Protocolo **Resumable Upload** obligatorio para archivos >5MB — permite reanudar si se corta la conexión.
- Al finalizar, el servidor elimina el archivo temporal (`Storage::delete($tmpPath)`) — disco siempre vacío.
- Se otorgan permisos `role=reader, type=anyone` para generar un `webViewLink` compartible.
- Costo de almacenamiento para el administrador: **$0**. Cada usuario usa sus 15GB gratuitos de Gmail.

### Capa 4 — Tiempo Real (Broadcasting)

Sistema de **Broadcasting Dual** via Pusher (desarrollo) / Reverb (producción).

El evento `DriveUploadUpdated` notifica simultáneamente al emisor y al receptor sobre **5 estados**:

| Estado | Significado | UI |
|---|---|---|
| `local` | Sin Drive vinculado | No se emite evento |
| `processing` | Job en cola, subiendo | Burbuja azul con spinner |
| `synced` | Completado | Burbuja verde con link de Drive |
| `failed` | Agotó 3 reintentos | Burbuja roja con botón "Reintentar" |
| `error_authorization` | Token revocado por Google | Burbuja naranja con enlace "Vincular Drive" |

- `broadcastOn()` retorna array de dos `PrivateChannel`: receptor (`toUserId`) y emisor (`fromUserId`).
- `broadcastWith()` devuelve solo lo mínimo: `message_id`, `status`, `drive_link`, `file_name`.
- El JS en `app.blade.php` escucha `channel.bind('drive-upload-updated')` y actualiza quirúrgicamente el DOM por `data-id` — sin re-fetch al servidor.

---

## Flujo completo

```
1. Usuario adjunta archivo (hasta 1GB) en el chat
2. Frontend divide en chunks de 10MB y envía secuencialmente (3 en paralelo)
3. ChunkedUploadController ensambla en storage/app/private/chunks/{uploadId}/
4. Controller crea ChMessage con drive_status='processing'
5. Controller copia archivo a drive_tmp/ y despacha UploadFileToDrive al queue
6. Controller emite DriveUploadUpdated(processing) → ambas burbujas muestran spinner
7. Job sube a Google Drive via Resumable Upload (chunks de 5MB)
8. Job otorga permisos reader/anyone → genera webViewLink
9. Job actualiza ChMessage con drive_status='synced' + webViewLink
10. Job emite DriveUploadUpdated(synced) → burbujas cambian a link de Drive
11. Job llama Storage::delete($tmpPath) → disco vacío
12. Si Job agota $tries=3 → drive_status='failed' → burbuja roja persistente
13. Si Google devuelve 401/invalid_grant → drive_status='error_authorization' → burbuja naranja
```

---

## Bóveda Ito — File Picker de Google Drive

Complemento al flujo de transferencia: permite al usuario seleccionar un archivo ya existente en su Drive y compartir su enlace en el chat, sin subir nada al servidor.

### Componentes

| Componente | Archivo | Responsabilidad |
|---|---|---|
| Panel flotante | `resources/views/chatify.blade.php` | HTML + CSS del panel, botón trigger en el sendForm |
| Botón trigger | `resources/views/vendor/Chatify/layouts/sendForm.blade.php` | Botón 🗄️ junto al clip de adjuntos |
| Lógica JS | `public/js/chatify/vault.js` | Fetch a `/vault/files`, render de grid, selección e inserción en input |
| Backend | `GoogleDriveController@vaultFiles` | Lista archivos con `drive.readonly`, refresca token si expiró |
| Ruta | `GET /vault/files` | Protegida con `auth:sanctum` + `verified` |

### Flujo

```
1. Usuario hace clic en 🗄️ en el formulario de envío
2. vault.js abre el panel flotante y llama GET /vault/files
3. GoogleDriveController refresca el token si expiró y llama Drive API
4. Drive API devuelve archivos (id, name, mimeType, thumbnailLink, webContentLink, webViewLink)
5. vault.js renderiza grid con thumbnails (o emoji de tipo si no hay thumbnail)
6. Usuario hace clic en un archivo → se inserta "📎 nombre\nlink" en el textarea del chat
7. Panel se cierra automáticamente, el foco queda en el input
```

### Scopes requeridos

| Scope | Propósito |
|---|---|
| `drive.file` | Subir archivos al Drive del usuario (backup de mensajes) |
| `drive.readonly` | Leer y listar todos los archivos del Drive (Bóveda Ito) |

> Usuarios que vincularon Drive antes de agregar `drive.readonly` deben reconectar en `/auth/google/drive`.

- **Ley 1581 de 2012**: Los archivos no residen en el servidor — facilita protección de datos personales.
- **NTC 5854 / WCAG 2.1 AA**: Los 5 estados de error tienen feedback visual con ícono + texto (no solo color). Las burbujas de error son persistentes — no desaparecen con toast.
- **Privacidad por diseño**: El link de Drive pertenece al Drive del usuario, no al servidor.

---

## Decisiones de diseño relevantes

| Decisión | Alternativa descartada | Razón |
|---|---|---|
| `QUEUE_CONNECTION=database` | Redis / SQS | Costo $0, suficiente para el volumen del proyecto |
| `Http::` de Laravel para Drive API | `google/apiclient` | Dependencia `firebase/php-jwt` con advisories de seguridad activos |
| Chunked Upload propio | Librerías como Uppy | Control total, sin dependencias externas |
| `drive.file` scope | `drive` scope completo | Acceso mínimo — solo archivos creados por la app |
| `drive.readonly` scope (Bóveda) | `drive` scope completo | Permite leer todos los archivos del usuario sin permiso de escritura — principio de mínimo privilegio |
| Inline styles en burbujas Drive | Clases CSS externas | Las burbujas se inyectan dinámicamente via JS — los estilos deben viajar con el HTML |
