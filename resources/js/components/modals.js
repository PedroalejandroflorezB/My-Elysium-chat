/**
 * Módulo de Modales
 * Gestiona apertura/cierre de modales
 */

/**
 * Mostrar Alerta Neon
 */
export function showNeonAlert(message, title = 'Notificación') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay neon-modal-overlay active';
        overlay.innerHTML = `
            <div class="neon-dialog" role="dialog" aria-modal="true">
                <p class="neon-dialog__title">${title}</p>
                <p class="neon-dialog__message">${message}</p>
                <div class="neon-dialog__actions">
                    <button class="neon-dialog__btn neon-dialog__btn--primary" id="neon-alert-ok">Aceptar</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        const btn = overlay.querySelector('#neon-alert-ok');
        btn.focus();
        btn.addEventListener('click', () => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
            resolve(true);
        });
    });
}

/**
 * Mostrar Confirmación Neon
 */
export function showNeonConfirm(message, title = 'Confirmar', onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay neon-modal-overlay active';
    overlay.innerHTML = `
        <div class="neon-dialog" role="dialog" aria-modal="true">
            <p class="neon-dialog__title">${title}</p>
            <p class="neon-dialog__message">${message}</p>
            <div class="neon-dialog__actions">
                <button class="neon-dialog__btn neon-dialog__btn--cancel" id="neon-confirm-cancel">Cancelar</button>
                <button class="neon-dialog__btn neon-dialog__btn--primary" id="neon-confirm-ok">Aceptar</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const btnOk     = overlay.querySelector('#neon-confirm-ok');
    const btnCancel = overlay.querySelector('#neon-confirm-cancel');

    btnCancel.focus();

    const closeAndResolve = (result) => {
        overlay.classList.remove('active');
        setTimeout(() => overlay.remove(), 300);
        if (result && typeof onConfirm === 'function') {
            onConfirm();
        }
    };

    btnOk.addEventListener('click',     () => closeAndResolve(true));
    btnCancel.addEventListener('click', () => closeAndResolve(false));
}

/**
 * Cerrar modal
 */
export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Aceptar contacto (ejemplo)
 */
export function acceptContact() {
    closeModal('modal-conoces');
    showNeonAlert('Contacto aceptado (demo)', 'Éxito');
}

/**
 * Inicializar modales
 */
export function initModals() {
    // Cerrar modales al hacer click fuera
    document.querySelectorAll('.modal-overlay:not(.neon-modal-overlay)').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active:not(.neon-modal-overlay)').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
    
    // Hacer funciones disponibles globalmente
    window.closeModal = closeModal;
    window.acceptContact = acceptContact;
    window.showNeonAlert = showNeonAlert;
    window.showNeonConfirm = showNeonConfirm;
}