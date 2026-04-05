/**
 * ============================================
 * TOAST & CONFIRM SYSTEM - Elysium Ito
 * Reemplaza alert() y confirm() nativos
 * ============================================
 */

const ICONS = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
    warning: '⚠'
};

/**
 * Obtener o crear el contenedor de toasts
 */
function getToastContainer() {
    let container = document.getElementById('elysium-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'elysium-toast-container';
        container.className = 'elysium-toast-container';
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Mostrar una notificación toast
 * @param {string} title - Título del toast
 * @param {string} [message] - Mensaje secundario (opcional)
 * @param {string} [type='info'] - Tipo: 'success', 'error', 'info', 'warning'
 * @param {number} [duration=4000] - Duración en ms antes de auto-cerrar
 */
export function showToast(title, message = '', type = 'info', duration = 4000) {
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.className = `elysium-toast elysium-toast--${type}`;

    toast.innerHTML = `
        <div class="elysium-toast__icon">${ICONS[type] || ICONS.info}</div>
        <div class="elysium-toast__content">
            <p class="elysium-toast__title">${escapeHtml(title)}</p>
            ${message ? `<p class="elysium-toast__message">${escapeHtml(message)}</p>` : ''}
        </div>
        <button class="elysium-toast__close" aria-label="Cerrar">✕</button>
        <div class="elysium-toast__progress" style="animation-duration: ${duration}ms;"></div>
    `;

    // Close button
    toast.querySelector('.elysium-toast__close').addEventListener('click', () => removeToast(toast));

    container.appendChild(toast);

    // Auto-remove
    const timer = setTimeout(() => removeToast(toast), duration);
    toast._timer = timer;

    // Pause on hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(toast._timer);
        const progress = toast.querySelector('.elysium-toast__progress');
        if (progress) progress.style.animationPlayState = 'paused';
    });

    toast.addEventListener('mouseleave', () => {
        toast._timer = setTimeout(() => removeToast(toast), 2000);
        const progress = toast.querySelector('.elysium-toast__progress');
        if (progress) {
            progress.style.animationDuration = '2000ms';
            progress.style.animationPlayState = 'running';
        }
    });

    // Limit to 5 visible toasts
    const toasts = container.querySelectorAll('.elysium-toast:not(.removing)');
    if (toasts.length > 5) {
        removeToast(toasts[0]);
    }

    return toast;
}

/**
 * Remover un toast con animación
 */
function removeToast(toast) {
    if (!toast || toast.classList.contains('removing')) return;
    toast.classList.add('removing');
    clearTimeout(toast._timer);
    setTimeout(() => toast.remove(), 350);
}

/**
 * Mostrar diálogo de confirmación estilizado (reemplaza confirm())
 * @param {object} options
 * @param {string} options.title - Título del diálogo
 * @param {string} options.message - Mensaje descriptivo
 * @param {string} [options.confirmText='Confirmar'] - Texto del botón confirmar
 * @param {string} [options.cancelText='Cancelar'] - Texto del botón cancelar
 * @param {string} [options.type='danger'] - Tipo visual: 'danger', 'warning', 'info'
 * @param {string} [options.icon] - Emoji o texto para el icono
 * @returns {Promise<boolean>} - true si confirma, false si cancela
 */
export function showConfirm({
    title = '¿Estás seguro?',
    message = '',
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    type = 'danger',
    icon = null
} = {}) {
    return new Promise((resolve) => {
        // Remove any existing confirm
        const existing = document.getElementById('elysium-confirm-overlay');
        if (existing) existing.remove();

        const defaultIcons = {
            danger: '🗑️',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const overlay = document.createElement('div');
        overlay.id = 'elysium-confirm-overlay';
        overlay.className = 'elysium-confirm-overlay';

        overlay.innerHTML = `
            <div class="elysium-confirm elysium-confirm--${type}">
                <div class="elysium-confirm__icon">
                    <div class="elysium-confirm__icon-circle">
                        ${icon || defaultIcons[type] || defaultIcons.info}
                    </div>
                </div>
                <div class="elysium-confirm__body">
                    <h3 class="elysium-confirm__title">${escapeHtml(title)}</h3>
                    ${message ? `<p class="elysium-confirm__message">${escapeHtml(message)}</p>` : ''}
                </div>
                <div class="elysium-confirm__actions">
                    <button class="elysium-confirm__btn elysium-confirm__btn--cancel">${escapeHtml(cancelText)}</button>
                    <button class="elysium-confirm__btn elysium-confirm__btn--confirm">${escapeHtml(confirmText)}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Activate with slight delay for animation
        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });

        const cleanup = (result) => {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
            document.removeEventListener('keydown', onEscape);
            resolve(result);
        };

        // Cancel button
        overlay.querySelector('.elysium-confirm__btn--cancel').addEventListener('click', () => cleanup(false));

        // Confirm button
        overlay.querySelector('.elysium-confirm__btn--confirm').addEventListener('click', () => cleanup(true));

        // Click outside to cancel
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) cleanup(false);
        });

        // Escape to cancel
        const onEscape = (e) => {
            if (e.key === 'Escape') cleanup(false);
        };
        document.addEventListener('keydown', onEscape);

        // Focus the cancel button for keyboard nav
        setTimeout(() => {
            overlay.querySelector('.elysium-confirm__btn--cancel')?.focus();
        }, 100);
    });
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// EXPORTAR GLOBALMENTE
// ============================================
window.showToast = showToast;
window.showConfirm = showConfirm;

export function initToast() {
    // Pre-create container
    getToastContainer();
    console.log('[TOAST] ✅ Sistema de notificaciones inicializado');
}
