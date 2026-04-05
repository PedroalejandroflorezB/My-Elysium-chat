/**
 * Módulo de Onboarding
 * Gestiona el tutorial inicial de 3 pasos
 */

let currentStep = 1;

/**
 * Mostrar onboarding
 */
export function showOnboarding() {
    const onboarding = document.getElementById('onboarding');
    if (onboarding) {
        onboarding.classList.remove('hidden');
        currentStep = 1;
        updateOnboardingStep();
    }
}

/**
 * Ir al siguiente paso
 */
export function nextOnboarding() {
    currentStep++;
    if (currentStep > 3) currentStep = 3;
    updateOnboardingStep();
}

/**
 * Omitir onboarding
 */
export function skipOnboarding() {
    const onboarding = document.getElementById('onboarding');
    const chatApp = document.getElementById('chat-app');
    if (onboarding) {
        onboarding.classList.add('hidden');
        localStorage.setItem('elysium-onboarding', 'completed');
        if (chatApp) chatApp.classList.remove('hidden');
    }
}

/**
 * Completar onboarding
 */
export function completeOnboarding() {
    const onboarding = document.getElementById('onboarding');
    const chatApp = document.getElementById('chat-app');
    if (onboarding) {
        onboarding.classList.add('hidden');
        localStorage.setItem('elysium-onboarding', 'completed');
        if (chatApp) chatApp.classList.remove('hidden');
    }
}

/**
 * Actualizar paso actual
 */
function updateOnboardingStep() {
    // Ocultar todos los pasos
    document.querySelectorAll('.onboarding-step').forEach(step => {
        step.classList.add('hidden');
    });
    
    // Mostrar paso actual
    const currentStepElement = document.querySelector(`.onboarding-step[data-step="${currentStep}"]`);
    if (currentStepElement) {
        currentStepElement.classList.remove('hidden');
    }
    
    // Actualizar dots de progreso
    document.querySelectorAll('.progress-dot').forEach((dot, index) => {
        if (index + 1 <= currentStep) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

/**
 * Inicializar onboarding al cargar
 */
export function initOnboarding() {
    const onboardingCompleted = localStorage.getItem('elysium-onboarding');
    const onboarding = document.getElementById('onboarding');
    const chatApp = document.getElementById('chat-app');
    
    if (onboarding) {
        if (!onboardingCompleted) {
            onboarding.classList.remove('hidden');
            if (chatApp) chatApp.classList.add('hidden');
        } else {
            onboarding.classList.add('hidden');
            if (chatApp) chatApp.classList.remove('hidden');
        }
    }
    
    // Hacer funciones disponibles globalmente para los onclick
    window.showOnboarding = showOnboarding;
    window.nextOnboarding = nextOnboarding;
    window.skipOnboarding = skipOnboarding;
    window.completeOnboarding = completeOnboarding;
}