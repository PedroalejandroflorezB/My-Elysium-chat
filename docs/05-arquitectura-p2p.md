# 🔬 Arquitectura de Transferencia P2P Puro

Este documento describe en detalle el núcleo técnico del sistema de transferencia de archivos de **Elysium P2P**: por qué es "Cero Almacenamiento", qué problemas se resolvieron durante su estabilización, y qué parámetros tocar si se necesitan ajustes en el futuro.

---

## 🌳 Árbol: Componentes del Módulo P2P

```text
📦 Elysium P2P (Módulo de Transferencia Directa)
 ┣ 📂 app
 ┃ ┣ 📂 Events
 ┃ ┃ ┗ 📜 P2PSignaling.php         — Clase Broadcasting: emite señales WebRTC al canal del usuario receptor
 ┃ ┗ 📂 Http/Controllers/P2P
 ┃   ┗ 📜 SignalingController.php  — Controlador auxiliar (sin uso activo de almacenamiento)
 ┣ 📂 config
 ┃ ┗ 📜 reverb.php                 — Configura max_payload_size para señales largas (SDP/ICE)
 ┣ 📂 resources/js/components
 ┃ ┗ 📜 p2p-file-transfer.js       — NÚCLEO: Clase P2PFileTransfer (WebRTC, DataChannels, ICE Queue)
 ┗ 📂 routes
   ┗ 📜 web.php                    — Ruta `POST /api/p2p/signal` (puente de señalización sin storage)
```

---

## 🏗️ Principio de Cero Almacenamiento

El servidor actúa **solo como intermediario de descubrimiento** (Handshake). La ruta de señalización en `routes/web.php` es la siguiente:

```php
// No guarda nada. Solo reenvía el mensaje por WebSocket y lo descarta.
broadcast(new \App\Events\P2PSignaling($from, $to, $type, $data))->toOthers();
return response()->json(['success' => true]);
```

Una vez que el par `(RTCPeerConnection)` entre los dos navegadores está establecido, **el servidor ya no participa en la transferencia**. Los archivos fluyen directamente de CPU-a-CPU entre navegadores.

| Fase | Pasa por Servidor | Almacena en Servidor |
| :--- | :--- | :--- |
| Negociación (offer/answer/ICE) | ✅ Sí (solo rebota) | ❌ No |
| Chunks del Archivo | ❌ No (P2P Directo) | ❌ No |
| Notificación de Completado | ✅ Sí (~200 bytes) | ❌ No |

---

---

## ⚡ Problemas Resueltos (Bugs y Optimizaciones Pro)

### 1. Soporte para Archivos Masivos (Gigas) — Novedad
**Problema:** Los navegadores crasheaban al intentar ensamblar archivos de varios GB en la RAM (usando `Blob`).
**Solución — Stream-to-Disk con Semáforo (Gating):**
- Al aceptar la transferencia, el receptor selecciona el destino mediante la **File System Access API**.
- **Semáforo de Seguridad:** El emisor conecta con el receptor (Handshake) pero NO envía datos hasta que el receptor emite la señal `ready_to_receive`. Esto garantiza que el disco duro esté listo **antes** de disparar los chunks.
- Esto elimina la necesidad de buffers temporales en RAM, garantizando un consumo de memoria constante (~100MB) sin importar el tamaño del archivo.

### 2. Control de Flujo (Backpressure)
**Problema:** El emisor saturaba el canal de datos si enviaba información más rápido de lo que la red podía procesar, provocando cierres de conexión.
**Solución:** Se implementó una guarda reactiva en el emisor que monitorea `dataChannel.bufferedAmount`. Si supera **1MB**, el envío se pausa hasta que el buffer se vacíe por debajo de **512KB**.

### 3. Sincronización de Candidatos ICE
**Causa:** Condición de carrera donde los candidatos llegaban antes que la descripción remota.
**Fix:** Cola de candidatos pendientes que se vacía automáticamente tras `setRemoteDescription()`.

### 4. Estabilidad de Handshake (Timeouts)
**Problema:** El diálogo de guardado del navegador bloqueaba el proceso, causando que la conexión WebRTC expirara antes de empezar el envío.
**Solución:** Se aumentó el timeout de aceptación inicial a **120 segundos** y se implementó la **limpieza atómica del timeout** en cuanto llega la señal `accepted` (Fase 1).

---

## ⚙️ Parámetros de Ajuste Profesional

| Parámetro | Valor Actual | Razón Técnica |
| :--- | :--- | :--- |
| `this.chunkSize` | `32768` (32KB) | Balance ideal entre overhead de paquetes y estabilidad en SCTP. |
| `Threshold Buffer` | `1MB` | Evita la saturación del canal y mantiene la UI fluida. |
| `Sync Interval` | `30ms` | Tiempo de espera entre re-checks de buffer. |

---

## 🌐 Compatibilidad y Fallback
El sistema detecta automáticamente las capacidades del navegador:
- **Chromium (Chrome, Edge, Opera):** Usa **Stream-to-Disk** (Alto rendimiento).
- **Legacy (Firefox, Safari antiguos):** Usa **RAM-Fallback** (Limitado por la memoria del dispositivo). Se muestra una advertencia si el archivo supera los 200MB en estos navegadores.

---

## 🛠️ Variables de Entorno Requeridas (`.env`)

```env
# Reverb WebSocket - Señalización de Descubrimiento
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_SCHEME=http

# Límite de payload para SDPs largos
REVERB_MAX_PAYLOAD_SIZE=1048576
```

> [!IMPORTANT]
> **Estado de Estabilidad Actual (Snapshot):** El sistema ha sido validado con éxito para transferencias de **1.6GB** utilizando el protocolo de **Semáforo (Gating)** y **Stream-to-Disk**. El uso de RAM se mantiene constante (~100MB) y los timeouts de 120s garantizan la conexión durante el proceso de guardado.

---

## 🚀 Próxima Fase: Motor Adaptativo de Velocidad
Estamos evolucionando hacia un sistema que ajusta automáticamente los parámetros (`chunkSize`, `bufferedAmount`) según el peso del archivo para maximizar el rendimiento sin colapsar.
