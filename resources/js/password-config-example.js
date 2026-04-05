/**
 * Ejemplo de configuración para PasswordManager
 * 
 * Este archivo muestra cómo configurar e inicializar el PasswordManager
 * en diferentes contextos (registro, reset de contraseña, perfil, admin)
 */

import PasswordManager from './components/PasswordManager.js';

// Configuración para formulario de registro
export const registerFormConfig = {
    toggles: [
        {
            inputId: 'password',
            toggleButtonId: 'password-toggle',
            eyeIconId: 'password-eye-icon',
            eyeOffIconId: 'password-eye-off-icon'
        },
        {
            inputId: 'password_confirmation',
            toggleButtonId: 'password-confirmation-toggle',
            eyeIconId: 'password-confirmation-eye-icon',
            eyeOffIconId: 'password-confirmation-eye-off-icon'
        }
    ],
    strengthIndicators: [
        {
            inputId: 'password',
            containerId: 'password-strength-container',
            barIds: ['strength-bar-1', 'strength-bar-2', 'strength-bar-3', 'strength-bar-4'],
            textId: 'password-strength-text'
        }
    ],
    matchValidators: [
        {
            passwordId: 'password',
            confirmationId: 'password_confirmation',
            indicatorId: 'password-match-indicator'
        }
    ],
    nameValidators: [
        {
            inputId: 'name',
            indicatorId: 'name-validation-indicator'
        }
    ],
    validationMessages: {
        nameInvalid: 'El nombre solo puede contener letras y espacios. No se permiten números ni símbolos.',
        emailInvalid: 'Ingrese un correo electrónico válido',
        passwordWeak: 'La contraseña debe ser más segura'
    }
};

// Configuración para formulario de reset de contraseña
export const resetPasswordConfig = {
    toggles: [
        {
            inputId: 'password',
            toggleButtonId: 'password-toggle',
            eyeIconId: 'password-eye-icon',
            eyeOffIconId: 'password-eye-off-icon'
        },
        {
            inputId: 'password_confirmation',
            toggleButtonId: 'password-confirmation-toggle',
            eyeIconId: 'password-confirmation-eye-icon',
            eyeOffIconId: 'password-confirmation-eye-off-icon'
        }
    ],
    strengthIndicators: [
        {
            inputId: 'password',
            containerId: 'password-strength-container',
            barIds: ['strength-bar-1', 'strength-bar-2', 'strength-bar-3', 'strength-bar-4'],
            textId: 'password-strength-text'
        }
    ],
    matchValidators: [
        {
            passwordId: 'password',
            confirmationId: 'password_confirmation',
            indicatorId: 'password-match-indicator'
        }
    ]
};

// Configuración para formulario de perfil (solo cambio de contraseña)
export const profilePasswordConfig = {
    toggles: [
        {
            inputId: 'current_password',
            toggleButtonId: 'current-password-toggle',
            eyeIconId: 'current-password-eye-icon',
            eyeOffIconId: 'current-password-eye-off-icon'
        },
        {
            inputId: 'password',
            toggleButtonId: 'password-toggle',
            eyeIconId: 'password-eye-icon',
            eyeOffIconId: 'password-eye-off-icon'
        },
        {
            inputId: 'password_confirmation',
            toggleButtonId: 'password-confirmation-toggle',
            eyeIconId: 'password-confirmation-eye-icon',
            eyeOffIconId: 'password-confirmation-eye-off-icon'
        }
    ],
    strengthIndicators: [
        {
            inputId: 'password',
            containerId: 'password-strength-container',
            barIds: ['strength-bar-1', 'strength-bar-2', 'strength-bar-3', 'strength-bar-4'],
            textId: 'password-strength-text'
        }
    ],
    matchValidators: [
        {
            passwordId: 'password',
            confirmationId: 'password_confirmation',
            indicatorId: 'password-match-indicator'
        }
    ]
};

// Configuración para formularios de administración
export const adminUserConfig = {
    toggles: [
        {
            inputId: 'password',
            toggleButtonId: 'password-toggle',
            eyeIconId: 'password-eye-icon',
            eyeOffIconId: 'password-eye-off-icon'
        },
        {
            inputId: 'password_confirmation',
            toggleButtonId: 'password-confirmation-toggle',
            eyeIconId: 'password-confirmation-eye-icon',
            eyeOffIconId: 'password-confirmation-eye-off-icon'
        }
    ],
    strengthIndicators: [
        {
            inputId: 'password',
            containerId: 'password-strength-container',
            barIds: ['strength-bar-1', 'strength-bar-2', 'strength-bar-3', 'strength-bar-4'],
            textId: 'password-strength-text'
        }
    ],
    matchValidators: [
        {
            passwordId: 'password',
            confirmationId: 'password_confirmation',
            indicatorId: 'password-match-indicator'
        }
    ]
};

// Función helper para inicializar PasswordManager
export function initializePasswordManager(config) {
    // Verificar que estamos en el DOM correcto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const manager = new PasswordManager(config);
            manager.init();
            
            // Guardar referencia global para cleanup si es necesario
            window.passwordManager = manager;
        });
    } else {
        const manager = new PasswordManager(config);
        manager.init();
        
        // Guardar referencia global para cleanup si es necesario
        window.passwordManager = manager;
    }
}

// Función para limpiar PasswordManager (útil en SPAs)
export function cleanupPasswordManager() {
    if (window.passwordManager) {
        window.passwordManager.destroy();
        window.passwordManager = null;
    }
}

// Ejemplo de uso:
/*
// En el archivo JS específico de cada página:

// Para registro:
import { initializePasswordManager, registerFormConfig } from './password-config-example.js';
initializePasswordManager(registerFormConfig);

// Para perfil:
import { initializePasswordManager, profilePasswordConfig } from './password-config-example.js';
initializePasswordManager(profilePasswordConfig);

// Para admin:
import { initializePasswordManager, adminUserConfig } from './password-config-example.js';
initializePasswordManager(adminUserConfig);
*/