/**
 * Utilidades Generales
 * Funciones helper reutilizables
 */

/**
 * Formatear fecha
 */
export function formatDate(date) {
    return new Intl.DateTimeFormat('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

/**
 * Formatear tamaño de archivo
 */
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Escapar HTML para prevenir XSS
 */
export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Generar ID único
 */
export function generateId() {
    return Math.random().toString(36).substring(2, 15);
}

/**
 * Inicializar utilidades
 */
export function initUtils() {
    // Hacer funciones disponibles globalmente si es necesario
    window.formatDate = formatDate;
    window.formatFileSize = formatFileSize;
    window.escapeHtml = escapeHtml;
}