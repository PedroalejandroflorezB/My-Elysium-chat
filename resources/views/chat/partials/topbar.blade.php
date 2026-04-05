<header class="top-bar">
    <div class="top-bar-left">
        <a href="{{ url('/chat') }}" class="brand">
            <x-application-logo class="w-8 h-8" />
            <span class="brand-name">Elysium</span>
        </a>
    </div>
    
    <div class="top-bar-right">
        <!-- Botón Menú 3-puntos (Solo Móvil) -->
        <button class="mobile-actions-toggle" id="mobile-actions-toggle" onclick="toggleMobileActions()" title="Más opciones">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
        </button>

        <div class="top-bar-actions" id="top-bar-actions">
            <!-- Botón de Seguridad -->
            <div class="security-switcher-chat">
                <button class="btn-security-chat" onclick="openSecurityModal()" title="Configuración de seguridad">
                    <i class="fas fa-shield-alt"></i>
                </button>
                <span class="security-label-chat">Seguridad</span>
            </div>

            <!-- Selector de Tema - Igual que Hero Home -->
            <div class="palette-switcher-chat">
                <button class="btn-palette-chat" onclick="window.cycleTheme()" title="Cambiar estética (PETROL/NOIR/VEGAS/NEON)">
                    <i id="chat-theme-icon" class="fas fa-palette"></i>
                </button>
                <span class="palette-label-chat">Personalizar Nexus</span>
            </div>
        
            <!-- User Avatar Button -->
            <button class="user-avatar-btn" onclick="openProfileModal()" title="Mi perfil">
                @if(auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="user-avatar-img">
                @else
                    <div class="user-avatar-img">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                @endif
            </button>
        
            <!-- Logout Button -->
            <button class="btn-logout" onclick="logout()">
                Salir
            </button>
        </div>
    </div>
</header>

<script>
// ============================================
// SISTEMA DE TEMAS - IGUAL QUE HERO HOME
// ============================================

// Importar funciones de tema - EXACTAMENTE IGUAL QUE HERO HOME
const THEMES = ['dark', 'light', 'petrol', 'noir', 'vegas', 'neon', 'storm'];
const STORAGE_KEY = 'elysium-theme-mode';

function setTheme(themeName) {
    if (!themeName || !THEMES.includes(themeName)) {
        themeName = 'petrol'; // Fallback al tema por defecto
    }
    
    // Remover todos los temas previos
    THEMES.forEach(t => document.documentElement.classList.remove(`theme-${t}`));
    
    // Aplicar nuevo tema
    document.documentElement.classList.add(`theme-${themeName}`);
    localStorage.setItem(STORAGE_KEY, themeName);
    
    console.log(`[THEME] 🎨 Modo cambiado a: ${themeName.toUpperCase()}`);
    
    // Actualizar iconos si existen - MANTENER SIEMPRE PALETTE COMO HERO HOME
    const icon = document.getElementById('chat-theme-icon');
    if (icon) {
        // Mantener siempre el icono de palette como en hero home
        icon.className = 'fas fa-palette';
    }
}

function cycleTheme() {
    const current = localStorage.getItem(STORAGE_KEY) || THEMES[2]; // petrol por defecto
    const currentIndex = THEMES.indexOf(current);
    const nextIndex = (currentIndex + 1) % THEMES.length;
    setTheme(THEMES[nextIndex]);
}

// Hacer funciones disponibles globalmente - IGUAL QUE HERO HOME
window.cycleTheme = cycleTheme;
window.setTheme = setTheme;

// Inicializar tema al cargar la página - IGUAL QUE HERO HOME
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem(STORAGE_KEY) || 'petrol';
    setTheme(savedTheme);
    
    // Asegurar que el icono sea palette al cargar
    const icon = document.getElementById('chat-theme-icon');
    if (icon) {
        icon.className = 'fas fa-palette';
    }
});

// ============================================
// MODAL DE SEGURIDAD
// ============================================

function openSecurityModal() {
    const modal = document.getElementById('security-modal');
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loadSecuritySettings();
    }
}

