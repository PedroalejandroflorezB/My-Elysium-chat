/**
 * Módulo de Perfil de Usuario
 * Gestiona modal de perfil y actualización de datos
 */

// Variable global para el archivo de avatar seleccionado
let avatarFile = null;

/**
 * Abrir modal de perfil
 */
export function openProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Cerrar modal de perfil
 */
export function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        resetProfileModal();
    }
}

/**
 * Resetear estado del modal
 */
function resetProfileModal() {
    // Limpiar avatar seleccionado
    avatarFile = null;
    const saveAvatarBtn = document.getElementById('save-avatar-btn');
    if (saveAvatarBtn) saveAvatarBtn.style.display = 'none';
    
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) avatarInput.value = '';
    
    // Cancelar edición si estaba activa
    const nameInput = document.getElementById('profile-name');
    if (nameInput && !nameInput.readOnly) {
        toggleProfileEdit();
    }
}

/**
 * Vista previa del avatar al seleccionar archivo
 * Soporta tanto event.target como input directo
 */
export function previewAvatar(eventOrInput) {
    // Soportar ambas firmas: previewAvatar(event) y previewAvatar(input)
    let input;
    if (eventOrInput.target) {
        // Es un evento
        input = eventOrInput.target;
    } else {
        // Es el input directamente
        input = eventOrInput;
    }
    
    const file = input.files?.[0];
    if (!file) return;
    
    // Validar tipo
    if (!file.type.startsWith('image/')) {
        showToast?.('Imagen inválida', 'Por favor selecciona una imagen válida (JPG, PNG)', 'error');
        input.value = '';
        return;
    }
    
    // Validar tamaño (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        showToast?.('Archivo muy grande', 'La imagen no debe superar 2MB', 'error');
        input.value = '';
        return;
    }
    
    avatarFile = file;
    
    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('modal-avatar-preview');
        const placeholder = document.getElementById('modal-avatar-initials');
        
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Mostrar botón guardar
        const saveBtn = document.getElementById('save-avatar-btn');
        if (saveBtn) saveBtn.style.display = 'flex';
    };
    reader.readAsDataURL(file);
    
    showToast?.('Avatar seleccionado', file.name, 'info');
}

/**
 * Guardar el avatar con compresión
 */
