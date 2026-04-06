# Arquitectura P2P WebRTC — Elysium Ito

## Resumen

Elysium Ito implementa transferencia de archivos P2P pura usando **WebRTC DataChannel**. Los archivos viajan directamente de navegador a navegador — el servidor nunca almacena los bytes del archivo, solo los metadatos del mensaje (nombre, tamaño, tipo) una vez completada la transferencia.

Pusher actúa exclusivamente como **servidor de señalización** para el handshake WebRTC inicial.

---

## Flujo completo

```
Sender                        Servidor (Pusher)              Receiver
──────                        ─────────────────              ────────
p2pSendFile(file, toId)
  │
  ├─ POST /webrtc/signal ──► relay 'transfer-request' ──► showIncomingRequest()
  │   type: transfer-request                                  │
  │                                                    [Acepta / Rechaza]
  │                                                           │
  │◄── relay 'transfer-accepted' ◄── POST /webrtc/signal ────┘
  │
  ├─ createPC(sender)
  ├─ createOffer()
  ├─ POST /webrtc/signal ──► relay 'offer' ──────────────► setRemoteDescription()
  │   type: offer                                            createAnswer()
  │                                                          setLocalDescription()
  │◄── relay 'answer' ◄──────────────── POST /webrtc/signal ─┘
  │   type: answer (SDP)
  │
  ├─ setRemoteDescription(answer)
  │
  ├─ ICE candidates exchange (ambos lados, vía relay)
  │
  ├─ DataChannel abierto ◄──────────────────────────────► DataChannel abierto
  │
  ├─ Envía chunks de 64 KB ──────────────────────────────► Recibe chunks
  ├─ Envía { type: 'done' } ─────────────────────────────► assembleAndDownload()
  │
  ├─ POST /webrtc/save-transfer  (solo metadatos)
  │   file_name, file_size, file_type, message
  │   └─► Crea ChMessage en BD
  │   └─► Push Pusher 'messaging' al receptor
  │
  └─ Reemplaza temp card con mensaje real
```

---

## Tipos de señal

| Tipo | Dirección | Descripción |
|------|-----------|-------------|
| `transfer-request` | Sender → Receiver | Notifica al receptor que hay un archivo entrante (nombre, tamaño, tipo) |
| `transfer-accepted` | Receiver → Sender | El receptor aceptó — el sender puede crear el offer WebRTC |
| `transfer-cancel` | Cualquiera → otro | Rechazo o cancelación de la transferencia |
| `offer` | Sender → Receiver | SDP offer del handshake WebRTC |
| `answer` | Receiver → Sender | SDP answer del handshake WebRTC |
| `ice-candidate` | Ambos | Candidatos ICE para establecer la conexión P2P |

> `transfer-accepted` y `answer` son tipos distintos a propósito — evitan la colisión entre "el usuario aceptó" y "la respuesta SDP de WebRTC".

---

## Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `public/js/chatify/webrtc-transfer.js` | Módulo P2P completo: señalización, DataChannel, UI de aceptar/rechazar, ensamblado |
| `public/js/chatify/code.js` | Llama a `p2pSendFile()` desde `sendMessage()` cuando hay archivo adjunto |
| `app/Http/Controllers/WebRTCController.php` | Relay de señales vía Pusher + guardado de metadatos en BD |
| `routes/web.php` | Rutas `POST /webrtc/signal` y `POST /webrtc/save-transfer` |
| `resources/views/vendor/Chatify/pages/app.blade.php` | Carga `webrtc-transfer.js`, contiene la UI de preview/progreso |

---

## Estado interno del módulo (`webrtc-transfer.js`)

```js
let _pc             = null;   // RTCPeerConnection
let _dc             = null;   // RTCDataChannel (lado sender)
let _pendingFile    = null;   // File a enviar
let _pendingMessage = '';     // Texto del mensaje acompañante
let _pendingTempId  = null;   // ID del temp card a reemplazar tras el save
let _toId           = null;   // ID del destinatario
let _recvMeta       = null;   // {name, size, type} del archivo entrante
let _recvBufs       = [];     // ArrayBuffers recibidos
let _recvBytes      = 0;      // Bytes acumulados
```

`_pendingTempId` se pasa desde `sendMessage()` en `code.js` para que, al completar el save, el temp card se reemplace correctamente (igual que en chunked upload).

---

## API pública (window)

```js
// Iniciar envío P2P — llamado desde sendMessage() en code.js
window.p2pSendFile(file, toId, textMessage, tempId)

// Manejar señal WebRTC entrante — llamado desde el listener Pusher en code.js
window.p2pHandleSignal(data)  // data = { from_id, type, payload }

// Guardar metadatos en BD tras transferencia exitosa — llamado internamente
window.p2pSaveTransfer(file, toId, message)
```

---

## Servidor — `WebRTCController`

### `signal()` — relay de señales
```php
POST /webrtc/signal
{ to_id, type, payload }
```
Valida el tipo (`offer`, `answer`, `ice-candidate`, `transfer-request`, `transfer-accepted`, `transfer-cancel`) y hace push al canal `private-chatify.{to_id}` de Pusher con el evento `webrtc-signal`.

### `saveTransfer()` — guardar metadatos
```php
POST /webrtc/save-transfer
{ to_id, file_name, file_size, file_type, message }
```
Crea un `ChMessage` con `attachment` JSON que incluye `"p2p": true` (flag para distinguir de uploads normales). El campo `new_name` es `null` — no hay archivo en el servidor. Notifica al receptor vía Pusher con el evento `messaging`.

---

## UI de transferencia entrante

Cuando llega un `transfer-request`, aparece un banner fijo en la esquina inferior derecha:

```
┌─────────────────────────────┐
│ 📎 Archivo entrante          │
│ documento.pdf  (12.4 MB)    │
│ [████████░░░░] (progreso)   │
│  [Aceptar]  [Rechazar]      │
└─────────────────────────────┘
```

Al aceptar: se crea el `RTCPeerConnection` receptor y se envía `transfer-accepted`.
Al rechazar: se envía `transfer-cancel` y se limpia el estado.

Al completar la recepción, el archivo se descarga automáticamente vía `URL.createObjectURL` + click programático.

---

## STUN servers configurados

```js
const ICE_SERVERS = [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
];
```

No hay TURN configurado — la conexión P2P puede fallar en redes con NAT simétrico estricto. Para producción con usuarios detrás de firewalls corporativos, considerar agregar un servidor TURN.

---

## Diferencias con chunked upload

| Aspecto | Chunked Upload | P2P WebRTC |
|---------|---------------|------------|
| Ruta del archivo | Servidor → BD → cliente | Directo navegador a navegador |
| Almacenamiento | `storage/app/public/attachments/` | Nunca toca el servidor |
| Límite de tamaño | `CHATIFY_MAX_FILE_SIZE` (configurable) | Sin límite de servidor (RAM del navegador) |
| Requiere internet estable | Sí | Sí (más sensible a latencia) |
| Funciona en NAT estricto | Siempre | Solo con STUN/TURN adecuado |
| Chunk size | 10 MB | 64 KB (DataChannel) |

---

*Documento técnico interno — Elysium Ito*
