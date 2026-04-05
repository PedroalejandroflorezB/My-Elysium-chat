{{-- ============================================
     MODALES PARA TRANSFERENCIA P2P DE ARCHIVOS
     ============================================ --}}

{{-- Modal de Preview (Lado del Emisor) --}}
<div id="p2p-preview-modal" class="p2p-modal hidden" role="dialog" aria-modal="true" aria-labelledby="p2p-preview-title">
    <div class="p2p-modal-overlay" tabindex="-1"></div>
    <div class="p2p-modal-content">
        <button class="p2p-modal-close" id="p2p-preview-close" aria-label="Cerrar">&times;</button>
        
        <h3 id="p2p-preview-title">
            <span class="icon">📎</span>
            Confirmar envío de archivo
        </h3>
        
        <div class="p2p-files-list" id="p2p-preview-list">
            <!-- Cargado dinámicamente -->
        </div>
        
        <div class="p2p-recipient-info">
            Para: <strong id="p2p-preview-recipient">@usuario</strong>
        </div>
        
        <div class="p2p-modal-warning">
            <span class="icon">⚠️</span>
            <div>Este archivo se enviará directamente (P2P). Asegúrate de que el receptor esté en línea.</div>
        </div>
        
        <div class="p2p-modal-actions">
            <button class="p2p-btn p2p-btn-secondary" id="p2p-preview-cancel">Cancelar</button>
            <button class="p2p-btn p2p-btn-primary" id="p2p-preview-send">
                <span class="icon">✅</span>
                Enviar archivo
            </button>
        </div>
    </div>
</div>

{{-- Modal de Aceptación (Lado del Receptor) --}}
<div id="p2p-accept-modal" class="p2p-modal hidden" role="dialog" aria-modal="true" aria-labelledby="p2p-accept-title">
    <div class="p2p-modal-overlay" tabindex="-1"></div>
    <div class="p2p-modal-content">
        <h3 id="p2p-accept-title">
            <span class="icon">📥</span>
            Archivo entrante
        </h3>
        
        <div class="p2p-file-preview">
            <div class="p2p-file-icon" id="p2p-accept-icon">📄</div>
            <div class="p2p-file-details">
                <div class="p2p-file-name" id="p2p-accept-name">nombre_archivo.zip</div>
                <div class="p2p-file-meta">
                    <span id="p2p-accept-size">0 MB</span>
                    <span>•</span>
                    <span id="p2p-accept-type">Archivo</span>
                </div>
                <div class="p2p-file-sender">
                    De: <strong id="p2p-accept-sender">@usuario</strong>
                </div>
            </div>
        </div>
        
        <div class="p2p-modal-info">
            <span class="icon">💡</span>
            <div>El archivo se transferirá directamente desde el navegador del emisor (P2P).</div>
        </div>
        
        <div class="p2p-modal-actions">
            <button class="p2p-btn p2p-btn-danger" id="p2p-accept-reject">
                <span class="icon">❌</span>
                Rechazar
            </button>
            <button class="p2p-btn p2p-btn-primary" id="p2p-accept-accept">
                <span class="icon">✅</span>
                Aceptar y descargar
            </button>
        </div>
    </div>
</div>

<style>
.p2p-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    visibility: visible;
    transition: all 0.3s ease;
}

.p2p-modal.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.p2p-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
}

.p2p-modal-content {
    position: relative;
    background: var(--bg-secondary, #111417);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    z-index: 10;
    animation: modalScaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.p2p-files-list {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    margin: 1.5rem 0;
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.p2p-file-item {
    padding: 0.75rem 1rem;
    display: flex;
    gap: 0.75rem;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}

.p2p-file-item:last-child {
    border-bottom: none;
}

.p2p-file-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.p2p-file-details {
    flex: 1;
    min-width: 0;
}

.p2p-file-name {
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.p2p-file-meta {
    font-size: 0.75rem;
    color: var(--text-muted);
    opacity: 0.7;
}

.p2p-recipient-info {
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.p2p-modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.p2p-btn {
    flex: 1;
    padding: 0.8rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.p2p-btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.p2p-btn-secondary {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.p2p-btn-danger {
    background: #ef444422;
    color: #ef4444;
    border: 1px solid #ef444444;
}

.p2p-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
}
</style>
