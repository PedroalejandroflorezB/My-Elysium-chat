@php
    $unreadMessagesCount = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count();
    $contactsCount = \App\Models\Contact::where('user_id', auth()->id())->count();
@endphp

<div class="sidebar">
    <div class="sidebar__header">
        <h3 class="sidebar__title">MENSAJES</h3>
        
        {{-- Buscador Mejorado --}}
        <div class="sidebar__search">
            <div class="search-input-wrapper">
                <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                </svg>
                <input type="text" id="search-input" placeholder="Buscar por nombre o @usuario" class="sidebar__search-input" autocomplete="off">
                <button class="search-clear" id="search-clear" style="display: none;" onclick="clearSearch()">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            
            {{-- ✅ CONTENEDOR DE RESULTADOS - Dropdown --}}
            <div id="search-results" class="search-results-container" style="display: none;"></div>
        </div>
        
        {{-- Tabs de Navegación Mejorados --}}
        <div class="sidebar__nav">
            <button class="sidebar__nav-btn active" data-tab="messages" onclick="switchSidebarTab('messages')" title="Mensajes">
                <div class="nav-icon-wrapper">
                    <svg class="nav-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                    </svg>
                </div>
                <span class="nav-badge" id="badge-messages">{{ $unreadMessagesCount }}</span>
            </button>
            
            <button class="sidebar__nav-btn" data-tab="contacts" onclick="switchSidebarTab('contacts')" title="Contactos">
                <div class="nav-icon-wrapper">
                    <svg class="nav-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <span class="nav-badge nav-badge--secondary" id="badge-contacts">{{ $contactsCount }}</span>
            </button>
            
            <button class="sidebar__nav-btn sidebar__nav-btn--files" data-tab="files" onclick="switchSidebarTab('files')" title="Archivos">
                <div class="nav-icon-wrapper">
                    <svg class="nav-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    <div class="nav-icon-fill" id="files-icon-fill"></div>
                </div>
                <span class="nav-badge nav-badge--primary" id="badge-files">0</span>
            </button>
        </div>
    </div>
    
    {{-- Contenido de Tabs --}}
    <div class="sidebar__content">
        {{-- Tab Mensajes --}}
        <div id="all-messages-list" class="sidebar__tab active">
            <div class="conversations-list">
                @php
                    $conversationsRec = \App\Models\Message::where(function($q) {
                            $q->where('sender_id', auth()->id())->where('deleted_for_sender', false);
                        })
                        ->orWhere(function($q) {
                            $q->where('receiver_id', auth()->id())->where('deleted_for_receiver', false);
                        })
                        ->selectRaw('LEAST(sender_id, receiver_id) as user1, GREATEST(sender_id, receiver_id) as user2, MAX(created_at) as last_message_at')
                        ->groupBy('user1', 'user2')
                        ->orderBy('last_message_at', 'desc')
                        ->limit(50)
                        ->get();
                @endphp

                @if($conversationsRec->isEmpty())
                    <div class="empty-state">
                        <p>No tienes conversaciones aún</p>
                    </div>
                @else
                    @foreach($conversationsRec as $conv)
                        @php
                            $otherUserId = $conv->user1 == auth()->id() ? $conv->user2 : $conv->user1;
                            $otherUser = \App\Models\User::find($otherUserId);
                            $lastMessage = \App\Models\Message::where(function($q) use ($conv) {
                                    $q->where('sender_id', $conv->user1)->where('receiver_id', $conv->user2);
                                })->orWhere(function($q) use ($conv) {
                                    $q->where('sender_id', $conv->user2)->where('receiver_id', $conv->user1);
                                })->orderBy('created_at', 'desc')->first();
                            
                            // Contar mensajes no leídos
                            $unreadCount = \App\Models\Message::where('sender_id', $otherUserId)
                                ->where('receiver_id', auth()->id())
                                ->where('is_read', false)
                                ->count();
                        @endphp
                        @if($otherUser && $lastMessage)
                            @include('chat.partials.conversation-item', ['otherUser' => $otherUser, 'lastMessage' => $lastMessage])
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
        
        {{-- Tab Contactos --}}
        <div id="contacts-list" class="sidebar__tab" style="display: none;">
            <div class="contacts-list-container">
                @php
                    $contacts = \App\Models\Contact::where('user_id', auth()->id())->with('contact')->get();
                @endphp
                @foreach($contacts as $contact_item)
                    @if($contact_item->contact)
                        @php
                            $c = $contact_item->contact;
                            $avatarSrc = $c->avatar
                                ? (str_starts_with($c->avatar, 'data:') ? $c->avatar : asset('storage/' . $c->avatar))
                                : null;
                        @endphp
                        <div class="contact-item"
                             data-username="{{ $c->username }}"
                             data-user-id="{{ $c->id }}"
                             style="cursor: pointer;"
                             onclick="if(typeof window.navigateToChat==='function'){window.navigateToChat('{{ $c->username }}')}else{window.location.href='/@{{ $c->username }}'}">
                            <div class="contact-avatar">
                                @if($avatarSrc)
                                    <img src="{{ $avatarSrc }}" alt="{{ $c->name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        <span style="font-weight:bold;font-size:0.9rem;">{{ strtoupper(substr($c->name, 0, 1)) }}</span>
                                    </div>
                                @endif
                                <div class="status-dot-mini offline" data-status-user-id="{{ $c->id }}"></div>
                            </div>
                            <div class="contact-info">
                                <span class="contact-name">{{ $c->name }}</span>
                                <span class="contact-status">Desconectado</span>
                            </div>
                        </div>
                    @endif
                @endforeach
                @if($contacts->isEmpty())
                    <div class="empty-state">
                        <p>No tienes contactos aún</p>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Tab Archivos --}}
        <div id="tab-files" class="sidebar__tab" style="display: none;">
            <div class="files-header">
                <h3>Transferencias</h3>
                <span class="files-counter" id="active-transfers-count">0 activas</span>
            </div>
            
            <div class="transfers-list" id="active-transfers">
                <div class="empty-state" id="no-active-transfers">
                    <span class="empty-state__icon">📁</span>
                    <p>Sin transferencias activas</p>
                </div>
            </div>
            
            <div class="transfers-history">
                <h4 class="history-title">Completadas</h4>
                <div id="completed-list"></div>
            </div>
        </div>
    </div>
