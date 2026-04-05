<div id="onboarding" class="onboarding-container hidden">
    <div class="onboarding-modal">
        <button class="onboarding-skip" onclick="skipOnboarding()">Omitir</button>
        
        <div class="onboarding-progress">
            <div class="progress-dot active" data-step="1"></div>
            <div class="progress-dot" data-step="2"></div>
            <div class="progress-dot" data-step="3"></div>
        </div>

        <!-- Paso 1 -->
        <div class="onboarding-step" data-step="1">
            <div class="onboarding-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="onboarding-title">Bienvenido a Elysium</h2>
            <p class="onboarding-text">
                Chatea y comparte archivos de forma segura y privada. 
                Tus mensajes nunca pasan por servidores externos.
            </p>
            <button class="btn-primary" onclick="nextOnboarding()" style="width: 100%;">
                Comenzar
            </button>
        </div>

        <!-- Paso 2 -->
        <div class="onboarding-step hidden" data-step="2">
            <div class="onboarding-icon">
                <i class="fas fa-search"></i>
            </div>
            <h2 class="onboarding-title">Encuentra contactos</h2>
            <p class="onboarding-text">
                Busca por nombre de usuario o comparte tu @usuario 
                para conectar con otros usuarios.
            </p>
            <button class="btn-primary" onclick="nextOnboarding()" style="width: 100%;">
                Entendido
            </button>
        </div>

        <!-- Paso 3 -->
        <div class="onboarding-step hidden" data-step="3">
            <div class="onboarding-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary);">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="onboarding-title">¡Todo listo!</h2>
            <p class="onboarding-text">
                Tus archivos nunca tocan servidores externos. 
                La red está lista para tus datos.
            </p>
            <button class="btn-success" onclick="completeOnboarding()" style="width: 100%;">
                ¡Empezar!
            </button>
        </div>
    </div>
</div>