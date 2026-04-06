# ⚡ QUICK REFERENCE - Axios en Elysium P2P

## Servicios Disponibles Globalmente

```javascript
window.axiosService              // Cliente HTTP principal
window.chunkedTransferManager    // Gestor de bloques
window.transferErrorHandler      // Manejo de errores
window.progressVisualizer        // Barra de progreso
```

---

## 🚀 Operaciones Básicas

### GET
```javascript
const data = await window.axiosService.get('/api/endpoint');
console.log(data.data); // Respuesta
```

### POST
```javascript
const response = await window.axiosService.post('/api/endpoint', {
    key: 'value'
});
console.log(response.data);
```

### PUT / PATCH / DELETE
```javascript
await window.axiosService.put('/api/endpoint', { data });
await window.axiosService.patch('/api/endpoint', { data });
await window.axiosService.delete('/api/endpoint');
```

---

## 🔄 Reintentos y Recuperación

### Automático (se hace interno)
```javascript
// Axios reintentar automáticamente 3 veces
// Backoff: 1s → 2s → 4s
const response = await window.axiosService.post('/api/signal', data);
```

### Manual con Control
```javascript
const errorHandler = window.transferErrorHandler;
try {
    const result = await errorHandler.retryWithBackoff(
        async () => {
            return await window.axiosService.post('/api/endpoint', data);
        },
        1  // maxRetries
        500 // initialDelayMs
    );
} catch (error) {
    console.error('Falló definitivamente:', error);
}
```

---

## 📦 Transferencia de Archivos

### Crear sesión
```javascript
const transfer = window.chunkedTransferManager.createTransfer(
    'transfer_id',
    file,
    {
        onProgress: (p) => console.log(`${p.progress}%`),
        onError: (e) => console.error(e.message),
        onComplete: (s) => console.log('Listo:', s.fileName)
    }
);
```

### Registrar progreso
```javascript
window.chunkedTransferManager.registerChunkSent(transferId, chunkIndex);
window.chunkedTransferManager.updateNetworkStats(transferId, bytes, ms);
window.chunkedTransferManager.registerChunkError(transferId, index, error);
```

### Verificar estado
```javascript
const transfer = window.chunkedTransferManager.getTransfer(transferId);
console.log(transfer.progress);      // 0-100
console.log(transfer.networkSpeed);  // bytes/s
console.log(transfer.buffer);        // bytes en vuelo
```

### Completar
```javascript
window.chunkedTransferManager.completeTransfer(transferId);
window.chunkedTransferManager.cancelTransfer(transferId);
```

---

## 📊 Progreso Visual

### Crear barra
```javascript
const bar = window.progressVisualizer.createProgressBar(
    transferId,
    'archivo.pdf',
    fileSizeBytes
);
```

### Actualizar
```javascript
window.progressVisualizer.updateProgress(
    transferId,
    75,        // percent
    '2.5MB/s', // speed
    '30s'      // timeRemaining
);
```

### Finalizar
```javascript
window.progressVisualizer.completeProgress(transferId);
window.progressVisualizer.errorProgress(transferId, 'Conexión perdida');
```

---

## ❌ Manejo de Errores

### Capturar y clasificar
```javascript
try {
    await window.axiosService.post('/api/endpoint', data);
} catch (error) {
    const errorInfo = window.transferErrorHandler.handleError(error, {
        type: 'tipo de operación',
        context: 'contexto adicional'
    });
    console.log(errorInfo);
}
```

### Validar chunks
```javascript
const validation = window.transferErrorHandler.validateChunk(chunkData);
if (!validation.valid) {
    console.error('Chunk inválido:', validation.error);
}
```

### Ver log
```javascript
const log = window.transferErrorHandler.getErrorLog();
log.forEach(error => {
    console.log(`[${error.timestamp}] ${error.message}`);
});

// Limpiar
window.transferErrorHandler.clearErrorLog();
```

---

## 📈 Monitoreo

### Estadísticas Axios
```javascript
const stats = window.axiosService.getStats();
console.log(stats);
// { queues: 2, activeRequests: 1, configuredRoutes: 5 }
```

### Estadísticas de Transferencias
```javascript
const stats = window.chunkedTransferManager.getStats();
console.log(stats);
// { activeTransfers: 1, totalBytesTransferred: 5MB, peakSpeed: "25MB/s", ... }
```

### Ver TODO de una vez
```javascript
window.displaySystemStats();
```

###Exportar diagnósticos
```javascript
window.exportDiagnostics();
// Descarga JSON con estado completo del sistema
```

