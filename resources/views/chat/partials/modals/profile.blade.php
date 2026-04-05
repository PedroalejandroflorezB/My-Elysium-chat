<!-- Modal de Perfil -->
<div id="profile-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Mi Perfil</h2>
        </div>
        
        <div class="modal-body">
            <!-- Avatar Section Compacta -->
            <div class="profile-avatar-section">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar-container">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="Avatar" id="modal-avatar-preview">
                        @else
                            <div class="profile-avatar-placeholder" id="modal-avatar-initials">
                                <i class="fas fa-user"></i>
                            </div>
                            <img id="modal-avatar-preview" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        @endif
                    </div>
                    
                    <!-- Camera Button -->
                    <button class="avatar-change-btn" onclick="document.getElementById('avatar-input').click()" title="Cambiar imagen">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <!-- Hidden File Input -->
                <input type="file" 
                       id="avatar-input" 
                       accept="image/*" 
                       style="display: none;" 
                       onchange="previewAvatar(event)">
            </div>
            
            <!-- User Info Compacta -->
            <div class="profile-info">
                <!-- Nombre -->
                <div class="profile-field">
                    <label for="profile-name" class="profile-label">NOMBRE</label>
                    <input 
                        type="text" 
                        id="profile-name" 
                        name="name"
                        class="profile-input"
                        value="{{ auth()->user()->name }}"
                        readonly
                        autocomplete="name"
                    >
                </div>
                
                <!-- Username -->
                <div class="profile-field">
                    <label for="profile-username" class="profile-label">USUARIO</label>
                    <input 
                        type="text" 
                        id="profile-username" 
                        name="username"
                        class="profile-input"
                        value="{{ auth()->user()->username }}"
                        readonly
                        autocomplete="username"
                    >
                </div>

                <!-- Contraseña — solo visible en modo edición -->
                <div class="profile-field" id="profile-password-section" style="display:none;">
                    <label for="profile-new-password" class="profile-label">NUEVA CONTRASEÑA</label>
                    <input 
                        type="password" 
                        id="profile-new-password"
                        class="profile-input"
                        placeholder="Dejar vacío para no cambiar"
                        autocomplete="new-password"
                    >
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn--secondary" onclick="closeProfileModal()">Cerrar</button>
            <button class="btn btn--primary" id="profile-edit-btn" onclick="toggleProfileEdit()">
                <i class="fas fa-pen"></i> Editar
            </button>
            <button class="btn btn--primary" id="save-avatar-btn" onclick="saveAvatar()" style="display: none;">
                <i class="fas fa-save"></i> Guardar
            </button>
            <button class="btn btn--primary" id="save-profile-btn" onclick="saveProfile()" style="display: none;">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para el modal de perfil - MISMAS DIMENSIONES QUE SEGURIDAD */
#profile-modal .modal-content {
    max-width: 400px;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

#profile-modal .modal-header {
    padding: 0 0 1rem 0;
    text-align: center;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

#profile-modal .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

#profile-modal .modal-body {
    padding: 0;
}

#profile-modal .modal-footer {
    padding: 1rem 0 0 0;
    border-top: 1px solid var(--border-color);
    margin-top: 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.profile-avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.profile-avatar-wrapper {
    position: relative;
    width: 60px;
    height: 60px;
}

.profile-avatar-container {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    position: relative;
}

.profile-avatar-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-gradient);
    border-radius: 50%;
    font-size: 1.25rem;
    color: white;
}

.avatar-change-btn {
    background: var(--primary);
    border: 2px solid var(--bg-secondary);
    color: white;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    position: absolute;
    bottom: -2px;
    right: -2px;
    z-index: 10;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    font-size: 0.6rem;
}

.avatar-change-btn:hover {
    background: var(--primary-dark);
    transform: scale(1.1);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.profile-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.profile-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.profile-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    font-size: 0.8rem;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.profile-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.profile-input[readonly] {
    cursor: default;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-secondary);
}

.profile-input:not([readonly]) {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--primary);
    color: var(--text-primary);
    cursor: text;
}

