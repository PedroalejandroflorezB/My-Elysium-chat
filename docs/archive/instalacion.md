# Guía de Instalación y Configuración

## Stack de assets

- Vite `^7` + plugin `laravel-vite-plugin ^2`
- Tailwind CSS `v4` via `@tailwindcss/vite ^4` (NO usar `tailwind.config.js`)
- El `app.css` usa `@import "tailwindcss"` — NO usar `@tailwind base/components/utilities` (eso es v3)

## Configuración correcta

**`vite.config.js`**
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
    ],
});
```

**`resources/css/app.css`**
```css
@import "tailwindcss";
```

## Vite (assets CSS/JS)

Si la página carga sin estilos o en blanco, es porque Vite no está corriendo.

**Síntomas:**
- La página se ve sin estilos (solo HTML plano)
- Errores en consola del navegador sobre archivos `.js` o `.css` no encontrados
- `@vite` en las vistas Blade no resuelve los assets

**Solución — correr Vite en desarrollo:**
```bash
npm run dev
```

Debe estar corriendo en una terminal separada mientras trabajas. Escucha en `http://localhost:5173`.

**Solución — compilar para producción:**
```bash
npm run build
```

Genera los assets en `public/build/`. No requiere que Vite esté corriendo.

## Notas importantes

- PowerShell bloquea scripts npm por política de ejecución. Usar siempre `cmd /c "npm run dev"` desde Kiro.
- `tailwindcss ^3` sigue en `package.json` como dependencia transitiva pero el que procesa los estilos es `@tailwindcss/vite ^4`. No eliminar ninguno.

---

## Soporte para archivos grandes (1GB+) en local

### Por qué el chunk upload es la arquitectura correcta

`php artisan serve` es un servidor de un solo hilo. Enviar 1GB de golpe lo congela completamente — nadie puede usar el chat mientras dura la subida. La solución implementada en `ChunkedUploadController` divide el archivo en trozos de ~10MB, permitiendo que el servidor "respire" entre chunk y chunk.

**Arquitectura implementada:**
- Frontend (`webrtc-transfer.js` / `code.js`): divide el archivo en chunks via `File.slice()` y los envía secuencialmente con `fetch`
- Backend (`ChunkedUploadController`): recibe cada chunk, los escribe en `storage/app/private/chunks/{uploadId}/`, y cuando llegan todos los ensambla con `stream_copy_to_stream` (más rápido que fread/fwrite)
- Lock atómico con `rename()` para evitar race conditions en el ensamblado
- Límite de 3 uploads concurrentes por usuario via Cache
- Rate limiting: 200 chunks/minuto por usuario

### php.ini — configuración requerida para desarrollo local

`php artisan serve` usa el PHP del sistema. Sin estos valores, PHP rechaza el archivo **antes** de que llegue al código de Laravel.

Busca tu `php.ini` activo con:
```bash
php --ini
```

Cambia o agrega estas líneas:
```ini
upload_max_filesize = 2G
post_max_size       = 2G
memory_limit        = 512M
max_execution_time  = 600
max_input_time      = 600
```

Reinicia `php artisan serve` después de guardar. Los cambios no aplican en caliente.

**Ubicaciones comunes en Windows:**
- XAMPP: `C:\xampp\php\php.ini`
- Laragon: `C:\laragon\bin\php\phpX.X.X\php.ini`
- PHP standalone: resultado de `php --ini`

### Verificar límites activos

```bash
php -r "echo ini_get('upload_max_filesize');"
php -r "echo ini_get('post_max_size');"
```

Si devuelve `2G`, está correcto. Si devuelve `8M` o `2M`, el `php.ini` correcto no está siendo leído.

### storage:link — requerido para ver archivos en local

```bash
php artisan storage:link
```

Crea el symlink `public/storage → storage/app/public`. Sin esto, los archivos subidos no son accesibles desde el navegador en desarrollo.

### Streaming de archivos grandes

Para previsualizar o descargar archivos pesados, usar `response()->stream()` — nunca cargar el archivo completo en memoria:

```php
// En el controller de descarga
return response()->stream(function () use ($path) {
    $stream = fopen($path, 'rb');
    fpassthru($stream);
    fclose($stream);
}, 200, [
    'Content-Type'        => $mimeType,
    'Content-Disposition' => 'attachment; filename="' . $displayName . '"',
    'X-Accel-Buffering'   => 'no',
]);
```

