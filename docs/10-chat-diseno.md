# Elysium P2P: Chat Interface Component Tree

Este documento detalla la jerarquía de componentes para las dos vistas principales de la aplicación, asegurando una separación clara entre el estado de bienvenida y el chat activo.

---

## 🌳 VISTA 1: Onboarding / Empty State
Esta vista se muestra cuando el usuario no ha seleccionado ninguna conversación.

- **Layout Base**: `layouts.chat.blade.php`
    - **Header Superior**: `chat.partials.topbar.blade.php`
    - **Contenedor Principal**: `.chat-layout`
        - **Columna Izquierda**: `chat.partials.sidebar.blade.php`
            - `sidebar__header`: Buscador y Tabs (Mensajes, Contactos, Archivos).
            - `sidebar__content`: Listas dinámicas filtradas por el controlador.
        - **Área Principal (Derecha)**: `.chat-main`
            - **`chat.partials.empty-state.blade.php`** (Componente Clave):
                - `empty-icon`: Icono 💬 con animación `float`.
                - `empty-title`: Mensaje central "¡Empecemos!".
                - `empty-text`: Instrucciones de uso.
                - `empty-actions`: Botón "Copiar mi @usuario".

---

## 🌳 VISTA 2: Chat de Conversación Activo
Esta vista se muestra al seleccionar un contacto para chatear.

- **Layout Base**: `layouts.chat.blade.php`
    - **Header Superior**: `chat.partials.topbar.blade.php`
    - **Contenedor Principal**: `.chat-layout`
        - **Columna Izquierda**: `chat.partials.sidebar.blade.php` (Mismo componente, estado sincronizado).
        - **Área Principal (Derecha)**: `.chat-main`
            - **`chat.partials.chat-header.blade.php`**:
                - Información del contacto (Avatar, Nombre, Estado "En línea").
                - Menú de opciones (3 puntos): `toggleChatMenu()`.
            - **`chat.partials.messages-container.blade.php`**:
                - Wrapper con scroll automático al final.
                - **`chat.partials.messages.blade.php`**: Bucle `@foreach` de burbujas.
            - **`chat.partials.chat-input.blade.php`** (Componente Crítico):
                - `chat-input-wrapper`: Contenedor unificado.
                - `send-file-btn`: Botón para transferencias P2P (WebRTC).
                - `message-input`: Textarea autoadaptable de 1 sola línea inicial.
                - `btn-send`: Botón de envío con icono SVG.

---

## 🛠️ Componentes Transversales (Modales)
Disponibles en ambas vistas mediante `@stack('scripts')` o inclusiones globales:
- `chat.partials.modals.profile`: Edición de perfil de usuario.
- `chat.partials.p2p-modals`: Diálogos de aceptación y progreso del sistema P2P.

## 🎨 Estilos Asociados
- `resources/css/components/chat.css`: Contiene la lógica visual de ambas ramas del árbol anterior.
