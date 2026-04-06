# P2P File Transfer — Análisis Técnico y Optimizaciones
**Elysium P2P · Abril 2026**

---

## Por qué el chunk size importa tanto

WebRTC usa SCTP (Stream Control Transmission Protocol) por debajo del DataChannel.
SCTP tiene un buffer interno limitado. Si envías chunks muy grandes, el buffer se llena
antes de que la red pueda drenar los datos, y la conexión cae con estado `failed`.

```
Emisor → [chunk] → Buffer SCTP → Red → Buffer SCTP → Receptor
                       ↑
               Si se llena → conexión cae
```

### El problema con 256KB para archivos grandes

Un archivo de 8GB tiene ~32,768 chunks de 256KB.
En una red de 2 MB/s, cada chunk tarda ~128ms en transmitirse.
El buffer SCTP de Chromium tiene un límite interno de ~16MB.

Con 256KB por chunk y backpressure en 12MB:
- Se acumulan ~48 chunks en buffer antes de pausar
- En redes lentas o con jitter, el buffer no drena a tiempo
- SCTP marca la conexión como muerta → `failed`

Con 16KB por chunk y backpressure en 3MB:
- Se acumulan ~192 chunks en buffer antes de pausar
- Cada chunk es pequeño, drena rápido
- El buffer nunca se satura → conexión estable durante horas

### La regla práctica

```
chunk_size × chunks_en_buffer < límite_buffer_SCTP (~16MB)
```

Para 8GB a 2 MB/s:
- 16KB chunks → buffer se llena en ~3MB → seguro ✅
- 256KB chunks → buffer se llena en ~12MB → al límite, cae en redes lentas ❌

---

## Cómo funciona el backpressure

El backpressure es el mecanismo que evita saturar el buffer.

```javascript
// Antes de enviar cada chunk:
if (dataChannel.bufferedAmount > pauseAt) {
    // Esperar hasta que el buffer baje
    await waitUntil(dataChannel.bufferedAmount < resumeAt);
}
dataChannel.send(chunk);
```

### Parámetros actuales por tamaño de archivo

```
< 500MB:   pause=6MB,  resume=2MB  → agresivo, máxima velocidad
>= 500MB:  pause=3MB,  resume=1MB  → conservador, máxima estabilidad
```

### Por qué resume=1MB y no resume=512KB

Con resume=512KB el ciclo era:
1. Buffer llega a 3MB → pausa
2. Espera hasta bajar a 512KB → reanuda
3. Sube rápido a 3MB → pausa de nuevo

Esto generaba muchas pausas cortas, CPU alta y throughput reducido.

Con resume=1MB:
1. Buffer llega a 3MB → pausa
2. Espera hasta bajar a 1MB → reanuda
3. Ciclo más largo, menos interrupciones, flujo más continuo

---

## Chunk size adaptativo por tamaño de archivo

### Versión anterior (backup)
```javascript
// Chunk fijo para todos los archivos
chunkSize: 32768  // 32KB siempre
```

### Versión actual
```javascript
_safeChunkSize(fileSize, networkChunk) {
    if (fileSize >= 4GB) return 16KB;   // Máxima estabilidad
    if (fileSize >= 1GB) return 32KB;   // Estable
    if (fileSize >= 500MB) return 64KB; // Balance
    return networkChunk;                // Hasta 256KB para archivos pequeños
}
```

### Por qué esta escala

| Tamaño | Chunk | Razón |
|--------|-------|-------|
| >4GB | 16KB | Transferencias de 1-2 horas necesitan máxima estabilidad. Un drop a los 90 min es inaceptable. |
| 1-4GB | 32KB | Balance entre velocidad y estabilidad para transferencias de 20-60 min. |
| 500MB-1GB | 64KB | Transferencias de 5-20 min, red generalmente estable. |
| <500MB | Hasta 256KB | Transferencias cortas, el buffer no tiene tiempo de saturarse. |

---

## Detección de red

```javascript
detectNetworkCondition() {
    // Network Information API (Chrome/Android)
    if (navigator.connection) {
        if (effectiveType === '2g') return { streams: 1, chunkSize: 16KB };
        if (effectiveType === '3g') return { streams: 2, chunkSize: 64KB };
    }
    if (isMobile) return { streams: 2, chunkSize: 64KB };
    return { streams: 4, chunkSize: 256KB }; // Desktop buena red
}
```

### Por qué limitar streams en móvil

4 DataChannels paralelos en móvil con 3G:
- Cada canal compite por el mismo ancho de banda limitado
- El scheduler de SCTP tiene que manejar 4 colas → más overhead
- Resultado: más lento que 1 canal bien optimizado

