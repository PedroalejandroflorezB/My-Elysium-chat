# Referencia de Componentes Chatify — Elysium Ito

Mapa de todos los componentes, clases CSS, IDs y archivos de Chatify. Consultar antes de modificar el diseño para no romper funcionalidad existente.

---

## Archivos principales

| Archivo | Descripción |
|---|---|
| `resources/views/vendor/Chatify/pages/app.blade.php` | Vista principal — HTML completo, JS inline, lógica de Pusher |
| `resources/views/vendor/Chatify/layouts/sendForm.blade.php` | Formulario de envío — incluye botón 🗄️ de la Bóveda |
| `resources/views/vendor/Chatify/layouts/listItem.blade.php` | Item de lista de contactos |
| `resources/views/vendor/Chatify/layouts/messageCard.blade.php` | Tarjeta de mensaje (texto, imagen, archivo) |
| `resources/views/vendor/Chatify/layouts/info.blade.php` | Panel lateral derecho — perfil del usuario |
| `resources/views/vendor/Chatify/layouts/modals.blade.php` | Todos los modales |
| `public/css/chatify/style.css` | Estilos base |
| `public/css/chatify/dark.mode.css` | Overrides dark mode |
| `public/css/chatify/light.mode.css` | Overrides light mode |
| `public/js/chatify/code.js` | Lógica principal (mensajes, contactos, Pusher) |
| `public/js/chatify/webrtc-transfer.js` | Transferencia P2P WebRTC |

---

## Estructura HTML — `.messenger`

```
.messenger
├── .messenger-listView                  ← Panel izquierdo (contactos)
│   ├── .m-header
│   │   ├── .messenger-headTitle         ← "MENSAJES"
│   │   └── input.messenger-search
│   └── .m-body.contacts-container
│       ├── .messenger-tab.users-tab
│       │   ├── .favorites-section
│       │   │   └── .messenger-favorites
│       │   └── .listOfContacts
│       └── .messenger-tab.search-tab
│           └── .search-records
│
├── .messenger-messagingView             ← Panel central (chat)
│   ├── .m-header.m-header-messaging
│   │   ├── .header-avatar
│   │   ├── .user-name #ark-contact-name-btn
│   │   └── .m-header-right > .chat-dropdown
│   ├── .m-body.messages-container
│   │   └── .messages > [messageCard × N]
│   ├── #ark-preview-zone                ← Preview multi-archivo
│   │   ├── #ark-file-counter
│   │   ├── #ark-preview-cancel
│   │   └── #ark-file-stack
│   └── sendForm
│
└── .messenger-infoView                  ← Panel derecho (info)
```

---

## Clases CSS críticas — NO renombrar

| Clase | Uso |
|---|---|
| `.messenger` | Contenedor raíz |
| `.messenger-listView` | Panel izquierdo |
| `.messenger-messagingView` | Panel central |
| `.messenger-infoView` | Panel derecho |
| `.m-header` | Header de cualquier panel |
| `.m-header-messaging` | Header del panel de chat |
| `.messenger-list-item` | Fila de contacto |
| `.m-list-active` | Contacto activo/seleccionado |
| `.message-card` | Tarjeta de mensaje |
| `.mc-sender` | Mensaje propio |
| `.message-card-content` | Contenido de la tarjeta |
| `.message` | Burbuja de texto |
| `.messenger-sendCard` | Contenedor del formulario de envío |
| `.m-send` | Textarea de mensaje |
| `.send-button` | Botón enviar |
| `.upload-attachment` | Input file adjunto |
| `.avatar` | Imagen de avatar circular |
| `.av-l / .av-m / .av-s` | Tamaños de avatar (100px / 45px / 32px) |
| `.activeStatus` | Punto de estado online |
| `.messenger-favorites` | Barra horizontal de favoritos |
| `.app-scroll` | Scrollbar personalizado |
| `.app-modal` | Overlay de modal genérico |
| `.file-download-card` | Tarjeta de archivo adjunto |
| `.attachment-preview` | Preview antes de enviar adjunto |
| `.internet-connection` | Banner de estado de conexión |

---

## IDs JS críticos — NO renombrar

| ID | Descripción |
|---|---|
| `#avatarModal` | Modal de cambio de avatar |
| `#avatarPreview` | Preview circular del avatar |
| `#avatarPreviewWrapper` | Wrapper clickeable del avatar |
| `#avatarGallery` | Grid de avatares DiceBear |
| `#avatarConfirm` | Barra de confirmación sticky |
| `#avatarConfirmSave` | Botón "Sí" confirmar avatar |
| `#avatarFileInput` | Input file del form de avatar |
| `#avatarUploadBtn` | Botón guardar avatar |
| `#selectedPresetAvatar` | Hidden input con URL del avatar seleccionado |
| `#ark-delete-conv-modal` | Modal eliminar conversación |
| `#ark-del-conv-me` | Botón eliminar solo para mí |
| `#ark-del-conv-all` | Botón eliminar para todos |
| `#chatDropdownMenu` | Menú desplegable del chat |
| `#ark-contact-name-btn` | Nombre del contacto en header |
| `#ark-toast-container` | Contenedor de toasts |
| `#ark-preview-zone` | Zona de preview multi-archivo |
| `#ark-file-stack` | Stack de tarjetas de archivos |
| `#ark-file-counter` | Contador de archivos seleccionados |
| `#ark-preview-cancel` | Cancelar todos los archivos |
| `#message-form` | Formulario de envío |
| `#vault-trigger` | Botón 🗄️ que abre la bóveda |

---

## Variables CSS

```css
:root { --primary-color: {$messengerColor}; }
```

Usada en: burbujas propias, bordes activos, íconos, rings de progreso de upload, zona de preview de archivos, tabs activos, badges de no leídos.

---

## Archivos JS

| Archivo | Responsabilidad |
|---|---|
| `public/js/chatify/code.js` | Core: mensajes, contactos, Pusher, chunked upload, multi-file queue |
| `public/js/chatify/webrtc-transfer.js` | Transferencia P2P de archivos via WebRTC |
| `public/js/chatify/autosize.js` | Auto-resize del textarea |

**JS inline en `app.blade.php`:**
- Sistema multi-file preview (`_arkFileQueue`, `arkAddFilesToPreview`, `arkCardProgress`, `arkCardDone`, `arkCardError`)
- Modal de avatar (`buildGallery`, `closeAvatarModal`)
- Sistema de toasts (`arkToast()`)
- Indicador de estado online/offline
- Panel de Google Drive

---

## Rutas usadas por JS

| Route name | Uso |
|---|---|
| `avatar.upload` | Subir imagen de avatar |
| `avatar.from-url` | Guardar avatar desde URL DiceBear |
| `avatar.update` | Actualizar settings (color, dark mode) |
| `profile.update` | Actualizar nombre/tagname/password |
| `send.message` | Enviar mensaje de texto |
| `chatify.chunk.upload` | Subir chunk de archivo |
| `pusher.auth` | Autenticación de canal privado Pusher |
| `attachments.download` | Descargar archivo adjunto |
| `webrtc.signal` | Relay de señales WebRTC |
| `webrtc.save` | Guardar metadatos de transferencia P2P |
