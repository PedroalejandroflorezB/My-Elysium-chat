# Buenas Prácticas: Laravel, Tailwind CSS y Estándares de Google

Este documento resume las mejores prácticas consolidadas para el desarrollo de aplicaciones web en este proyecto (Elysium Ito).

## 🐘 Laravel (Backend)

### 1. Lógica y Estructura
- **Controladores Delgados**: Los controladores solo deben validar la entrada, llamar a un servicio/acción y devolver una respuesta.
- **Services / Actions**: Mantén la lógica de negocio en clases dedicadas (`app/Actions` o `app/Services`).
- **Form Requests**: Usa siempre Form Requests para la validación (`app/Http/Requests`). Nunca valides directamente en el controlador.
- **Form Validation**: Prefiere `Rule::unique()->ignore($id)` para actualizaciones y `alpha_dash` para nombres de usuario.

### 2. Modelos y Base de Datos
- **Asignación Masiva**: Define siempre `$fillable` o `$guarded`.
- **Casting**: Usa `$casts` para asegurar tipos (ej: `'active_status' => 'boolean'`, `'password' => 'hashed'`).
- **Privacidad**: Oculta campos sensibles en JSON usando `$hidden` (ej: `google_id`, `is_king`).
- **Scopes**: Utiliza `scopeActive`, `scopeAdmins`, etc., para consultas reutilizables.
- **N+1**: Evita consultas en bucles; usa `with()` para carga ambiciosa (Eager Loading).
- **Transacciones**: Usa `DB::transaction()` para operaciones que deben ser atómicas (ej: mensaje + archivo).
- **Soft Deletes**: Implementa `SoftDeletes` en modelos críticos como mensajes y usuarios para auditoría.

### 3. Seguridad
- **CSRF**: Token obligatorio en cada petición POST/PUT/DELETE.
- **Escapado**: Usa siempre `{{ }}` en Blade. No uses `{!! !!}` con datos del usuario.
- **Sesión**: Regenera la sesión tras el login (`$request->session()->regenerate()`).
- **2FA**: Obligatorio en rutas críticas y para administradores.
- **Headers**: Configura CSP, HSTS y X-Frame-Options para proteger contra ataques comunes.
- **Rate Limiting**: Limita intentos en formularios públicos (contacto, login) usando `RateLimiter`.

---

## 🎨 Tailwind CSS (Frontend)

### 1. Stack y Configuración (v4)
- **Versión 4**: NOTA: Este proyecto usa **v4**. No existe `tailwind.config.js`.
- **Configuración**: El tema se define en `app.css` usando `@theme`.
- **Directivas**: Usa `@import "tailwindcss"` en lugar de las directivas `@tailwind` antiguas.

### 2. Estilo y Organización
- **Utility-First**: Usa clases utilitarias directamente en el HTML. Evita crear clases CSS propias para layouts.
- **Componentes**: Si una combinación se repite mucho, usa `@layer components` en el CSS o crea un componente Blade (`x-`).
- **Responsive**: Mobile-first siempre. Usa prefijos `sm:`, `md:`, `lg:` para pantallas grandes.
- **Orden de Clases**: Sigue una estructura lógica: layout → spacing → sizing → typography → colors.
- **Dark Mode**: Implementado mediante la clase `dark` en el `<html>`. Usa el prefijo `dark:`.

---

## 🌐 Estándares de Google (Web Excellence)

### 1. Core Web Vitals
- **LCP**: Optimiza la carga de imágenes superiores (LCP).
- **FID/CLS**: Minimiza la ejecución de JS pesado y reserva espacio para elementos dinámicos para evitar saltos visuales.

### 2. OAuth y APIs
- **Google Drive**: Actúa como puente, no como almacenamiento permanente.
- **Tokens**: Almacena `access_token` y `refresh_token` siempre encriptados en la base de datos.
- **Scope Mínimo**: Solicita solo los permisos necesarios (evita `drive.full`, prefiere `drive.file`).

### 3. SEO y Performance
- **HTML Semántico**: Usa etiquetas como `<main>`, `<section>`, `<header>`.
- **WebP**: Prefiere formatos de imagen modernos para reducir el peso de carga.
- **Vite**: Mantén los assets optimizados y versionados.

---

## 🛠️ Convenciones de Nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| Modelo | PascalCase singular | `User`, `ChMessage` |
| Controller | PascalCase + Controller | `UserController` |
| Migración | snake_case descriptivo | `add_tagname_to_users_table` |
| Ruta | snake_case con puntos | `users.index`, `chatify.send` |
| Vista | kebab-case | `profile-settings.blade.php` |