.modal-footer .btn {
    flex: 1;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    max-width: 140px;
}

.modal-footer .btn--primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.modal-footer .btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.modal-footer .btn--secondary {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.modal-footer .btn--secondary:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
}

/* Responsive */
@media (max-width: 640px) {
    #profile-modal .modal-content {
        max-width: 340px;
        margin: 1rem;
        padding: 1.25rem;
    }
    
    .modal-footer {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .modal-footer .btn {
        width: 100%;
        max-width: none;
    }
    
    .profile-avatar-wrapper {
        width: 50px;
        height: 50px;
    }
    
    .profile-avatar-container {
        width: 50px;
        height: 50px;
    }
    
    .avatar-change-btn {
        width: 18px;
        height: 18px;
        font-size: 0.55rem;
    }
}
</style>

<script>
// Variables globales
let avatarFile = null;

function previewAvatar(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        if (typeof showToast === 'function') {
            showToast('Imagen inválida', 'Por favor selecciona una imagen válida (JPG, PNG)', 'error');
        } else {
            alert('Por favor selecciona una imagen válida (JPG, PNG)');
        }
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) { // Reducido a 2MB para t3.micro
        if (typeof showToast === 'function') {
            showToast('Archivo muy grande', 'La imagen no debe superar 2MB', 'error');
        } else {
            alert('La imagen no debe superar 2MB');
        }
        return;
    }
    
    avatarFile = file;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('modal-avatar-preview');
        const placeholder = document.getElementById('modal-avatar-initials');
        
        if (img) {
            img.src = e.target.result;
            img.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        
        const saveBtn = document.getElementById('save-avatar-btn');
        if (saveBtn) saveBtn.style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

async function saveAvatar() {
    if (!avatarFile) {
        if (typeof showToast === 'function') {
            showToast('Error', 'No hay imagen para guardar', 'warning');
        }
        return;
    }
    
    const btn = document.getElementById('save-avatar-btn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        // Convertir imagen a base64 con compresión
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = async function() {
            // Redimensionar a máximo 100x100 para ahorrar espacio
            const maxSize = 100;
            let { width, height } = img;
            
            if (width > height) {
                if (width > maxSize) {
                    height = (height * maxSize) / width;
                    width = maxSize;
                }
            } else {
                if (height > maxSize) {
                    width = (width * maxSize) / height;
                    height = maxSize;
                }
            }
            
            canvas.width = width;
            canvas.height = height;
            
            // Dibujar imagen redimensionada
            ctx.drawImage(img, 0, 0, width, height);
            
            // Convertir a base64 con calidad reducida
            const avatarData = canvas.toDataURL('image/jpeg', 0.7);
            
            // Enviar al servidor
            const response = await fetch('/api/profile/avatar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ avatar_data: avatarData })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast('Éxito', 'Avatar actualizado correctamente', 'success');
                }
                
                // Actualizar avatar en topbar
                const topbarAvatar = document.querySelector('.user-avatar-img');
                if (topbarAvatar && data.avatar) {
                    if (topbarAvatar.tagName === 'IMG') {
                        topbarAvatar.src = data.avatar;
                    } else {
                        // Reemplazar div con imagen
                        const img = document.createElement('img');
                        img.src = data.avatar;
                        img.alt = 'Avatar';
                        img.className = 'user-avatar-img';
                        topbarAvatar.parentNode.replaceChild(img, topbarAvatar);
                    }
                }
                
                // Ocultar botón guardar
                btn.style.display = 'none';
                avatarFile = null;
                
            } else {
                if (typeof showToast === 'function') {
                    showToast('Error', data.message || 'No se pudo guardar el avatar', 'error');
                } else {
                    alert(data.message || 'No se pudo guardar el avatar');
                }
            }
            
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        };
        
        img.onerror = function() {
            if (typeof showToast === 'function') {
                showToast('Error', 'Error al procesar la imagen', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        };
        
        // Crear URL de la imagen
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
        };
        reader.readAsDataURL(avatarFile);
        
    } catch (error) {
        console.error('Error al guardar:', error);
        if (typeof showToast === 'function') {
            showToast('Error', 'Error de conexión al guardar', 'error');
        } else {
            alert('Error de conexión al guardar');
        }
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

function openProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Reset avatar preview
        avatarFile = null;
        const saveBtn = document.getElementById('save-avatar-btn');
        if (saveBtn) saveBtn.style.display = 'none';
        
        const avatarInput = document.getElementById('avatar-input');
        if (avatarInput) avatarInput.value = '';
        
        // Restaurar avatar original si había preview
        const img = document.getElementById('modal-avatar-preview');
        const placeholder = document.getElementById('modal-avatar-initials');
        
        @if(auth()->user()->avatar)
            if (img) {
                img.src = "{{ auth()->user()->avatar }}";
                img.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
        @else
            if (img) img.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
        @endif
    }
}

function toggleProfileEdit() {
    const nameInput      = document.getElementById('profile-name');
    const usernameInput  = document.getElementById('profile-username');
    const passwordSection = document.getElementById('profile-password-section');
    const editBtn        = document.getElementById('profile-edit-btn');
    const saveBtn        = document.getElementById('save-profile-btn');
    const isEditing      = !nameInput.readOnly;

    if (isEditing) {
        // Cancelar — restaurar valores y ocultar contraseña
        nameInput.value     = '{{ auth()->user()->name }}';
        usernameInput.value = '{{ auth()->user()->username }}';
        document.getElementById('profile-new-password').value = '';
        nameInput.readOnly     = true;
        usernameInput.readOnly = true;
        passwordSection.style.display = 'none';
        editBtn.innerHTML = '<i class="fas fa-pen"></i> Editar';
        saveBtn.style.display = 'none';
    } else {
        // Activar edición — mostrar campo contraseña
        nameInput.readOnly     = false;
        usernameInput.readOnly = false;
        passwordSection.style.display = 'flex';
        nameInput.focus();
        editBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
        saveBtn.style.display = 'flex';
    }
}

async function saveProfile() {
    const name     = document.getElementById('profile-name').value.trim();
    const username = document.getElementById('profile-username').value.trim().replace('@', '');
    const password = document.getElementById('profile-new-password').value;
    const btn      = document.getElementById('save-profile-btn');

    if (!name || !username) {
        if (typeof showToast === 'function') showToast('Error', 'Nombre y usuario son obligatorios', 'error');
        return;
    }

    const originalHTML = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const payload = { name, username };
    if (password) {
        if (password.length < 8) {
            if (typeof showToast === 'function') showToast('Error', 'La contraseña debe tener al menos 8 caracteres', 'error');
            btn.disabled  = false;
            btn.innerHTML = originalHTML;
            return;
        }
        payload.password = password;
        payload.password_confirmation = password;
    }

    try {
        const response = await fetch('/profile', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            if (typeof showToast === 'function') showToast('Éxito', 'Perfil actualizado', 'success');

            // Volver a modo lectura
            document.getElementById('profile-name').readOnly     = true;
            document.getElementById('profile-username').readOnly = true;
            document.getElementById('profile-new-password').value = '';
            document.getElementById('profile-password-section').style.display = 'none';
            document.getElementById('profile-edit-btn').innerHTML = '<i class="fas fa-pen"></i> Editar';
            btn.style.display = 'none';
        } else {
            const msg = data.errors?.username?.[0] || data.errors?.name?.[0] || data.errors?.password?.[0] || data.message || 'Error al guardar';
            if (typeof showToast === 'function') showToast('Error', msg, 'error');
        }
    } catch (err) {
        if (typeof showToast === 'function') showToast('Error', 'Error de conexión', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalHTML;
    }
}

// Asegurar que las funciones sean globales
window.openProfileModal  = openProfileModal;
window.closeProfileModal = closeProfileModal;
window.previewAvatar     = previewAvatar;
window.saveAvatar        = saveAvatar;
window.toggleProfileEdit = toggleProfileEdit;
window.saveProfile       = saveProfile;

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileModal();
    }
});

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    if (e.target.id === 'profile-modal') {
        closeProfileModal();
    }
});
</script>