/**
 * ==========================================
 * ESTILOS PARA BARRAS DE PROGRESO
 * Complementa el visualizador de progreso
 * ==========================================
 */

const progressStyles = `
.transfer-progress-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.transfer-progress-card.completed {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.2);
}

.transfer-progress-card.error {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    box-shadow: 0 4px 12px rgba(235, 51, 73, 0.2);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
}

.progress-filename {
    color: rgba(255, 255, 255, 0.95);
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 8px;
}

.progress-size {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    flex-shrink: 0;
}

.progress-bar-container {
    position: relative;
    height: 24px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.8) 50%, rgba(255, 255, 255, 0.4) 100%);
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
    transition: width 0.3s ease;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.progress-bar-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    pointer-events: none;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.progress-speed {
    flex: 1;
}

.progress-time {
    text-align: right;
}

/* Toast notifications */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 16px;
    min-width: 300px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast.success {
    border-left: 4px solid #11998e;
}

.toast.error {
    border-left: 4px solid #eb3349;
}

.toast.warning {
    border-left: 4px solid #f39c12;
}

.toast.info {
    border-left: 4px solid #3498db;
}
`;

// Inyectar estilos
function injectProgressStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = progressStyles;
    document.head.appendChild(styleElement);
}

// Auto-inyectar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectProgressStyles);
} else {
    injectProgressStyles();
}

export { injectProgressStyles };