### Vite y subidas asíncronas

Vite no interfiere con las subidas, pero si hay un error de JS que recarga la página, la subida se pierde. Reglas:

- Toda lógica de subida usa `fetch` con `async/await` — nunca `<form>` con submit tradicional
- El estado de la subida (progreso, uploadId, chunks enviados) se guarda en variables JS del módulo, no en el DOM
- Si el WebSocket se desconecta durante una subida, la subida continúa — son requests HTTP independientes
- El `uploadId` (UUID v4) identifica la sesión de subida y permite reanudar si se implementa retry

---

## Colas en Windows (desarrollo local)

`php artisan serve` no ejecuta Jobs en background. Para probar la subida a Drive mientras el chat sigue activo, se necesitan **dos terminales**:

**Terminal 1** — servidor web:
```bash
php artisan serve
```

**Terminal 2** — worker de colas (dejar corriendo):
```bash
php artisan queue:work --timeout=900 --queue=drive-uploads,default
```

### Regla de Timeout Alignment (sincronización obligatoria)

El `--timeout` del worker **debe ser siempre ≥ al `$timeout` del Job**. Si el worker mata el proceso antes de que el Job termine, la subida queda a medias en la tabla `jobs` de MySQL y el archivo nunca llega a Drive.

| Capa | Valor | Dónde se configura |
|---|---|---|
| `$timeout` del Job | `900` | `app/Jobs/UploadFileToDrive.php` |
| `--timeout` del worker | `900` | comando `queue:work` |
| `retry_after` en DB | `960` | `config/queue.php` / `.env` |

`retry_after` debe ser **mayor** que el timeout del Job (960 > 900) para que Laravel no reencole el Job como "perdido" mientras todavía está corriendo. Con 90s (el default) y un Job de 900s, Laravel reencolaría el mismo archivo 10 veces antes de que termine la primera subida.

En producción Linux ajustar los tres valores a `3600` / `3600` / `3660`.

Si algo falla, el error aparece en la tabla `failed_jobs` de MySQL — visible en Workbench.

### Verificar que las tablas de colas existen

```bash
php artisan migrate
```

Las tablas `jobs`, `job_batches` y `failed_jobs` las crea la migración `0001_01_01_000002_create_jobs_table.php` que ya viene con Laravel. Si `QUEUE_CONNECTION=database` está en `.env`, no se necesita nada más.

### php.ini para el worker en Windows

El worker de consola usa su propio límite de `max_execution_time`. Para archivos de 1GB, agregar en `php.ini`:

```ini
max_execution_time = 0   ; Sin límite para procesos de consola
memory_limit       = 512M
```