function closeSecurityModal() {
    const modal = document.getElementById('security-modal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function loadSecuritySettings() {
    // Cargar configuración actual de Gmail
    const gmailLinked = localStorage.getItem('gmail-linked') === 'true';
    const gmailEmail = localStorage.getItem('gmail-email') || '';
    
    // Actualizar UI
    const gmailStatus = document.getElementById('gmail-status');
    const gmailEmailDisplay = document.getElementById('gmail-email-display');
    const gmailEmailText = document.getElementById('gmail-email-text');
    const linkGmailBtn = document.getElementById('link-gmail-btn');
    const unlinkGmailBtn = document.getElementById('unlink-gmail-btn');
    
    if (gmailStatus) {
        if (gmailLinked && gmailEmail) {
            gmailStatus.textContent = 'Vinculado';
            gmailStatus.className = 'status-badge status-success';
            
            if (gmailEmailDisplay) gmailEmailDisplay.style.display = 'block';
            if (gmailEmailText) gmailEmailText.textContent = gmailEmail;
            if (linkGmailBtn) linkGmailBtn.style.display = 'none';
            if (unlinkGmailBtn) unlinkGmailBtn.style.display = 'inline-flex';
        } else {
            gmailStatus.textContent = 'No vinculado';
            gmailStatus.className = 'status-badge status-warning';
            
            if (gmailEmailDisplay) gmailEmailDisplay.style.display = 'none';
            if (linkGmailBtn) linkGmailBtn.style.display = 'inline-flex';
            if (unlinkGmailBtn) unlinkGmailBtn.style.display = 'none';
        }
    }
    
    // Cargar códigos de recuperación
    loadRecoveryCodes();
}

function loadRecoveryCodes() {
    const codes = JSON.parse(localStorage.getItem('recovery-codes') || '[]');
    const codesContainer = document.getElementById('recovery-codes-list');
    
    if (!codesContainer) return;
    
    if (codes.length === 0) {
        codesContainer.innerHTML = `
            <div class="no-codes-message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <p style="color: rgba(255, 255, 255, 0.6); font-style: italic; margin: 0;">No hay códigos generados</p>
                <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.8rem; margin: 0.5rem 0 0 0;">Genera códigos de respaldo para mayor seguridad</p>
            </div>
        `;
    } else {
        codesContainer.innerHTML = codes.map((code, index) => 
            `<div class="recovery-code-item">
                <span class="code-text">${code}</span>
                <span class="code-number">#${index + 1}</span>
            </div>`
        ).join('');
    }
}

function generateRecoveryCodes() {
    const codes = [];
    for (let i = 0; i < 8; i++) {
        const code = generateRandomCode();
        codes.push(code);
    }
    
    localStorage.setItem('recovery-codes', JSON.stringify(codes));
    loadRecoveryCodes();
    
    showNotification('Códigos de recuperación generados correctamente', 'success');
}

function generateRandomCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 12; i++) {
        if (i === 4 || i === 8) {
            code += '-';
        }
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return code;
}

function downloadRecoveryCodes() {
    const codes = JSON.parse(localStorage.getItem('recovery-codes') || '[]');
    if (codes.length === 0) {
        showNotification('No hay códigos para descargar', 'warning');
        return;
    }
    
    const content = `CÓDIGOS DE RECUPERACIÓN - ELYSIUM P2P
Generados: ${new Date().toLocaleString()}
Usuario: ${document.querySelector('.user-avatar-btn').title || 'Usuario'}

IMPORTANTE: Guarda estos códigos en un lugar seguro.
Cada código solo se puede usar una vez.

${codes.map((code, index) => `${index + 1}. ${code}`).join('\n')}

INSTRUCCIONES:
- Usa estos códigos si pierdes acceso a tu cuenta
- Cada código es de un solo uso
- Genera nuevos códigos si pierdes estos
- No compartas estos códigos con nadie`;
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `elysium-recovery-codes-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showNotification('Códigos descargados correctamente', 'success');
}

function showNotification(message, type = 'info') {
    // Agregar estilos de animación si no existen
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#22c55e' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        font-size: 0.875rem;
        max-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function linkGmail() {
    // Simulación temporal - solicitar email
    const email = prompt('Ingresa tu email de Gmail:');
    if (email && email.includes('@gmail.com')) {
        localStorage.setItem('gmail-linked', 'true');
        localStorage.setItem('gmail-email', email);
        loadSecuritySettings();
        showNotification('Gmail vinculado correctamente', 'success');
    } else if (email) {
        showNotification('Por favor ingresa un email de Gmail válido', 'warning');
    }
}

function unlinkGmail() {
    localStorage.removeItem('gmail-linked');
    localStorage.removeItem('gmail-email');
    loadSecuritySettings();
    showNotification('Gmail desvinculado correctamente', 'success');
}

// ============================================
// MODAL DE PERFIL
// ============================================

function openProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    } else {
        window.location.href = '/profile';
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Logout
async function logout() {
    const confirmed = await showConfirm({
        title: '¿Cerrar sesión?',
        message: 'Se cerrará tu sesión actual en Elysium.',
        confirmText: 'Cerrar sesión',
        cancelText: 'Cancelar',
        type: 'info',
        icon: '👋'
    });
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("logout") }}';
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Hacer funciones disponibles globalmente
window.openSecurityModal = openSecurityModal;
window.closeSecurityModal = closeSecurityModal;
window.loadSecuritySettings = loadSecuritySettings;
window.generateRecoveryCodes = generateRecoveryCodes;
window.downloadRecoveryCodes = downloadRecoveryCodes;
window.linkGmail = linkGmail;
window.unlinkGmail = unlinkGmail;
window.showNotification = showNotification;

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileModal();
        closeSecurityModal();
    }
});
</script>