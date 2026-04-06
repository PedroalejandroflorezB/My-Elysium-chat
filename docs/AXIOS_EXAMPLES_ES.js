/**
 * ================================================
 * EJEMPLO DE INTEGRACION COMPLETA
 * Cómo usar todos los servicios en una transferencia
 * ================================================
 */

// Ejemplo 1: Usar Axios para enviar señales P2P
async function sendP2PSignal(toUserId, signalType, data) {
    try {
        const response = await window.axiosService.post('/api/p2p/signal', {
            from: window.myPeerId,
            to_id: toUserId,
            type: signalType,
            data: data
        });

        console.log('✅ Señal enviada:', signalType);
        return response.data;
    } catch (error) {
        window.transferErrorHandler.handleError(error, {
            type: 'envío de señal',
            signalType: signalType,
            toUserId: toUserId
        });
        throw error;
    }
}

// Ejemplo 2: Transferencia con progreso visual completo
async function startFileTransferWithVisuals(recipientId, file) {
    const transferId = `transfer_${Date.now()}_${Math.random()}`;

    // Crear sesión de transferencia
    const transfer = window.chunkedTransferManager.createTransfer(
        transferId,
        file,
        {
            onProgress: (progress) => {
                window.progressVisualizer.updateProgress(
                    transferId,
                    progress.progress,
                    progress.speed,
                    progress.timeRemaining
                );
            },
            onError: (error) => {
                window.progressVisualizer.errorProgress(
                    transferId,
                    'Error: ' + error.message.substring(0, 20)
                );
            },
            onComplete: (stats) => {
                window.progressVisualizer.completeProgress(transferId);
                window.showToast(
                    '✅ Transferencia Completada',
                    `${stats.fileName} - ${Math.round(stats.averageSpeed)}B/s`,
                    'success'
                );
            }
        }
    );

    // Mostrar barra de progreso
    window.progressVisualizer.createProgressBar(
        transferId,
        file.name,
        file.size
    );

    // Enviar solicitud de transferencia
    try {
        await sendP2PSignal(recipientId, 'transfer.request', {
            transferId: transferId,
            fileName: file.name,
            fileSize: file.size
        });

        return transferId;
    } catch (error) {
        window.chunkedTransferManager.cancelTransfer(transferId);
        throw error;
    }
}

// Ejemplo 3: Enviar chunks con reintentos automáticos
async function sendFileChunk(transferId, chunkIndex, chunkData) {
    const transfer = window.chunkedTransferManager.getTransfer(transferId);
    if (!transfer) {
        throw new Error('Transferencia no encontrada');
    }

    // Validar chunk
    const validation = window.transferErrorHandler.validateChunk(chunkData);
    if (!validation.valid) {
        throw new Error(validation.error);
    }

    // Reintentos con backoff automático
    try {
        const response = await window.axiosService.uploadChunk(
            `/api/p2p/chunk/${transferId}/${chunkIndex}`,
            chunkData,
            chunkIndex,
            transfer.totalChunks,
            (progress) => {
                // Actualizar estadísticas
                window.chunkedTransferManager.updateNetworkStats(
                    transferId,
                    progress.loaded,
                    Date.now() - transfer.startTime
                );
            }
        );

        // Registrar éxito
        window.chunkedTransferManager.registerChunkSent(transferId, chunkIndex);
        return response.data;
    } catch (error) {
        // Registrar error
        window.chunkedTransferManager.registerChunkError(
            transferId,
            chunkIndex,
            error
        );

        // El sistema de Axios reintentar automáticamente
        throw error;
    }
}

