<div class="messages-container" id="messages-container">
    @include('chat.partials.messages', ['messages' => $messages ?? collect()])
</div>
