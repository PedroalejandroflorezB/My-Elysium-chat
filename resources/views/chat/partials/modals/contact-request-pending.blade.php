<!-- Modal de Solicitud de Contacto Pendiente -->
<div id="modal-contact-request-pending" class="toast-overlay" style="display: none;">
    <div class="modal modal--neon-small">
        <div class="modal-header">
            <h3 class="neon-title"><i class="fas fa-user-plus"></i> Solicitud Recibida</h3>
        </div>
        <div class="modal-body">
            <div class="pending-user">
                <div class="avatar-sm" id="pendingAvatar"></div>
                <div class="user-info">
                    <div id="pendingName" class="user-name"></div>
                    <div id="pendingUsername" class="user-tag"></div>
                </div>
            </div>
            <p class="neon-text">¿Aceptar contacto?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-request-action btn-reject" data-action="deny">
                <i class="fas fa-times"></i>
            </button>
            <button class="btn-request-action btn-accept" data-action="accept">
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* Diseño Compacto Dark/Neon */
.toast-overlay {
    position: fixed; bottom: 30px; right: 30px;
    z-index: 9999; display: flex; align-items: flex-end; justify-content: flex-end;
}
.modal--neon-small {
    background: #0f0f13; border: 1px solid #00f0ff;
    border-radius: 12px; width: 280px; padding: 15px;
    box-shadow: 0 0 15px rgba(0, 240, 255, 0.2), inset 0 0 10px rgba(0, 240, 255, 0.05);
    animation: neonPop 0.3s ease-out;
}
@keyframes neonPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.modal-header { text-align: center; margin-bottom: 15px; }
.neon-title { color: #00f0ff; font-size: 1rem; margin: 0; font-weight: 600; text-shadow: 0 0 5px rgba(0,240,255,0.5); }
.pending-user { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px; margin-bottom: 12px; }
.avatar-sm { width: 40px; height: 40px; border-radius: 50%; background: #222; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff; overflow: hidden; border: 1px solid #333; }
.avatar-sm img { width: 100%; height: 100%; object-fit: cover; }
.user-name { color: #e1e1e1; font-size: 0.95rem; font-weight: 500; }
.user-tag { color: #888; font-size: 0.8rem; }
.neon-text { color: #aaa; font-size: 0.85rem; text-align: center; margin: 0 0 15px; }
.modal-footer { display: flex; gap: 10px; justify-content: center; }
.btn-request-action { flex: 1; border: none; padding: 10px; border-radius: 8px; cursor: pointer; transition: 0.2s; font-size: 1.1rem; }
.btn-reject { background: rgba(255, 0, 85, 0.1); color: #ff0055; border: 1px solid rgba(255, 0, 85, 0.3); }
.btn-reject:hover { background: #ff0055; color: #fff; box-shadow: 0 0 10px #ff0055; }
.btn-accept { background: rgba(0, 255, 170, 0.1); color: #00ffaa; border: 1px solid rgba(0, 255, 170, 0.3); }
.btn-accept:hover { background: #00ffaa; color: #fff; box-shadow: 0 0 10px #00ffaa; }
.btn-request-action:disabled { opacity: 0.5; pointer-events: none; }
</style>

<script>
// Exponer la función de apertura globalmente para Echo y el script de carga
window.showPendingRequestModal = function(userData) {
    const modal = document.getElementById('modal-contact-request-pending');
    if (!modal) return;
    
    document.getElementById('pendingName').textContent = userData.name || '';
    document.getElementById('pendingUsername').textContent = userData.username || '';
    
    const avatarEl = document.getElementById('pendingAvatar');
    if (userData.avatar) {
        avatarEl.innerHTML = '<img src="' + userData.avatar + '" alt="">';
    } else {
        avatarEl.innerHTML = (userData.name || 'U').charAt(0).toUpperCase();
    }
    
    // Guardar ID y username en dataset del modal
    modal.dataset.requestId = userData.request_id;
    modal.dataset.username = userData.username || userData.name;
    modal.style.display = 'flex';
};

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-contact-request-pending');
    
    if (modal) {
        // Usar delegación de eventos para optimizar memoria
        modal.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-request-action');
            if (!btn) return;
            
            const action = btn.dataset.action;
            const requestId = modal.dataset.requestId;
            if (!requestId) return;
            
            // UI state: deshabilitar botones
            const buttons = modal.querySelectorAll('.btn-request-action');
            buttons.forEach(b => b.disabled = true);
            
            try {
                const response = await fetch('/api/chat/respond-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ request_id: requestId, action: action })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cerrar modal solo al confirmar éxito
                    modal.style.display = 'none';
                    // Recargar o navegar a la conversación
                    if (action === 'accept') {
                        const targetUser = modal.dataset.username;
                        window.location.href = '/chat/' + targetUser; 
                    }
                } else {
                    console.error('Error:', data.message);
                    showToast('Error', data.message || 'No se pudo procesar la solicitud', 'error');
                }
            } catch (err) {
                console.error('Fetch error:', err);
                showToast('Error', 'Error de conexión al servidor', 'error');
            } finally {
                // Restaurar botones por si hubo error o si no se recargó la página
                buttons.forEach(b => b.disabled = false);
            }
        });
    }
});
</script>