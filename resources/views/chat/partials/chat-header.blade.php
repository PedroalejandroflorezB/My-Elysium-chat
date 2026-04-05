<header class="chat-header">
    <button class="chat-header__back" onclick="window.backToChatList()" style="display: none; background: none; border: none; font-size: 1.5rem; color: var(--text-primary); cursor: pointer; padding: 0.5rem; align-items: center; justify-content: center;">
        ←
    </button>
    <div class="chat-header__contact">
        <div class="chat-header__avatar">
            @if($contact?->avatar)
                <img src="{{ asset('storage/' . $contact->avatar) }}" alt="{{ $contact->name }}">
            @else
                <span class="user-avatar-initials">{{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}</span>
            @endif
            <div class="avatar-status-badge" id="chat-header-status-badge"></div>
        </div>
        <div class="chat-header__info" style="justify-content: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2px;">
                <h3 class="chat-header__name" id="chat-username" style="line-height: 1;">{{ $contact?->name ?? 'Usuario' }}</h3>
                <button id="add-contact-btn" data-user-id="{{ $contact?->id }}" class="btn-contact-state hidden" title="Agregar contacto">
                    <span class="icon" style="font-size: 0.9rem;">➕</span>
                </button>
            </div>
            <span id="chat-header-status-text" class="status-text" style="font-size: 0.75rem; opacity: 0.6; line-height: 1;">Desconectado</span>
        </div>
    </div>
    
    <input type="hidden" id="chat-user-id" value="{{ $contact?->id }}">
    
    <div class="chat-header__actions">
        <!-- Dropdown de opciones -->
        <div class="chat-header__dropdown">
            <button class="chat-header__action" onclick="toggleChatMenu()" title="Más opciones">
                ⋮
            </button>
            
            <div class="dropdown-menu" id="chat-dropdown-menu">
                <button class="dropdown-item" onclick="viewContactProfile()">
                    <span class="icon">👤</span>
                    Ver perfil
                </button>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item" onclick="closeCurrentChat()">
                    <span class="icon">✕</span>
                    Cerrar chat
                </button>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item text-warning" onclick="deleteChatForMe()">
                    <span class="icon">🗑</span>
                    Borrar para mí
                </button>
                <button class="dropdown-item text-danger" onclick="deleteChatForAll()">
                    <span class="icon">🗑</span>
                    Borrar para todos
                </button>
            </div>
        </div>
    </div>
</header>
