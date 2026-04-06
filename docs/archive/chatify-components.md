# Chatify — Mapa de Componentes

Referencia completa de todos los componentes, clases CSS, IDs y archivos de Chatify en Elysium Ito.
Usar como guía antes de modificar el diseño para no romper funcionalidad existente.

---

## Archivos principales

| Archivo | Descripción |
|---|---|
| `resources/views/vendor/Chatify/pages/app.blade.php` | Vista principal — estructura HTML completa, JS inline, lógica de Pusher |
| `resources/views/chatify.blade.php` | Vista del chat personalizada — incluye panel de Bóveda Ito, estilos y scripts |
| `resources/views/vendor/Chatify/layouts/headLinks.blade.php` | `<head>`: meta tags, scripts, estilos |
| `resources/views/vendor/Chatify/layouts/footerLinks.blade.php` | Footer: Pusher init, variables globales `window.chatify`, scripts JS |
| `resources/views/vendor/Chatify/layouts/listItem.blade.php` | Item de lista de contactos (3 variantes: `saved`, `users`, `search_item`, `sharedPhoto`) |
| `resources/views/vendor/Chatify/layouts/messageCard.blade.php` | Tarjeta de mensaje (texto, imagen, archivo) |
| `resources/views/vendor/Chatify/layouts/sendForm.blade.php` | Formulario de envío de mensajes — incluye botón 🗄️ de la Bóveda |
| `resources/views/vendor/Chatify/layouts/info.blade.php` | Panel lateral derecho — perfil del usuario logado |
| `resources/views/vendor/Chatify/layouts/favorite.blade.php` | Item de favorito en la barra horizontal |
| `resources/views/vendor/Chatify/layouts/modals.blade.php` | Todos los modales (avatar, eliminar conversación, eliminar mensaje, editar mensaje, settings) |
| `public/css/chatify/style.css` | Estilos base — clases principales, layout, componentes |
| `public/css/chatify/dark.mode.css` | Overrides de dark mode |
| `public/css/chatify/light.mode.css` | Overrides de light mode |
| `public/js/chatify/code.js` | Lógica principal de Chatify (mensajes, contactos, Pusher) |
| `public/js/chatify/utils.js` | Utilidades JS (formateo, helpers) |
| `public/js/chatify/webrtc-transfer.js` | Transferencia P2P WebRTC |
| `public/js/chatify/vault.js` | Bóveda Ito — Google Drive file picker |

---

## Estructura HTML — `.messenger`

```
.messenger
├── .messenger-listView                  ← Panel izquierdo (lista de contactos)
│   ├── .m-header
│   │   ├── nav > .messenger-headTitle   ← "MENSAJES"
│   │   ├── .m-header-right
│   │   │   ├── .settings-btn (fa-cog)
│   │   │   └── .listView-x (fa-times)  ← Cerrar en móvil
│   │   └── input.messenger-search
│   └── .m-body.contacts-container
│       ├── .messenger-tab.users-tab
│       │   ├── .favorites-section
│       │   │   ├── .messenger-title "Favoritos"
│       │   │   └── .messenger-favorites
│       │   ├── .messenger-title "Tu Espacio"
│       │   ├── listItem (saved)
│       │   ├── .messenger-title "Todos los Mensajes"
│       │   └── .listOfContacts
│       └── .messenger-tab.search-tab
│           └── .search-records
│
├── .messenger-messagingView             ← Panel central (chat)
│   ├── .m-header.m-header-messaging
│   │   ├── nav
│   │   │   ├── .show-listView (fa-arrow-left)  ← Volver en móvil
│   │   │   ├── .avatar.av-s.header-avatar
│   │   │   ├── a.user-name #ark-contact-name-btn
│   │   │   └── .m-header-right
│   │   │       ├── .chat-dropdown
│   │   │       │   ├── .chat-dropdown-toggle (fa-ellipsis-v)
│   │   │       │   └── #chatDropdownMenu.chat-dropdown-menu
│   │   │       │       ├── .chat-dropdown-item.delete-conversation
│   │   │       │       └── .chat-dropdown-item.close-chat-btn
│   │   │       └── a.show-infoSide (fa-info-circle)
│   │   └── .internet-connection
│   ├── .m-body.messages-container
│   │   └── .messages
│   │       ├── .message-hint.center-el  ← Placeholder vacío
│   │       └── [messageCard × N]
│   ├── #ark-preview-zone                ← Preview de adjunto antes de enviar
│   └── sendForm
│
└── .messenger-infoView                  ← Panel derecho (info)
    ├── nav > a.show-infoSide (fa-times) ← Cerrar panel
    ├── info.blade.php                   ← Perfil del usuario logado
    └── [info de contacto cuando se abre un chat]
```

