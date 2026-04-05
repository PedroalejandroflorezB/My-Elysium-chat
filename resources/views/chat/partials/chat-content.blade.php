{{-- SECCIÓN 1: Chat Activo (Oculto si no hay contacto) --}}
<div id="active-chat-view" style="{{ isset($contact) ? 'display: flex;' : 'display: none;' }} flex-direction: column; width: 100%; flex: 1; min-height: 0; overflow: hidden;">
    @include('chat.partials.chat-header', ['contact' => $contact ?? null])
    @include('chat.partials.messages-container', ['messages' => $messages ?? []])
    @include('chat.partials.chat-input', ['contact' => $contact ?? null])
</div>

{{-- SECCIÓN 2: Empty State (Oculto si hay contacto) --}}
<div id="empty-chat-view" class="{{ isset($contact) ? 'hidden' : '' }}" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
    @include('chat.partials.empty-state')
</div>

{{-- MODAL GLOBAL DE SOLICITUD DE CONTACTO --}}
<div id="contact-request-modal" class="modal-overlay hidden" style="z-index: 2000;">
    <div class="modal p2p-modal" style="max-width: 320px;">
        <div class="modal-header">
            <div class="modal-icon">👋</div>
            <h2 class="modal-title">¿Añadir contacto?</h2>
        </div>
        <div class="modal-body text-center">
            <p id="contact-request-text" class="mb-4">¿Deseas añadir a <strong id="contact-request-name">...</strong> a tus contactos?</p>
        </div>
        <div class="modal-footer" style="justify-content: center; padding-top: 0;">
            <button id="btn-reject-contact" class="btn btn-secondary">Rechazar</button>
            <button id="btn-accept-contact" class="btn btn-primary">Aceptar</button>
        </div>
    </div>
</div>

{{-- MODAL ELIMINAR CONTACTO --}}
<div id="contact-remove-modal" class="modal-overlay hidden" style="z-index: 2000;">
    <div class="modal p2p-modal" style="max-width: 320px;">
        <div class="modal-header">
            <div class="modal-icon" style="background: line-gradient(135deg, #ef4444 0%, #b91c1c 100%);">⚠️</div>
            <h2 class="modal-title">¿Eliminar contacto?</h2>
        </div>
        <div class="modal-body text-center">
            <p class="mb-4">¿Estás seguro de que deseas eliminar este contacto?</p>
        </div>
        <div class="modal-footer" style="justify-content: center; padding-top: 0;">
            <button id="btn-cancel-remove-contact" class="btn btn-secondary">No</button>
            <button id="btn-confirm-remove-contact" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Sí, eliminar</button>
        </div>
    </div>
</div>

{{-- Scripts para reinicializar si es necesario --}}
<script>
    window.targetUserId = {{ $contact?->id ?? 'null' }};
    if (window.targetUserId) {
        const ids = [{{ auth()->id() }}, window.targetUserId].sort();
        const roomId = `chat_${ids[0]}_${ids[1]}`;
        window.roomId = roomId;
        
        if (typeof window.subscribeToChatRoom === 'function') {
            window.subscribeToChatRoom(roomId, {{ auth()->id() }}, window.targetUserId);
        }
        
        const container = document.getElementById('messages-container');
        if (container) {
            // No auto-scroll para que el usuario controle la lectura manualmente.
            // Ajuste a preview de último bloque en JS (ver recursos/js/components/messages.js).
            console.log('[CHAT] 🛠️ Scroll automático inicial deshabilitado (modo manual).');
        }
    }
</script>
