/**
 * ==========================================
 * SERVICIO AXIOS CENTRALIZADO
 * Manejo de reintentos, rate limiting, feedback visual
 * y optimización de rutas para P2P
 * ==========================================
 */

import axios from 'axios';

const DEFAULT_TIMEOUT = 15000;
const DEFAULT_MAX_RETRIES = 3;
const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];
const NON_RETRY_STATUS_CODES = [401, 403, 404, 422, 419];

// Almacenar configuración de rate limiting por ruta
const routeConfig = {
    '/api/p2p/signal': { maxRequests: 10, windowMs: 1000, concurrent: 2 },
    '/api/p2p/offer': { maxRequests: 5, windowMs: 2000, concurrent: 1 },
    '/api/p2p/answer': { maxRequests: 5, windowMs: 2000, concurrent: 1 },
    '/api/p2p/ice-candidate': { maxRequests: 20, windowMs: 1000, concurrent: 3 },
    '/api/contacts/request/respond': { maxRequests: 5, windowMs: 1000, concurrent: 1 },
    '/api/p2p/signals/new': { maxRequests: 10, windowMs: 1000, concurrent: 2 },
};

// Queues para rate limiting
const requestQueues = new Map();
const requestCounts = new Map();
const activeRequests = new Map();

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function getRouteKey(url) {
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return url || '';
    }
}

function parseRetryAfterMs(error) {
    const header = error.response?.headers?.['retry-after'] || error.response?.headers?.['Retry-After'];
    if (!header) {
        return null;
    }

    const seconds = Number(header);
    if (!Number.isNaN(seconds)) {
        return Math.min(seconds * 1000, 10000);
    }

    const date = Date.parse(header);
    if (!Number.isNaN(date)) {
        return Math.max(0, date - Date.now());
    }

    return null;
}

/**
 * Inicializar instancia de Axios con configuración base
 */