---

## Componentes detallados

### `.messenger-list-item` — Item de contacto

```html
<table class="messenger-list-item" data-contact="{userId}">
  <tr data-action="0">
    <td>
      <span class="activeStatus [offline]"></span>
      <div class="avatar av-m" style="background-image:url(...)"></div>
    </td>
    <td>
      <p data-id="{userId}" data-type="user">
        {nombre} <span class="contact-item-time">{hora}</span>
      </p>
      <span>{último mensaje}</span>
      <b>{contador no leídos}</b>  <!-- solo si > 0 -->
    </td>
  </tr>
</table>
```

**Estados:**
- `.m-list-active` — conversación activa (fondo `--primary-color`)
- `.activeStatus` — punto verde online
- `.activeStatus.offline` — punto gris offline

---

### `.message-card` — Tarjeta de mensaje

```html
<div class="message-card [mc-sender]" data-id="{messageId}">
  <!-- Solo si es sender -->
  <div class="actions">
    <i class="fas fa-pen edit-btn" data-id="..." data-message="..."></i>
    <i class="fas fa-trash delete-btn" data-id="..."></i>
  </div>

  <div class="message-card-content">
    <!-- Texto -->
    <div class="message">
      {texto}
      <span class="message-time">
        <span class="fas fa-check[-double]"></span>
        <span class="time">{hora}</span>
      </span>
    </div>

    <!-- Imagen -->
    <div class="image-wrapper">
      <div class="image-file chat-image" style="background-image:url(...)"></div>
    </div>

    <!-- Archivo -->
    <div class="file-download-card">
      <span class="file-download-icon"><i class="fas fa-file-*"></i></span>
      <span class="file-download-info">
        <span class="file-download-name">{nombre}</span>
        <span class="file-download-meta">
          <span class="file-download-ext">{EXT}</span>
          <span class="file-download-size">{tamaño}</span>
          <span class="file-download-time">{hora}</span>
        </span>
      </span>
      <button class="file-download-btn"><i class="fas fa-download"></i></button>
    </div>
  </div>
</div>
```

**Clases importantes:**
- `.mc-sender` — mensaje propio (burbuja `--primary-color`)
- `.mc-error` — mensaje con error (rojo)
- `.actions` — botones editar/eliminar, visibles en hover

---

### `sendForm.blade.php` — Formulario de envío

```html
<div class="messenger-sendCard">
  <form id="message-form">
    <label>
      <span class="fas fa-plus-circle"></span>
      <input type="file" class="upload-attachment" name="file">
    </label>
    <textarea class="m-send app-scroll" name="message"></textarea>
    <button class="send-button"><span class="fas fa-paper-plane"></span></button>
  </form>
</div>
```

**Preview de adjunto** (generado por JS):
```html
<div id="ark-preview-zone" class="attachment-preview">
  <div class="image-file" id="ark-preview-thumb"></div>
  <p><i class="fas fa-*"></i> <span id="ark-preview-name"></span></p>
  <span id="ark-preview-size"></span>
  <span class="cancel" id="ark-preview-cancel">×</span>
</div>
```

---

### `info.blade.php` — Panel de perfil

**IDs clave:**

| ID | Descripción |
|---|---|
| `#avatarPreviewWrapper` | Wrapper del avatar — click abre modal de cambio |
| `#ark-profile-view` | Vista de nombre/tagname/badge |
| `#ark-display-name` | Texto del nombre mostrado |
| `#ark-display-tagname` | Texto del tagname mostrado |
| `#self-status-badge` | Badge "Activo/Inactivo" |
| `#self-status-dot` | Punto de color del badge |
| `#self-status-label` | Texto del badge |
| `#ark-edit-profile-btn` | Botón "Editar perfil" |
| `#ark-profile-form` | Formulario de edición (oculto por defecto) |
| `#ark-input-name` | Input nombre |
| `#ark-input-tagname` | Input tagname |
| `#ark-input-password` | Input nueva contraseña |
| `#ark-input-password-confirm` | Input confirmar contraseña |
| `#ark-profile-save` | Botón guardar cambios |
| `#ark-profile-cancel` | Botón cancelar edición |
| `#ark-profile-error` | Div de error inline |
| `#ark-profile-success` | Div de éxito inline |

