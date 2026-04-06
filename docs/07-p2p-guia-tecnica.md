# P2P Transferencia - Guía Técnica Profunda
**Para entender qué hace el código y por qué**

---

## 1. ¿Qué es WebRTC y por qué lo usamos?

WebRTC (Web Real-Time Communication) es una tecnología del navegador que permite
conectar dos dispositivos **directamente** sin pasar por un servidor intermedio.

```
SIN WebRTC:
  Tu PC → Servidor → PC del receptor   (el archivo pasa por el servidor)

CON WebRTC:
  Tu PC ══════════════════════════════ PC del receptor   (directo)
  El servidor solo ayuda a "presentar" los dos dispositivos
```

Esto es importante porque:
- El servidor no ve el archivo
- No hay límite de tamaño impuesto por el servidor
- La velocidad depende solo de las redes de los dos usuarios

---

## 2. ¿Cómo se establece la conexión? (Signaling)

Antes de transferir datos, los dos navegadores necesitan "conocerse".
Esto se llama **signaling** y es lo único que pasa por el servidor.

```
Paso 1: Emisor crea una "oferta" (SDP offer) y la manda al servidor
Paso 2: Servidor reenvía la oferta al receptor
Paso 3: Receptor crea una "respuesta" (SDP answer) y la manda al servidor
Paso 4: Servidor reenvía la respuesta al emisor
Paso 5: Ambos intercambian "candidatos ICE" (posibles rutas de conexión)
Paso 6: WebRTC elige la mejor ruta y conecta directo
```

**SDP** = Session Description Protocol. Es un texto que describe las capacidades
del navegador (codecs, IPs, puertos).

**ICE** = Interactive Connectivity Establishment. Es el proceso de encontrar
la mejor ruta entre los dos dispositivos.

**Todo esto pesa ~50KB. El archivo nunca toca el servidor.**

---

## 3. ¿Qué es un DataChannel?

Una vez conectados, WebRTC abre un **DataChannel** — un tubo bidireccional
por donde fluyen los datos binarios.

```javascript
// Así se crea en el código
const dataChannel = peerConnection.createDataChannel('fileTransfer', {
    ordered: true  // Los chunks llegan en orden garantizado
});
```

**`ordered: true`** es crítico para archivos. Significa que si un chunk llega
tarde, el navegador espera en lugar de descartarlo. Sin esto, el archivo
se ensamblaría en el orden equivocado y quedaría corrupto.

---

## 4. ¿Qué es un Chunk y por qué importa el tamaño?

Un archivo de 8GB no se puede mandar de un golpe. Se divide en pedazos
llamados **chunks**.

```
Archivo 8GB
├── Chunk 0: bytes 0 → 16383        (16 KB)
├── Chunk 1: bytes 16384 → 32767    (16 KB)
├── Chunk 2: bytes 32768 → 49151    (16 KB)
└── ... 524,288 chunks en total
```

### ¿Por qué no mandar chunks enormes?

WebRTC usa internamente el protocolo **SCTP** para DataChannels.
SCTP tiene un buffer interno limitado. Si mandas chunks muy grandes:

```
Chunk 256KB → SCTP lo fragmenta internamente en ~16 paquetes de 16KB
            → Si algún paquete se pierde, SCTP retransmite TODO el chunk
            → Con mala red, esto satura el buffer
            → Buffer lleno → conexión cae → "failed"
```

Esto fue exactamente lo que pasó cuando se intentó 256KB para archivos de 8GB.

### ¿Por qué no mandar chunks de 1 byte?

```
Chunk 1 byte → 8,589,934,592 chunks para 8GB
             → 8.5 mil millones de operaciones de envío
             → CPU al 100%, transferencia lentísima
```

### El sweet spot por tamaño de archivo

```
> 4 GB  →  16 KB  (máxima estabilidad, muchos chunks pequeños)
> 1 GB  →  32 KB  (balance estabilidad/velocidad)
> 500MB →  64 KB  (buen balance)
< 500MB →  hasta 256 KB según la red (máxima velocidad)
```

La lógica es: **archivos más grandes necesitan chunks más pequeños**
porque la transferencia dura más tiempo y hay más oportunidades de que
la red fluctúe.

---

## 5. ¿Qué es el Buffer y el Backpressure?

El **buffer** es una zona de memoria donde se acumulan los chunks
antes de ser enviados por la red.

```
Código JS → [BUFFER] → Red → Receptor
              ↑
         Aquí se acumulan
         los chunks
```

Si el código manda chunks más rápido de lo que la red los puede enviar,
el buffer se llena. Si se llena demasiado:
- Chrome lanza error "buffer full"
- La conexión puede caer

**Backpressure** es el mecanismo para pausar el envío cuando el buffer
está lleno y reanudar cuando hay espacio:

```javascript
// En el código actual:
if (dataChannel.bufferedAmount > pauseAt) {
    // PAUSA: esperar a que el buffer baje
    await waitForBufferSpace();
}
// REANUDA cuando bufferedAmount <= resumeAt
```

### Configuración actual para archivos grandes (>500MB):

```
bufferLimit: 4 MB   → tamaño máximo del buffer
pauseAt:     3 MB   → pausar cuando llega a 3MB
resumeAt:    1 MB   → reanudar cuando baja a 1MB
```