const axiosInstance = axios.create({
    baseURL: window.location.origin,
    timeout: DEFAULT_TIMEOUT,
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/**
 * INTERCEPTOR DE SOLICITUD: Agregar CSRF token y manejo de rate limiting
 */
axiosInstance.interceptors.request.use(
    (config) => {
        // Agregar CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // Aplicar rate limiting de forma normalizada por ruta
        const route = getRouteKey(config.url || '');
        const config_limits = routeConfig[route];
        if (config_limits) {
            config._rateLimitConfig = config_limits;
            config._routeKey = route;
            config._requestId = Math.random().toString(36).slice(2);
        }

        return config;
    },
    (error) => {
        console.error('[Axios] Error en interceptor de solicitud:', error);
        return Promise.reject(error);
    }
);

/**
 * INTERCEPTOR DE RESPUESTA: Reintentos con backoff exponencial
 */
axiosInstance.interceptors.response.use(
    (response) => {
        // Éxito: limpiar contador de reintentos
        if (response.config._retryCount) {
            console.log(`[Axios] ✅ Éxito después de ${response.config._retryCount} reintentos`);
        }
        return response;
    },
    async (error) => {
        const config = error.config || {};
        config._retryCount = config._retryCount ?? 0;

        const status = error.response?.status;
        const isCanceled = error.code === 'ERR_CANCELED' || axios.isCancel?.(error);

        const shouldNotRetry = isCanceled || NON_RETRY_STATUS_CODES.includes(status);

        if (shouldNotRetry) {
            console.error('[Axios] ❌ No reintentar:', status || error.code, error.message);
            return Promise.reject(error);
        }

        const isNetworkError = !error.response && error.request;
        const isRetryableStatus = RETRYABLE_STATUS_CODES.includes(status);
        const shouldRetry = isNetworkError || isRetryableStatus;

        if (!shouldRetry) {
            console.error('[Axios] ❌ Error no retryable:', status || 'network', error.message);
            return Promise.reject(error);
        }

        if (config._retryCount < DEFAULT_MAX_RETRIES) {
            config._retryCount++;
            const retryAfter = status === 429 ? parseRetryAfterMs(error) : null;
            const backoffMs = retryAfter ?? Math.min(Math.pow(2, config._retryCount - 1) * 1000, 8000);

            console.warn(`[Axios] 🔄 Reintentando ${config._retryCount}/${DEFAULT_MAX_RETRIES} en ${backoffMs}ms - ${config.url}`);

            if (config._retryCount > 1) {
                window.showToast?.('Reintentando...', `Intento ${config._retryCount} de ${DEFAULT_MAX_RETRIES}`, 'info');
            }

            await sleep(backoffMs);
            return axiosInstance(config);
        }

        console.error('[Axios] ❌ Máximo de reintentos alcanzado:', config.url, error.message);
        window.showToast?.('Error de Conexión', 'No se pudo conectar después de varios intentos', 'error');
        return Promise.reject(error);
    }
);

/**
 * Enqueue y ejecutar solicitud respetando rate limits
 */
async function executeWithRateLimit(config) {
    const route = config._routeKey || getRouteKey(config.url || '');
    const limits = routeConfig[route];

    if (!limits) {
        // Sin límites configurados, ejecutar directamente
        return axiosInstance(config);
    }

    const queueKey = route;
    if (!requestQueues.has(queueKey)) {
        requestQueues.set(queueKey, []);
        requestCounts.set(queueKey, 0);
        activeRequests.set(queueKey, 0);
    }

    const queue = requestQueues.get(queueKey);

    return new Promise((resolve, reject) => {
        const executeRequest = async () => {
            const current = activeRequests.get(queueKey) || 0;
            if (current >= limits.concurrent) {
                // Cola llena, esperar
                queue.push({ resolve, reject, executeRequest });
                return;
            }

            activeRequests.set(queueKey, current + 1);

            try {
                const response = await axiosInstance(config);
                resolve(response);
            } catch (error) {
                reject(error);
            } finally {
                activeRequests.set(queueKey, current - 1);

                // Ejecutar siguiente en cola
                if (queue.length > 0) {
                    const next = queue.shift();
                    next.executeRequest();
                }
            }
        };

        // Verificar límite de solicitudes en ventana de tiempo
        const now = Date.now();
        const windowKey = `${queueKey}:window`;
        if (!requestCounts.has(windowKey)) {
            requestCounts.set(windowKey, { count: 0, startTime: now });
        }

        const window_data = requestCounts.get(windowKey);
        if (now - window_data.startTime > limits.windowMs) {
            // Nueva ventana
            window_data.count = 0;
            window_data.startTime = now;
        }

        if (window_data.count >= limits.maxRequests) {
            // Límite alcanzado, esperar a siguiente ventana
            const delayMs = limits.windowMs - (now - window_data.startTime);
            console.warn(`[RateLimit] ⏸️ Limite alcanzado para ${route}, esperando ${delayMs}ms`);
            window.showToast?.('Sistema Ocupado', `Esperando ${Math.ceil(delayMs / 1000)}s...`, 'warning');

            setTimeout(() => {
                window_data.count++;
                executeRequest();
            }, delayMs);
        } else {
            window_data.count++;
            executeRequest();
        }
    });
}

/**
 * Métodos públicos del servicio
 */
export default {
    /**
     * GET request
     */
    async get(url, config = {}) {
        return executeWithRateLimit({ ...config, method: 'GET', url });
    },

    /**
     * POST request con soporte para chunks y progreso
     */
    async post(url, data = {}, config = {}) {
        return executeWithRateLimit({
            ...config,
            method: 'POST',
            url,
            data,
        });
    },

    /**
     * POST con soporte para upload en chunks
     * Ideal para archivos grandes con progreso y reintentos por chunk
     */
    async uploadChunk(url, chunk, chunkIndex, totalChunks, onProgress = null, config = {}) {
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunkIndex', chunkIndex);
        formData.append('totalChunks', totalChunks);

        // Re-intentar este chunk específicamente 3 veces
        let retries = 0;
        const maxChunkRetries = 3;

        while (retries < maxChunkRetries) {
            try {
                const response = await axiosInstance.post(url, formData, {
                    ...config,
                    headers: {
                        ...config.headers,
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        if (onProgress) {
                            onProgress({
                                loaded: progressEvent.loaded,
                                total: progressEvent.total,
                                percent: Math.round((progressEvent.loaded / progressEvent.total) * 100),
                            });
                        }
                    },
                });

                return response;
            } catch (error) {
                retries++;
                if (retries >= maxChunkRetries) {
                    console.error(`[Axios] ❌ Chunk ${chunkIndex} falló después de ${maxChunkRetries} intentos`);
                    throw error;
                }

                // Backoff exponencial: 500ms, 1s, 2s
                const delayMs = Math.pow(2, retries - 1) * 500;
                console.warn(`[Axios] 🔄 Reintentando chunk ${chunkIndex} (${retries}/${maxChunkRetries}) en ${delayMs}ms`);
                window.showToast?.('Reintentando Chunk', `Chunk ${chunkIndex + 1}/${totalChunks} - Intento ${retries}`, 'info');

                await new Promise(resolve => setTimeout(resolve, delayMs));
            }
        }
    },

    /**
     * PUT request
     */
    async put(url, data = {}, config = {}) {
        return executeWithRateLimit({
            ...config,
            method: 'PUT',
            url,
            data,
        });
    },

    /**
     * DELETE request
     */
    async delete(url, config = {}) {
        return executeWithRateLimit({
            ...config,
            method: 'DELETE',
            url,
        });
    },

    /**
     * PATCH request
     */
    async patch(url, data = {}, config = {}) {
        return executeWithRateLimit({
            ...config,
            method: 'PATCH',
            url,
            data,
        });
    },

    /**
     * Obtener instancia Axios bruta para casos especiales
     */
    getInstance() {
        return axiosInstance;
    },

    /**
     * Resetear rate limiting (útil para pruebas)
     */
    resetRateLimits() {
        requestCounts.clear();
        requestQueues.clear();
        activeRequests.clear();
        console.log('[Axios] 🔄 Rate limits reseteados');
    },

    /**
     * Estadísticas del servicio
     */
    getStats() {
        return {
            queues: requestQueues.size,
            activeRequests: Array.from(activeRequests.values()).reduce((a, b) => a + b, 0),
            configuredRoutes: Object.keys(routeConfig).length,
        };
    }
};
