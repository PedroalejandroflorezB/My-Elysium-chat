/**
 * ==========================================
 * GESTOR DE TRANSFERENCIAS EN BLOQUES
 * Manejo inteligente de chunks, reintentos y estadísticas
 * ==========================================
 */

export class ChunkedTransferManager {
    constructor(options = {}) {
        // Configuración de chunks
        this.minChunkSize = options.minChunkSize || 16 * 1024; // 16KB mínimo
        this.maxChunkSize = options.maxChunkSize || 512 * 1024; // 512KB máximo
        this.baseChunkSize = options.baseChunkSize || 64 * 1024; // 64KB base
        
        // Configuración de red y reintentos
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = options.backoffMultiplier || 2;
        this.initialBackoffMs = options.initialBackoffMs || 500;
        
        // Configuración de buffer
        this.bufferLimit = options.bufferLimit || 8 * 1024 * 1024; // 8MB
        this.pauseThreshold = options.pauseThreshold || 0.8; // 80% buffer
        this.resumeThreshold = options.resumeThreshold || 0.5; // 50% buffer
        
        // Estado de la transferencia
        this.transfers = new Map();
        this.networkStats = {
            startTime: null,
            bytesTransferred: 0,
            totalBytes: 0,
            peakSpeed: 0, // bytes/second
            averageSpeed: 0, // bytes/second
        };
    }

    /**
     * Crear una nueva sesión de transferencia
     */
    createTransfer(transferId, file, options = {}) {
        const transfer = {
            id: transferId,
            file: file,
            fileName: file.name,
            fileSize: file.size,
            totalChunks: Math.ceil(file.size / this.baseChunkSize),
            chunkSize: this.baseChunkSize,
            currentChunkSize: this.baseChunkSize,
            sentChunks: new Set(),
            failedChunks: new Map(),
            startTime: Date.now(),
            progress: 0,
            status: 'pending', // pending, uploading, paused, completed, failed
            isPaused: false,
            buffer: 0,
            networkSpeed: 0, // bytes/second
            estimatedTimeRemaining: 0,
            lastProgressUpdate: Date.now(),
            onProgress: options.onProgress || (() => {}),
            onError: options.onError || (() => {}),
            onComplete: options.onComplete || (() => {}),
        };

        this.transfers.set(transferId, transfer);
        console.log(`[ChunkedTransfer] ✅ Transferencia ${transferId} creada (${file.name}, ${this.formatBytes(file.size)})`);
        return transfer;
    }

    /**
     * Obtener información de una transferencia
     */
    getTransfer(transferId) {
        return this.transfers.get(transferId);
    }

    /**
     * Calcular tamaño óptimo de chunk basado en velocidad de red
     */
    calculateOptimalChunkSize(networkSpeed) {
        if (!networkSpeed || networkSpeed <= 0) return this.baseChunkSize;

        // Objetivo: completar cada chunk en 1 segundo (ideal)
        const optimalSize = Math.round(networkSpeed);

        // Respetar límites
        if (optimalSize < this.minChunkSize) return this.minChunkSize;
        if (optimalSize > this.maxChunkSize) return this.maxChunkSize;

        return optimalSize;
    }

    /**
     * Actualizar estadísticas de velocidad de red
     */
    updateNetworkStats(transferId, bytesTransferred, elapsedMs) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        const currentSpeed = bytesTransferred / (elapsedMs / 1000);
        transfer.networkSpeed = currentSpeed;

        // Actualizar estadísticas globales
        this.networkStats.bytesTransferred += bytesTransferred;
        this.networkStats.totalBytes = transfer.fileSize;
        this.networkStats.peakSpeed = Math.max(this.networkStats.peakSpeed, currentSpeed);
        
        // Calcular promedio
        const totalElapsed = Date.now() - (transfer.startTime || Date.now());
        this.networkStats.averageSpeed = this.networkStats.bytesTransferred / (totalElapsed / 1000);

        // Calcular tiempo estimado restante
        const bytesRemaining = transfer.fileSize - (transfer.sentChunks.size * transfer.currentChunkSize);
        if (currentSpeed > 0) {
            transfer.estimatedTimeRemaining = Math.ceil(bytesRemaining / currentSpeed);
        }