`max_execution_time = 0` solo aplica a CLI — no afecta las peticiones web.
php artisan storage:link       # Symlink public/storage
php artisan config:clear       # Limpiar caché de config tras cambiar .env
php artisan migrate            # Ejecutar migraciones pendientes
php artisan queue:work         # Procesar jobs en cola (necesario para uploads async)
php --ini                      # Ver qué php.ini está activo
php -r "phpinfo();" | grep ini # Alternativa para ver php.ini en uso
```

---

## Herramientas de calidad y debugging

### Laravel Pint — estilo de código automático

Corrige automáticamente el estilo del código para que sea consistente en todo el proyecto.

```bash
./vendor/bin/pint              # Corregir todo el proyecto
./vendor/bin/pint --test       # Solo verificar sin modificar (para CI)
./vendor/bin/pint app/         # Solo la carpeta app/
```

No requiere configuración adicional — usa el preset de Laravel por defecto. Ejecutar antes de cada commit.

### PHPStan / Larastan — análisis estático

Detecta errores sin ejecutar el código. Especialmente útil para el `ChunkedUploadController` donde hay muchas operaciones con archivos que pueden devolver `false`.

```bash
./vendor/bin/phpstan analyse   # Analizar con el nivel configurado
```

Instalar si no está:
```bash
composer require --dev nunomaduro/larastan phpstan/phpstan
```

Crear `phpstan.neon` en la raíz:
```neon
includes:
    - vendor/nunomaduro/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app/
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
```

Nivel recomendado: **5** para proyectos en crecimiento, **6** para máxima seguridad. Subir gradualmente — no intentar pasar de 0 a 9 de golpe.

**Errores típicos que detecta en este proyecto:**
- `fopen()` devuelve `resource|false` — hay que verificar antes de usar
- Variables que pueden ser `null` en el ensamblado de chunks
- Métodos de Eloquent que pueden no encontrar el modelo

### Laravel Ignition — errores amigables en local

Ya viene incluido en Laravel. En `APP_DEBUG=true` muestra errores con contexto, stack trace navegable y botones "Run Solution" para errores comunes (migraciones pendientes, symlinks faltantes, etc.).

Si no aparece el panel de Ignition y solo ves texto plano, verificar:
```bash
composer require --dev spatie/laravel-ignition
```

Y en `.env`: `APP_DEBUG=true` (solo en local, nunca en producción).

### Logging con contexto para uploads

Para archivos pesados, un `Log::error('timeout')` no es suficiente. Siempre incluir contexto que permita reproducir el fallo:

```php
// En ChunkedUploadController — patrón a seguir en todos los catch
} catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error('Fallo en chunk upload', [
        'user_id'      => Auth::id(),
        'upload_id'    => $uploadId,
        'chunk_index'  => $chunkIndex,
        'total_chunks' => $totalChunks,
        'chunk_size'   => $chunk?->getSize(),
        'memory_usage' => memory_get_usage(true),
        'memory_peak'  => memory_get_peak_usage(true),
        'error'        => $e->getMessage(),
        'file'         => $e->getFile() . ':' . $e->getLine(),
    ]);
    return response()->json(['error' => 1, 'error_msg' => 'Error interno.'], 500);
}
```

Ver los logs en tiempo real durante desarrollo:
```bash
tail -f storage/logs/laravel.log        # Linux/Mac
Get-Content storage/logs/laravel.log -Wait  # PowerShell Windows
```

### Tests automatizados (Pest / PHPUnit)

Si se modifica `ChunkedUploadController` o cualquier lógica de validación, correr los tests antes de probar manualmente:

```bash
php artisan test                          # Todos los tests
php artisan test --filter ChunkUpload     # Solo tests de upload
php artisan test --filter StoreUser       # Solo tests de creación de usuarios
```

Estructura de tests para este proyecto:
```
tests/
├── Feature/
│   ├── Auth/          # Login, registro, rate limiting
│   ├── Chat/          # Envío de mensajes, fetch, delete
│   ├── Upload/        # Chunk upload, validación de archivos
│   └── Dashboard/     # CRUD de usuarios, permisos
└── Unit/
    ├── Actions/       # CreateNewUser, sanitización
    └── Models/        # Scopes, helpers del modelo User
```

Ejemplo de test de chunk upload:
```php
it('rechaza chunks mayores al límite', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bigChunk = UploadedFile::fake()->create('video.mp4', 12 * 1024); // 12MB

    $response = $this->post(route('chatify.chunk.upload'), [
        'chunk'       => $bigChunk,
        'chunkIndex'  => 0,
        'totalChunks' => 1,
        'uploadId'    => Str::uuid(),
        'fileName'    => 'video.mp4',
    ]);

    $response->assertStatus(422);
});
```

El entorno de testing usa MySQL (`Elysium Ito_testing`) — crear esa base de datos antes de correr tests por primera vez.

---

## Arquitectura de almacenamiento cero costo — Google Drive personal

### Principio: el servidor Linux como "puente"

El servidor no almacena archivos permanentemente. Solo actúa de tránsito:

```
Usuario → chunk → storage/tmp (Linux) → Job → Google Drive del usuario → borrar tmp
```

Costo total: $0. Cada usuario usa sus 15GB gratuitos de Gmail. El disco del servidor siempre queda vacío.

### Registro de la app en Google Cloud Console

1. Ir a [console.cloud.google.com](https://console.cloud.google.com) → crear proyecto → habilitar **Google Drive API**.
2. Crear credenciales OAuth 2.0 (tipo "Web application").
3. Agregar URI de redirección: `https://tudominio.com/auth/google/callback`.
4. Copiar `client_id` y `client_secret` al `.env`.

Es 100% gratuito para uso personal y proyectos pequeños. No se paga por la API de Drive.

### Instalación

```bash
composer require laravel/socialite
```

