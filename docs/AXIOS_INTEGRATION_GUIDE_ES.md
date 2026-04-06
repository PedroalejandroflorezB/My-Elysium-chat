# INTEGRACIÓN DE AXIOS - GUÍA COMPLETA

## 📋 Descripción General

Se han integrado mejoras significativas en el sistema de transferencias de archivos P2P mediante la adopción de **Axios** como cliente HTTP centralizado, reemplazando las llamadas `fetch()` dispersas. El sistema incluye:

### ✨ Características Principales

1. **Axios Service Centralizado** (`resources/js/services/axios-service.js`)
   - Configuración global con CSRF token automático
   - Reintentos automáticos con backoff exponencial (1s, 2s, 4s)
   - Interceptores para manejo de errores
   - Rate limiting por ruta para evitar saturación

2. **Gestor de Transferencias en Bloques** (`resources/js/services/chunked-transfer.js`)
   - Gestión inteligente de tamaño de chunks (16KB - 512KB)
   - Adaptación automática basada en velocidad de red
   - Estadísticas en tiempo real (velocidad, tiempo estimado)
   - Reintentos por chunk individual

3. **Manejo de Errores Mejorado** (`resources/js/services/transfer-errors.js`)
   - Clasificación inteligente de errores HTTP
   - Feedback visual contextual para cada tipo de error
   - Validación de integridad de chunks
   - Visualizador de progreso con animaciones

4. **Rate Limiting Inteligente**
   - `/api/p2p/signal`: 10 req/s, máx 2 concurrentes
   - `/api/p2p/offer|answer`: 5 req/2s, máx 1 concurrente
   - `/api/p2p/ice-candidate`: 20 req/s, máx 3 concurrentes
   - `/api/contacts/request/respond`: 5 req/s, máx 1 concurrente

---

## 🔧 Instalación y Configuración

### Paso 1: Verificar Axios

```bash
npm list axios
# Si no está instalado:
npm install axios
```

### Paso 2: Importar Servicios (Ya hecho en app.js)

```javascript
// app.js - Ya incluido
import axiosService from './services/axios-service.js';
import { chunkedTransferManager } from './services/chunked-transfer.js';
import { transferErrorHandler, progressVisualizer } from './services/transfer-errors.js';
```

### Paso 3: Usar en Componentes

```javascript
// Ejemplo: POST simple
const response = await window.axiosService.post('/api/endpoint', {
    data: 'value'
});

// Ejemplo: Con manejo de errores
try {
    const response = await window.axiosService.post('/api/endpoint', data);
    console.log('Éxito:', response.data);
} catch (error) {
    window.transferErrorHandler.handleError(error, {
        type: 'transferencia',
        context: 'nombre-del-endpoint'
    });
}
```

---

## 📊 Uso del Gestor de Transferencias

### Crear una Transferencia

```javascript
const transfer = window.chunkedTransferManager.createTransfer(
    'unique-transfer-id',
    fileObject,
    {
        onProgress: (progress) => {
            console.log(`${progress.progress}% - ${progress.speed}`);
        },
        onError: (error) => {
            console.error('Error:', error.message);
        },
        onComplete: (stats) => {
            console.log('Completado:', stats.fileName, stats.duration + 's');
        }
    }
);
```

### Registrar Progreso

```javascript
// Cuando se envía un chunk exitosamente
window.chunkedTransferManager.registerChunkSent(transferId, chunkIndex);

// Actualizar estadísticas de velocidad
window.chunkedTransferManager.updateNetworkStats(
    transferId,
    bytesTransferred,
    elapsedMilliseconds
);

// Manejar errores de chunk
window.chunkedTransferManager.registerChunkError(
    transferId,
    chunkIndex,
    error
);
```

### Verificar Pausa/Reanudación

```javascript
if (window.chunkedTransferManager.shouldPause(transferId)) {
    // Pausar envío de chunks
    console.log('Buffer lleno, pausando...');
}

if (window.chunkedTransferManager.shouldResume(transferId)) {
    // Reanudar envío de chunks
    console.log('Buffer disponible, reanudando...');
}
```

### Completar Transferencia

```javascript
window.chunkedTransferManager.completeTransfer(transferId);
// O cancelar
window.chunkedTransferManager.cancelTransfer(transferId);
```

---

## 🎨 Feedback Visual

### Barra de Progreso

```javascript
// Crear barra de progreso visual
window.progressVisualizer.createProgressBar(
    transferId,
    'archivo.pdf',
    fileSize
);

// Actualizar progreso
window.progressVisualizer.updateProgress(
    transferId,
    percentComplete,
    speedString,  // "2.5 MB/s"
    timeRemaining // "30s"
);

// Marcar como completado o error
window.progressVisualizer.completeProgress(transferId);
window.progressVisualizer.errorProgress(transferId, 'Conexión perdida');
```

### Toast Notifications

```javascript
// El sistema automáticamente muestra toasts para:
// - Errores de conexión
// - Token CSRF expirado
// - Validación fallida
// - Rate limit alcanzado

// Manual (si es necesario):
window.showToast('Título', 'Mensaje', 'success|error|warning|info');
```

---

## 🔄 Reintentos y Recuperación

### Reintentos Automáticos

El sistema Axios reintentan automáticamente con backoff exponencial:

```
Intento 1: Inmediato
Intento 2: Espera 1 segundo
Intento 3: Espera 2 segundos
Intento 4: Falla definitiva
```

### Reintentos por Chunk

Cada chunk se reintenta hasta 3 veces:

```javascript
// El sistema obtiene automáticamente el delay:
const delay = chunkedTransferManager.getRetryDelay(chunkIndex);
// Reintenta después de: 500ms, 1s, 2s
```

### Manejo Manual de Reintentos

