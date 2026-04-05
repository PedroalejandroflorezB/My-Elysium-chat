@props(['message', 'isOwn'])

<div class="message-bubble {{ $isOwn ? 'message-bubble--sent' : 'message-bubble--received' }}" id="msg-{{ $message->id }}">
    @if(!$isOwn)
        <div class="message-avatar">
            @if($message->sender?->avatar)
                <img src="{{ asset('storage/' . $message->sender->avatar) }}" alt="{{ $message->sender->name }}">
            @else
                <span>{{ strtoupper(substr($message->sender?->name ?? 'U', 0, 1)) }}</span>
            @endif
        </div>
    @endif
    
    <div class="message-content">
        @if(!$isOwn)
            <div class="message-sender">{{ $message->sender?->name ?? 'Usuario' }}</div>
        @endif
        
        <div class="message-text">{{ $message->message }}</div>
        
        <div class="message-meta">
            <span class="message-time">{{ $message->created_at ? $message->created_at->format('H:i') : now()->format('H:i') }}</span>
            @if($isOwn)
                <span class="message-status">
                    @if($message->is_read)
                        ✓✓
                    @else
                        ✓
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>