`.env`:
```ini
GOOGLE_CLIENT_ID=tu_client_id
GOOGLE_CLIENT_SECRET=tu_client_secret
GOOGLE_REDIRECT_URI=https://tudominio.com/auth/google/callback
```

`config/services.php`:
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

### Flujo OAuth — vincular Drive del usuario

```php
// Ruta 1: redirigir a Google
Route::get('/auth/google/drive', fn() =>
    Socialite::driver('google')
        ->scopes(['https://www.googleapis.com/auth/drive.file'])
        ->with(['access_type' => 'offline', 'prompt' => 'consent'])
        ->redirect()
)->middleware('auth')->name('google.drive.connect');

// Ruta 2: callback — guardar tokens
Route::get('/auth/google/callback', function () {
    $googleUser = Socialite::driver('google')->user();
    auth()->user()->update([
        'google_access_token'  => encrypt($googleUser->token),
        'google_refresh_token' => $googleUser->refreshToken
            ? encrypt($googleUser->refreshToken)
            : auth()->user()->google_refresh_token, // Refresh Token Preservation — no sobreescribir con null
    ]);
    return redirect()->route('chatify')->with('status', 'Drive vinculado correctamente.');
})->middleware('auth');
```

Guardar tokens encriptados en la tabla `users` — agregar migración:
```bash
php artisan make:migration add_google_tokens_to_users_table
```
```php
$table->text('google_access_token')->nullable();
$table->text('google_refresh_token')->nullable();
```

### Protocolo Resumable Upload — obligatorio para 1GB+

No usar upload simple. Google puede cortar conexiones largas. El protocolo resumable permite pausar y continuar:

```php
// app/Actions/Drive/UploadToDriveAction.php
class UploadToDriveAction
{
    public function execute(User $user, string $tmpPath, string $fileName): string
    {
        $accessToken = decrypt($user->google_access_token);
        $fileSize    = filesize($tmpPath);
        $mimeType    = mime_content_type($tmpPath);

        // 1. Iniciar sesión resumable — obtener upload URL
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-Upload-Content-Type' => $mimeType, 'X-Upload-Content-Length' => $fileSize])
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable', [
                'name' => $fileName,
            ]);

        $uploadUrl = $response->header('Location');

        // 2. Enviar en chunks de 5MB
        $chunkSize = 5 * 1024 * 1024;
        $handle    = fopen($tmpPath, 'rb');
        $offset    = 0;

        while (!feof($handle)) {
            $chunk     = fread($handle, $chunkSize);
            $chunkLen  = strlen($chunk);
            $rangeEnd  = $offset + $chunkLen - 1;

            Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Range' => "bytes {$offset}-{$rangeEnd}/{$fileSize}",
                    'Content-Type'  => $mimeType,
                ])
                ->put($uploadUrl, $chunk);

            $offset += $chunkLen;
        }

        fclose($handle);

        // 3. Obtener el fileId del archivo subido
        $meta = Http::withToken($accessToken)
            ->get("https://www.googleapis.com/drive/v3/files?q=name='{$fileName}'&fields=files(id,webViewLink)")
            ->json();

        return $meta['files'][0]['webViewLink'] ?? '';
    }
}
```

### Queue con base de datos — costo $0

No usar Redis ni SQS de pago. La cola en DB es suficiente para este caso:

```ini
# .env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work --queue=drive-uploads  # correr en segundo plano
```

Job de subida:
```php
// app/Jobs/UploadFileToDrive.php
class UploadFileToDrive implements ShouldQueue
{
    public int $tries   = 3;
    public int $timeout = 900; // 15 min en Windows; 3600 en producción Linux

    public function __construct(
        public readonly int    $userId,
        public readonly int    $toUserId,   // receptor — para broadcast
        public readonly string $tmpPath,
        public readonly string $fileName,
        public readonly int    $messageId,
    ) {}

    public function handle(): void
    {
        // ... resolveAccessToken, resumableUpload ...
        ChMessage::where('id', $this->messageId)->update([
            'attachment'   => $driveLink,
            'drive_status' => 'synced',
        ]);
        Storage::delete($this->tmpPath);
        $this->broadcastStatus('synced', $driveLink); // notifica a receptor y emisor
    }

    public function failed(\Throwable $e): void
    {
        Storage::delete($this->tmpPath);
        ChMessage::where('id', $this->messageId)->update(['drive_status' => 'failed']);
        $this->broadcastStatus('failed'); // burbuja de error persistente (NTC 5854)
    }
}
```

