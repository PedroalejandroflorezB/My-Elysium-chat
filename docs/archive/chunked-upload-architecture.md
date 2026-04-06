# Arquitectura de Upload Chunked — Elysium Ito

## Resumen

Elysium Ito implementa un sistema de subida de archivos por fragmentos (chunked upload) que permite enviar archivos de cualquier tamaño (probado hasta 600 MB) sin interrupciones, con integridad garantizada y progreso visible en tiempo real.

---

## Cómo funciona

### 1. División en chunks (cliente)

Cuando el usuario selecciona un archivo y presiona enviar, el JS divide el archivo en fragmentos de **10 MB** usando la API nativa `File.slice()`:

```js
const CHUNK_SIZE   = 10 * 1024 * 1024; // 10 MB
const PARALLEL_MAX = 3;                // 3 chunks en paralelo

const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
```

Un archivo de 600 MB genera ~60 chunks. Se suben 3 en paralelo para maximizar el throughput sin saturar el servidor.

### 2. Identificación de sesión

Cada upload recibe un `uploadId` único (UUID v4) generado en el cliente:

```js
const uploadId = crypto.randomUUID();
```

Este ID agrupa todos los chunks de un mismo archivo en el servidor y permite distinguir uploads concurrentes del mismo usuario.

### 3. Envío de cada chunk

Cada chunk se envía como `multipart/form-data` vía `XMLHttpRequest` con los siguientes campos:

| Campo | Descripción |
|-------|-------------|
| `chunk` | Blob binario del fragmento |
| `chunkIndex` | Índice del fragmento (0-based) |
| `totalChunks` | Total de fragmentos del archivo |
| `uploadId` | UUID de la sesión de upload |
| `fileName` | Nombre original del archivo |
| `id` | ID del destinatario del mensaje |
| `message` | Texto opcional del mensaje |
| `temporaryMsgId` | ID temporal para reemplazar el card en el chat |

### 4. Recepción y ensamblado (servidor)

`ChunkedUploadController::upload()` procesa cada chunk:

1. **Validaciones rápidas** (sin I/O): formato del `uploadId`, rango de índices, tamaño del chunk (máx 11 MB de tolerancia), extensión permitida.
2. **Escritura directa** con `move_uploaded_file()` — más rápido que el filesystem de Laravel.
3. **Contador atómico** con lock de archivo (`.lock`) para contar chunks llegados sin usar `glob`.
4. Cuando `arrived === totalChunks`: **ensamblado** leyendo los chunks en orden con `stream_copy_to_stream`.
5. Validación del tamaño final contra `CHATIFY_MAX_FILE_SIZE`.
6. Creación del mensaje en la BD y push por Pusher al destinatario.

### 5. Limpieza de chunks

Tras ensamblar (o en caso de error), se eliminan los archivos temporales con `scandir` (no `glob`, que en Windows omite archivos ocultos como `.lock` y `.count`):

```php
$files = array_diff(scandir($chunkDir), ['.', '..']);
foreach ($files as $file) {
    @unlink($chunkDir . DIRECTORY_SEPARATOR . $file);
}
@rmdir($chunkDir);
```

---

## Integridad del archivo

| Mecanismo | Detalle |
|-----------|---------|
| Orden garantizado | Los chunks se nombran `chunk_000000`, `chunk_000001`... y se ensamblan en orden estricto |
| Verificación de presencia | Si falta algún chunk al ensamblar, se aborta y se elimina el archivo parcial |
| Tamaño máximo | El archivo ensamblado se valida contra `CHATIFY_MAX_FILE_SIZE` antes de guardarse |
| Lock de escritura | El contador de chunks usa `flock(LOCK_EX)` para evitar race conditions en uploads paralelos |

---

## Progreso en tiempo real

El cliente muestra una barra de progreso con velocidad y tiempo estimado usando una **ventana deslizante de 4 segundos**:

```js
// Velocidad = bytes transferidos en los últimos 4s / tiempo transcurrido
const speed     = windowSpeed();           // bytes/seg
const remaining = (file.size - loaded) / speed; // segundos restantes
```

La UI muestra: `Subiendo... 47% · 3.2 MB/s · ~2m 15s`

---

## Cómo escalar progresivamente

### Aumentar el tamaño máximo de archivo

**1. Variable de entorno** (`.env`):
```env
CHATIFY_MAX_FILE_SIZE=2048000   # en MB — actualmente 1 TB efectivo
```

**2. PHP** (`php.ini` o `.htaccess`):
```ini
upload_max_filesize = 2048M
post_max_size       = 2048M
max_execution_time  = 0         # sin límite de tiempo
```

**3. Nginx** (si aplica):
```nginx
client_max_body_size 2048M;
```

### Aumentar el tamaño de chunk

Para conexiones rápidas o archivos muy grandes, aumentar el chunk size reduce el número de requests:

```js
// En public/js/chatify/code.js
const CHUNK_SIZE = 25 * 1024 * 1024; // 25 MB — para redes rápidas
```

> El servidor acepta hasta 11 MB por chunk (`MAX_CHUNK_BYTES`). Si se aumenta el chunk size en el cliente, hay que ajustar también esta constante en `ChunkedUploadController`.

### Aumentar paralelismo

```js
const PARALLEL_MAX = 6; // más chunks simultáneos en redes de alta velocidad
```

Y en el servidor, el límite de uploads concurrentes por usuario:
```php
private const MAX_ACTIVE_UPLOADS = 5; // en ChunkedUploadController
```

### Aumentar el límite de chunks

Para archivos muy grandes con chunks pequeños:
```php
private const MAX_CHUNKS = 500; // actualmente 105 (permite ~1 GB con chunks de 10 MB)
```

---

## Límites actuales (configuración por defecto)

| Parámetro | Valor | Archivo |
|-----------|-------|---------|
| Tamaño de chunk | 10 MB | `code.js` |
| Chunks en paralelo | 3 | `code.js` |
| Máx chunks por upload | 105 | `ChunkedUploadController` |
| Máx uploads concurrentes/usuario | 3 | `ChunkedUploadController` |
| Máx tamaño de archivo | `CHATIFY_MAX_FILE_SIZE` MB | `.env` |
| PHP upload_max_filesize | 1024 MB | `php.ini` |
| Rate limit requests | 200/min por usuario | `ChunkedUploadController` |

---

## Flujo completo resumido

```
Cliente                          Servidor
───────                          ────────
Selecciona archivo
  │
  ├─ Genera uploadId (UUID)
  ├─ Divide en N chunks de 10MB
  ├─ Inserta temp card en el chat
  ├─ Muestra barra de progreso
  │
  ├─ POST /chatify/upload-chunk (chunk 0) ──► Guarda chunk_000000
  ├─ POST /chatify/upload-chunk (chunk 1) ──► Guarda chunk_000001   { done: false }
  ├─ POST /chatify/upload-chunk (chunk 2) ──► Guarda chunk_000002
  │   ... (3 en paralelo)
  │
  └─ POST /chatify/upload-chunk (chunk N) ──► Ensambla archivo final
                                              Crea mensaje en BD
                                              Push Pusher al receptor
                                           ◄── { done: true, message: "..." }
  │
  ├─ Reemplaza temp card con mensaje real
  ├─ Oculta barra de progreso
  └─ Habilita botón de envío
```

---

*Documento técnico interno — Elysium Ito*