export async function saveAvatar() {
    if (!avatarFile) {
        showToast?.('Error', 'No hay imagen para guardar', 'warning');
        return;
    }
    
    const btn = document.getElementById('save-avatar-btn');
    if (!btn) return;
    
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        // Convertir imagen a base64 con compresión
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = async function() {
            try {
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ avatar_data: avatarData })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast?.('Éxito', 'Avatar actualizado correctamente', 'success');
                    
                    // Actualizar avatar en header/topbar
                    const avatarElements = document.querySelectorAll('.user-avatar-img, .user-avatar');
                    if (data.avatar) {
                        avatarElements.forEach(el => {
                            if (el.tagName === 'IMG') {
                                el.src = data.avatar;
                            } else {
                                el.style.backgroundImage = `url(${data.avatar})`;
                            }
                        });
                    }
                    
                    // Ocultar botón guardar
                    btn.style.display = 'none';
                    avatarFile = null;
                    
                } else {
                    showToast?.('Error', data.message || 'No se pudo guardar el avatar', 'error');
                }
                
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            } catch (err) {
                console.error('[PROFILE] Error procesando imagen:', err);
                showToast?.('Error', 'Error al procesar la imagen', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        };
        
        img.onerror = function() {
            showToast?.('Error', 'Error al cargar la imagen', 'error');
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
        console.error('[PROFILE] Error al guardar:', error);
        showToast?.('Error', 'Error de conexión al guardar', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

/**
 * Alternar modo edición del perfil
 */
export function toggleProfileEdit() {
    const nameInput = document.getElementById('profile-name');
    const usernameInput = document.getElementById('profile-username');
    const passwordSection = document.getElementById('profile-password-section');
    const editBtn = document.getElementById('profile-edit-btn');
    const saveBtn = document.getElementById('save-profile-btn');
    
    if (!nameInput) return;
    
    const isEditing = !nameInput.readOnly;

    if (isEditing) {
        // Cancelar edición
        nameInput.value = document.querySelector('meta[data-user-name]')?.content || nameInput.value;
        if (usernameInput) {
            usernameInput.value = document.querySelector('meta[data-user-username]')?.content || usernameInput.value;
        }
        const passwordEl = document.getElementById('profile-new-password');
        if (passwordEl) passwordEl.value = '';
        nameInput.readOnly = true;
        if (usernameInput) usernameInput.readOnly = true;
        if (passwordSection) passwordSection.style.display = 'none';
        if (editBtn) editBtn.innerHTML = '<i class="fas fa-pen"></i> Editar';
        if (saveBtn) saveBtn.style.display = 'none';
    } else {
        // Activar edición
        nameInput.readOnly = false;
        if (usernameInput) usernameInput.readOnly = false;
        if (passwordSection) passwordSection.style.display = 'flex';
        nameInput.focus();
        if (editBtn) editBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
        if (saveBtn) saveBtn.style.display = 'flex';
    }
}

/**
 * Guardar los cambios del perfil
 */
export async function saveProfile() {
    const nameInput = document.getElementById('profile-name');
    const usernameInput = document.getElementById('profile-username');
    const passwordInput = document.getElementById('profile-new-password');
    const btn = document.getElementById('save-profile-btn');
    
    if (!nameInput || !btn) {
        showToast?.('Error', 'Elementos del formulario no encontrados', 'error');
        return;
    }
    
    const name = nameInput.value?.trim();
    const username = usernameInput?.value?.trim()?.replace('@', '') || '';
    const password = passwordInput?.value || '';
    
    // Validar campos requeridos
    if (!name || !username) {
        showToast?.('Error', 'Nombre y usuario son obligatorios', 'error');
        return;
    }
    
    // Validar contraseña si se proporciona
    if (password && password.length < 8) {
        showToast?.('Error', 'La contraseña debe tener al menos 8 caracteres', 'error');
        return;
    }
    
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    try {
        const payload = { name, username };
        if (password) {
            payload.password = password;
            payload.password_confirmation = password;
        }
        
        const response = await fetch('/profile', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast?.('Éxito', 'Perfil actualizado correctamente', 'success');
            
            // Volver a modo lectura
            nameInput.readOnly = true;
            if (usernameInput) usernameInput.readOnly = true;
            if (passwordInput) passwordInput.value = '';
            
            const passwordSection = document.getElementById('profile-password-section');
            if (passwordSection) passwordSection.style.display = 'none';
            
            const editBtn = document.getElementById('profile-edit-btn');
            if (editBtn) editBtn.innerHTML = '<i class="fas fa-pen"></i> Editar';
            
            btn.style.display = 'none';
            
            // Actualizar avatar en header si cambió
            if (data.avatar) {
                const avatarElements = document.querySelectorAll('.user-avatar-img, .user-avatar');
                avatarElements.forEach(el => {
                    if (el.tagName === 'IMG') {
                        el.src = data.avatar;
                    } else {
                        el.style.backgroundImage = `url(${data.avatar})`;
                    }
                });
            }
        } else {
            const errorMsg = data.errors?.username?.[0] || 
                            data.errors?.name?.[0] || 
                            data.errors?.password?.[0] || 
                            data.message || 
                            'Error al guardar el perfil';
            showToast?.('Error', errorMsg, 'error');
        }
    } catch (error) {
        console.error('[PROFILE] Error:', error);
        showToast?.('Error', 'Error de conexión al guardar', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

/**
 * Mostrar notificación toast (fallback local si no está disponible globalmente)
 */
function localShowToast(title, message, type = 'info') {
    if (window.showToast) {
        return window.showToast(title, message, type);
    }
    
    // Fallback
    console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
}

// Alias para compatibilidad
const showToast = (title, msg, type) => {
    if (typeof window.showToast === 'function') {
        window.showToast(title, msg, type);
    } else {
        localShowToast(title, msg, type);
    }
};

/**
 * Inicializar módulo de perfil
 */
export function initProfile() {
    console.log('[PROFILE] Inicializando módulo de perfil');
    
    // Registrar funciones globales
    window.openProfileModal = openProfileModal;
    window.closeProfileModal = closeProfileModal;
    window.previewAvatar = previewAvatar;
    window.saveAvatar = saveAvatar;
    window.toggleProfileEdit = toggleProfileEdit;
    window.saveProfile = saveProfile;
    
    // Event listener: cerrar modal al hacer click fuera
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('profile-modal');
        if (modal && e.target === modal) {
            closeProfileModal();
        }
    });
    
    // Event listener: cerrar modal con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('profile-modal');
            if (modal && modal.style.display === 'flex') {
                closeProfileModal();
            }
        }
    });
    
    console.log('[PROFILE] ✅ Módulo de perfil inicializado');
}