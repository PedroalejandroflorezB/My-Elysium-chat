<!-- resources/views/partials/modals/contact-form.blade.php -->
<div id="modal-contact-form" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 class="modal-title">Añadir Contacto</h2>
        </div>
        <div class="modal-body">
            <form id="contact-form" action="{{ route('contacts.add') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="contact-username">@usuario</label>
                    <input type="text" id="contact-username" name="username" class="form-input" placeholder="Ej: @juan.perez" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="contact-message">Mensaje (opcional)</label>
                    <textarea id="contact-message" name="message" class="form-input" rows="3" placeholder="Mensaje de presentación..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-contact-form')">
                Cancelar
            </button>
            <button type="button" class="btn btn-primary" onclick="submitContactForm()">
                <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function submitContactForm() {
    const form = document.getElementById('contact-form');
    // Lógica de envío...
}
</script>
@endpush
