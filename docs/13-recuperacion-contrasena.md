# Sistema de Recuperación de Contraseñas Unificado

## Descripción General

El sistema de recuperación de contraseñas maneja tanto usuarios regulares como administradores en un flujo unificado, utilizando el formulario existente `reset-password.blade.php` para ambos casos.

## Flujo de Recuperación

### 1. Página de Inicio (`forgot-password.blade.php`)

**Ruta:** `/forgot-password`

- El usuario ingresa su correo electrónico
- El sistema detecta automáticamente si es un administrador
- **Si es usuario regular:** Se envía un email con enlace de recuperación
- **Si es administrador:** Se muestra el formulario de código de recuperación

### 2. Recuperación para Usuarios Regulares

**Flujo estándar de Laravel:**
1. Se envía email con token de recuperación
2. Usuario hace clic en el enlace del email
3. Se redirige a `/reset-password/{token}`
4. Usuario ingresa nueva contraseña
5. Se procesa con `NewPasswordController`

### 3. Recuperación para Administradores

**Flujo con códigos de recuperación:**
1. Admin ingresa código de recuperación (formato: XXXX-XXXX-XXXX)
2. Se verifica el código con `AdminRecoveryController::verifyRecoveryCode`
3. Si es válido, se almacena en sesión y redirige a `/admin/recovery/reset`
4. Se muestra el mismo formulario `reset-password.blade.php` pero con datos de sesión
5. Se procesa con `AdminRecoveryController::resetPassword`

## Archivos Principales

### Controladores
- `AdminRecoveryController.php` - Maneja la recuperación de administradores
- `NewPasswordController.php` - Maneja la recuperación de usuarios regulares
- `PasswordResetLinkController.php` - Envía emails de recuperación

### Vistas
- `forgot-password.blade.php` - Página inicial unificada
- `reset-password.blade.php` - Formulario unificado para nueva contraseña

### Rutas
```php
// Página inicial
Route::get('forgot-password', [AdminRecoveryController::class, 'showRecoveryForm'])
    ->name('password.request');

// Email para usuarios regulares
Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email');

// Verificación de código admin
Route::post('admin/recovery/verify', [AdminRecoveryController::class, 'verifyRecoveryCode'])
    ->name('admin.recovery.verify');

// Formulario de nueva contraseña admin
Route::get('admin/recovery/reset', [AdminRecoveryController::class, 'showResetForm'])
    ->name('admin.recovery.reset.form');

// Procesar nueva contraseña admin
Route::post('admin/recovery/reset', [AdminRecoveryController::class, 'resetPassword'])
    ->name('admin.recovery.reset');

// Recuperación estándar con token
Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');
```

## Características del Sistema

### Detección Automática
- JavaScript detecta si el email pertenece a un administrador
- Se obtiene la lista de admins desde el backend: `\App\Models\User::where('is_admin', true)->pluck('email')`

### Formulario Unificado
- `reset-password.blade.php` detecta si es recuperación admin mediante `session('recovery_verified')`
- Muestra badge de "Código de Recuperación Verificado" para admins
- Email se muestra como readonly para admins
- Envía a diferentes rutas según el tipo de usuario

### Seguridad
- Códigos de recuperación tienen formato específico: XXXX-XXXX-XXXX
- Sesión de recuperación expira en 15 minutos
- Códigos se marcan como usados (simulado en sesión, debería ser en BD)
- Validación de formato y existencia de usuario

### Experiencia de Usuario
- Interfaz consistente para ambos tipos de usuario
- Mensajes de error específicos y claros
- Auto-formateo de códigos de recuperación
- Indicadores visuales para admins

## Códigos de Recuperación

Los códigos se generan en el panel de administración y se almacenan localmente. En una implementación completa, deberían:

1. Almacenarse en base de datos encriptados
2. Tener fecha de expiración
3. Permitir regeneración por otros admins
4. Llevar registro de uso y auditoría

## Próximas Mejoras

1. **Base de Datos:** Migrar códigos de localStorage a tabla de BD
2. **Auditoría:** Registrar intentos de recuperación
3. **Notificaciones:** Alertar a otros admins sobre uso de códigos
4. **Expiración:** Códigos con tiempo de vida limitado
5. **Backup:** Sistema de códigos de respaldo para emergencias