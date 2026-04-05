<div id="modal-conoces" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-icon" style="background: rgba(255, 180, 171, 0.1); color: var(--error);">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="modal-title">¿Conoces a esta persona?</h2>
        </div>
        <div class="modal-body">
            <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: var(--radius-full); margin: 0 auto var(--spacing-md); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; font-weight: 700;">
                    KV
                </div>
                <h3 style="margin-bottom: var(--spacing-xs);">Kaelen Vance</h3>
                <p style="color: var(--on-surface-variant); font-size: 0.9rem;">@kaelen.vance</p>
            </div>
            <p style="color: var(--on-surface-variant); line-height: 1.6; text-align: center;">
                Solo chatea con personas en quienes confías. 
                Si no la conoces, es mejor bloquearla.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-danger" onclick="closeModal('modal-conoces')">
                <i class="fas fa-ban"></i> Bloquear
            </button>
            <button class="btn-secondary" onclick="closeModal('modal-conoces')">
                Ignorar
            </button>
            <button class="btn-success" onclick="acceptContact()">
                <i class="fas fa-check"></i> Aceptar
            </button>
        </div>
    </div>
</div>