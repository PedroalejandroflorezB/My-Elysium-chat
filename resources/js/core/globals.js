/**
 * Gestión Centralizada de Variables Globales
 * Optimizado para t3.micro - Mínimo uso de memoria
 */

// Namespace único para evitar conflictos
window.Elysium = window.Elysium || {
    // Core
    currentUserId: null,
    targetUserId: null,
    roomId: null,
    
    // Estados
    isAndroid: () => /Android/i.test(navigator.userAgent),
    isMobile: () => /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
    
    // Funciones principales (lazy loading)
    nav: null,
    toast: null,
    theme: null,
    qr: null,
    
    // Cleanup para SPA
    cleanup: () => {
        // Limpiar listeners y recursos
        if (window.Echo) {
            Object.keys(window.Echo.connector.channels).forEach(channel => {
                window.Echo.leave(channel);
            });
        }
    }
};

// Alias cortos para funciones frecuentes
window.E = window.Elysium;

export default window.Elysium;