{{-- Cargar mensajes existentes desde el backend --}}
@if(isset($messages) && $messages->count() > 0)
    @foreach($messages as $message)
        @php
            $isOwn = $message->sender_id === auth()->id();
            $time = \Carbon\Carbon::parse($message->created_at)->format('H:i');
            $avatar = $message->sender->avatar ? asset('storage/' . $message->sender->avatar) : null;
            $initials = strtoupper(substr($message->sender->name, 0, 1));
        @endphp
        
        <div class="message-wrapper {{ $isOwn ? 'message-wrapper--sent' : 'message-wrapper--received' }}">
            @if(!$isOwn)
                <div class="message-avatar" title="{{ $message->sender->name }}">
                    @if($avatar)
                        <img src="{{ $avatar }}" alt="{{ $message->sender->name }}">
                    @else
                        <div class="message-avatar-placeholder">{{ $initials }}</div>
                    @endif
                </div>
            @endif

            <div class="message-bubble {{ $isOwn ? 'message-bubble--sent' : 'message-bubble--received' }}" 
                 id="message-{{ $message->id }}">
                <div class="message-content">
                    <div class="message-text">{{ e($message->message) }}</div>
                    <div class="message-meta">
                        <span class="message-time">{{ $time }}</span>
                        @if($isOwn)
                            <span class="message-status {{ $message->is_read ? 'message-status--read' : '' }}" id="status-{{ $message->id }}">
                                {{ $message->is_read ? '✓✓' : '✓' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="messages-empty-state">
        <div class="empty-icon">💬</div>
        <p class="empty-text">Empieza la conversación</p>
    </div>
@endif

{{-- Los modales P2P están incluidos GLOBALMENTE en layouts/chat.blade.php. NO incluir aquí para evitar IDs duplicados. --}}

{{-- Script de inicialización de modales --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modales al hacer click en overlay
    document.querySelectorAll('.p2p-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function() {
            this.closest('.p2p-modal').classList.add('hidden');
        });
    });
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.p2p-modal:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });
});
</script>
@endpush
