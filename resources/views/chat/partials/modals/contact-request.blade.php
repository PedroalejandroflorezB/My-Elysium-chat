<!-- Modal de Solicitud de Contacto Recibida -->
<div id="modal-contact-request" class="modal-overlay">
    <div class="modal modal--contact-request">
        <div class="modal-header">
            <div class="modal-icon"><i class="fas fa-user-plus"></i></div>
            <h2 class="modal-title">Nueva Solicitud</h2>
        </div>
        <div class="modal-body">
            <div class="contact-request-info">
                <div class="contact-request-avatar" id="request-sender-avatar"></div>
                <p class="contact-request-text">
                    <strong id="request-sender-name">Nombre Usuario</strong> 
                    (<span id="request-sender-username">@usuario</span>) 
                    te ha enviado una solicitud de contacto.
                </p>
            </div>
            <div class="contact-request-actions">
                <button class="btn btn-danger" onclick="closeContactRequestModal()">
                    <i class="fas fa-times"></i> Denegar
                </button>
                <button class="btn btn-success" onclick="respondToContactRequest(document.getElementById('modal-contact-request').dataset.requestId, 'accept')">
                    <i class="fas fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.modal--contact-request { max-width: 420px; text-align: center; }
.contact-request-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;
    color: white; font-size: 2rem; font-weight: 700; overflow: hidden;
}
.contact-request-avatar img { width: 100%; height: 100%; object-fit: cover; }
.contact-request-text { color: var(--on-surface-variant); line-height: 1.6; margin-bottom: 1.5rem; }
.contact-request-text strong { color: var(--on-surface); }
.contact-request-actions { display: flex; gap: 1rem; justify-content: center; }
.contact-request-actions .btn { flex: 1; max-width: 140px; padding: 0.75rem 1rem; }
</style>
