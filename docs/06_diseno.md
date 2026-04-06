# 06. Diseño — Elysium Ito

## Arquitectura general

Elysium Ito usa una arquitectura de "Servidor como Orquestador". El servidor recibe fragmentos de datos, los valida y los despacha a servicios externos o los entrega directamente a otros clientes — nunca almacena archivos de forma permanente.

## Stack tecnológico

| Capa | Tecnología | Versión | Justificación |
|---|---|---|---|
| Backend | Laravel | 12.55.1 | Framework robusto, seguro y con ecosistema maduro |
| Lenguaje | PHP | 8.2.12 | Soporte nativo de fibers, enums y tipos de intersección |
| Frontend | Blade + Alpine.js + Tailwind CSS | v4.2.1 | Interfaz reactiva sin overhead de SPA completa |
| Broadcast | Pusher | — | WebSockets con canales privados y de presencia |
| Base de Datos | MySQL | 8.0 | Relacional con soporte nativo de UUIDs para IDs de mensajes |
| Autenticación | Laravel Fortify + Jetstream | — | 2FA (TOTP), OAuth2 y gestión de sesiones integrados |
| OAuth2 / Storage | Google Drive API | v3 | Almacenamiento externo gratuito (15 GB por usuario) |
| OAuth2 Social | Laravel Socialite | 5.25 | Login social + vinculación de Drive con un solo driver `google` |
| Pipeline | Vite | 7.0.7 | Compilación ESM con HMR (polling en Windows) |
| Colas | Laravel Queues (database) | — | Jobs asíncronos para subidas a Drive sin bloquear el hilo principal |
| Sesión / Caché | MySQL (driver database) | — | Sesiones y caché persistentes sin Redis |
| Livewire | Livewire | v3.7.11 | Componentes reactivos del servidor (Jetstream) |

## Modelo de roles (RBAC)

```
Super Usuario (is_super = true)
    └── Admin (is_admin = true)
            └── Usuario
```

- **Super Usuario** (`is_super = true`): acceso total, invisible en listados públicos, recibe los mensajes del formulario de contacto del home. Solo puede existir uno. Recuperable via `php artisan super:setup`.
- **Admin** (`is_admin = true`): gestión de usuarios de su mismo nivel o inferior. Acceso al dashboard.
- **Usuario**: acceso solo al chat y sus propios archivos.

El Super Usuario también tiene `is_admin = true` para heredar todos los permisos del panel de administración. El `AdminMiddleware` verifica `isAdmin()`, que retorna `true` si `is_admin || is_super`.

## Configuración de rutas

Las rutas están definidas en `routes/web.php` con tres grupos de middleware:

| Grupo | Middleware | Rutas |
|---|---|---|
| Público | — | `/`, `/login`, `/register`, `/auth/google`, páginas legales, `/contact` |
| Admin | `auth:sanctum`, `verified`, `admin` | `/dashboard`, CRUD usuarios |
| Protegido | `auth:sanctum`, `verified` | `/chatify`, chunked upload, WebRTC, Drive, 2FA |

Los middlewares están registrados en `bootstrap/app.php` con alias:

| Alias | Clase | Función |
|---|---|---|
| `admin` | `AdminMiddleware` | Verifica `isAdmin()` (is_admin o is_super) y `active_status` |
| `super` | `SuperMiddleware` | Verifica `is_super`, redirige silenciosamente si no |
| `storage.cors` | `StorageCorsHeaders` | Headers CORS para descarga de attachments |
| `require.2fa` | `RequireTwoFactor` | Exige 2FA activo para acceder al chat |

## Mecanismo de recuperación del Super Usuario

En caso de pérdida de acceso a la cuenta del Super Usuario, el sistema provee el comando artisan `super:setup`:

```bash
# Usando credenciales del .env (flujo normal)
php artisan super:setup

# Especificando credenciales directamente (emergencia)
php artisan super:setup --email=nuevo@correo.com --password=nuevaClave123 --force
```

Las variables de entorno de respaldo:

```env
SUPER_USER_EMAIL="cuenta@gmail.com"
SUPER_USER_PASSWORD="clave_segura_minimo_8_chars"
```

El comando garantiza que solo exista un Super Usuario en todo momento — desactiva el flag del anterior antes de crear el nuevo.

## Flujo del formulario de contacto

El formulario del hero del home envía mensajes al email del Super Usuario registrado en BD:

```
ContactController::send()
    └── User::where('is_super', true)->first()
            ├── Si existe → envía a $superUser->email
            └── Si no existe → fallback a config('mail.admin_address')
```

## Configuración de variables de entorno

