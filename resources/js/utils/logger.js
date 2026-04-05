/**
 * Sistema de Logging Optimizado - Elysium Ito
 * Reduce logs innecesarios para mejorar rendimiento
 */

// Configuración simple: solo mostrar errores y éxitos importantes
const SHOW_LOGS = {
    // Errores siempre se muestran
    error: true,
    warn: true,
    
    // Solo logs importantes
    success: ['inicializado', 'registrado', 'completado', 'conectado'],
    
    // Filtros para reducir spam
    ignore: [
        'Suscribiéndose a',
        'Suscripción a',
        'Lista de conversaciones',
        'Scroll al final',
        'Features inicializados',
        'DOM cargado',
        'Inicializando features',
        'RoomID',
        'Navegando a chat',
        'HTML recibido',
        'URL actualizada',
        'Mobile view',
        'Loading overlay'
    ]
};

// Guardar console original
const originalConsole = {
    log: console.log,
    error: console.error,
    warn: console.warn
};

// Función para filtrar logs
function shouldShowLog(message) {
    const msg = String(message).toLowerCase();
    
    // Siempre mostrar si contiene palabras de éxito importantes
    if (SHOW_LOGS.success.some(word => msg.includes(word))) {
        return true;
    }
    
    // Ocultar si está en la lista de ignorados
    if (SHOW_LOGS.ignore.some(ignore => msg.includes(ignore.toLowerCase()))) {
        return false;
    }
    
    // Mostrar otros logs importantes (errores, warnings, etc.)
    return true;
}

// Sobrescribir console.log para filtrar
console.log = function(...args) {
    if (args.length > 0 && shouldShowLog(args[0])) {
        originalConsole.log.apply(console, args);
    }
};

// Mantener errores y warnings siempre visibles
console.error = originalConsole.error;
console.warn = originalConsole.warn;

// Función para restaurar console original (para debugging)
window.restoreConsole = () => {
    console.log = originalConsole.log;
    console.error = originalConsole.error;
    console.warn = originalConsole.warn;
};

// Logger simple para casos específicos
const Logger = {
    success: (message) => originalConsole.log(`✅ ${message}`),
    error: (message) => originalConsole.error(`❌ ${message}`),
    warn: (message) => originalConsole.warn(`⚠️ ${message}`)
};

export default Logger;