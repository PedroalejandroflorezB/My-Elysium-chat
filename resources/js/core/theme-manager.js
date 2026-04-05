/**
 * Gestor de Temas Centralizado
 * Evita duplicación de código en múltiples vistas
 */

const THEMES = ['dark', 'light', 'petrol', 'noir', 'vegas', 'neon', 'storm'];
const STORAGE_KEY = 'elysium-theme-mode';

class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem(STORAGE_KEY) || 'petrol';
        this.init();
    }
    
    init() {
        // Aplicar tema inicial inmediatamente
        this.applyTheme(this.currentTheme);
        
        // Hacer funciones disponibles globalmente
        window.Elysium.theme = {
            set: this.setTheme.bind(this),
            cycle: this.cycleTheme.bind(this),
            current: () => this.currentTheme
        };
        
        // Compatibilidad con código existente
        window.setTheme = this.setTheme.bind(this);
        window.cycleTheme = this.cycleTheme.bind(this);
    }
    
    setTheme(themeName) {
        if (!themeName || !THEMES.includes(themeName)) {
            themeName = 'petrol';
        }
        
        // Remover temas previos
        THEMES.forEach(t => document.documentElement.classList.remove(`theme-${t}`));
        
        // Aplicar nuevo tema
        document.documentElement.classList.add(`theme-${themeName}`);
        localStorage.setItem(STORAGE_KEY, themeName);
        this.currentTheme = themeName;
        
        // Actualizar iconos (mantener palette)
        document.querySelectorAll('[id*="theme-icon"], [id*="chat-theme-icon"]').forEach(icon => {
            if (icon) icon.className = 'fas fa-palette';
        });
    }
    
    cycleTheme() {
        const currentIndex = THEMES.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % THEMES.length;
        this.setTheme(THEMES[nextIndex]);
    }
    
    applyTheme(themeName) {
        this.setTheme(themeName);
    }
}

// Inicializar inmediatamente para evitar FOUC
const themeManager = new ThemeManager();

export default themeManager;