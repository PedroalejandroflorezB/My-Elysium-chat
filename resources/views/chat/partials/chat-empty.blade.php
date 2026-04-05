<main class="main-content">
    <div class="chat-empty">
        <div class="chat-empty-icon">
            <i class="fas fa-comments"></i>
        </div>
        <h2>¡Empecemos!</h2>
        <p>
            Este chat está vacío. Para comenzar, busca un contacto 
            o comparte tu @usuario para que te encuentren.
        </p>
        
        <!-- User Card -->
        <div class="user-card">
            <p class="user-card__label">Tu usuario</p>
            <p class="user-card__username" id="current-username">
                {{ auth()->check() ? (auth()->user()->username ?? '@'.strtolower(str_replace(' ', '_', auth()->user()->name ?? 'usuario'))) : '@tu_nombre' }}
            </p>
            <button class="btn btn-primary" onclick="copyUsername()">
                <i class="fas fa-copy"></i> Copiar @usuario
            </button>
            <!-- QR Generate button for PC (always visible) -->
            <button class="btn btn-secondary qr-generate-btn" onclick="generateQR()">
                <i class="fas fa-qrcode"></i> Generar QR
            </button>
        </div>
        
        <div class="btn-actions">
            <button class="btn btn-secondary" onclick="showAddUserQR()">
                <i class="fas fa-user-plus"></i> Añadir Usuario
            </button>
            <!-- QR Scan button for Android only -->
            <button class="btn btn-secondary qr-scan-btn" onclick="showQRModal()" style="display: none;">
                <i class="fas fa-qrcode"></i> Escanear QR
            </button>
        </div>

        <script>
        // Platform-specific QR button visibility
        document.addEventListener('DOMContentLoaded', function() {
            const scanBtn = document.querySelector('.qr-scan-btn');
            const generateBtn = document.querySelector('.qr-generate-btn');
            
            if (isAndroid()) {
                // Android: Show both scan and generate buttons
                if (scanBtn) scanBtn.style.display = 'inline-flex';
                if (generateBtn) generateBtn.style.display = 'inline-flex';
            } else {
                // PC: Only show generate button
                if (scanBtn) scanBtn.style.display = 'none';
                if (generateBtn) generateBtn.style.display = 'inline-flex';
            }
        });

        // Función para mostrar QR de añadir usuario
        function showAddUserQR() {
            if (isAndroid()) {
                // En Android, mostrar ambas opciones
                showAddUserOptions();
            } else {
                // En PC, generar QR directamente
                generateQR();
            }
        }

        function showAddUserOptions() {
            // Crear modal temporal con opciones para Android
            const modalHtml = `
                <div id="add-user-options-modal" class="modal-overlay" style="display: flex;">
                    <div class="modal">
                        <button class="modal-close" onclick="closeModal('add-user-options-modal')">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="modal-header">
                            <div class="modal-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h2 class="modal-title">Añadir Usuario</h2>
                        </div>
                        <div class="modal-body">
                            <p style="text-align: center; margin-bottom: 1.5rem; color: var(--text-muted);">
                                Elige cómo quieres añadir un nuevo contacto:
                            </p>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <button class="btn btn-primary" onclick="closeModal('add-user-options-modal'); generateQR();" style="width: 100%;">
                                    <i class="fas fa-qrcode"></i> Generar mi QR
                                </button>
                                <button class="btn btn-secondary" onclick="closeModal('add-user-options-modal'); showQRModal();" style="width: 100%;">
                                    <i class="fas fa-camera"></i> Escanear QR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Insertar modal en el DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.body.style.overflow = 'hidden';
        }
        </script>
    </div>
</main>