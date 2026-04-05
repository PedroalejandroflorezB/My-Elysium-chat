<div class="chat-empty-state">
    <div class="empty-icon">💬</div>
    <h2 class="empty-title">¡Empecemos!</h2>
    <p class="empty-text">
        Este chat está vacío. Para comenzar, busca un contacto<br>
        o comparte tu @usuario para que te encuentren.
    </p>

    <div class="empty-actions">
        <button class="btn btn-secondary" onclick="window.copyUsername()">
            📋 Copiar mi @usuario
        </button>
        <button class="btn btn-primary qr-action-btn" onclick="window.handleQRAction()">
            <i class="fas fa-qrcode"></i> <span id="qr-btn-text">Generar QR</span>
        </button>
    </div>
</div>

{{-- Script inline: se ejecuta siempre, incluso en navegación SPA --}}
<script>
(function() {
    // Detección de plataforma
    function isAndroid() {
        return /Android/i.test(navigator.userAgent);
    }
    window.isAndroid = isAndroid;
    window.isMobile = function() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    };

    // Copiar @usuario
    window.copyUsername = function() {
        const username = '{{ "@" . auth()->user()->username }}';
        navigator.clipboard.writeText(username).then(() => {
            if (typeof showToast === 'function') showToast('Copiado', username + ' copiado', 'success');
        }).catch(() => {
            if (typeof showToast === 'function') showToast('Error', 'No se pudo copiar', 'error');
        });
    };

    // Acción principal del botón QR
    // PC: genera directo. Android: muestra opciones (generar o escanear)
    window.handleQRAction = function() {
        if (typeof window.generateQR === 'function') {
            window.generateQR();
        } else {
            if (typeof showToast === 'function') showToast('Error', 'Función QR no disponible', 'error');
        }
    };

    // Modal de seguridad
    window.openSecurityModal = function() {
        if (document.getElementById('security-modal')) {
            showSecurityModal(); return;
        }
        fetch('/chat/security-modal')
            .then(r => r.text())
            .then(html => {
                const tmp = document.createElement('div');
                tmp.innerHTML = html;
                document.body.appendChild(tmp.firstElementChild);
                showSecurityModal();
            })
            .catch(() => alert('Error al cargar seguridad'));
    };

    window.showSecurityModal = function() {
        const modal = document.getElementById('security-modal');
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (typeof loadSecuritySettings === 'function') loadSecuritySettings();
        }
    };

    // Texto fijo del botón QR
    const qrBtnText = document.getElementById('qr-btn-text');
    if (qrBtnText) qrBtnText.textContent = 'Generar QR';

    console.log('Empty state initialized, isAndroid:', isAndroid());
})();
</script>
