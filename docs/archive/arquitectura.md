# Referencia de Arquitectura Técnica

## Stack principal

| Capa | Tecnología | Notas |
|---|---|---|
| Frontend | JavaScript nativo + jQuery | Chunked Upload via `File.slice()`, Pusher JS SDK |
| Backend | Laravel 12 (PHP) | Controllers delgados, Jobs en cola, Broadcasting |
| Base de datos | MySQL | Cola `database`, sesiones, mensajes, tokens encriptados |
| Tiempo real | Pusher (dev) / Reverb (prod) | Canales privados `private-chatify.{userId}` |
| Almacenamiento | Google Drive personal (OAuth2) | Scope `drive.file`, Resumable Upload, costo $0 |
| Assets | Vite 7 + Tailwind CSS v4 | `@tailwindcss/vite`, sin `tailwind.config.js` |
| Auth | Laravel Fortify + Jetstream | 2FA, King Challenge, Ley 1581 |

---

## Estados de drive_status (ENUM en ch_messages)

Estos son los 5 valores exactos — usar siempre estos nombres en código, docs y tesis:

| Valor | Significado | Acción del frontend |
|---|---|---|
| `local` | Sin Drive vinculado | No se emite evento Pusher |
| `processing` | Job en cola / subiendo | Burbuja azul con spinner animado |
| `synced` | Subida completada | Burbuja verde con `webViewLink` |
| `failed` | Agotó `$tries=3` | Burbuja roja persistente + botón "Reintentar" |
| `error_authorization` | Token revocado por Google (401/invalid_grant) | Burbuja naranja persistente + enlace "Vincular Drive" |

**Regla crítica**: `failed` y `error_authorization` son flujos distintos.
- `failed` → reencolar con botón "Reintentar" → ruta `POST /chatify/drive-retry/{message}`
- `error_authorization` → re-vincular cuenta → enlace a `route('google.drive.connect')`

---

## Canales Pusher del proyecto

| Canal | Tipo | Uso |
|---|---|---|
| `private-chatify.{userId}` | Privado | Mensajes, Drive status, perfil actualizado |
| `presence-activeStatus` | Presencia | Estado online/offline de usuarios |

Autorización en `routes/channels.php`:
```php
Broadcast::channel('chatify.{userId}', fn($user, $userId) => (int)$user->id === (int)$userId);
```

---

## Archivos clave del sistema de transferencia

| Archivo | Responsabilidad |
|---|---|
| `app/Http/Controllers/ChunkedUploadController.php` | Recibe chunks, ensambla, despacha Job |
| `app/Jobs/UploadFileToDrive.php` | Sube a Drive, emite eventos, limpia tmp |
| `app/Events/DriveUploadUpdated.php` | Evento Pusher — payload mínimo, canales duales |
| `resources/views/vendor/Chatify/pages/app.blade.php` | Listener JS + plantillas de burbuja por estado |
| `public/js/chatify/code.js` | Lógica principal del chat, Pusher init, chunked upload |
| `routes/channels.php` | Autorización de canales privados |
| `app/Console/Commands/CheckDiskSpace.php` | Monitor de disco — bloquea uploads al 90% |

---

## Timeouts sincronizados (obligatorio)

| Capa | Valor dev (Windows) | Valor prod (Linux) | Dónde |
|---|---|---|---|
| `$timeout` del Job | `900` | `3600` | `UploadFileToDrive.php` |
| `--timeout` del worker | `900` | `3600` | comando `queue:work` |
| `retry_after` | `960` | `3660` | `config/queue.php` |
| `php.ini max_execution_time` | `0` (CLI) | `0` (CLI) | `php.ini` |

`retry_after` siempre debe ser mayor que `$timeout` para evitar que Laravel reencole un Job que todavía está corriendo.

---

## Decisiones de diseño — justificación para tesis

| Decisión | Alternativa descartada | Razón |
|---|---|---|
| `QUEUE_CONNECTION=database` | Redis / SQS | Costo $0, sin infraestructura adicional |
| `Http::` de Laravel para Drive API | `google/apiclient` | `firebase/php-jwt` tiene advisories de seguridad activos |
| Chunked Upload propio (10MB/chunk) | Uppy u otras librerías | Control total, sin dependencias externas, compatible con `php artisan serve` |
| Scope `drive.file` | Scope `drive` completo | Principio de mínimo privilegio — solo archivos creados por la app |
| Inline styles en burbujas Drive | Clases CSS en hoja externa | Las burbujas se inyectan dinámicamente via JS — los estilos deben viajar con el HTML para garantizar que se apliquen independientemente del orden de carga |
| JS inline en `app.blade.php` para Drive | Archivo `.js` externo | Necesita `{{ route() }}` de Blade — encapsulado en IIFE, el resto va en `code.js` |
