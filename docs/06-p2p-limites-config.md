# Sistema P2P - Documentación Técnica
**Elysium P2P · Versión actual · Abril 2026**

---

## Arquitectura

El archivo NUNCA toca el servidor. El servidor (t3.micro) solo maneja señalización (~50KB por transferencia).

```
Emisor → [Reverb/WebSocket ~50KB] → Servidor → Receptor
Emisor ══════════════[ WebRTC P2P directo ]══════════════ Receptor
```

---

## Configuración de Chunks (Adaptativa)

| Tamaño de archivo | Chunk size | Buffer | Pause | Resume |
|-------------------|-----------|--------|-------|--------|
| < 10 MB           | Red-dependiente | 4 MB | 4 MB | 1 MB |
| 10 MB – 500 MB    | Red-dependiente | 8 MB | 6 MB | 2 MB |
| 500 MB – 1 GB     | 64 KB | 4 MB | 3 MB | 1 MB |
| 1 GB – 4 GB       | 32 KB | 4 MB | 3 MB | 1 MB |
| > 4 GB            | 16 KB | 4 MB | 3 MB | 1 MB |

### Chunk size por red (archivos < 500MB)

| Red | Chunk | Streams |
|-----|-------|---------|
| Desktop / WiFi bueno | 256 KB | 4 |
| 3G | 64 KB | 2 |
| 2G / saveData | 16 KB | 1 |
| Móvil sin Network API | 64 KB | 2 |

---

## Estrategias de Guardado (Receptor)

| Estrategia | Cuándo activa | RAM usada | Límite práctico |
|------------|--------------|-----------|-----------------|
| **File System API** | Chrome/Edge desktop y Android Chromium | ~0 MB | Sin límite (disco) |
| **IndexedDB + SW** | Brave desktop, Firefox, archivos >500MB sin File System API | ~0 MB | Sin límite (disco) |
| **Memoria (fallback)** | Safari, iOS, navegadores sin las anteriores | = tamaño del archivo | ~2 GB |

---

## Compatibilidad por Navegador

| Navegador | File System API | Estrategia | Límite real |
|-----------|----------------|------------|-------------|
| Chrome desktop | ✅ | Disco directo | Sin límite |
| Edge desktop | ✅ | Disco directo | Sin límite |
| Chrome Android | ✅ | Disco directo | Sin límite |
| Firefox desktop | ❌ | IndexedDB + SW | Sin límite |
| Brave desktop | ⚠️ Solo con flag | IndexedDB + SW | Sin límite |
| Brave Android | ❌ | IndexedDB + SW | Sin límite |
| Safari macOS/iOS | ❌ | Memoria | ~2 GB |
| Samsung Internet | ❌ | Memoria | ~2 GB |

---

## Velocidades Reales Esperadas

> La velocidad depende del cuello de botella entre emisor y receptor.

| Conexión | Velocidad estimada | 8 GB en |
|----------|--------------------|---------|
| LAN Ethernet | 50–100 MB/s | 1–3 min |
| WiFi 5GHz | 20–50 MB/s | 3–7 min |
| WiFi 2.4GHz | 5–15 MB/s | 10–25 min |
| Internet (upload típico) | 1–5 MB/s | 25–130 min |

---

## Impacto en Servidor (t3.micro)

| Recurso | Durante transferencia | Durante señalización |
|---------|-----------------------|----------------------|
| CPU | ~0% | ~5% por 5 segundos |
| RAM | ~5 MB | ~5 MB |
| Ancho de banda | ~0 KB | ~50 KB por transferencia |

**El archivo nunca pasa por el servidor.**

---

## Límites del Sistema

| Límite | Valor | Motivo |
|--------|-------|--------|
| Conexiones simultáneas Reverb | 50 | RAM del t3.micro (1GB) |
| Transferencias P2P simultáneas | 8–10 | CPU credits del t3.micro |
| Tamaño máximo (Chrome/Edge) | Sin límite | File System API |
| Tamaño máximo (Firefox/Brave) | Sin límite | IndexedDB |
| Tamaño máximo (Safari/iOS) | ~2 GB | Límite de RAM del navegador |
| Payload señalización Reverb | 1 MB | `REVERB_MAX_PAYLOAD_SIZE` |

---

## Archivos del Sistema

| Archivo | Descripción |
|---------|-------------|
| `resources/js/components/p2p-file-transfer.js` | Clase principal P2P |
| `resources/js/components/p2p-file-saver.js` | Estrategias de guardado |
| `resources/js/p2p/connection.js` | Manejo WebRTC |
| `public/sw.js` | Service Worker (IndexedDB → descarga) |
| `app/Events/P2PSignaling.php` | Evento de señalización |
| `routes/web.php` → `/api/p2p/signal` | Endpoint de señalización |

---

## Backup

El código original estable está en:
- `docs/p2p-file-transfer-backup.js`
- `docs/p2p-file-transfer-backup.md`

Para restaurar:
```bash
cp docs/p2p-file-transfer-backup.js resources/js/components/p2p-file-transfer.js
npm run build
```
