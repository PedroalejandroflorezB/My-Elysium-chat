# PasswordManager - Guía de Uso

## Descripción General

El PasswordManager es un módulo JavaScript centralizado que proporciona funcionalidades consistentes para el manejo de contraseñas en toda la aplicación Laravel. Incluye:

- **Toggle de visibilidad**: Botones para mostrar/ocultar contraseñas
- **Indicador de fortaleza**: Evaluación en tiempo real de la seguridad de la contraseña
- **Validación de coincidencia**: Verificación de que las contraseñas coincidan
- **Accesibilidad completa**: Soporte para lectores de pantalla y navegación por teclado

## Estructura de Archivos

```
resources/
├── js/
│   ├── components/
│   │   └── PasswordManager.js          # Módulo principal
│   └── password-config-example.js      # Configuraciones de ejemplo
└── css/
    └── components/
        └── password-components.css      # Estilos específicos
```

## Configuración Básica

### 1. Importar el módulo

```javascript
import PasswordManager from './components/PasswordManager.js';
```

### 2. Crear configuración

```javascript
const config = {
    toggles: [
        {
            inputId: 'password',
            toggleButtonId: 'password-toggle',
            eyeIconId: 'password-eye-icon',
            eyeOffIconId: 'password-eye-off-icon'
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
```

### 3. Inicializar

```javascript
const manager = new PasswordManager(config);
manager.init();
```

## Estructura HTML Requerida

### Toggle de Visibilidad

```html
<div class="password-toggle">
    <input type="password" id="password" class="password-toggle-input" />
    <button type="button" id="password-toggle" class="password-toggle-button" aria-label="Mostrar contraseña">
        <svg id="password-eye-icon" class="password-toggle-icon hidden">
            <!-- Icono de ojo abierto -->
        </svg>
        <svg id="password-eye-off-icon" class="password-toggle-icon">
            <!-- Icono de ojo cerrado -->
        </svg>
    </button>
</div>
```

### Indicador de Fortaleza

```html
<div id="password-strength-container" class="password-strength-container hidden">
    <div class="password-strength-bars">
        <div id="strength-bar-1" class="password-strength-bar"></div>
        <div id="strength-bar-2" class="password-strength-bar"></div>
        <div id="strength-bar-3" class="password-strength-bar"></div>
        <div id="strength-bar-4" class="password-strength-bar"></div>
    </div>
    <div id="password-strength-text" class="password-strength-text"></div>
</div>
```

### Validador de Coincidencia

```html
<div id="password-match-indicator" class="password-match-container hidden">
    <div class="password-match-indicator">
        <!-- Contenido generado dinámicamente -->
    </div>
</div>
```

## Criterios de Fortaleza

El sistema evalúa las contraseñas según estos criterios:

### Niveles de Fortaleza

1. **Muy débil** (Rojo): Menos de 8 caracteres O solo longitud mínima
2. **Débil** (Naranja): 8+ caracteres + 1 criterio adicional
3. **Aceptable** (Amarillo): 8+ caracteres + 2 criterios adicionales
4. **Fuerte** (Verde): 12+ caracteres + 3+ criterios adicionales

### Criterios Evaluados

- ✅ Longitud mínima (8 caracteres)
- ✅ Longitud preferida (12+ caracteres)
- ✅ Letras minúsculas (a-z)
- ✅ Letras mayúsculas (A-Z)
- ✅ Números (0-9)
- ✅ Caracteres especiales (!@#$%^&*()_+-=[]{}|;':"\\,.<>?/)

## Configuraciones Predefinidas

### Formulario de Registro

```javascript
import { initializePasswordManager, registerFormConfig } from './password-config-example.js';
initializePasswordManager(registerFormConfig);
```

### Formulario de Perfil

```javascript
import { initializePasswordManager, profilePasswordConfig } from './password-config-example.js';
initializePasswordManager(profilePasswordConfig);
```

### Formularios de Administración

```javascript
import { initializePasswordManager, adminUserConfig } from './password-config-example.js';
initializePasswordManager(adminUserConfig);
```

## Personalización

### Textos Personalizados

```javascript
const customConfig = {
    matchTexts: {
        match: 'Las contraseñas son iguales',
        mismatch: 'Las contraseñas no coinciden'
    },
    ariaLabels: {
        showPassword: 'Mostrar contraseña',
        hidePassword: 'Ocultar contraseña'
    },
    strengthLevels: {
        1: { label: 'Muy débil', color: '#ef4444', class: 'strength-very-weak' },
        2: { label: 'Débil', color: '#f97316', class: 'strength-weak' },
        3: { label: 'Aceptable', color: '#eab308', class: 'strength-acceptable' },
        4: { label: 'Fuerte', color: '#22c55e', class: 'strength-strong' }
    }
};
```

## Gestión del Ciclo de Vida

### Inicialización Segura

```javascript
function initializePasswordManager(config) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const manager = new PasswordManager(config);
            manager.init();
            window.passwordManager = manager;
        });
    } else {
        const manager = new PasswordManager(config);
        manager.init();
        window.passwordManager = manager;
    }
}
```

### Limpieza (importante para SPAs)

```javascript
function cleanupPasswordManager() {
    if (window.passwordManager) {
        window.passwordManager.destroy();
        window.passwordManager = null;
    }
}
```

## Accesibilidad

El PasswordManager incluye soporte completo para accesibilidad:

- **ARIA labels**: Descripciones apropiadas para lectores de pantalla
- **Navegación por teclado**: Soporte para Enter y Espacio en botones
- **Estados ARIA**: `aria-pressed` para indicar estado del toggle
- **Focus management**: Indicadores visuales claros de foco
- **Contraste alto**: Soporte para modo de alto contraste

## Troubleshooting

### Problemas Comunes

1. **Los iconos no cambian**: Verificar que los IDs de los elementos SVG coincidan con la configuración
2. **Las barras de fortaleza no se actualizan**: Asegurar que los IDs de las barras estén correctos
3. **El indicador de coincidencia no aparece**: Verificar que el contenedor tenga la clase `hidden` inicialmente

### Debug

```javascript
// Verificar estado interno
console.log(manager.toggleStates);
console.log(manager.strengthStates);
console.log(manager.matchStates);

// Verificar elementos DOM
console.log(manager.elements);
```

## Integración con Laravel

### En Blade Templates

```blade
@push('scripts')
<script type="module">
    import { initializePasswordManager, registerFormConfig } from '/js/password-config-example.js';
    initializePasswordManager(registerFormConfig);
</script>
@endpush
```

### Con Vite

```javascript
// En resources/js/app.js
import { initializePasswordManager } from './password-config-example.js';

// Detectar página actual y aplicar configuración apropiada
const currentPage = document.body.dataset.page;
switch(currentPage) {
    case 'register':
        import('./password-config-example.js').then(({ registerFormConfig }) => {
            initializePasswordManager(registerFormConfig);
        });
        break;
    case 'profile':
        import('./password-config-example.js').then(({ profilePasswordConfig }) => {
            initializePasswordManager(profilePasswordConfig);
        });
        break;
}
```