---

## ⚙️ Configuración

### Rate Limiting
Editar: `resources/js/services/axios-service.js`
```javascript
const routeConfig = {
    '/api/ruta': { 
        maxRequests: 10,    // solicitudes
        windowMs: 1000,     // cada N ms
        concurrent: 2       // máximo simultáneas
    }
};
```

### Chunks
Editar: `resources/js/services/chunked-transfer.js`
```javascript
this.minChunkSize = 16 * 1024;      // 16 KB mínimo
this.maxChunkSize = 512 * 1024;     // 512 KB máximo
this.baseChunkSize = 64 * 1024;     // 64 KB base
this.bufferLimit = 8 * 1024 * 1024; // 8 MB buffer
```

### Reintentos
```javascript
this.maxRetries = 3;              // intentos máximos
this.backoffMultiplier = 2;       // x2 cada vez
this.initialBackoffMs = 500;      // 500ms inicial
```

---

## 🐛 Debugging

### Verificar inicialización
```javascript
// En consola (F12 → Console):
window.axiosService          // ✓ debe existir
window.showToast             // ✓ debe existir
navigator.onLine             // ✓ debe ser true
```

### Ver último error
```javascript
const errors = window.transferErrorHandler.getErrorLog();
console.log(errors[errors.length - 1]);
```

### Simular error
```javascript
// Simular respuesta error:
window.axiosService.post('/api/invalid', {}).catch(e => {
    console.log('Error capturado:', e.message);
});
```

### Limitar velocidad (dev)
```javascript
// Simular red lenta:
// Chrome DevTools → Network → Throttling → Slow 3G
```

---

## 💡 Patrones Comunes

### Transferencia Completa
```javascript
// Paso 1: Crear sesión
const transfer = window.chunkedTransferManager.createTransfer(
    'id_' + Date.now(),
    file,
    { onProgress: updateUI, onError: showError, onComplete: finish }
);

// Paso 2: Mostrar progreso
const bar = window.progressVisualizer.createProgressBar(
    transfer.id, file.name, file.size
);

// Paso 3: Capturar bloques (loop)
while (offset < file.size) {
    const chunk = file.slice(offset, offset + chunkSize);
    
    // Enviar con reintentos automáticos
    try {
        await window.axiosService.uploadChunk(
            `/api/chunk/${transfer.id}/${index}`,
            chunk, index, total
        );
        window.chunkedTransferManager.registerChunkSent(transfer.id, index);
    } catch (e) {
        window.chunkedTransferManager.registerChunkError(transfer.id, index, e);
    }
    
    offset += chunkSize;
}

// Paso 4: Completar
window.chunkedTransferManager.completeTransfer(transfer.id);
```

### Contacto - Aceptar/Rechazar
```javascript
// Aceptar
try {
    const response = await window.axiosService.post(
        '/api/contacts/request/respond',
        { request_id: id, action: 'accept' }
    );
    window.showToast('✅ Aceptado', response.data.message, 'success');
} catch (error) {
    window.transferErrorHandler.handleError(error, 
        { type: 'aceptar contacto' }
    );
}

// Rechazar (igual pero action: 'deny')
```

---

## 🚨 Errores Comunes

| Error | Solución |
|-------|----------|
| `window.axiosService is undefined` | Recargar página, verificar app.js |
| `Cannot read property 'post'` | window.axiosService no están cargados |
| Reintentos infinitos | Verificar que no es error permanente (401, 404) |
| Barras no aparecen | Div `p2p-transfers-container` no existe en HTML |
| Toast no visible | Función `window.showToast` no disponible |
| Rate limit constante | Aumentar maxRequests en routeConfig |

---

## ✅ Checklist Implementación

- [x] Axios instalado
- [x] Servicios cargados en app.js
- [x] fetch() reemplazados
- [x] Reintentos funcionando
- [x] Rate limiting activo
- [x] Feedback visual listo
- [x] Errores manejados
- [x] Documentación completa

---

## 📚 Documentación Completa

- `AXIOS_README_ES.md` - Resumen ejecutivo
- `AXIOS_INTEGRATION_GUIDE_ES.md` - Guía técnica detallada
- `AXIOS_EXAMPLES_ES.js` - Ejemplos prácticos
- `AXIOS_CHANGELOG.md` - Cambios realizados

---

**Última actualización:** Abril 2026  
**Versión:** 2.0.0  
**Para dudas:** Revisar documentación completa en `/docs`
