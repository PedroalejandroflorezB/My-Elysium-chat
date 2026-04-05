/**
 * PasswordManager - Módulo centralizado para manejo de contraseñas
 * 
 * Proporciona funcionalidades de:
 * - Toggle de visibilidad de contraseña
 * - Indicador de fortaleza en tiempo real
 * - Validación de coincidencia de contraseñas
 * - Accesibilidad completa
 */
class PasswordManager {
    constructor(config = {}) {
        this.config = {
            // Configuración por defecto
            strengthLevels: {
                1: { label: 'Muy débil', color: '#ef4444', class: 'strength-very-weak' },
                2: { label: 'Débil', color: '#f97316', class: 'strength-weak' },
                3: { label: 'Aceptable', color: '#eab308', class: 'strength-acceptable' },
                4: { label: 'Fuerte', color: '#22c55e', class: 'strength-strong' }
            },
            matchTexts: {
                match: 'Las contraseñas coinciden',
                mismatch: 'Las contraseñas no coinciden'
            },
            ariaLabels: {
                showPassword: 'Mostrar contraseña',
                hidePassword: 'Ocultar contraseña'
            },
            validationMessages: {
                nameInvalid: 'El nombre solo puede contener letras y espacios',
                emailInvalid: 'Ingrese un correo electrónico válido',
                passwordWeak: 'La contraseña debe ser más segura'
            },
            ...config
        };

        // Estado interno
        this.toggleStates = new Map();
        this.strengthStates = new Map();
        this.matchStates = new Map();
        
        // Referencias a elementos DOM
        this.elements = new Map();
        
        // Event listeners para cleanup
        this.eventListeners = [];
    }

    /**
     * Inicializa el PasswordManager
     * Configura todos los componentes según la configuración proporcionada
     */
    init() {
        try {
            // Inicializar toggles de visibilidad
            if (this.config.toggles) {
                this.config.toggles.forEach(toggleConfig => {
                    this._setupToggle(toggleConfig);
                });
            }

            // Inicializar indicadores de fortaleza
            if (this.config.strengthIndicators) {
                this.config.strengthIndicators.forEach(strengthConfig => {
                    this._setupStrengthIndicator(strengthConfig);
                });
            }

            // Inicializar validadores de coincidencia
            if (this.config.matchValidators) {
                this.config.matchValidators.forEach(matchConfig => {
                    this._setupMatchValidator(matchConfig);
                });
            }

            // Inicializar validadores de nombre
            if (this.config.nameValidators) {
                this.config.nameValidators.forEach(nameConfig => {
                    this._setupNameValidator(nameConfig);
                });
            }

            console.log('PasswordManager inicializado correctamente');
        } catch (error) {
            console.error('Error al inicializar PasswordManager:', error);
        }
    }

    /**
     * Limpia todos los event listeners y referencias
     * Debe llamarse cuando el componente ya no se necesite
     */
    destroy() {
        // Remover todos los event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });

        // Limpiar mapas de estado
        this.toggleStates.clear();
        this.strengthStates.clear();
        this.matchStates.clear();
        this.elements.clear();
        this.eventListeners = [];

        console.log('PasswordManager destruido correctamente');
    }

    /**
     * Configura el toggle de visibilidad para un campo de contraseña
     * @param {Object} config - Configuración del toggle
     */
    _setupToggle(config) {
        const {
            inputId,
            toggleButtonId,
            eyeIconId,
            eyeOffIconId
        } = config;

        // Obtener elementos DOM
        const input = document.getElementById(inputId);
        const toggleButton = document.getElementById(toggleButtonId);
        const eyeIcon = document.getElementById(eyeIconId);
        const eyeOffIcon = document.getElementById(eyeOffIconId);

        if (!input || !toggleButton || !eyeIcon || !eyeOffIcon) {
            console.warn(`Toggle setup fallido: elementos no encontrados para ${inputId}`);
            return;
        }

        // Estado inicial
        const initialState = {
            isVisible: false,
            inputType: 'password',
            activeIcon: 'eyeOff'
        };

        this.toggleStates.set(inputId, initialState);
        this.elements.set(`${inputId}_toggle`, { input, toggleButton, eyeIcon, eyeOffIcon });

        // Configurar estado inicial
        this._updateToggleState(inputId);

        // Event listener para el botón
        const clickHandler = (e) => {
            e.preventDefault();
            this._handleToggleClick(inputId);
        };

        toggleButton.addEventListener('click', clickHandler);
        this.eventListeners.push({ element: toggleButton, event: 'click', handler: clickHandler });

        // Soporte para teclado
        const keyHandler = (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this._handleToggleClick(inputId);
            }
        };

