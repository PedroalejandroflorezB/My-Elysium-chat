# Arquitectura del Sistema — Elysium Ito

## Resumen ejecutivo

Elysium Ito resuelve la transferencia de archivos de hasta 1 GB en un chat en tiempo real con **costo de almacenamiento $0**, usando Google Drive personal de cada usuario como capa de persistencia. El servidor actúa como orquestador temporal — nunca almacena archivos de forma permanente.

---

## Capas del sistema

### Capa 1 — Transporte (Frontend)

Sistema de **Chunked Upload** con JavaScript nativo (`File.slice()`).

- Archivos divididos en bloques de **10 MB** para evitar desbordamiento de memoria en el servidor.
- Hasta **3 chunks en paralelo** para maximizar el uso del ancho de banda.
- `uploadId` UUID v4 generado en el cliente — identifica la sesión y permite distinguir uploads concurrentes.
- Si el WebSocket se desconecta durante la subida, la subida continúa (son requests HTTP independientes).

### Capa 2 — Procesamiento (Backend)

`ChunkedUploadController` recibe cada chunk y lo escribe en `storage/app/private/chunks/{uploadId}/`.

- Escritura directa con `move_uploaded_file()` (sin abstracción Laravel).
- Lock atómico con `rename()` para evitar race conditions en el ensamblado.
- Al llegar el último chunk: ensambla con `stream_copy_to_stream`, valida Magic Bytes, crea el mensaje en BD.
- Límite de 3 uploads concurrentes por usuario (Cache, TTL 30 min).
- Rate limiting: 200 chunks/minuto por usuario.

### Capa 3 — Almacenamiento (Cloud Bridge)

**Google Drive API (OAuth2 + scope `drive.file`)** — el Job sube el archivo al Drive personal del usuario.

- Protocolo **Resumable Upload** en chunks de 5 MB — soporta archivos de 1 GB+ sin timeout.
- Al finalizar: `Storage::delete($tmpPath)` — disco del servidor siempre vacío.
- Permisos `role=reader, type=anyone` → genera `webViewLink` compartible.
- Costo de almacenamiento para el administrador: **$0**. Cada usuario usa sus 15 GB gratuitos de Google.

### Capa 4 — Tiempo Real (Broadcasting)

**Broadcasting Dual** via Pusher. El evento `DriveUploadUpdated` notifica simultáneamente al emisor y al receptor.

| Estado | Significado | UI |
|---|---|---|
| `local` | Sin Drive vinculado | Sin evento |
| `processing` | Job en cola, subiendo | Spinner azul |
| `synced` | Completado | Link verde de Drive |
| `failed` | Agotó 3 reintentos | Botón rojo "Reintentar" |
| `error_authorization` | Token revocado | Enlace naranja "Vincular Drive" |

---

## Flujo completo de transferencia

```
1.  Usuario adjunta archivo (hasta 1 GB) en el chat
2.  Frontend divide en chunks de 10 MB, envía 3 en paralelo
3.  ChunkedUploadController ensambla en storage/app/private/chunks/{uploadId}/
4.  Valida Magic Bytes (MIME real vs extensión declarada)
5.  Crea ChMessage con drive_status='processing'
6.  Copia archivo a drive_tmp/ y despacha UploadFileToDrive al queue
7.  Broadcast DriveUploadUpdated(processing) → burbujas muestran spinner
8.  Job sube a Google Drive via Resumable Upload (chunks de 5 MB)
9.  Job otorga permisos reader/anyone → genera webViewLink
10. Job actualiza ChMessage: drive_status='synced' + webViewLink
11. Broadcast DriveUploadUpdated(synced) → burbujas cambian a link de Drive
12. Job llama Storage::delete($tmpPath) → disco vacío
13. Si Job agota $tries=3 → drive_status='failed' → burbuja roja persistente
14. Si Google devuelve 401/invalid_grant → drive_status='error_authorization'
```

---

## Backup simétrico (emisor + receptor)

Cuando el emisor sube un archivo, el controlador despacha **dos jobs independientes**:

- **Job del emisor** (`isRecipientCopy=false`): actualiza `drive_status`, hace broadcast.
- **Job del receptor** (`isRecipientCopy=true`): sube silenciosamente al Drive del receptor, no toca `drive_status`, no hace broadcast. Fallo silencioso.