Despachar desde el controller tras recibir el archivo ensamblado:
```php
// Copiar a drive_tmp antes de despachar — el Job trabaja sobre esa copia
$tmpForDrive = storage_path('app/private/drive_tmp/' . $newName);
copy($finalPath, $tmpForDrive);

UploadFileToDrive::dispatch(
    userId:    $userId,
    toUserId:  (int) $toId,
    tmpPath:   'private/drive_tmp/' . $newName,
    fileName:  $fileName,
    messageId: $chatMessage->id,
)->onQueue('drive-uploads');
```

### Limpieza automática — disco siempre vacío

El Job borra el tmp al terminar (éxito o fallo). Además, programar un cleanup de seguridad para huérfanos:

```php
// app/Console/Commands/CleanTmpUploads.php
// En routes/console.php o Kernel:
Schedule::command('uploads:clean-tmp')->hourly();
```

```php
// Lógica: borrar archivos en storage/app/private/chunks/ con más de 2 horas
Storage::allFiles('private/chunks')
    ->filter(fn($f) => Storage::lastModified($f) < now()->subHours(2)->timestamp)
    ->each(fn($f) => Storage::delete($f));
```

### Frontend — barra de progreso con Uppy (opcional) o fetch nativo

Si se usa Uppy (open source, gratis):
```bash
npm install @uppy/core @uppy/dashboard @uppy/xhr-upload
```

Con fetch nativo (ya implementado en `code.js`): el chunk upload existente es compatible — solo cambiar el destino final de local a Drive via el Job.

### Privacidad y Ley 1581 (Colombia)

- Los archivos **no viven en el servidor** — solo pasan por él. Facilita cumplimiento de protección de datos.
- El `access_token` y `refresh_token` se guardan **encriptados** en DB (`encrypt()`/`decrypt()`).
- Nunca loguear tokens de Google — verificar que `config/logging.php` no registre el modelo `User` completo.
- El usuario puede desvincular su Drive en cualquier momento — implementar ruta que ponga los tokens a `null`.
- Los links de Drive en el chat son del Drive **del usuario**, no del servidor — privacidad por diseño.

---

## Monitor de disco — `disk:check`

- Comando: `php artisan disk:check` — verifica espacio libre, limpia chunks huérfanos (>2h) y envía alerta por correo al admin.
- Schedule registrado en `routes/console.php`: `Schedule::command('disk:check')->hourly()`.
- Umbrales: **80%** = advertencia por correo, **90%** = bloqueo de uploads + correo crítico.
- Anti-spam: solo envía un correo por nivel cada 2 horas (via caché).
- El bloqueo se libera automáticamente en la siguiente verificación si el disco baja del 90%.

**Para que el scheduler corra en producción Linux**, agregar al crontab:
```bash
* * * * * php /var/www/html/artisan schedule:run >> /dev/null 2>&1
```

**En Windows local**, correr manualmente para verificar el estado del disco:
```bash
php artisan disk:check
```

**Configurar el correo del admin** en `.env`:
```ini
MAIL_FROM_ADDRESS=admin@tudominio.com
```
El comando usa `MAIL_FROM_ADDRESS` como destinatario de alertas si no se define `mail.admin_address` en `config/mail.php`.

---

## Correo Gmail SMTP — formulario de contacto

El formulario de contacto del home y el del panel admin usan Gmail SMTP para enviar correos al propietario.

