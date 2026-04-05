/**
 * ==========================================
 * SERVICIO DE ERRORES Y FEEDBACK VISUAL
 * Manejo centralizado de errores de transferencia
 * con feedback visual mejorado
 * ==========================================
 */

export class TransferErrorHandler {
    constructor() {
        this.errorLog = [];
        this.maxLogEntries = 50;
    }

    /**
     * Clasificar y manejar errores
     */
    handleError(error, context = {}) {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            message: error.message || 'Error desconocido',
            code: error.code,
            status: error.response?.status,
            context: context,
            stack: error.stack,
        };

        // Agregar a log (máximo 50 entradas)
        this.errorLog.push(errorInfo);
        if (this.errorLog.length > this.maxLogEntries) {
            this.errorLog.shift();
        }

        // Determinar tipo de error y mostrar feedback
        this.displayFeedback(errorInfo);

        console.error('[ErrorHandler]', errorInfo);
        return errorInfo;
    }

    /**
     * Mostrar feedback visual según tipo de error
     */
    displayFeedback(errorInfo) {
        const { status, message, context } = errorInfo;
        const transferType = context.type || 'transferencia';

        // Errores de red
        if (!status || status >= 500) {
            window.showToast?.(
                '❌ Error de Conexión',
                `No se pudo ${transferType}. El servidor podría estar temporalmente no disponible.`,
                'error'
            );
            return;
        }

        // Autenticación
        if (status === 401) {
            window.showToast?.(
                '🔐 Autenticación Requerida',
                'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                'error'
            );
            return;
        }

        // Token CSRF
        if (status === 419) {
            window.showToast?.(
                '🔄 Sesión Expirada',
                'Por favor, recarga la página y vuelve a intentar.',
                'error'
            );
            return;
        }

        // Validación
        if (status === 422) {
            const detail = context.validationErrors ? 
                Object.values(context.validationErrors)[0]?.[0] || message :
                message;
            window.showToast?.(
                '⚠️ Datos Inválidos',
                detail,
                'error'
            );
            return;
        }

        // Rate limit
        if (status === 429) {
            window.showToast?.(
                '⏸️ Limite Alcanzado',
                'El sistema está ocupado. Esperando unos segundos...',
                'warning'
            );
            return;
        }

        // No encontrado
        if (status === 404) {
            window.showToast?.(
                '🔍 No Encontrado',
                'El recurso solicitado no existe.',
                'error'
            );
            return;
        }

        // Error genérico
        window.showToast?.(
            '❌ Error',
            message || `Error durante la ${transferType}`,
            'error'
        );
    }

    /**
     * Validar integridad de chunk
     */
    validateChunk(chunk, expectedHash = null) {
        if (!chunk || chunk.byteLength === 0) {
            return {
                valid: false,
                error: 'Chunk vacío',
            };
        }

        if (expectedHash) {
            // Hash validation would go here (if available)
            // For now, just basic validation
        }

        return { valid: true };
    }

    /**
     * Retries con backoff exponencial
     */
    async retryWithBackoff(fn, maxRetries = 3, initialDelay = 500) {
        let lastError;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await fn();
            } catch (error) {
                lastError = error;

                if (attempt < maxRetries) {
                    const delay = initialDelay * Math.pow(2, attempt - 1);
                    console.warn(
                        `[Retry] Intento ${attempt}/${maxRetries} falló, reintentando en ${delay}ms`
                    );
                    await new Promise(resolve => setTimeout(resolve, delay));
                }
            }
        }

        throw lastError;
    }

    /**
     * Obtener log de errores
     */
    getErrorLog() {
        return [...this.errorLog];
    }

    /**
     * Limpiar log de errores
     */
    clearErrorLog() {
        this.errorLog = [];
    }

    /**
     * Exportar log para debugging
     */
    exportErrorLog() {
        return JSON.stringify(this.errorLog, null, 2);
    }
}

/**
 * VISUALIZADOR DE PROGRESO MEJORADO
 */
export class ProgressVisualizer {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.transfers = new Map();
    }

    /**
     * Crear barra de progreso visual
     */
    createProgressBar(transferId, fileName, fileSize) {
        const progressHTML = `
            <div class="transfer-progress-card" id="progress-${transferId}">
                <div class="progress-header">
                    <span class="progress-filename">${this.escapeHtml(fileName)}</span>
                    <span class="progress-size">${this.formatBytes(fileSize)}</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: 0%"></div>
                    <div class="progress-bar-label">0%</div>
                </div>
                <div class="progress-stats">
                    <span class="progress-speed">— KB/s</span>
                    <span class="progress-time">∞</span>
                </div>
            </div>
        `;

        const element = document.createElement('div');
        element.innerHTML = progressHTML;
        this.container?.appendChild(element);

        this.transfers.set(transferId, {
            element: element.firstChild,
            lastUpdate: Date.now(),
        });

        return element.firstChild;
    }

    /**
     * Actualizar progreso
     */
    updateProgress(transferId, progress, speed, timeRemaining) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        // Throttle updates (máximo 1 por 300ms)
        if (Date.now() - transfer.lastUpdate < 300) return;

        const fill = transfer.element.querySelector('.progress-bar-fill');
        const label = transfer.element.querySelector('.progress-bar-label');
        const speedSpan = transfer.element.querySelector('.progress-speed');
        const timeSpan = transfer.element.querySelector('.progress-time');

        fill.style.width = progress + '%';
        label.textContent = progress + '%';
        speedSpan.textContent = speed;
        timeSpan.textContent = timeRemaining;

        transfer.lastUpdate = Date.now();
    }

    /**
     * Marcar como completo
     */
    completeProgress(transferId) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        const element = transfer.element;
        element.classList.add('completed');
        element.querySelector('.progress-bar-fill').style.width = '100%';
        element.querySelector('.progress-bar-label').textContent = '✅ Completado';

        // Remover después de 3 segundos
        setTimeout(() => {
            element.remove();
            this.transfers.delete(transferId);
        }, 3000);
    }

    /**
     * Marcar como error
     */
    errorProgress(transferId, errorMessage) {
        const transfer = this.transfers.get(transferId);
        if (!transfer) return;

        const element = transfer.element;
        element.classList.add('error');
        element.querySelector('.progress-bar-label').textContent = `❌ ${errorMessage}`;

        // Remover después de 5 segundos
        setTimeout(() => {
            element.remove();
            this.transfers.delete(transferId);
        }, 5000);
    }

    /**
     * Utilidades
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Exportar instancias
export const transferErrorHandler = new TransferErrorHandler();
export const progressVisualizer = new ProgressVisualizer('p2p-transfers-container');