---

### `modals.blade.php` — Modales

#### Modal Avatar (`#avatarModal`)

```
#avatarModal (overlay fixed, display:none → flex al abrir)
└── div (modal box, flex-direction:column)
    ├── div (scrolleable)
    │   ├── #avatarModalClose (botón ×)
    │   ├── p "Cambiar foto de perfil"
    │   ├── #avatarPreview (círculo preview)
    │   ├── tabs (.avatar-tab-btn data-tab="gallery|upload")
    │   ├── #avatar-tab-gallery
    │   │   ├── #avatarGallery (grid 5 cols — generado por JS)
    │   │   └── #selectedPresetAvatar (hidden input)
    │   └── #avatar-tab-upload
    │       └── #avatarUploadForm
    │           ├── label (drag area)
    │           ├── #avatarFileInput (file input oculto)
    │           ├── #avatarFileName (nombre del archivo)
    │           ├── #avatarModalCancel
    │           └── #avatarUploadBtn
    └── #avatarConfirm (sticky fondo — oculto hasta seleccionar avatar)
        ├── #avatarConfirmImg
        ├── span "¿Usar este avatar?"
        ├── #avatarConfirmCancel "No"
        └── #avatarConfirmSave "Sí"
```

**JS que lo controla (en `app.blade.php`):**
- Abrir: `$('#avatarPreviewWrapper').click` → `$('#avatarModal').css('display','flex')`
- Cerrar: `closeAvatarModal()` — oculta modal, resetea confirm y selección
- Galería: `buildGallery()` — genera avatares DiceBear dinámicamente
- Confirmar: `#avatarConfirmSave` → POST a `route('avatar.from-url')`

#### Modal Eliminar Conversación (`#ark-delete-conv-modal`)

```
#ark-delete-conv-modal (overlay fixed)
└── div (card)
    ├── icono fa-trash-alt
    ├── p "¿Eliminar conversación?"
    ├── #ark-del-conv-me  "Solo para mí"
    ├── #ark-del-conv-all "Para todos"
    └── #ark-del-conv-cancel "Cancelar"
```

#### Modales genéricos `.app-modal`

| `data-name` | Uso |
|---|---|
| `delete-message` | Eliminar mensaje (para mí / para todos) |
| `edit-message` | Editar texto de un mensaje |
| `alert` | Alertas genéricas |
| `settings` | Configuración (dark mode, color del chat, logout) |

---

## Variables CSS (`--primary-color`)

Definida en `<head>` desde PHP:
```css
:root { --primary-color: {$messengerColor}; }
```

Usada en: bordes activos, burbujas de mensajes propios, íconos, tabs activos, badges de no leídos.

---

## Clases CSS críticas — NO renombrar

| Clase | Uso |
|---|---|
| `.messenger` | Contenedor raíz — `display:inline-flex; height:100vh` |
| `.messenger-listView` | Panel izquierdo |
| `.messenger-messagingView` | Panel central |
| `.messenger-infoView` | Panel derecho |
| `.m-header` | Header de cualquier panel |
| `.m-header-messaging` | Header del panel de chat |
| `.m-header-right` | Íconos derecha del header |
| `.messenger-list-item` | Fila de contacto |
| `.m-list-active` | Contacto activo/seleccionado |
| `.message-card` | Tarjeta de mensaje |
| `.mc-sender` | Mensaje propio |
| `.message-card-content` | Contenido de la tarjeta |
| `.message` | Burbuja de texto |
| `.message-time` | Hora + icono de visto |
| `.actions` | Botones editar/eliminar (hover) |
| `.messenger-sendCard` | Contenedor del formulario de envío |
| `.m-send` | Textarea de mensaje |
| `.send-button` | Botón enviar |
| `.upload-attachment` | Input file adjunto |
| `.avatar` | Imagen de avatar circular |
| `.av-l / .av-m / .av-s` | Tamaños de avatar (100px / 45px / 32px) |
| `.activeStatus` | Punto de estado online |
| `.messenger-favorites` | Barra horizontal de favoritos |
| `.messenger-title` | Separador de sección con línea |
| `.app-scroll` | Scrollbar personalizado |
| `.app-modal` | Overlay de modal genérico |
| `.app-modal-card` | Tarjeta del modal genérico |
| `.chat-dropdown-menu` | Menú desplegable 3 puntos |
| `.file-download-card` | Tarjeta de archivo adjunto |
| `.attachment-preview` | Preview antes de enviar adjunto |
| `.internet-connection` | Banner de estado de conexión |