El `refresh_token` del receptor funciona aunque no esté conectado — el Job se ejecuta en background.

---

## Límites de configuración

| Parámetro | Valor | Archivo |
|---|---|---|
| Tamaño de chunk (cliente) | 10 MB | `code.js` |
| Chunks en paralelo | 3 | `code.js` |
| Máx chunks por upload | 105 (~1 GB) | `ChunkedUploadController` |
| Máx uploads concurrentes/usuario | 3 | `ChunkedUploadController` |
| Tamaño máximo de archivo | `CHATIFY_MAX_FILE_SIZE` MB | `.env` |
| Rate limit requests | 200/min por usuario | `ChunkedUploadController` |
| Job timeout | 900 s | `UploadFileToDrive` |
| Job reintentos | 3 | `UploadFileToDrive` |

---

## Transferencia P2P WebRTC

Alternativa al chunked upload para transferencias directas entre navegadores. El servidor **nunca almacena los bytes** del archivo — solo los metadatos del mensaje.

### Flujo

```
Sender                     Pusher (señalización)          Receiver
──────                     ─────────────────────          ────────
p2pSendFile(file, toId)
  │
  ├─ POST /webrtc/signal ──► relay transfer-request ──► showIncomingRequest()
  │                                                    [Acepta / Rechaza]
  │◄── relay transfer-accepted ◄──────────────────────────┘
  │
  ├─ createOffer() ──► relay offer ──────────────────► createAnswer()
  │◄── relay answer ◄─────────────────────────────────────┘
  │
  ├─ ICE candidates exchange (ambos lados)
  │
  ├─ DataChannel abierto ◄──────────────────────────► DataChannel abierto
  ├─ Envía chunks de 64 KB ─────────────────────────► Recibe + ensambla
  │
  ├─ POST /webrtc/save-transfer (solo metadatos)
  │   └─► Crea ChMessage en BD con attachment.p2p=true
  │   └─► Push Pusher 'messaging' al receptor
  └─ Reemplaza temp card con mensaje real
```

### Tipos de señal

| Tipo | Dirección | Descripción |
|---|---|---|
| `transfer-request` | Sender → Receiver | Notifica archivo entrante (nombre, tamaño, tipo) |
| `transfer-accepted` | Receiver → Sender | Receptor aceptó |
| `transfer-cancel` | Cualquiera | Rechazo o cancelación |
| `offer` | Sender → Receiver | SDP offer WebRTC |
| `answer` | Receiver → Sender | SDP answer WebRTC |
| `ice-candidate` | Ambos | Candidatos ICE |

### Comparativa chunked upload vs P2P

| Aspecto | Chunked Upload | P2P WebRTC |
|---|---|---|
| Ruta del archivo | Servidor → BD → cliente | Directo navegador a navegador |
| Almacenamiento servidor | `storage/app/public/attachments/` | Nunca toca el servidor |
| Límite de tamaño | `CHATIFY_MAX_FILE_SIZE` | Sin límite de servidor |
| Funciona en NAT estricto | Siempre | Solo con STUN/TURN adecuado |
| Chunk size | 10 MB | 64 KB (DataChannel) |

### STUN configurado

```js
{ urls: 'stun:stun.l.google.com:19302' },
{ urls: 'stun:stun1.l.google.com:19302' }
```

> No hay TURN configurado. Para producción con usuarios detrás de firewalls corporativos, agregar un servidor TURN.

---

## Decisiones de diseño

| Decisión | Alternativa descartada | Razón |
|---|---|---|
| `QUEUE_CONNECTION=database` | Redis / SQS | Costo $0, suficiente para el volumen del proyecto |
| `Http::` de Laravel para Drive API | `google/apiclient` | Dependencia con advisories de seguridad activos |
| Chunked Upload propio | Librerías como Uppy | Control total, sin dependencias externas |
| `drive.file` scope | `drive` scope completo | Acceso mínimo — solo archivos creados por la app |
| Inline styles en burbujas Drive | Clases CSS externas | Las burbujas se inyectan dinámicamente via JS |
