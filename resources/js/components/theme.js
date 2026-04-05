/**
 * Módulo de Tema Cyberpunk
 * Gestiona 4 modos: Petrol, Noir, Vegas, Neon
 */

const THEMES = ['dark', 'light', 'petrol', 'noir', 'vegas', 'neon', 'storm'];
const STORAGE_KEY = 'elysium-theme-mode';

/**
 * Aplicar un tema específico
 */
export function setTheme(themeName) {
    if (!themeName || !THEMES.includes(themeName)) {
        themeName = 'petrol'; // Fallback to petrol like hero home
    }
    
    // Remover todos los temas previos
    THEMES.forEach(t => document.documentElement.classList.remove(`theme-${t}`));
    
    // Aplicar nuevo tema
    document.documentElement.classList.add(`theme-${themeName}`);
    localStorage.setItem(STORAGE_KEY, themeName);
    
    console.log(`[THEME] 🎨 Modo cambiado a: ${themeName.toUpperCase()}`);
    
    // Actualizar iconos si existen - MANTENER SIEMPRE PALETTE COMO HERO HOME
    const icon = document.getElementById('theme-icon');
    const chatIcon = document.getElementById('chat-theme-icon');
    
    // Mantener siempre el icono de palette como en hero home
    if (icon) {
        icon.className = 'fas fa-palette';
    }
    if (chatIcon) {
        chatIcon.className = 'fas fa-palette';
    }
}

/**
 * Ciclar entre los 7 temas
 */
export function cycleTheme() {
    const current = localStorage.getItem(STORAGE_KEY) || THEMES[2]; // petrol por defecto
    const currentIndex = THEMES.indexOf(current);
    const nextIndex = (currentIndex + 1) % THEMES.length;
    setTheme(THEMES[nextIndex]);
}

/**
 * Inicializar tema guardado
 */
export function initTheme() {
    const savedTheme = localStorage.getItem(STORAGE_KEY) || THEMES[2]; // petrol por defecto
    setTheme(savedTheme);
    
    // Hacer funciones disponibles globalmente
    window.cycleTheme = cycleTheme;
    window.setTheme = setTheme;
}