        // Adaptar tamaño de chunk si es beneficioso
        const newChunkSize = this.calculateOptimalChunkSize(currentSpeed);
        if (Math.abs(newChunkSize - transfer.currentChunkSize) / transfer.currentChunkSize > 0.2) {
            // Cambio de más del 20%
            transfer.currentChunkSize = newChunkSize;
            console.log(`[ChunkedTransfer] 📊 Chunk size ajustado a ${this.formatBytes(newChunkSize)} (velocidad: ${this.formatSpeed(currentSpeed)})`);
        }
    }

    /**
     * Manejar pausa de envío por buffer lleno
     */
    shouldPause(transferId) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return false;

        return transfer.buffer >= (this.bufferLimit * this.pauseThreshold);
    }

    /**
     * Manejar reanudación de envío
     */
    shouldResume(transferId) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return false;

        return transfer.buffer <= (this.bufferLimit * this.resumeThreshold);
    }

    /**
     * Registrar un chunk enviado exitosamente
     */
    registerChunkSent(transferId, chunkIndex) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        transfer.sentChunks.add(chunkIndex);
        
        // Limpiar reintentos fallidos
        if (transfer.failedChunks.has(chunkIndex)) {
            transfer.failedChunks.delete(chunkIndex);
        }

        // Actualizar progreso
        const progress = Math.round((transfer.sentChunks.size / transfer.totalChunks) * 100);
        transfer.progress = progress;

        // Llamar callback de progreso (máximo 1 vez por 500ms)
        const now = Date.now();
        if (now - transfer.lastProgressUpdate >= 500) {
            transfer.onProgress?.({
                transferId,
                progress,
                chunksSent: transfer.sentChunks.size,
                totalChunks: transfer.totalChunks,
                speed: this.formatSpeed(transfer.networkSpeed),
                timeRemaining: this.formatTime(transfer.estimatedTimeRemaining),
            });
            transfer.lastProgressUpdate = now;
        }
    }

    /**
     * Registrar un error de chunk
     */
    registerChunkError(transferId, chunkIndex, error) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        if (!transfer.failedChunks.has(chunkIndex)) {
            transfer.failedChunks.set(chunkIndex, { attempts: 0, lastError: error });
        } else {
            const failData = transfer.failedChunks.get(chunkIndex);
            failData.attempts++;
            failData.lastError = error;
        }

        const failData = transfer.failedChunks.get(chunkIndex);
        console.warn(`[ChunkedTransfer] ⚠️ Chunk ${chunkIndex} falló (intento ${failData.attempts}):`, error.message);

        if (failData.attempts >= this.maxRetries) {
            console.error(`[ChunkedTransfer] ❌ Chunk ${chunkIndex} excedió máximo de reintentos`);
            transfer.onError?.({
                transferId,
                chunkIndex,
                message: `Chunk ${chunkIndex} falló después de ${this.maxRetries} intentos`,
                error: failData.lastError,
            });
        }
    }

    /**
     * Obtener delay para reintento con backoff exponencial
     */
    getRetryDelay(chunkIndex) {
        const failData = this.transfers.get(Array.from(this.transfers.values())[0])?.failedChunks?.get(chunkIndex);
        if (!failData) return this.initialBackoffMs;

        return this.initialBackoffMs * Math.pow(this.backoffMultiplier, failData.attempts);
    }

    /**
     * Marcar transferencia como completada
     */
    completeTransfer(transferId) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        const duration = (Date.now() - transfer.startTime) / 1000;
        const avgSpeed = transfer.fileSize / duration;

        transfer.status = 'completed';
        transfer.progress = 100;

        console.log(`[ChunkedTransfer] ✅ Transferencia completada - ${this.formatBytes(transfer.fileSize)} en ${this.formatTime(duration)}`);
        console.log(`[ChunkedTransfer] 📊 Velocidad promedio: ${this.formatSpeed(avgSpeed)}`);

        transfer.onComplete?.({
            transferId,
            fileName: transfer.fileName,
            fileSize: transfer.fileSize,
            duration: duration,
            averageSpeed: avgSpeed,
        });

        // Limpiar después de 5 segundos
        setTimeout(() => this.transfers.delete(transferId), 5000);
    }

    /**
     * Cancelar transferencia
     */
    cancelTransfer(transferId) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        transfer.status = 'cancelled';
        console.log(`[ChunkedTransfer] 🛑 Transferencia ${transferId} cancelada`);

        this.transfers.delete(transferId);
    }

    /**
     * Utilidades de formato
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    formatSpeed(bytesPerSecond) {
        return this.formatBytes(bytesPerSecond) + '/s';
    }

    formatTime(seconds) {
        if (!seconds || seconds < 0) return '∞';
        if (seconds < 60) return Math.round(seconds) + 's';
        const mins = Math.floor(seconds / 60);
        const secs = Math.round(seconds % 60);
        return mins + 'm ' + secs + 's';
    }

    /**
     * Obtener estadísticas globales
     */
    getStats() {
        return {
            activeTransfers: this.transfers.size,
            totalBytesTransferred: this.networkStats.bytesTransferred,
            peakSpeed: this.formatSpeed(this.networkStats.peakSpeed),
            averageSpeed: this.formatSpeed(this.networkStats.averageSpeed),
        };
    }

    /**
     * Resetear estadísticas
     */
    resetStats() {
        this.networkStats = {
            startTime: null,
            bytesTransferred: 0,
            totalBytes: 0,
            peakSpeed: 0,
            averageSpeed: 0,
        };
        console.log('[ChunkedTransfer] 🔄 Estadísticas reseteadas');
    }
}

// Exportar instancia global
export const chunkedTransferManager = new ChunkedTransferManager();
