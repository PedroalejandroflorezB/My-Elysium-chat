# Referencia de Formularios — Elysium Ito

Documento técnico completo de todos los formularios, endpoints, validaciones y flujos de la aplicación.

---

## Índice

1. [Registro de usuario](#1-registro-de-usuario)
2. [Login](#2-login)
3. [Login con Google](#3-login-con-google)
4. [Recuperación de contraseña](#4-recuperación-de-contraseña)
5. [Formulario de mensajes (texto)](#5-formulario-de-mensajes-texto)
6. [Subida de archivos (chunked upload)](#6-subida-de-archivos-chunked-upload)
7. [Edición de mensaje](#7-edición-de-mensaje)
8. [Actualización de perfil propio](#8-actualización-de-perfil-propio)
9. [Avatar — subida de archivo](#9-avatar--subida-de-archivo)
10. [Avatar — desde URL DiceBear](#10-avatar--desde-url-dicebear)
11. [Google Drive — vincular](#11-google-drive--vincular)
12. [Google Drive — toggle backup](#12-google-drive--toggle-backup)
13. [Google Drive — desvincular](#13-google-drive--desvincular)
14. [Administración — crear usuario](#14-administración--crear-usuario)
15. [Administración — editar usuario](#15-administración--editar-usuario)
16. [Administración — eliminar usuario](#16-administración--eliminar-usuario)
17. [Administración — toggle estado](#17-administración--toggle-estado)
18. [Formulario de contacto (público)](#18-formulario-de-contacto-público)
19. [Formulario de contacto (admin)](#19-formulario-de-contacto-admin)
20. [King Challenge — verificación](#20-king-challenge--verificación)
21. [King — reset de contraseña](#21-king--reset-de-contraseña)
22. [WebRTC — señalización P2P](#22-webrtc--señalización-p2p)
23. [WebRTC — guardar transferencia P2P](#23-webrtc--guardar-transferencia-p2p)
24. [Ajustes del messenger (vendor)](#24-ajustes-del-messenger-vendor)
25. [Búsqueda de usuarios](#25-búsqueda-de-usuarios)

---

## 1. Registro de usuario

**Vista:** `resources/views/auth/register.blade.php` (Jetstream/Fortify)
**Acción:** `App\Actions\Fortify\CreateNewUser`
**Método:** `POST /register`
**Middleware:** público

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | required, min:2, max:255, strip_tags, sin caracteres de control Unicode |
| `email` | string | required, email:rfc,dns, max:255, unique:users, lowercase, trim |
| `password` | string | required, min:8, mixedCase, numbers, symbols, confirmed |
| `password_confirmation` | string | debe coincidir con `password` |
| `data_consent` | checkbox | required, accepted (Ley 1581 de 2012) |
| `terms` | checkbox | accepted si Jetstream tiene `termsAndPrivacyPolicy` habilitado |

### Pre-procesamiento

- `name`: elimina caracteres de control Unicode (`\x00-\x08`, `\x0B`, `\x0C`, `\x0E-\x1F`, `\x7F`, `\xC2\xA0`), luego `strip_tags`, luego `trim`.
- `email`: `strtolower(trim(...))`.

### Lógica post-validación

1. Genera avatar automático con `User::generateAvatar($name)` (DiceBear).
2. Genera `tagname` único: base alfanumérica del nombre + sufijo numérico de N dígitos (N escala con `User::count()`). Ej: `jackbossman_42`. Reintenta hasta encontrar uno libre.
3. Crea el usuario con `data_consent=true`, `data_consent_at=now()`, `accepted_privacy_at=now()`, `privacy_version='ARK-2026-01'`, `registration_ip`.
4. Redirige a verificación de email si está habilitada.

### Errores personalizados

- `data_consent.accepted` → "Debes autorizar el tratamiento de datos personales según la Ley 1581 de 2012."

---

## 2. Login

**Vista:** `resources/views/auth/login.blade.php` (Fortify)
**Método:** `POST /login`
**Middleware:** público (con throttle de Fortify)

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | required, email |
| `password` | string | required |
| `remember` | checkbox | opcional |

### Notas

- Gestionado íntegramente por Fortify. Sin personalización adicional.
- Tras login exitoso redirige a `route('chatify')`.
- El usuario King pasa por `KingChallengeMiddleware` antes de acceder al dashboard.

---

## 3. Login con Google

**Ruta redirect:** `GET /auth/google` → `GoogleAuthController@redirect`
**Ruta callback:** `GET /auth/google/login-callback` → `GoogleAuthController@callback`
**Middleware:** público

### Scopes solicitados

```
openid, profile, email, https://www.googleapis.com/auth/drive.file
```

`access_type=offline`, `prompt=consent` → garantiza `refresh_token` en el primer intercambio.

### Flujo del callback

1. Busca usuario por `google_id`.
2. Si no existe, busca por `email` (cuenta manual) → vincula `google_id`.
3. Si no existe, crea cuenta nueva con contraseña aleatoria de 32 chars, avatar DiceBear, tagname generado.
4. En todos los casos: actualiza `google_access_token` (encriptado), preserva `google_refresh_token` existente si Google no devuelve uno nuevo, guarda `google_token_expires_at`.
5. `Auth::login($user, remember: true)` + `session()->regenerate()`.
6. Redirige a `route('chatify')`.

### Sanitización del nombre

- Elimina caracteres de control (`\x00-\x1F`, `\x7F`).
- `strip_tags`.
- `Str::limit($name, 40, '')`.

---

## 4. Recuperación de contraseña

### 4a. Flujo estándar (Fortify)

**Método:** `POST /forgot-password` → envía email con link
**Método:** `POST /reset-password` → `App\Actions\Fortify\ResetUserPassword`

#### Campos del reset

| Campo | Tipo | Reglas |
|---|---|---|
| `token` | string | required (viene en la URL) |
| `email` | string | required, email |
| `password` | string | required, min:8, mixedCase, numbers, symbols, confirmed |
| `password_confirmation` | string | debe coincidir |

#### Lógica especial

Al resetear, si el usuario tenía `google_id`, se pone a `null`. Esto permite que el usuario gestione su contraseña desde el perfil a partir de ese momento.

### 4b. King — reset por puzzle

Ver sección [21. King — reset de contraseña](#21-king--reset-de-contraseña).

---

## 5. Formulario de mensajes (texto)

**Vista:** `resources/views/vendor/Chatify/layouts/sendForm.blade.php`
**Ruta:** `POST /chatify/sendMessage` → `ChatController@send`
**Middleware:** auth, verified

### HTML del formulario

```html
<form id="message-form" method="POST" action="{{ route('send.message') }}" enctype="multipart/form-data">
    @csrf
    <button type="button" id="vault-trigger">🗄️</button>
    <label class="send-attach-btn">
        <input type="file" class="upload-attachment" name="file" multiple
               accept=".png,.jpg,...,.zip,.pdf,..."/>
    </label>
    <textarea name="message" class="m-send" placeholder="Escribe un mensaje..."></textarea>
    <button disabled class="send-button">...</button>
</form>
```

### Envío JS (text-only path)

`sendMessage()` en `code.js`:

1. Incrementa `temporaryMsgId` → `temp_N`.
2. Si no hay archivo y no hay texto → `return false`.
3. Si solo texto: `FormData` del form + `id` (contacto) + `temporaryMsgId` + `_token`.
4. `$.ajax POST /chatify/sendMessage`.
5. `beforeSend`: inserta `sendTempMessageCard` en el DOM, limpia el textarea.
6. `success`: reemplaza la tarjeta temporal con `data.message`.

### Backend `ChatController@send`

1. Borra registros de `chat_deletions` para ambos lados (el chat "reaparece" si fue eliminado).
2. Delega a `VendorMessagesController@send` (Chatify).

### Activación del botón de envío

- El botón empieza `disabled`.
- Se habilita cuando el usuario escribe texto (listener `input` en el textarea) o selecciona un archivo (listener `change` en `.upload-attachment`).
- Se deshabilita de nuevo al enviar y se re-habilita cuando todos los archivos del queue terminan.

---

## 6. Subida de archivos (chunked upload)

**Ruta:** `POST /chatify/upload-chunk` → `ChunkedUploadController@upload`
**Middleware:** auth, verified
**Cola:** `drive-uploads` (worker: `php artisan queue:work --queue=drive-uploads,default --timeout=900`)

### Flujo completo frontend → backend

#### Paso 1 — Selección de archivos

El usuario selecciona uno o más archivos con el `<input type="file" multiple>`.

El listener `change` en `code.js` valida cada archivo con `attachmentValidate()`:
- Extensión en `chatify.allAllowedExtensions`.
- Tamaño ≤ `chatify.maxUploadSize` (por defecto 1 GB, configurable en `.env` con `CHATIFY_MAX_FILE_SIZE`).

Los archivos válidos se pasan a `window.arkAddFilesToPreview(files)` que:
- Genera un `tempID` único por archivo: `pre_<timestamp>_<random>`.
- Crea una tarjeta visual (80×80px) con miniatura (imagen) o icono (otros tipos).
- Añade la tarjeta al `#ark-file-stack`.
- Registra `{ file, card, status: 'pending' }` en `window._arkFileQueue[tempID]`.
- Actualiza el contador `#ark-file-counter`.

#### Paso 2 — Envío

Al pulsar enviar o Enter, `sendMessage()` detecta que `_arkFileQueue` tiene entradas.

Para cada entrada con `status === 'pending'` o `'error'`:
1. Inserta `sendTempMessageCard` en el chat.
2. Llama `window.arkCardUploading(tempID)` → muestra el ring de progreso, oculta el botón X.
3. Llama `sendChunked(file, inputValue, tempID)`.

#### Paso 3 — `sendChunked(file, inputValue, tempID)`

Parámetros de configuración:
- `CHUNK_SIZE = 10 MB`
- `PARALLEL_MAX = 3` chunks simultáneos

Por cada chunk:
- Genera un `uploadId` UUID v4 (una vez por archivo).
- Crea `FormData` con: `chunk`, `chunkIndex`, `totalChunks`, `uploadId`, `fileName`, `id` (contacto), `message`, `temporaryMsgId`, `_token`.
- `XHR.upload.progress` → calcula porcentaje total y llama `window.arkCardProgress(tempID, pct)` que actualiza el SVG ring (`stroke-dashoffset`).
- Ventana deslizante de velocidad (4 s) para calcular bytes/s y tiempo restante.

#### Paso 4 — Backend `ChunkedUploadController@upload`

**Rate limiting:** 200 requests/min por usuario (cache en memoria).

**Bloqueo por disco:** si `CheckDiskSpace::UPLOADS_BLOCKED_KEY` está activo en cache → 503.

**Validaciones rápidas (sin I/O):**

| Campo | Validación |
|---|---|
| `uploadId` | UUID v4 regex: `/^[0-9a-f]{8}-...-4...-[89ab]...-...$/i` |
| `totalChunks` | 1–105 (máx ~1 GB con chunks de 10 MB) |
| `chunkIndex` | 0 ≤ index < totalChunks |
| `fileName` | `basename()`, sin `\0`, `/`, `\`, max 255 chars |
| `chunk` (file) | presente, ≤ 11 MB (tolerancia) |

**Solo en `chunkIndex === 0`:**
- Extensión en `Chatify::getAllowedImages() + getAllowedFiles()`.
- Concurrencia: máx 3 uploads activos por usuario (cache, TTL 30 min). Si se supera → 429.

**Escritura del chunk:**
- Directorio: `storage/app/private/chunks/<uploadId>/`
- Archivo: `chunk_000000`, `chunk_000001`, ...
- `move_uploaded_file()` directo (sin abstracción Laravel).

**Ensamblado (cuando llegan todos los chunks):**
- Lock atómico con `rename()` para evitar race conditions entre requests paralelos.
- Concatena chunks en orden con `stream_copy_to_stream`.
- Nombre final: `Str::uuid() . '.' . $ext` (UUID — nunca el nombre original en disco).
- Destino: `storage/app/public/attachments/`.

**Validaciones post-ensamblado:**

| Check | Detalle |
|---|---|
| Archivo existe y no está vacío | 500 si falla |
| Tamaño ≤ `Chatify::getMaxUploadSize()` | 422 si supera |
| Magic Bytes | `finfo_file()` compara MIME real vs extensión declarada. Ej: un `.jpg` con bytes de `.exe` → 422 |

**Tipos permitidos con su MIME esperado:**

```
jpg/jpeg → image/jpeg
png      → image/png
gif      → image/gif
webp     → image/webp
pdf      → application/pdf
zip      → application/zip | application/x-zip-compressed
mp4      → video/mp4
mp3      → audio/mpeg
doc      → application/msword
docx     → application/vnd.openxmlformats-officedocument.wordprocessingml.document
xls      → application/vnd.ms-excel
xlsx     → application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
txt      → text/plain
```

**Creación del mensaje Chatify:**

```json
{
  "from_id": <auth_id>,
  "to_id": <contacto_id>,
  "body": "<mensaje_texto_htmlentities>",
  "attachment": {
    "new_name": "<uuid>.<ext>",
    "old_name": "<nombre_original>",
    "size": <bytes>
  },
  "drive_status": "processing" | "local"
}
```

**Backup en Drive — emisor:**

Si `google_access_token` + `google_refresh_token` + `drive_backup_enabled`:
1. Copia el archivo a `storage/app/private/drive_tmp/<uuid>.<ext>`.
2. Despacha `UploadFileToDrive` en cola `drive-uploads`.
3. Broadcast `DriveUploadUpdated` con `status=processing` al receptor.

**Backup en Drive — receptor (copia simétrica):**

Si el receptor tiene Drive activo y no es el mismo usuario:
1. Copia el archivo a `storage/app/private/drive_tmp/rcv_<uuid>.<ext>`.
2. Despacha `UploadFileToDrive` con `isRecipientCopy=true`.
3. Este job sube silenciosamente, no actualiza `drive_status`, no hace broadcast.

**Respuesta final:**

```json
{
  "status": "200",
  "error": 0,
  "done": true,
  "message": "<html_burbuja>",
  "tempID": "<temp_N>"
}
```

#### Paso 5 — Frontend al recibir `done: true`

1. `arkCardDone(tempID)` → elimina la tarjeta del stack y del queue.
2. `arkHidePreview()` → oculta `#ark-preview-zone` si el queue quedó vacío.
3. Reemplaza la tarjeta temporal en el chat con `data.message`.
4. Si el queue está vacío: limpia el `.attachment-preview` invisible y re-habilita el botón de envío.

#### Paso 6 — Job `UploadFileToDrive`

- `tries = 3`, `timeout = 900 s`.
- Resuelve el access token: si expiró (por timestamp local, con margen de 5 min), usa el `refresh_token` para obtener uno nuevo y lo persiste.
- Si el token fue revocado (`invalid_grant`) → marca `drive_status = error_authorization`, cancela reintentos.
- Sube a Drive con Resumable Upload en chunks de 5 MB.
- Otorga permiso público de lectura (`role: reader, type: anyone`).
- Actualiza `attachment = webViewLink` y `drive_status = synced`.
- Broadcast `DriveUploadUpdated` con `status=synced` y el link.
- Si `isRecipientCopy=true`: solo sube y loguea, sin tocar el mensaje ni hacer broadcast.
- Si falla definitivamente: `drive_status = failed`, broadcast `status=failed`.

### Extensiones permitidas (config/chatify.php)

**Imágenes:** `png, jpg, jpeg, gif, webp, svg, bmp, tiff, ico, heic, heif`

**Archivos:** `zip, rar, txt, mp4, mov, avi, mkv, webm, mp3, wav, ogg, aac, flac, pdf, doc, docx, m4v, m4a, 3gp, wmv, flv, ts, m3u8, opus, wma, mid, midi, iso`

### Cancelación y limpieza

`cancelAttachment()` (llamada desde el botón "Cancelar todo" o al cambiar de chat):
1. Elimina `.attachment-preview` del DOM.
2. Reemplaza el `<input>` con un clon limpio (resetea la selección).
3. Vacía `#ark-file-stack` y `window._arkFileQueue = {}`.
4. Oculta `#ark-preview-zone`.

Botón X individual en cada tarjeta: llama `arkRemoveFileCard(tempID)`. Si el queue queda vacío, llama `cancelAttachment()`.

Botón "Reintentar" (overlay rojo en tarjeta con error): resetea el overlay, llama `arkCardUploading(tempID)` y relanza `sendChunked(entry.file, inputValue, tempID)`.

---

## 7. Edición de mensaje

**Ruta:** `POST /chatify/editMessage` → `ChatController@editMessage`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | integer | ID del mensaje |
| `message` | string | required, no vacío tras trim |

### Lógica

- Solo el emisor (`from_id === auth_id`) puede editar.
- Solo mensajes sin adjunto (`whereNull('attachment')`).
- El cuerpo se guarda con `htmlentities($body, ENT_QUOTES, 'UTF-8')`.
- Respuesta: `{ updated: 1, message: "<cuerpo_editado>" }`.

---

## 8. Actualización de perfil propio

**Ruta:** `POST /profile/update` → `UserController@updateProfile`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | required, max:100 |
| `tagname` | string | nullable, max:50, alpha_dash, unique:users (ignora el propio) |
| `password` | string | nullable, min:8, confirmed |
| `password_confirmation` | string | debe coincidir si se envía password |

### Lógica especial

- Si el usuario tiene `google_id` y envía `password` → 403 "Los usuarios de Google no pueden cambiar la contraseña aquí."
- Si el `tagname` actual contiene `@` (email legacy) y no se envía uno nuevo → genera uno automáticamente con `CreateNewUser::generateTagname()`.
- Tras actualizar, hace broadcast `profile-updated` en el canal Pusher del usuario con `name` y `tagname` nuevos (actualiza en tiempo real el header del chat y la lista de contactos de otros usuarios).
- Respuesta: `{ status: 1, name, tagname, is_google_user }`.

---

## 9. Avatar — subida de archivo

**Ruta:** `POST /chatify/avatar-upload` → `UserController@uploadAvatar`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `avatar` | file | required, image (MIME), max:4096 KB |

### Lógica

1. Borra el avatar anterior si no es el default.
2. Guarda con nombre `Str::uuid().<ext>` en `storage/public/users-avatar/`.
3. Actualiza `users.avatar`.
4. Broadcast `profile-updated` con `avatar_url` al canal Pusher del usuario.
5. Respuesta: `{ status: 1, avatar_url: "<url>" }`.

---

## 10. Avatar — desde URL DiceBear

**Ruta:** `POST /chatify/avatar-from-url` → `UserController@updateAvatarFromUrl`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `avatar_url` | string | required, url, regex: debe empezar con `https://api.dicebear.com/` |

### Lógica

1. Descarga la imagen con `Http::timeout(8)->get($url)`.
2. Borra el avatar anterior.
3. Guarda como `Str::uuid().png` en `users-avatar/`.
4. Broadcast `profile-updated` con `avatar_url`.
5. Respuesta: `{ status: 1, avatar_url }`.

---

## 11. Google Drive — vincular

**Ruta redirect:** `GET /auth/google/drive` → `GoogleDriveController@redirect`
**Ruta callback:** `GET /auth/google/callback` → `GoogleDriveController@callback`
**Middleware:** auth, verified

### Scopes

```
https://www.googleapis.com/auth/drive.file
```

`access_type=offline`, `prompt=consent`, `redirect_uri` desde `config('services.google_drive.redirect')`.

### Callback

1. Obtiene el usuario de Google con el redirect_uri de Drive.
2. Guarda `google_access_token` (encriptado), `google_refresh_token` (encriptado, preserva el existente si Google no devuelve uno nuevo), `google_token_expires_at`.
3. Redirige a `route('chatify')` con flash `status`.

---

## 12. Google Drive — toggle backup

**Ruta:** `POST /auth/google/backup-toggle` → `GoogleDriveController@toggleBackup`
**Middleware:** auth, verified

Sin campos de formulario. Invierte `drive_backup_enabled` del usuario autenticado.

Respuesta: `{ enabled: true|false, message: "..." }`.

---

## 13. Google Drive — desvincular

**Ruta:** `GET /auth/google/disconnect` → `GoogleDriveController@disconnect`
**Middleware:** auth, verified

1. Intenta revocar el token en `https://oauth2.googleapis.com/revoke` (fallo silencioso).
2. Pone a `null`: `google_access_token`, `google_refresh_token`, `google_token_expires_at`.
3. Redirige a `route('chatify')` con flash `status`.

---

## 14. Administración — crear usuario

**Ruta:** `POST /dashboard/users` → `UserController@store`
**Middleware:** auth, verified, admin
**Form Request:** `StoreUserRequest`

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | required, min:2, max:255 |
| `tagname` | string | required, max:50, unique:users, regex:`/^[a-zA-Z0-9_]+$/` |
| `email` | string | required, email:rfc, max:255, unique:users |
| `password` | string | required, confirmed, min:8, mixedCase, numbers, symbols |
| `password_confirmation` | string | debe coincidir |
| `is_admin` | boolean | required |

### Pre-procesamiento (prepareForValidation)

- `email`: `strtolower(trim(...))`.
- `name`: `strip_tags(trim(...))`.

### Lógica

1. Verifica registros MX del dominio del email (advertencia si no tiene, no bloquea).
2. Genera avatar DiceBear.
3. Crea usuario con `active_status=true`.
4. Respuesta 201: `{ status: 1, message, warning?, user: { id, name, tagname, email, is_admin, active_status, avatar, created_at } }`.

---

## 15. Administración — editar usuario

**Ruta:** `PUT /dashboard/users/{user}` → `UserController@update`
**Middleware:** auth, verified, admin
**Form Request:** `UpdateUserRequest`

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | required, min:2, max:255 |
| `tagname` | string | required, max:50, regex:`/^[a-zA-Z0-9_]+$/`, unique (ignora el propio) |
| `email` | string | required, email:rfc, max:255, unique (ignora el propio) |
| `is_admin` | boolean | required |
| `password` | string | nullable, confirmed, min:8, mixedCase, numbers, symbols |
| `password_confirmation` | string | debe coincidir si se envía password |

### Lógica

- Si `password` está presente → hashea y actualiza.
- Respuesta: `{ status: 1, message, user: {...} }`.

---

## 16. Administración — eliminar usuario

**Ruta:** `DELETE /dashboard/users/{user}` → `UserController@destroy`
**Middleware:** auth, verified, admin

Sin campos. No permite eliminar la propia cuenta (403).

Respuesta: `{ status: 1, message }`.

---

## 17. Administración — toggle estado

**Ruta:** `PATCH /dashboard/users/{user}/toggle-status` → `UserController@toggleStatus`
**Middleware:** auth, verified, admin

Sin campos. Invierte `active_status`. No permite desactivar la propia cuenta (403).

Respuesta: `{ status: 1, message, active_status: true|false }`.

---

## 18. Formulario de contacto (público)

**Ruta:** `POST /contact` → `ContactController@send`
**Middleware:** público

### Rate limiting

3 mensajes por IP cada 24 horas. Si se supera:
```json
{ "error": "rate_limit", "seconds_remaining": N, "hours_remaining": N }
```
HTTP 429.

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | required, email:rfc, max:150, debe existir en `users` con `is_admin=false` |
| `message` | string | required, min:10, max:1000, sin `<script`, sin `javascript:` |

### Sanitización

- `email`: `filter_var(FILTER_SANITIZE_EMAIL)`.
- `message`: elimina zero-width chars (`\x{200B}-\x{200D}`, `\x{FEFF}`, `\x{202E}`, `\x{200F}`), caracteres de control, `strip_tags`, `trim`.

### Envío

`Mail::raw(...)` al `mail.admin_address` con `replyTo` del remitente.

Respuesta: `{ ok: true }`.

---

## 19. Formulario de contacto (admin)

**Ruta:** `POST /dashboard/contact` → `ContactController@sendFromAdmin`
**Middleware:** auth, verified, admin

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `message` | string | required, min:10, max:2000, sin `<script`, sin `javascript:` |

Sin rate limiting (el admin es de confianza).

Respuesta: `{ ok: true }`.

---

## 20. King Challenge — verificación

**Ruta show:** `GET /king-challenge` → `KingChallengeController@show`
**Ruta verify:** `POST /king-challenge` → `KingChallengeController@verify`
**Middleware:** auth (solo el usuario King)

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `code` | string | required |

### Lógica

- El `code` es una secuencia de teclas separadas por comas. Ej: `ArrowLeft,ArrowRight,A,B`.
- Se compara con `king.getPuzzleSequence()` → `implode(',', ...)`.
- Si coincide: `session(['king_verified' => true])` → redirige al dashboard.
- Si no: `back()->withErrors(['code' => 'Código incorrecto.'])`.
- Si ya tiene `session('king_verified')` → redirige directo al dashboard.

---

## 21. King — reset de contraseña

Flujo en dos pasos protegido por el puzzle.

### Paso 1 — Verificar puzzle

**Ruta:** `POST /king-forgot` → `KingPasswordResetController@verifyPuzzle`

| Campo | Tipo | Reglas |
|---|---|---|
| `code` | string | required |

Si el puzzle es correcto: `session(['king_puzzle_solved' => true])` → redirige al formulario de reset.

### Paso 2 — Nueva contraseña

**Ruta:** `POST /king-reset` → `KingPasswordResetController@reset`

| Campo | Tipo | Reglas |
|---|---|---|
| `password` | string | required, min:8, confirmed |
| `password_confirmation` | string | debe coincidir |

- Requiere `session('king_puzzle_solved')`, si no → redirige al paso 1.
- Actualiza la contraseña del usuario King.
- Borra `session('king_puzzle_solved')`.
- Redirige a login con flash `status`.

---

## 22. WebRTC — señalización P2P

**Ruta:** `POST /webrtc/signal` → `WebRTCController@signal`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `to_id` | integer | required |
| `type` | string | required, in: `offer, answer, ice-candidate, transfer-request, transfer-accepted, transfer-cancel` |
| `payload` | mixed | required |

### Lógica

Relay puro: el servidor nunca ve el archivo. Solo reenvía la señal al canal Pusher `private-chatify.<to_id>` con evento `webrtc-signal`.

Respuesta: `{ ok: true }`.

---

## 23. WebRTC — guardar transferencia P2P

**Ruta:** `POST /webrtc/save-transfer` → `WebRTCController@saveTransfer`
**Middleware:** auth, verified

### Campos

| Campo | Tipo | Reglas |
|---|---|---|
| `to_id` | integer | required |
| `file_name` | string | required, max:255 |
| `file_size` | integer | required, min:1 |
| `file_type` | string | required, max:127 |
| `message` | string | nullable, max:1000 |

### Lógica

Crea un mensaje Chatify con `attachment.p2p=true` y `new_name=null` (el archivo nunca pasó por el servidor). Hace broadcast al receptor. El mensaje en el chat muestra el nombre y tamaño del archivo pero no tiene link de descarga del servidor.

Respuesta: `{ status: 200, message: "<html_burbuja>" }`.

---

## 24. Ajustes del messenger (vendor)

**Ruta:** `POST /chatify/updateSettings` → `MessagesController@updateSettings` (vendor Chatify)
**Middleware:** auth

Gestiona color del messenger y modo oscuro. El color seleccionado se aplica como `--primary-color` en CSS y afecta: burbujas de chat, zona de preview de archivos, rings de progreso, bordes de tarjetas.

Colores disponibles (config/chatify.php):
```
#6366f1 (Indigo), #8b5cf6 (Violet), #db2777 (Pink),
#0d9488 (Teal), #0891b2 (Cyan), #2563eb (Blue),
#059669 (Emerald), #b45309 (Amber), #c2410c (Orange), #475569 (Slate)
```

---

## 25. Búsqueda de usuarios

**Ruta:** `GET /users/search?q=<término>` → `UserController@search`
**Middleware:** auth, verified

### Parámetros

| Parámetro | Tipo | Descripción |
|---|---|---|
| `q` | string | Término de búsqueda. Si empieza con `@` → búsqueda exacta por tagname |

### Lógica

- Excluye al propio usuario, usuarios King (`is_king=false`) y usuarios inactivos.
- Si el buscador es admin → solo devuelve otros admins.
- Si no es admin → solo devuelve no-admins.
- Búsqueda por `name LIKE %q%` o `tagname = q` (exacto).
- Límite: 20 resultados.
- Respuesta: array de `{ id, name, tagname, avatar }`.

---

## Resumen de seguridad transversal

| Mecanismo | Dónde aplica |
|---|---|
| CSRF token (`@csrf`) | Todos los formularios POST/PUT/PATCH/DELETE |
| Rate limiting (RateLimiter) | Chunked upload (200/min), contacto público (3/24h) |
| UUID en disco | Todos los archivos subidos (avatar, adjuntos) |
| Magic Bytes validation | Chunked upload — post-ensamblado |
| `.htaccess` en attachments | `Options -ExecCGI`, `php_flag engine off` |
| Tokens Google encriptados | `encrypt()`/`decrypt()` en BD |
| Sanitización de texto | Registro, perfil, mensajes, contacto |
| Autorización por rol | Admin middleware, Form Requests con `authorize()` |
| Concurrencia de uploads | Máx 3 activos por usuario (cache) |
| Lock atómico de ensamblado | `rename()` para evitar race conditions |
| Bloqueo por disco lleno | `CheckDiskSpace` command + cache key |