### Configuración en `.env`

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD="xxxx xxxx xxxx xxxx"   # App Password — NO la contraseña real de Gmail
MAIL_FROM_ADDRESS="tu_correo@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_ADDRESS="tu_correo@gmail.com"   # Correo donde llegan los mensajes de contacto
```

### Generar App Password de Gmail

1. Ir a [myaccount.google.com](https://myaccount.google.com)
2. Seguridad → Verificación en 2 pasos (debe estar activa)
3. Contraseñas de aplicaciones → crear nueva → copiar los 16 caracteres
4. Pegar en `MAIL_PASSWORD` con comillas si tiene espacios

> Sin verificación en 2 pasos activa, Google no permite App Passwords.

### `config/mail.php` — campo admin_address

```php
'admin_address' => env('MAIL_ADMIN_ADDRESS', env('MAIL_FROM_ADDRESS')),
```

Este campo centraliza el destinatario de los mensajes de contacto. Tanto el `ContactController` (usuarios) como el `sendFromAdmin` (admins) lo usan.

### Diferenciación de remitentes

- Mensajes de **usuarios regulares**: asunto `Contacto — Elysium Ito`, sin prefijo especial.
- Mensajes de **admins desde el panel**: asunto `Admin "nombre" — Elysium Ito`, con nombre y email del admin en el cuerpo.

### Ejecutar tras cambiar `.env`

```bash
php artisan config:clear
php artisan config:cache
```

---

## Google OAuth en desarrollo local — `localhost` vs `127.0.0.1`

Google OAuth requiere que la URI de redirección registrada en Google Cloud Console coincida **exactamente** con la que genera la app. `localhost` y `127.0.0.1` son distintos para Google aunque apunten al mismo servidor local.

**Regla**: usar siempre `http://127.0.0.1:8000` en desarrollo — tanto en `APP_URL` como en las URIs de Google.

```env
APP_URL=http://127.0.0.1:8000
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

En Google Cloud Console registrar exactamente esas dos URIs en "URIs de redireccionamiento autorizados". Google tarda ~5 minutos en propagar los cambios.

Tras actualizar `.env`:
```bash
php artisan config:clear
php artisan config:cache
```

---

## Verificar e instalar Socialite — checklist rápido

Antes de asumir que Socialite está o no está, verificar primero:

```bash
composer show laravel/socialite
```

- Si devuelve la versión instalada → ya está, saltar al paso de configuración.
- Si devuelve `Package laravel/socialite not found` → instalar:

```bash
composer require laravel/socialite
```

### Pasos de configuración tras confirmar Socialite

1. Agregar las variables al `.env`:

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

2. Registrar los drivers en `config/services.php`:

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
'google_drive' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_DRIVE_REDIRECT_URI'),
],
```

3. Limpiar caché de configuración:

```bash
php artisan config:clear
php artisan config:cache
```

4. Verificar que las rutas de callback existen en `routes/web.php`:
   - `GET /auth/google/login-callback` → `GoogleAuthController@callback`
   - `GET /auth/google/callback` → `GoogleDriveController@callback`

5. Confirmar que ambas URIs están registradas en Google Cloud Console → Credenciales → URIs de redireccionamiento autorizados. Google tarda ~5 minutos en propagar cambios nuevos.

### Síntoma más común: `redirect_uri_mismatch`

Google rechaza el callback si la URI en el request no coincide exactamente con la registrada en la consola. Causas frecuentes:

- Usar `localhost` en el código pero `127.0.0.1` en la consola (o viceversa)
- Puerto distinto (`8000` vs `80`)
- Trailing slash (`/callback` vs `/callback/`)
- URI de login-callback registrada pero no la de drive-callback (o al revés)

Solución: copiar la URI exacta que aparece en el error de Google y registrarla tal cual en la consola.

---

## Seguridad de credenciales OAuth — nunca en archivos del proyecto

Las credenciales de Google OAuth (Client ID y Client Secret) deben vivir **únicamente en `.env`**. Nunca guardarlas en archivos `.md`, `.txt`, comentarios de código, ni ningún archivo que pueda llegar al repositorio Git — incluso en repos privados, el historial de Git es permanente.

- `.env` ya está en `.gitignore` — es el único lugar correcto.
- Si accidentalmente se commitean credenciales, revocarlas inmediatamente en Google Cloud Console (APIs & Services → Credentials → editar cliente → regenerar secret).
- El Client Secret de Google **no se puede volver a ver** tras cerrar el diálogo de creación. Si se pierde, generar uno nuevo desde la consola — no intentar recuperarlo.
- En producción, las credenciales van en las variables de entorno del servidor, nunca en el repo.

### Error 403: access_denied — usuarios de prueba

Mientras la app esté en modo "Testing" en Google Cloud Console, Google bloquea cualquier cuenta que no esté en la lista de usuarios de prueba. Solución:

1. Ir a **APIs & Services → OAuth consent screen → Test users**
2. Agregar el correo que se quiere usar para probar
3. Google aplica el cambio de inmediato — no requiere esperar propagación

---

## Archivos de entorno — `.env` vs `.env.example`

Este proyecto usa dos archivos de entorno con responsabilidades distintas:

