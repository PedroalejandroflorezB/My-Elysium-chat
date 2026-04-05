<div id="modal-profile" class="modal-overlay">
    <div class="modal">
        <!-- Header Centrado -->
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2 class="modal-title">Mi Perfil</h2>
        </div>
        
        <div class="modal-body">
            <form id="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                
                <!-- Avatar Centrado -->
                <div class="profile-avatar-section">
                    <div class="profile-avatar-preview" onclick="document.getElementById('avatar-input').click()">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        <div class="camera-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <label for="avatar-input">
                        <i class="fas fa-upload"></i> Cambiar avatar
                    </label>
                    <input type="file" id="avatar-input" name="avatar" accept="image/*">
                    <p class="helper-text">JPG, PNG o GIF. Máx 2MB.</p>
                </div>

                <!-- Nombre -->
                <div class="form-group">
                    <label for="profile-name">Nombre</label>
                    <input type="text" id="profile-name" name="name" class="form-input" value="{{ auth()->user()->name ?? '' }}" required>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label for="profile-username">@usuario</label>
                    <input type="text" id="profile-username" name="username" class="form-input" value="{{ auth()->user()->username ?? '@'.strtolower(str_replace(' ', '_', auth()->user()->name ?? 'usuario')) }}" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="profile-email">Email</label>
                    <input type="email" id="profile-email" class="form-input" value="{{ auth()->user()->email ?? '' }}" readonly>
                    <p class="form-helper">
                        <i class="fas fa-lock"></i> El email no se puede cambiar
                    </p>
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="profile-password">Nueva contraseña (opcional)</label>
                    <input type="password" id="profile-password" name="password" class="form-input" placeholder="Dejar vacío para mantener la actual">
                </div>

                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="profile-password-confirm">Confirmar contraseña</label>
                    <input type="password" id="profile-password-confirm" name="password_confirmation" class="form-input" placeholder="Confirmar nueva contraseña">
                </div>
            </form>
        </div>
        
        <!-- Footer con Botones Centrados -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-profile')">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" onclick="saveProfile()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>