// Ejemplo 4: Loop de envío con manejo de buffer
async function uploadFileInChunks(transferId, fileData) {
    const transfer = window.chunkedTransferManager.getTransfer(transferId);
    if (!transfer) throw new Error('Transferencia no encontrada');

    let offset = 0;
    let chunkIndex = 0;

    while (offset < fileData.byteLength) {
        // Verificar pausa por buffer lleno
        if (window.chunkedTransferManager.shouldPause(transferId)) {
            console.log('⏸️ Buffer lleno, pausando...');
            await new Promise(resolve => setTimeout(resolve, 1000));
            continue;
        }

        // Verificar reanudación
        if (window.chunkedTransferManager.shouldResume(transferId)) {
            console.log('▶️ Buffer disponible, reanudando...');
            transfer.isPaused = false;
        }

        // Calcular tamaño del chunk actual
        const chunkBytes = Math.min(
            transfer.currentChunkSize,
            fileData.byteLength - offset
        );

        const chunk = fileData.slice(offset, offset + chunkBytes);

        try {
            // Enviar chunk
            await sendFileChunk(transferId, chunkIndex, chunk);

            // Actualizar offset
            offset += chunkBytes;
            chunkIndex++;

            // Pequeña pausa entre chunks para evitar saturación
            await new Promise(resolve => setTimeout(resolve, 10));
        } catch (error) {
            // El sistema de reintentos se encargará
            console.error(`Chunk ${chunkIndex} falló, reintentando...`);
            await new Promise(resolve => setTimeout(resolve, 500));
        }
    }

    // Completar transferencia
    window.chunkedTransferManager.completeTransfer(transferId);
}

// Ejemplo 5: Aceptar solicitud de contacto con reintentos
async function acceptContactWithRetry(requestId) {
    try {
        const response = await window.transferErrorHandler.retryWithBackoff(
            async () => {
                return await window.axiosService.post('/api/contacts/request/respond', {
                    request_id: requestId,
                    action: 'accept'
                });
            },
            maxRetries = 3,
            initialDelay = 500
        );

        window.showToast(
            '✅ Contacto Agregado',
            response.data.message || 'Solicitud aceptada',
            'success'
        );

        return response.data;
    } catch (error) {
        window.transferErrorHandler.handleError(error, {
            type: 'aceptar contacto',
            requestId: requestId
        });
        throw error;
    }
}

// Ejemplo 6: Monitoreo de sistema
function displaySystemStats() {
    const axiosStats = window.axiosService.getStats();
    const transferStats = window.chunkedTransferManager.getStats();
    const errorLog = window.transferErrorHandler.getErrorLog();

    console.group('📊 Estadísticas del Sistema');
    console.table({
        'Axios Activos': axiosStats.activeRequests,
        'Colas Pendientes': axiosStats.queues,
        'Rutas Limitadas': axiosStats.configuredRoutes,
        'Transferencias Activas': transferStats.activeTransfers,
        'Bytes Transferidos': transferStats.totalBytesTransferred,
        'Velocidad Máxima': transferStats.peakSpeed,
        'Velocidad Promedio': transferStats.averageSpeed,
        'Errores Registrados': errorLog.length
    });

    if (errorLog.length > 0) {
        console.group('❌ Últimos Errores');
        errorLog.slice(-5).forEach(error => {
            console.error(`[${error.timestamp}] ${error.message}`, error);
        });
        console.groupEnd();
    }

    console.groupEnd();
}

// Ejemplo 7: Exportar diagnósticos
function exportDiagnostics() {
    const diagnostics = {
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        online: navigator.onLine,
        connection: navigator.connection?.effectiveType,
        axios: window.axiosService.getStats(),
        transfers: window.chunkedTransferManager.getStats(),
        errors: window.transferErrorHandler.getErrorLog(),
    };

    const json = JSON.stringify(diagnostics, null, 2);
    console.log('📋 Diagnósticos:', json);

    // Descargar como archivo
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `diagnostics-${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

// Exportar funciones globales
window.sendP2PSignal = sendP2PSignal;
window.startFileTransferWithVisuals = startFileTransferWithVisuals;
window.sendFileChunk = sendFileChunk;
window.uploadFileInChunks = uploadFileInChunks;
window.acceptContactWithRetry = acceptContactWithRetry;
window.displaySystemStats = displaySystemStats;
window.exportDiagnostics = exportDiagnostics;

console.log('✅ Funciones de ejemplo cargadas globalmente');
console.log('Usar: displaySystemStats(), exportDiagnostics(), etc.');