</div>



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[SIDEBAR] DOM cargado');
    
    // Obtener IDs
    const currentUserId = {{ auth()->id() }};
    const chatUserIdInput = document.getElementById('chat-user-id');
    
    // Calcular roomId
    let roomId = null;
    
    if (chatUserIdInput && chatUserIdInput.value) {
        const targetUserId = parseInt(chatUserIdInput.value);
        const ids = [currentUserId, targetUserId].sort((a, b) => a - b);
        roomId = `chat_${ids[0]}_${ids[1]}`;
        window.roomId = roomId;
        console.log('[SIDEBAR] ✅ Room ID:', roomId);
    } else {
        console.log('[SIDEBAR] ℹ️ Empty state - roomId: null');
        window.roomId = null;
    }
    
    // Guardar para acceso global
    window.currentUserId = currentUserId;
    
    // Inicializar features
    if (typeof window.initializeChatFeatures === 'function') {
        window.initializeChatFeatures();
    }
    
    // Refrescar lista inicial
    if (typeof window.refreshConversationsList === 'function') {
        window.refreshConversationsList();
    }
});

// ✅ TABS - Función corregida
window.switchSidebarTab = function(tabName) {
    const messages = document.getElementById('all-messages-list');
    const contacts = document.getElementById('contacts-list');
    const files = document.getElementById('tab-files');
    
    // Hide tabs only
    if(messages) messages.style.display = 'none';
    if(contacts) contacts.style.display = 'none';
    if(files) files.style.display = 'none';
    
    // Show specific tab
    if(tabName === 'messages' && messages) messages.style.display = 'block';
    if(tabName === 'contacts' && contacts) contacts.style.display = 'block';
    if(tabName === 'files' && files) files.style.display = 'block';
    
    // Update button classes
    document.querySelectorAll('.sidebar__nav-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
};

// ✅ FUNCIÓN AUXILIAR: Ocultar resultados de búsqueda
window.hideSearchResults = function() {
    const results = document.getElementById('search-results');
    if (results) {
        results.style.display = 'none';
        results.innerHTML = '';
        results.classList.remove('has-results');
    }
};

// ✅ FUNCIÓN AUXILIAR: Limpiar búsqueda
window.clearSearch = function() {
    const searchInput = document.getElementById('search-input');
    const clearBtn = document.getElementById('search-clear');
    
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }
    
    window.hideSearchResults();
};



// Aliases
window.showAllMessagesTab = (e) => (e?.preventDefault(), window.switchSidebarTab('messages'));
window.showContactsTab = (e) => (e?.preventDefault(), window.switchSidebarTab('contacts'));
window.showFilesTab = (e) => (e?.preventDefault(), window.switchSidebarTab('files'));
</script>
@endpush