        toggleButton.addEventListener('keydown', keyHandler);
        this.eventListeners.push({ element: toggleButton, event: 'keydown', handler: keyHandler });
    }

    /**
     * Configura el indicador de fortaleza para un campo de contraseña
     * @param {Object} config - Configuración del indicador de fortaleza
     */
    _setupStrengthIndicator(config) {
        const {
            inputId,
            containerId,
            barIds,
            textId
        } = config;

        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        const textElement = document.getElementById(textId);

        if (!input || !container || !textElement) {
            console.warn(`Strength indicator setup fallido: elementos no encontrados para ${inputId}`);
            return;
        }

        // Estado inicial
        this.strengthStates.set(inputId, {
            level: 0,
            criteria: {
                minLength: false,
                hasLowerCase: false,
                hasUpperCase: false,
                hasNumbers: false,
                hasSpecialChars: false
            }
        });

        this.elements.set(`${inputId}_strength`, { input, container, textElement, barIds });

        // Event listener para input
        const inputHandler = () => {
            this._handleStrengthCheck(inputId);
        };

        input.addEventListener('input', inputHandler);
        this.eventListeners.push({ element: input, event: 'input', handler: inputHandler });
    }

    /**
     * Configura el validador de coincidencia entre contraseña y confirmación
     * @param {Object} config - Configuración del validador
     */
    _setupMatchValidator(config) {
        const {
            passwordId,
            confirmationId,
            indicatorId
        } = config;

        const passwordInput = document.getElementById(passwordId);
        const confirmationInput = document.getElementById(confirmationId);
        const indicator = document.getElementById(indicatorId);

        if (!passwordInput || !confirmationInput || !indicator) {
            console.warn(`Match validator setup fallido: elementos no encontrados para ${passwordId}-${confirmationId}`);
            return;
        }

        // Estado inicial
        const matchKey = `${passwordId}_${confirmationId}`;
        this.matchStates.set(matchKey, {
            password: '',
            confirmation: '',
            isMatch: false,
            shouldDisplay: false
        });

        this.elements.set(`${matchKey}_match`, { passwordInput, confirmationInput, indicator });

        // Event listeners
        const passwordHandler = () => this._handleMatchCheck(matchKey);
        const confirmationHandler = () => this._handleMatchCheck(matchKey);

        passwordInput.addEventListener('input', passwordHandler);
        confirmationInput.addEventListener('input', confirmationHandler);

        this.eventListeners.push({ element: passwordInput, event: 'input', handler: passwordHandler });
        this.eventListeners.push({ element: confirmationInput, event: 'input', handler: confirmationHandler });
    }

    /**
     * Configura el validador de nombre para permitir solo letras y espacios
     * @param {Object} config - Configuración del validador de nombre
     */
    _setupNameValidator(config) {
        const { inputId, indicatorId } = config;

        const input = document.getElementById(inputId);
        const indicator = document.getElementById(indicatorId);

        if (!input || !indicator) {
            console.warn(`Name validator setup fallido: elementos no encontrados para ${inputId}`);
            return;
        }

        this.elements.set(`${inputId}_name`, { input, indicator });

        // Event listener para validación en tiempo real
        const inputHandler = () => this._handleNameValidation(inputId);
        
        input.addEventListener('input', inputHandler);
        this.eventListeners.push({ element: input, event: 'input', handler: inputHandler });
    }

    /**
     * Maneja la validación del campo nombre
     * @param {string} inputId - ID del input de nombre
     */
    _handleNameValidation(inputId) {
        const elements = this.elements.get(`${inputId}_name`);
        if (!elements) return;

        const { input, indicator } = elements;
        const value = input.value;

        // Regex para solo letras, espacios y acentos
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]*$/;
        const isValid = nameRegex.test(value);

        if (value.length > 0 && !isValid) {
            indicator.classList.remove('hidden');
            indicator.classList.add('mismatch');
            indicator.innerHTML = `
                <svg class="password-match-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <span>${this.config.validationMessages.nameInvalid}</span>
            `;
        } else {
            indicator.classList.add('hidden');
        }
    }

    // Métodos privados para manejo de eventos
    /**
     * Maneja el click en el botón de toggle de visibilidad
     * @param {string} inputId - ID del input de contraseña
     */
    _handleToggleClick(inputId) {
        const state = this.toggleStates.get(inputId);
        if (!state) return;

        // Cambiar estado
        state.isVisible = !state.isVisible;
        state.inputType = state.isVisible ? 'text' : 'password';
        state.activeIcon = state.isVisible ? 'eye' : 'eyeOff';

        // Actualizar DOM
        this._updateToggleState(inputId);
    }

    /**
     * Actualiza el DOM según el estado actual del toggle
     * @param {string} inputId - ID del input de contraseña
     */
    _updateToggleState(inputId) {
        const state = this.toggleStates.get(inputId);
        const elements = this.elements.get(`${inputId}_toggle`);
        
        if (!state || !elements) return;

        const { input, toggleButton, eyeIcon, eyeOffIcon } = elements;

        // Actualizar tipo de input
        input.type = state.inputType;

        // Actualizar iconos
        if (state.isVisible) {
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
        } else {
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
        }

        // Actualizar ARIA label
        const ariaLabel = state.isVisible 
            ? this.config.ariaLabels.hidePassword 
            : this.config.ariaLabels.showPassword;
        
        toggleButton.setAttribute('aria-label', ariaLabel);
        toggleButton.setAttribute('title', ariaLabel);

        // Actualizar aria-pressed para indicar estado
        toggleButton.setAttribute('aria-pressed', state.isVisible.toString());
    }

    /**
     * Maneja la verificación de fortaleza de contraseña
     * @param {string} inputId - ID del input de contraseña
     */
    _handleStrengthCheck(inputId) {
        const elements = this.elements.get(`${inputId}_strength`);
        if (!elements) return;

        const { input, container, textElement, barIds } = elements;
        const password = input.value;

        // Calcular fortaleza
        const strengthResult = this._calculateStrength(password);
        
        // Actualizar estado
        this.strengthStates.set(inputId, strengthResult);

        // Actualizar UI
        this._updateStrengthIndicator(inputId, strengthResult);

        // Mostrar/ocultar contenedor según si hay contenido
        if (password.length > 0) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    /**
     * Calcula la fortaleza de una contraseña
     * @param {string} password - La contraseña a evaluar
     * @returns {Object} Resultado con nivel y criterios
     */
    _calculateStrength(password) {
        const criteria = {
            minLength: password.length >= 8,
            preferredLength: password.length >= 12,
            hasLowerCase: /[a-z]/.test(password),
            hasUpperCase: /[A-Z]/.test(password),
            hasNumbers: /\d/.test(password),
            hasSpecialChars: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };

        // Contar criterios cumplidos (excluyendo minLength que es base)
        const additionalCriteria = [
            criteria.hasLowerCase,
            criteria.hasUpperCase,
            criteria.hasNumbers,
            criteria.hasSpecialChars
        ].filter(Boolean).length;

        let level = 0;

        if (password.length === 0) {
            level = 0;
        } else if (!criteria.minLength) {
            level = 1; // Muy débil - no cumple longitud mínima
        } else if (additionalCriteria === 0) {
            level = 1; // Muy débil - solo longitud
        } else if (additionalCriteria === 1) {
            level = 2; // Débil - longitud + 1 criterio
        } else if (additionalCriteria === 2 || (additionalCriteria >= 3 && !criteria.preferredLength)) {
            level = 3; // Aceptable - longitud + 2 criterios O 3+ criterios sin longitud preferida
        } else if (criteria.preferredLength && additionalCriteria >= 3) {
            level = 4; // Fuerte - longitud preferida + 3+ criterios
        }

        return {
            level,
            criteria,
            additionalCriteria
        };
    }

    /**
     * Actualiza el indicador visual de fortaleza
     * @param {string} inputId - ID del input
     * @param {Object} strengthResult - Resultado del cálculo de fortaleza
     */
    _updateStrengthIndicator(inputId, strengthResult) {
        const elements = this.elements.get(`${inputId}_strength`);
        if (!elements) return;

        const { container, textElement, barIds } = elements;
        const { level } = strengthResult;

        // Actualizar barras
        barIds.forEach((barId, index) => {
            const bar = document.getElementById(barId);
            if (!bar) return;

            // Limpiar clases anteriores
            bar.classList.remove('active', 'strength-very-weak', 'strength-weak', 'strength-acceptable', 'strength-strong');

            // Activar barra si está dentro del nivel
            if (index < level && level > 0) {
                bar.classList.add('active');
                
                // Añadir clase de color según nivel
                const strengthConfig = this.config.strengthLevels[level];
                if (strengthConfig) {
                    bar.classList.add(strengthConfig.class);
                }
            }
        });

        // Actualizar texto
        if (level > 0) {
            const strengthConfig = this.config.strengthLevels[level];
            if (strengthConfig) {
                textElement.textContent = strengthConfig.label;
                textElement.className = `password-strength-text ${strengthConfig.class}`;
            }
        } else {
            textElement.textContent = '';
            textElement.className = 'password-strength-text';
        }
    }

    /**
     * Maneja la verificación de coincidencia de contraseñas
     * @param {string} matchKey - Clave del validador de coincidencia
     */
    _handleMatchCheck(matchKey) {
        const elements = this.elements.get(`${matchKey}_match`);
        const state = this.matchStates.get(matchKey);
        
        if (!elements || !state) return;

        const { passwordInput, confirmationInput, indicator } = elements;
        
        // Actualizar estado
        state.password = passwordInput.value;
        state.confirmation = confirmationInput.value;
        state.isMatch = state.password === state.confirmation && state.password.length > 0;
        state.shouldDisplay = state.confirmation.length > 0;

        // Actualizar UI
        this._updateMatchIndicator(matchKey, state);
    }

    /**
     * Actualiza el indicador visual de coincidencia
     * @param {string} matchKey - Clave del validador
     * @param {Object} state - Estado actual de la coincidencia
     */
    _updateMatchIndicator(matchKey, state) {
        const elements = this.elements.get(`${matchKey}_match`);
        if (!elements) return;

        const { indicator } = elements;

        // Mostrar/ocultar indicador
        if (state.shouldDisplay) {
            indicator.classList.remove('hidden');
            
            // Actualizar clases y contenido según coincidencia
            if (state.isMatch) {
                indicator.classList.remove('mismatch');
                indicator.classList.add('match');
                indicator.innerHTML = `
                    <svg class="password-match-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>${this.config.matchTexts.match}</span>
                `;
            } else {
                indicator.classList.remove('match');
                indicator.classList.add('mismatch');
                indicator.innerHTML = `
                    <svg class="password-match-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span>${this.config.matchTexts.mismatch}</span>
                `;
            }
        } else {
            indicator.classList.add('hidden');
        }
    }
}

// Exportar para uso en otros módulos
export default PasswordManager;