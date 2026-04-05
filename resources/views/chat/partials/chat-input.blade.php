<footer class="chat-input-area">
    <form class="chat-input-form" id="message-form">
        @csrf
        <input type="hidden" id="chat-user-id" value="{{ $contact?->id ?? '' }}">
        
        <input type="file" id="file-input" name="file_attachment" accept="*" style="display:none" multiple>
        
        <div class="chat-input-wrapper">
            <button type="button" class="chat-input-tool" id="send-file-btn" title="Adjuntar archivo">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 015 0v10.5c0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5V6H9v9.5a3 3 0 106 0V5a4 4 0 10-8 0v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/>
                </svg>
            </button>

            <textarea 
                id="message-input" 
                name="message_content"
                class="chat-input-field" 
                placeholder="Escribe un mensaje..."
                rows="1"
                autocomplete="off"
            ></textarea>
            
            <button type="submit" class="chat-submit-btn" id="btn-send">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </form>
</footer>
