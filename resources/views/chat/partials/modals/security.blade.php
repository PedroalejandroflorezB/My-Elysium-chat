<!-- Modal de Seguridad -->
<div id="security-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="modal-title">Configuración de Seguridad</h2>
        </div>
        
        <div class="modal-body">
            <!-- Sección Gmail -->
            <div class="security-section">
                <div class="security-section-header">
                    <div class="security-section-icon gmail-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="security-section-info">
                        <h4>Recuperación por Gmail</h4>
                    </div>
                    <div class="security-section-status">
                        <span id="gmail-status" class="status-badge status-warning">No vinculado</span>
                    </div>
                </div>
                
                <div id="gmail-email-display" class="gmail-info" style="display: none;">
                    <span id="gmail-email-text" style="font-size: 0.7rem; color: var(--text-primary);"></span>
                </div>
                
                <div class="security-actions">
                    <button id="link-gmail-btn" class="btn btn--primary" onclick="linkGmail()">
                        <i class="fas fa-link"></i>
                        Vincular
                    </button>
                    
                    <button id="unlink-gmail-btn" class="btn btn--secondary" onclick="unlinkGmail()" style="display: none;">
                        <i class="fas fa-unlink"></i>
                        Desvincular
                    </button>
                </div>
            </div>
            
            <!-- Separador -->
            <div style="height: 1px; background: var(--border-color); margin: 0.75rem 0;"></div>
            
            <!-- Sección Códigos de Recuperación -->
            <div class="security-section">
                <div class="security-section-header">
                    <div class="security-section-icon codes-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="security-section-info">
                        <h4>Códigos de Recuperación</h4>
                    </div>
                </div>
                
                <div class="security-actions">
                    <button class="btn btn--primary" onclick="generateRecoveryCodes()">
                        <i class="fas fa-plus"></i>
                        Generar
                    </button>
                    
                    <button class="btn btn--secondary" onclick="downloadRecoveryCodes()">
                        <i class="fas fa-download"></i>
                        Descargar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn--secondary" onclick="closeSecurityModal()">Cerrar</button>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el modal de seguridad - DISEÑO ARREGLADO */
.security-section {
    margin-bottom: 1rem;
}

.security-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.security-section-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.875rem;
}

.gmail-icon {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.codes-icon {
    background: rgba(168, 85, 247, 0.15);
    color: #a855f7;
    border: 1px solid rgba(168, 85, 247, 0.3);
}

.security-section-info {
    flex: 1;
}

.security-section-info h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.2;
}

.security-section-status {
    flex-shrink: 0;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid;
}

.status-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border-color: rgba(34, 197, 94, 0.3);
}

.status-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
    border-color: rgba(245, 158, 11, 0.3);
}

.gmail-info {
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 8px;
    text-align: center;
}

.security-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.security-actions .btn {
    flex: 1;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.security-actions .btn--primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.security-actions .btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.security-actions .btn--secondary {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.security-actions .btn--secondary:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
}

/* Modal específico - DISEÑO MEJORADO */
#security-modal .modal-content {
    max-width: 400px;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

#security-modal .modal-header {
    padding: 0 0 1rem 0;
    text-align: center;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

#security-modal .modal-icon {
    background: var(--primary-gradient);
    color: white;
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem auto;
    font-size: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

#security-modal .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

#security-modal .modal-body {
    padding: 0;
}

#security-modal .modal-footer {
    padding: 1rem 0 0 0;
    border-top: 1px solid var(--border-color);
    margin-top: 1.5rem;
    text-align: center;
}

#security-modal .modal-footer .btn {
    padding: 0.75rem 2rem;
    font-size: 0.85rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

#security-modal .modal-footer .btn:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
}

#security-modal .modal-close {
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: var(--text-muted);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

#security-modal .modal-close:hover {
    background: rgba(255, 255, 255, 0.15);
    color: var(--text-primary);
}

/* Separador mejorado */
#security-modal .modal-body > div[style*="height: 1px"] {
    background: var(--border-color) !important;
    margin: 1.5rem 0 !important;
    opacity: 0.5;
}

/* Responsive mejorado */
@media (max-width: 640px) {
    .security-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .security-actions .btn {
        width: 100%;
    }
    
    #security-modal .modal-content {
        max-width: 340px;
        margin: 1rem;
        padding: 1.25rem;
    }
    
    .security-section-header {
        gap: 0.5rem;
    }
    
    .security-section-icon {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
}
</style>

<script>
// Security modal functions
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
            if (unlinkGmailBtn) unlinkGmailBtn.style.display = 'flex';
        } else {
            gmailStatus.textContent = 'No vinculado';
            gmailStatus.className = 'status-badge status-warning';
            
            if (gmailEmailDisplay) gmailEmailDisplay.style.display = 'none';
            if (linkGmailBtn) linkGmailBtn.style.display = 'flex';
            if (unlinkGmailBtn) unlinkGmailBtn.style.display = 'none';
        }
    }
    
    // Cargar códigos de recuperación
    loadRecoveryCodes();
}

function loadRecoveryCodes() {
    // No mostrar códigos en el modal compacto
    // Solo generar y descargar directamente
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
Usuario: ${document.querySelector('.user-avatar-btn')?.title || 'Usuario'}

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

function showNotification(message, type = 'info') {
    // Usar el sistema de notificaciones existente si está disponible
    if (typeof showToast === 'function') {
        showToast(type === 'success' ? 'Éxito' : type === 'warning' ? 'Advertencia' : 'Información', message, type);
    } else {
        // Fallback a alert si no hay sistema de notificaciones
        alert(message);
    }
}

// Asegurar que las funciones sean globales
window.openSecurityModal = openSecurityModal;
window.closeSecurityModal = closeSecurityModal;
window.loadSecuritySettings = loadSecuritySettings;
window.generateRecoveryCodes = generateRecoveryCodes;
window.downloadRecoveryCodes = downloadRecoveryCodes;
window.linkGmail = linkGmail;
window.unlinkGmail = unlinkGmail;

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSecurityModal();
    }
});

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    if (e.target.id === 'security-modal') {
        closeSecurityModal();
    }
});
</script>