---

## IDs JS críticos — NO renombrar

| ID | Descripción |
|---|---|
| `#avatarModal` | Modal de cambio de avatar |
| `#avatarPreview` | Preview circular del avatar |
| `#avatarPreviewWrapper` | Wrapper clickeable del avatar en info panel |
| `#avatarGallery` | Grid de avatares DiceBear |
| `#avatarConfirm` | Barra de confirmación sticky |
| `#avatarConfirmSave` | Botón "Sí" confirmar avatar |
| `#avatarConfirmCancel` | Botón "No" cancelar avatar |
| `#avatarUploadForm` | Form de subida de imagen |
| `#avatarFileInput` | Input file del form |
| `#avatarFileName` | Nombre del archivo seleccionado |
| `#avatarModalClose` | Botón × cerrar modal avatar |
| `#avatarModalCancel` | Botón cancelar en tab upload |
| `#avatarUploadBtn` | Botón guardar en tab upload |
| `#selectedPresetAvatar` | Hidden input con URL del avatar seleccionado |
| `#ark-delete-conv-modal` | Modal eliminar conversación |
| `#ark-del-conv-me` | Botón eliminar solo para mí |
| `#ark-del-conv-all` | Botón eliminar para todos |
| `#ark-del-conv-cancel` | Botón cancelar eliminar conv |
| `#chatDropdownMenu` | Menú desplegable del chat |
| `#ark-contact-name-btn` | Nombre del contacto en header |
| `#ark-toast-container` | Contenedor de toasts |
| `#ark-preview-zone` | Preview de adjunto |
| `#ark-preview-thumb` | Miniatura del adjunto |
| `#ark-preview-name` | Nombre del adjunto |
| `#ark-preview-cancel` | Cancelar adjunto |
| `#message-form` | Formulario de envío |
| `#info-panel-self` | Panel info del usuario logado |
| `#edit-message-input` | Textarea del modal editar mensaje |
| `#vault-panel` | Panel flotante de la Bóveda Ito |
| `#vault-trigger` | Botón 🗄️ que abre la bóveda (en sendForm) |
| `#vault-grid` | Grid de archivos de Drive |
| `#vault-spinner` | Indicador de carga de la bóveda |
| `#vault-error` | Mensaje de error de la bóveda |
| `#vault-reload` | Botón recargar archivos de Drive |

---

## Archivos JS

| Archivo | Responsabilidad |
|---|---|
| `public/js/chatify/code.js` | Core: mensajes, contactos, Pusher, favoritos, búsqueda |
| `public/js/chatify/utils.js` | Helpers: formateo de tiempo, scroll, NProgress |
| `public/js/chatify/autosize.js` | Auto-resize del textarea de mensajes |
| `public/js/chatify/webrtc-transfer.js` | Transferencia P2P de archivos via WebRTC |
| `public/js/chatify/vault.js` | Bóveda Ito — Google Drive file picker (panel flotante, fetch, render, selección) |
| `public/js/chatify/font.awesome.min.js` | Font Awesome (íconos) |

**JS inline en `app.blade.php`** (al final del archivo):
- Inicialización de Pusher y canales
- Lógica del modal de avatar (`buildGallery`, `closeAvatarModal`, `applyNewAvatar`)
- Tabs del modal de avatar
- Lógica del dropdown de chat
- Lógica del perfil inline (editar nombre/tagname/password)
- Sistema de toasts (`arkToast()`)
- Indicador de estado online/offline propio
- Chunked upload de archivos grandes

---

## Rutas usadas por JS (no romper)

| Route name | Uso |
|---|---|
| `avatar.upload` | Subir imagen de avatar |
| `avatar.from-url` | Guardar avatar desde URL DiceBear |
| `avatar.update` | Actualizar settings (color, dark mode) |
| `profile.update` | Actualizar nombre/tagname/password |
| `send.message` | Enviar mensaje |
| `pusher.auth` | Autenticación de canal privado Pusher |
| `chatify.download` | Descargar archivo adjunto |
| `vault.files` | Listar archivos de Google Drive (Bóveda Ito) |