El rango pause/resume (3MB → 1MB) es importante. Si el resume fuera
muy bajo (ej: 512KB), el código pausaría y reanudaría constantemente
generando muchas interrupciones. Con 1MB hay un margen cómodo.

---

## 6. ¿Cómo se guarda el archivo en el receptor?

Hay 3 estrategias según el navegador:

### Estrategia 1: File System Access API (Chrome/Edge)
```
Chunk llega → se escribe directo al archivo en disco
RAM usada: ~0 bytes
```
El usuario elige dónde guardar ANTES de que empiece la transferencia.
Los chunks se escriben directamente ahí. Es como si el archivo
"apareciera" en el disco mientras se transfiere.

### Estrategia 2: IndexedDB (Firefox/Brave)
```
Chunk llega → se guarda en IndexedDB (base de datos del navegador)
Al final → Service Worker lee IndexedDB y genera la descarga
RAM usada: ~0 bytes (IndexedDB usa disco)
```
IndexedDB es una base de datos que vive en el disco del usuario,
no en RAM. Por eso puede manejar archivos grandes.

### Estrategia 3: Memoria (Safari/iOS/fallback)
```
Chunk llega → se guarda en un array en RAM
Al final → se crea un Blob y se descarga
RAM usada: = tamaño del archivo
Límite: ~2GB (límite de RAM del navegador)
```
Esta es la estrategia más simple pero la más limitada.

---

## 7. ¿Por qué la conexión a veces dice "failed"?

Cuando ves `Estado de conexión: failed` en los logs, puede ser por:

1. **Buffer saturado** — chunks muy grandes + red lenta = buffer lleno = conexión cae
   - Solución: reducir chunk size (ya implementado)

2. **NAT/Firewall** — algunos routers bloquean conexiones P2P directas
   - En ese caso WebRTC intenta usar STUN (servidores de Google) como relay
   - Si STUN también falla, la conexión no se puede establecer
   - Solución real: servidor TURN propio (tiene costo)

3. **Timeout de ICE** — si el handshake tarda demasiado
   - Puede pasar en redes muy lentas o con mucha latencia

4. **Inactividad** — si no llegan datos por mucho tiempo
   - WebRTC cierra la conexión por timeout

---

## 8. ¿Qué es STUN y por qué lo usamos?

```javascript
// En el código:
iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
]
```

**STUN** (Session Traversal Utilities for NAT) es un servidor que ayuda
a los dispositivos detrás de un router a descubrir su IP pública.

```
Tu PC (IP privada: 192.168.1.5)
    → pregunta al servidor STUN: "¿cuál es mi IP pública?"
    → STUN responde: "tu IP pública es 203.0.113.42, puerto 54321"
    → compartes esa info con el receptor
    → el receptor puede conectarse directamente a ti
```

Usamos los servidores STUN de Google porque son gratuitos y confiables.
**No pasan datos del archivo**, solo ayudan a descubrir IPs.

---

## 9. ¿Qué mejoró entre la versión anterior y la actual?

### Versión anterior (backup)
```javascript
// Chunk size fijo para todos los archivos
chunkSize: 32768  // 32KB siempre

// Buffer fijo
bufferLimit: 12MB  // igual para todos
backpressure: { pause: 12MB, resume: 6MB }
```

**Problema:** Con archivos de 8GB, 32KB chunks y buffer de 12MB,
el buffer se llenaba demasiado y la conexión caía.

### Versión actual
```javascript
// Chunk size adaptativo por tamaño
> 4GB  → 16KB
> 1GB  → 32KB
> 500MB → 64KB
< 500MB → hasta 256KB según red

// Buffer conservador para archivos grandes
> 500MB: bufferLimit: 4MB, pause: 3MB, resume: 1MB
```

**Mejora:** Los archivos grandes usan chunks más pequeños y buffer
más conservador → menos saturación → menos drops de conexión.

### Formato de tiempo
```
Antes: "358m 22s restante"   ← confuso y feo
Ahora: "~5h 58m"             ← claro y limpio
```

### ETA estabilizado
```
Antes: mostraba tiempo desde el primer chunk (muy inestable al inicio)
Ahora: espera 5 muestras de velocidad antes de mostrar ETA
       mientras tanto muestra "Calculando..."
```

---

## 10. Glosario Rápido

| Término | Qué es |
|---------|--------|
| **WebRTC** | Tecnología para conexiones P2P en el navegador |
| **DataChannel** | El "tubo" por donde fluyen los datos en WebRTC |
| **SDP** | Texto que describe las capacidades de conexión |
| **ICE** | Proceso de encontrar la mejor ruta de conexión |
| **STUN** | Servidor que ayuda a descubrir la IP pública |
| **TURN** | Servidor relay cuando P2P directo no es posible |
| **Chunk** | Pedazo del archivo para enviar por partes |
| **Buffer** | Memoria temporal donde se acumulan chunks |
| **Backpressure** | Mecanismo de pausa/reanudación del envío |
| **SCTP** | Protocolo interno que usa WebRTC para DataChannels |
| **NAT** | Router que traduce IPs privadas a públicas |
| **File System API** | API del navegador para escribir directo a disco |
| **IndexedDB** | Base de datos del navegador que usa disco |
| **Service Worker** | Script que corre en background en el navegador |
