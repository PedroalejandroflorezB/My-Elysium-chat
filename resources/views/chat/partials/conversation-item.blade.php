<div class="conversation-item {{ request()->is('chat/' . $otherUser->username) ? ' active' : '' }}" data-chat-link="true" data-username="{{ $otherUser->username }}" data-user-id="{{ $otherUser->id }}" style="cursor: pointer;">
    <div class="conversation-avatar">
        @if($otherUser->avatar)
            <img src="{{ asset('storage/' . $otherUser->avatar) }}" alt="{{ $otherUser->name }}">
        @else
            <div class="avatar-placeholder">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
        @endif
        <div class="status-dot-mini offline" data-status-user-id="{{ $otherUser->id }}"></div>
    </div>
    <div class="conversation-info">
        <div class="conversation-header">
            <span class="conversation-name">{{ $otherUser->name }}</span>
            <span class="conversation-time">{{ $lastMessage->created_at->format('H:i') }}</span>
        </div>
        <div class="conversation-preview">
            @if($lastMessage->sender_id == auth()->id())
                <span class="preview-status">Tú: </span>
            @else
                @php $firstName = strtok($otherUser->name, ' '); @endphp
                <span class="preview-status">{{ $firstName }}: </span>
            @endif
            
            @if(isset($unreadCount) && $unreadCount > 0)
                <span class="unread-badge">{{ $unreadCount }}</span>
            @endif
            <span class="preview-text">{{ \Str::limit($lastMessage->message, 35) }}</span>
        </div>
    </div>
</div>