| Variable `.env` | Config PHP | Uso |
|---|---|---|
| `GOOGLE_CLIENT_ID` | `services.google.client_id` | OAuth2 login social |
| `GOOGLE_CLIENT_SECRET` | `services.google.client_secret` | OAuth2 login social |
| `GOOGLE_REDIRECT_URI` | `services.google.redirect` | Callback login (`/auth/google/login-callback`) |
| `GOOGLE_DRIVE_REDIRECT_URI` | `services.google_drive.redirect` | Callback Drive (`/auth/google/callback`) |
| `PUSHER_APP_KEY` | `chatify.pusher.key` | WebSockets broadcast |
| `CHATIFY_MAX_FILE_SIZE` | `chatify.attachments.max_upload_size` | Límite de subida (default 1 GB) |
| `SUPER_USER_EMAIL` | — | Email de recuperación del Super Usuario |
| `SUPER_USER_PASSWORD` | — | Contraseña de recuperación del Super Usuario |
| `MAIL_ADMIN_ADDRESS` | `mail.admin_address` | Fallback si no hay Super Usuario en BD |

Los middlewares de Chatify están definidos como arrays fijos en `config/chatify.php`:

```php
'routes' => [
    'middleware' => ['web', 'auth'],   // array fijo — env() no puede devolver arrays
],
```

## Pipeline de assets (Vite)

**Procesados por Vite (ESM):**
- `resources/js/app.js`, `code.js`, `utils.js`, `webrtc-transfer.js`, `vault.js`
- `resources/css/app.css`, `style.css`, `light.mode.css`, `dark.mode.css`, etc.

**Servidos como estáticos desde `public/` (UMD — no compatibles con ESM):**
- `public/js/chatify/autosize.js` — bundle UMD que usa `this` como `global`
- `public/js/chatify/cropper.min.js` — bundle UMD

## Flujo de Chunked Upload

```
Frontend                    Servidor                      Google Drive
   |                            |                               |
   |-- chunk_0 (10MB) --------> |                               |
   |-- chunk_1 (10MB) --------> |  (paralelo, máx 3)            |
   |-- chunk_2 (10MB) --------> |                               |
   |        ...                 |                               |
   |-- chunk_N (último) ------> |                               |
   |                            |-- ensambla con stream_copy    |
   |                            |-- valida Magic Bytes          |
   |                            |-- crea ChMessage en BD        |
   |                            |-- dispatch UploadFileToDrive  |
   |                            |-- borra archivo local         |
   |                            |                               |
   |                            |-- Job: Resumable Upload ----> |
   |                            |                               |-- fileId
   |                            |<-- webViewLink --------------- |
   |                            |-- actualiza drive_status      |
   |<-- broadcast synced ------ |                               |
```

## Flujo WebRTC P2P

```
Emisor                    Servidor (Pusher)              Receptor
  |                             |                            |
  |-- transfer-request -------> |-- push private-chatify --> |
  |                             |                            |-- acepta
  |<-- transfer-accepted ------- |<-- push private-chatify --|
  |-- SDP offer -------------> |-- push private-chatify --> |
  |<-- SDP answer ------------ |<-- push private-chatify --|
  |<======= DataChannel directo (64 KB chunks) ============>|
  |-- saveTransfer (metadata) -> |                           |
```

## Modelo de datos principal

```
users
  ├── id, name, tagname, email, password
  ├── is_admin, is_super
  ├── active_status, dark_mode, messenger_color
  ├── google_access_token (encrypted), google_refresh_token (encrypted)
  ├── google_token_expires_at, drive_backup_enabled
  ├── google_id
  ├── data_consent, data_consent_at, accepted_privacy_at
  ├── privacy_version, registration_ip
  └── avatar

ch_messages
  ├── id (UUID), from_id, to_id
  ├── body, attachment (JSON → webViewLink tras sync)
  └── drive_status: local | processing | synced | failed | error_authorization

ch_favorites
  └── user_id, favorite_id

chat_deletions
  └── user_id, other_user_id, deleted_at

hidden_messages
  └── user_id, message_id
```

## Seguridad multinivel

| Capa | Mecanismo |
|---|---|
| Autenticación | Email+contraseña o Google OAuth2 + 2FA (TOTP) |
| Super Usuario | `SuperMiddleware` + campo `is_super` en BD + recuperación via CLI |
| Autorización | `AdminMiddleware`, `SuperMiddleware`, route model binding estándar |
| Tokens | OAuth2 almacenados con `encrypt()` de Laravel (AES-256-CBC) |
| Archivos | Magic Bytes validation, extensión whitelist, UUID como nombre en disco |
| Rate Limiting | Dinámico en chunks, fijo en contacto (3 mensajes/24h por IP) |
| Disco | Monitor horario con alerta por email y bloqueo automático al 90% |
| Cumplimiento | IP y fecha de consentimiento registradas (Ley 1581 de Colombia) |
| Headers HTTP | `SecurityHeadersMiddleware` aplica CSP, HSTS, X-Frame-Options globalmente |
