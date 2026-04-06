# 07. Desarrollo — Elysium Ito

## Tecnologías usadas

| Tecnología | Versión | Rol |
|---|---|---|
| Laravel | 12.55.1 | Framework principal (backend, rutas, ORM, colas) |
| PHP | 8.2.12 | Lenguaje del servidor |
| MySQL | 8.0 | Base de datos relacional con soporte UUID |
| Blade + Alpine.js | — | Plantillas del servidor + reactividad ligera en cliente |
| Tailwind CSS | v4.2.1 | Estilos utilitarios con soporte nativo de Modo Oscuro |
| Vite | 7.0.7 | Pipeline de assets ESM con HMR (polling en Windows) |
| Pusher | — | WebSockets para mensajería y señalización WebRTC |
| Laravel Fortify | — | Autenticación, 2FA (TOTP) y recuperación de contraseña |
| Laravel Jetstream | 5.4 | Gestión de perfil y sesiones (stack Livewire) |
| Laravel Sanctum | 4.0 | Tokens de API para canales privados de Pusher |
| Laravel Socialite | 5.25 | OAuth2 con Google (login social + vinculación de Drive) |
| Google Drive API | v3 | Almacenamiento externo (Resumable Upload, OAuth2) |
| Livewire | v3.7.11 | Componentes reactivos del servidor |
| Chatify | 1.6 | Base del sistema de mensajería (vendor personalizado) |

## Metodología de desarrollo

El sistema se desarrolló de forma iterativa, priorizando la seguridad y la resiliencia en cada capa. Los componentes de Chatify se migraron de archivos estáticos en `public/` a un ecosistema gestionado por Vite, permitiendo compilación optimizada y HMR estable en Windows mediante polling.

## Correcciones técnicas aplicadas

### Variables de entorno y configuración

- `config/chatify.php`: los arrays de middleware estaban definidos con `env()`, que no puede devolver arrays. Corregido a arrays fijos.
- `config/services.php`: restaurado el bloque `google_drive` como entrada independiente.
- `.env`: eliminado comentario inline en `LOG_LEVEL` que corrompía el valor leído por Laravel.

### Compatibilidad UMD / ESM en Vite

`autosize.js` y `cropper.min.js` son bundles UMD que usan `this` como referencia a `global`. En el contexto ESM de Vite, `this` es `undefined`. Solución: se cargan como `<script src="{{ asset('js/chatify/...') }}">` estáticos desde `public/`.

## Componentes clave implementados

### SetupSuperUser (Comando Artisan)

Comando de recuperación del Super Usuario: `php artisan super:setup`.

- Lee `SUPER_USER_EMAIL` y `SUPER_USER_PASSWORD` del `.env` por defecto.
- Acepta `--email=` y `--password=` para emergencias sin acceso al `.env`.
- Acepta `--force` para omitir la confirmación interactiva.
- Garantiza unicidad: desactiva `is_super` del usuario anterior antes de crear el nuevo.
- El Super Usuario creado tiene `is_admin = true` para heredar acceso al dashboard.

```bash
# Uso normal
php artisan super:setup

# Emergencia (sin .env)
php artisan super:setup --email=nuevo@correo.com --password=nuevaClave123 --force
```

### ContactController

El método `send()` (formulario del home) resuelve el destinatario dinámicamente:

```php
$superUser    = User::where('is_super', true)->first();
$adminAddress = $superUser?->email ?? config('mail.admin_address', ...);
```

Esto garantiza que los mensajes siempre lleguen al Super Usuario activo en BD, sin depender de una dirección hardcodeada en configuración.

### ChunkedUploadController

- Segmentación: chunks de 10 MB, hasta 3 en paralelo.
- Ensamblado seguro con `stream_copy_to_stream()`.
- Double-Lock con `Cache::lock` para evitar condiciones de carrera.
- Validación de Magic Bytes con `finfo_buffer()`.
- Aislamiento por usuario: `storage/app/private/chunks/{userId}/{uploadId}/`.

### UploadFileToDrive (Job)

- Resumable Upload de Google Drive API v3 en chunks de 5 MB.
- Refresh automático de `access_token` por timestamp local.
- Detección de `invalid_grant` → estado `error_authorization`.
- Backup dual: emisor y receptor con Jobs independientes.
- 3 reintentos, timeout de 15 minutos por intento.

### GoogleDriveController

- `redirect()`: OAuth2 con scope `drive.file`.
- `callback()`: guarda tokens encriptados + `google_token_expires_at`.
- `toggleBackup()`: activa/pausa backup sin revocar tokens.
- `disconnect()`: revoca token en Google y limpia BD.

### WebRTCController

- `signal()`: retransmite señales WebRTC via Pusher. El servidor nunca ve bytes del archivo.
- `saveTransfer()`: guarda metadatos del mensaje P2P en BD.

### UserController (Panel Administrativo)

- `store()`: crea usuario con avatar DiceBear, verifica registros MX del dominio.
- `toggleStatus()`: activa/desactiva cuenta.
- `search()`: búsqueda por nombre o `@tagname` con segregación por rol.
- `destroy()`: eliminación física con protección contra auto-eliminación.

## Hitos de desarrollo

1. Migración de assets de Chatify de `public/` a Vite.
2. `ChunkedUploadController` con Double-Lock y Magic Bytes validation.
3. Google Socialite con doble scope (login + Drive) y tokens encriptados.
4. Job `UploadFileToDrive` con Resumable Upload, refresh automático y backup dual.
5. WebRTC P2P con Pusher como servidor de señalización.
6. Modelo de roles RBAC: Super Usuario → Admin → Usuario (eliminación del rol King).
7. Comando `super:setup` para recuperación de acceso del Super Usuario via CLI.
8. Formulario de contacto enrutado dinámicamente al Super Usuario en BD.
9. Monitor de disco con alerta por email y bloqueo al 90%.
10. Cumplimiento Ley 1581: registro de IP y fecha de consentimiento.
11. Corrección UMD/ESM: `autosize.js` y `cropper.min.js` excluidos del pipeline de Vite.

## Estructura de directorios relevante

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ChunkedUploadController.php
│   │   ├── GoogleDriveController.php
│   │   ├── WebRTCController.php
│   │   ├── ContactController.php
│   │   └── UserController.php
│   └── Middleware/
│       ├── AdminMiddleware.php
│       ├── SuperMiddleware.php
│       └── SecurityHeadersMiddleware.php
├── Console/Commands/
│   └── SetupSuperUser.php          ← Recuperación del Super Usuario
├── Jobs/
│   └── UploadFileToDrive.php
├── Events/
│   └── DriveUploadUpdated.php
└── Models/
    ├── User.php                    ← is_super, isAdmin(), isSuper()
    ├── ChMessage.php
    ├── ChatDeletion.php
    └── HiddenMessage.php

database/migrations/
    └── 2026_03_24_000001_add_is_super_to_users_table.php

config/
├── chatify.php     # Middlewares como arrays fijos
├── services.php    # google + google_drive como bloques independientes

routes/web.php      # 3 grupos de middleware, sin rutas King
```