| Archivo | Propósito | En Git |
|---|---|---|
| `.env` | Entorno local de desarrollo (`APP_ENV=local`) | ❌ Nunca — está en `.gitignore` |
| `.env.example` | Plantilla para producción (`APP_ENV=production`) | ✅ Sí — sin valores sensibles |

### `.env` — desarrollo local

Valores fijos para `php artisan serve` en Windows local:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_HOST=127.0.0.1
DB_PORT=3307          # Puerto local de MySQL/Laragon — puede variar
DB_DATABASE=elysium_ito

SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false   # HTTP en local, no HTTPS

GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

### `.env.example` — plantilla de producción (EC2 + RDS Aurora)

Copiar a `.env` en el servidor y rellenar los valores:

```bash
cp .env.example .env
php artisan key:generate
# Editar .env con los valores reales del servidor
```

Diferencias clave respecto al `.env` local:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_HOST=your-aurora-cluster.rds.amazonaws.com
DB_PORT=3306

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true    # HTTPS obligatorio en producción

GOOGLE_REDIRECT_URI=https://tudominio.com/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=https://tudominio.com/auth/google/callback

LOG_STACK=daily               # Rotación de logs — no acumular en un solo archivo
LOG_LEVEL=error               # Solo errores en producción, no debug
```

### Reglas obligatorias

- Nunca copiar el `.env` local al servidor — las URLs, puertos y flags de debug son distintos.
- `APP_KEY` debe generarse en el servidor con `php artisan key:generate` — nunca reutilizar la clave local.
- Las URIs de Google OAuth deben actualizarse en Google Cloud Console cuando cambia el dominio.
- `SESSION_SECURE_COOKIE=true` requiere HTTPS — activarlo sin HTTPS rompe el login.

### Checklist de deploy completo (EC2 + RDS Aurora)

```bash
# 1. Copiar plantilla y generar clave
cp .env.example .env
php artisan key:generate

# 2. Migraciones
php artisan migrate --force

# 3. Storage
php artisan storage:link
php artisan storage:setup      # Crea avatar default + repara usuarios con placeholder

# 4. Cachear todo
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Assets
composer install --no-dev --optimize-autoloader
npm run build

# 6. Queue worker (con Supervisor en Linux)
# Ver sección "Colas en Windows" para la config de Supervisor
```

---

## Comando `storage:setup` — qué hace y cuándo ejecutarlo

`php artisan storage:setup` es idempotente — se puede correr múltiples veces sin efectos secundarios.

**Qué hace en orden:**
1. Crea la carpeta `storage/app/public/users-avatar/` si no existe.
2. Descarga `avatar.png` desde DiceBear API como avatar default. Si DiceBear no está disponible, guarda un PNG 1×1 gris como fallback — nunca deja el archivo vacío.
3. Detecta usuarios con `avatar = null`, `avatar.png` o `default.png` y regenera su avatar con `User::generateAvatar()`.

**Cuándo ejecutarlo:**
- En cada deploy nuevo (ya incluido en el checklist de deploy).
- Después de restaurar un backup de DB en un servidor nuevo donde el storage no existe.
- Si se reportan avatares rotos o círculos vacíos en la UI.

**No ejecutar en producción con datos reales sin revisar** — el paso 3 sobreescribe avatares de usuarios que tengan `avatar.png` como valor. Si un usuario eligió ese nombre para su avatar personalizado (improbable pero posible), se regeneraría.

---

## Guardas defensivas en vistas Blade — `filemtime()` y `file_exists()`

`filemtime()` lanza un `E_WARNING` y devuelve `false` si el archivo no existe. En producción con `APP_DEBUG=false` esto no rompe la página, pero en local con `APP_DEBUG=true` puede causar un error visible.

**Patrón obligatorio** cuando se usa `filemtime()` para cache-busting de assets en Blade:

```php
{{-- MAL — falla si el archivo no existe (primer deploy, CI, etc.) --}}
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

{{-- BIEN — guarda defensiva --}}
<script src="{{ asset('js/app.js') }}?v={{ file_exists(public_path('js/app.js')) ? filemtime(public_path('js/app.js')) : '1' }}"></script>
```

Aplica a cualquier asset referenciado con `filemtime()` en vistas Blade: JS, CSS, imágenes. En este proyecto aplica a los archivos en `public/js/chatify/` que se referencian en `app.blade.php`.