```javascript
const errorHandler = window.transferErrorHandler;

await errorHandler.retryWithBackoff(
    async () => {
        return await window.axiosService.post('/endpoint', data);
    },
    maxRetries = 3,
    initialDelay = 500
);
```

---

## 🚦 Optimizaciones Implementadas

### 1. Rate Limiting por Ruta

**Problema:** Saturación de rutas al enviar muchas solicitudes P2P
**Solución:** Límites configurables por tipo de solicitud

```javascript
// Archivo: axios-service.js
const routeConfig = {
    '/api/p2p/signal': { 
        maxRequests: 10,    // 10 solicitudes
        windowMs: 1000,     // por segundo
        concurrent: 2       // máximo 2 simultáneas
    },
    // ... más rutas
};
```

### 2. Tamaño Adaptativo de Chunks

**Problema:** Tamaño fijo (64KB) no óptimo para todas las redes
**Solución:** Adaptación automática basada en velocidad

```javascript
// Rango: 16KB (móvil 2G) hasta 512KB (gigabit)
transfer.currentChunkSize = calculateOptimalChunkSize(networkSpeed);

// Mínimo cambio del 20% para evitar adaptación constante
if (Math.abs(newSize - currentSize) / currentSize > 0.2) {
    transfer.currentChunkSize = newSize;
}
```

### 3. Backpressure Inteligente

**Problema:** Acumulación de datos en buffer causando memoria excesiva
**Solución:** Pausa automática al 80%, reanuda al 50%

```javascript
if (transfer.buffer >= 6.4 MB) {
    // Pausar envío (buffer = 8MB * 0.8)
}

if (transfer.buffer <= 4 MB) {
    // Reanudar envío (buffer = 8MB * 0.5)
}
```

### 4. Deduplicación de Señales

**Problema:** Echo (WebSocket) + Polling (HTTP) pueden enviar duplicados
**Solución:** Registro de señales procesadas

```javascript
// En p2p-file-transfer.js
if (this.processedSignals.has(signalId)) {
    return; // Ya procesada
}
this.processedSignals.add(signalId);
```

---

## 📈 Estadísticas y Monitoreo

### Obtener Estadísticas

```javascript
// Servicio Axios
const axiosStats = window.axiosService.getStats();
console.log(axiosStats);
// {
//   queues: 3,
//   activeRequests: 2,
//   configuredRoutes: 5
// }

// Gestor de transferencias
const transferStats = window.chunkedTransferManager.getStats();
console.log(transferStats);
// {
//   activeTransfers: 2,
//   totalBytesTransferred: 5242880,
//   peakSpeed: "25.5 MB/s",
//   averageSpeed: "18.3 MB/s"
// }

// Log de errores
const errorLog = window.transferErrorHandler.getErrorLog();
```

### Resetear Estadísticas

```javascript
window.axiosService.resetRateLimits();
window.chunkedTransferManager.resetStats();
window.transferErrorHandler.clearErrorLog();
```

---

## ⚠️ Manejo de Errores Común

### Error: Token CSRF Expirado (419)

```
❌ Sesión Expirada
Por favor, recarga la página y vuelve a intentar.

Acción: El usuario debe recargar la página.
```

### Error: Rate Limit (429)

```
⏸️ Sistema Ocupado
Espera antes de reintentar.

Acción: El sistema automáticamente reintentar después de X segundos.
```

### Error: Validación (422)

```
⚠️ Datos Inválidos
[Detalles específicos del campo]

Acción: Revisar y corregir los datos enviados.
```

### Error: Servidor (5xx)

```
❌ Error de Servidor
Reintentando automáticamente...

Acción: Reintentos con backoff exponencial, máximo 3 intentos.
```

---

## 🔗 Archivos Modificados

1. **resources/js/app.js**
   - Añadidos imports de servicios
   - Exponibles como globales

2. **resources/js/p2p/connection.js**
   - Reemplazado `fetch()` con `axiosService.post()`
   - Improved error handling

3. **resources/js/components/p2p-file-transfer.js**
   - `iniciarPollingSignaling()`: Usa `axiosService.get()`
   - `sendSignalingMessage()`: Usa `axiosService.post()`
   - Error management mejorado

4. **resources/js/components/contact-modal.js**
   - `acceptPendingRequest()`: Usa `axiosService.post()`
   - `denyPendingRequest()`: Usa `axiosService.post()`
   - Async/await pattern

---

## 🚀 Próximas Mejoras (Opcionales)

1. **Compresión de Chunks**
   ```javascript
   // Comprimir chunks antes de enviar
   const compressed = await compress(chunk);
   ```

2. **Validación por Hash**
   ```javascript
   // Validar integridad con SHA-256
   const hash = await sha256(chunk);
   ```

3. **Caché de Transferencias Fallidas**
   ```javascript
   // Guardar en IndexedDB para reanudar
   ```

4. **Monitoreo de Ancho de Banda**
   ```javascript
   // Limitador de velocidad configurable
   ```

---

## ✅ Checklist de Verificación

- [ ] Axios está instalado: `npm list axios`
- [ ] Los archivos de servicio están en `resources/js/services/`
- [ ] Importes en `app.js` están correctos
- [ ] Las peticiones fetch() han sido reemplazadas
- [ ] El rate limiting está configurado
- [ ] El feedback visual aparece en las transferencias
- [ ] Los reintentos funcionan automáticamente
- [ ] Los errores CSRF se manejan correctamente

---

## 📞 Soporte

Para preguntas o problemas:

1. Revisar console de navegador: `F12` → Console
2. Exportar log de errores: `window.transferErrorHandler.exportErrorLog()`
3. Verificar estadísticas: `window.axiosService.getStats()`
4. Verificar conexión: `navigator.onLine`