2 canales en móvil es el balance correcto.

---

## Estrategias de guardado y por qué

### 1. File System Access API (Chrome/Edge)

```javascript
const fileHandle = await window.showSaveFilePicker();
const writer = await fileHandle.createWritable();
// Por cada chunk:
await writer.write(arrayBuffer);
// Al final:
await writer.close();
```

**Por qué es la mejor opción:**
- El OS maneja el buffer de escritura a disco
- RAM del navegador = ~0 bytes durante toda la transferencia
- No hay ensamblado final — el archivo ya está en disco
- Funciona con archivos de cualquier tamaño

**Limitación:** Brave lo bloquea por privacidad (requiere flag manual).

### 2. IndexedDB + Service Worker

Para navegadores sin File System API con archivos grandes:

```
Chunks → IndexedDB (disco) → Service Worker → ReadableStream → Descarga
```

**Por qué IndexedDB y no memoria:**
- IndexedDB escribe en disco, no en RAM
- Un archivo de 5GB en memoria = crash del navegador
- IndexedDB puede manejar decenas de GB

**El Service Worker actúa como proxy:**
Lee los chunks de IndexedDB y los pipea como un stream de descarga,
evitando cargar todo en memoria.

### 3. Memoria (fallback final)

```javascript
transfer.chunksArray[index] = arrayBuffer;
// Al final:
const blob = new Blob(transfer.chunksArray);
```

**Cuándo se usa:** Safari, iOS, archivos pequeños sin las APIs anteriores.

**Limitación real:** Safari en iOS tiene ~2GB de heap disponible para JS.
Un archivo de 3GB = OOM crash. Por eso el límite práctico es ~2GB en Safari.

**Optimización aplicada:** `requestIdleCallback` para el ensamblado final,
evita bloquear el hilo principal durante la creación del Blob.

---

## ETA — Por qué era inestable antes

### Problema original

El ETA se calculaba desde el primer chunk recibido:
```javascript
const speed = bytesReceived / (now - startTime);
const eta = bytesRemaining / speed;
```

Al inicio, la velocidad medida era muy baja (conexión estableciéndose),
lo que generaba ETAs de "358m 22s" que luego bajaban rápido a "45m".

### Solución actual

```javascript
// Ventana deslizante de 10 muestras
transfer.speedHistory.push(instantSpeed);
if (transfer.speedHistory.length > 10) transfer.speedHistory.shift();
const avgSpeed = average(transfer.speedHistory);

// Solo mostrar ETA cuando hay al menos 5 muestras estables
if (transfer.speedHistory.length >= 5) {
    timeText = formatTimeRemaining(bytesRemaining / avgSpeed);
} else {
    timeText = 'Calculando...';
}
```

La ventana deslizante suaviza los picos de velocidad.
Las 5 muestras mínimas evitan ETAs disparatados al inicio.

### Formato mejorado

```
Antes: "358m 22s restante"  → confuso, feo
Ahora: "~5h 58m"            → claro, limpio

Antes: "80m 30s restante"   → confuso
Ahora: "~1h 20m"            → claro

Antes: "45m 10s restante"   → aceptable
Ahora: "~45m 10s"           → igual pero con ~
```

El `~` indica que es una estimación, no un valor exacto.

---

## Resumen de mejoras v1 → v2

| Aspecto | v1 (backup) | v2 (actual) | Impacto |
|---------|-------------|-------------|---------|
| Chunk size | 32KB fijo | Adaptativo 16-256KB | Estabilidad en archivos grandes |
| Archivos >4GB | Inestable con 256KB | 16KB → estable | Crítico |
| Backpressure resume | 512KB | 1MB | Menos interrupciones |
| Detección de red | No existía | Network API + UA | Optimización automática |
| ETA | Inestable al inicio | Ventana deslizante 10 muestras | UX mejorada |
| Formato tiempo | "358m 22s restante" | "~5h 58m" | UX mejorada |
| DataChannel ordered | Implícito | Explícito `true` | Claridad, sin riesgo corrupción |

---

## Lo que NO se cambió (y por qué)

- **Ensamblado final** — ya era óptimo con las 3 estrategias
- **Signaling WebRTC** — funcionaba perfectamente
- **Handshake SDP/ICE** — sin problemas conocidos
- **UI de progreso** — solo se mejoró el formato del tiempo
- **Service Worker** — ya manejaba IndexedDB correctamente

---

## Restaurar versión anterior

Si algo falla, el backup está intacto:

```bash
cp docs/p2p-file-transfer-backup.js resources/js/components/p2p-file-transfer.js
npm run build
```
