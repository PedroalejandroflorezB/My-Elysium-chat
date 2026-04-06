# Estándares Web, UX/UI y Accesibilidad

## UX/UI — Diseño centrado en el usuario

- La navegación debe ser clara, intuitiva y predecible.
- Los estados de error, carga y éxito siempre deben comunicarse al usuario con feedback visual.
- Usar modales o toasts para errores y confirmaciones — nunca `alert()` nativo del navegador.
- Los formularios deben validar en tiempo real cuando sea posible, no solo al submit.

### Modales de autenticación — cierre exclusivo por X

Los modales de login y registro (`#modal-login`, `#modal-register` en `welcome.blade.php`) son **no-dismissibles**: no se cierran al hacer clic en el overlay ni al presionar `Escape`. El único mecanismo de cierre es el botón X del propio modal.

- Quitar `onclick="handleOverlayClick(event)"` del elemento overlay cuando contiene modales de auth.
- En el listener de `keydown`, el `Escape` no debe llamar `closeModal()` mientras `activeModal` sea login o registro — solo debe actuar sobre paneles de navegación (`goHome()`).
- Razón: evitar cierres accidentales en formularios con datos ya ingresados, y reforzar que el usuario tome una decisión explícita (cerrar o enviar).
- Esta regla aplica únicamente a modales de autenticación. Los demás modales del sistema (confirmación, perfil, avatar) pueden seguir cerrándose con `Escape` o clic en backdrop según su contexto.

## Responsividad (Mobile-First)

- Diseñar primero para móvil, luego escalar con breakpoints `sm:`, `md:`, `lg:`.
- Ningún elemento debe desbordarse horizontalmente en pantallas pequeñas.
- Menús, modales y paneles deben ser usables en touch.

## Velocidad de carga

- No cargar librerías JS/CSS que no se usen en la página actual.
- Las llamadas a la API deben ser mínimas — no hacer múltiples requests cuando uno es suficiente.
- Evitar re-renders o re-fetches innecesarios. Cachear respuestas cuando el dato no cambia frecuentemente.
- Imágenes: usar tamaños apropiados, nunca cargar una imagen de 2000px para mostrarla en 40px.

## Estructura y Jerarquía

- Usar jerarquía semántica de títulos: un solo `<h1>` por página, luego `<h2>`, `<h3>`.
- El contenido debe ser legible por humanos y por motores de búsqueda.
- Usar etiquetas semánticas: `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`.

## Accesibilidad

- Todo elemento interactivo debe ser accesible por teclado (`Tab`, `Enter`, `Escape`).
- Imágenes deben tener `alt` descriptivo. Iconos decorativos: `aria-hidden="true"`.
- Inputs siempre deben tener `<label>` asociado (via `for` o wrapping).
- Contraste mínimo de texto: 4.5:1 para texto normal, 3:1 para texto grande.
- Modales deben atrapar el foco mientras están abiertos y restaurarlo al cerrarse.

## Seguridad

- HTTPS obligatorio en producción.
- Nunca exponer datos sensibles en respuestas JSON (`is_king`, contraseñas, tokens).
- CSRF token en todos los formularios y requests POST/PUT/DELETE.
- Validar y sanitizar toda entrada del usuario en el servidor, no solo en el cliente.

## Optimización de imágenes

- Usar `alt` descriptivo en todas las imágenes de contenido.
- Preferir formatos modernos (WebP) cuando sea posible.
- Definir `width` y `height` para evitar layout shift (CLS).

## Consistencia en el diseño

- Paleta de colores: indigo/slate/gray como base, verde para éxito, rojo para error, amarillo para advertencia.
- Tipografía consistente — no mezclar más de 2 familias de fuentes.
- Espaciado y bordes coherentes en todos los componentes (mismos `rounded-xl`, `border-gray-700`, etc.).
- El modo oscuro es el predeterminado. Todos los componentes nuevos deben verse bien en dark mode.

## Estética Midnight Executive — formularios auth y modales del home

Los formularios de autenticación (`login`, `register`, `forgot-password`, `reset-password`) y los modales del `welcome.blade.php` usan CSS inline en lugar de clases Tailwind. Esto es una excepción deliberada justificada porque estas vistas son páginas standalone donde el CSS inline garantiza que la paleta no sea sobreescrita por resets de terceros.

Tokens de diseño obligatorios para estos componentes:

| Elemento | Valor |
|---|---|
| Fondo de página | `background: #080e1a` |
| Fondo de card | `background: rgba(22,28,40,0.95)` |
| Fondo de inputs | `background: rgba(8,14,26,0.8)`, sin border, `border-radius: 12px` |
| Glow al focus | `box-shadow: 0 0 0 2px rgba(182,196,255,.25)` |
| Labels | `font-size: 0.65rem`, `text-transform: uppercase`, `letter-spacing: .05em`, `color: #8d90a2` |
| Texto secundario / subtítulos | `color: #c3c5d8` — nunca `#8d90a2` para texto de párrafo en fondo oscuro, ese valor no cumple contraste suficiente |
| Títulos de card | `color: #b6c4ff`, `font-weight: 900`, `letter-spacing: -.02em` |
| Botón primario | `border-radius: 10px`, `background: linear-gradient(135deg,#2962ff,#4f7fff)`, `font-weight: 700`, `text-transform: uppercase`, `font-size: .78rem` |
| Botón secundario (outline) | `border-radius: 10px`, `border: 1px solid rgba(41,98,255,.45)`, `color: #c3c5d8` |
| Accent pill izquierdo | `position:absolute; left:0; width:4px; background:rgba(41,98,255,0.6); border-radius:0 4px 4px 0` |
| Footer branding | opacidad `.4`, texto uppercase `0.6rem`, punto decorativo `#8dcdff` |

### Autofill del navegador — override obligatorio

Chrome/Edge inyectan `background: #faffbd` (amarillo) en inputs autocompletados, sobreescribiendo el fondo oscuro. Además, Chrome resetea el `border-radius` del input al aplicar autofill. Neutralizar siempre con:

```css
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px rgba(8,14,26,0.95) inset !important;
    -webkit-text-fill-color: #dde2f4 !important;
    caret-color: #dde2f4;
    border-radius: 12px !important; /* Chrome resetea border-radius en autofill */
}
```

Y en la transición del input agregar `background-color 9999s` — el delay extremo impide que el navegador aplique su animación de autofill sin causar saltos bruscos:

```css
transition: box-shadow .2s, background-color 9999s;
```

Aplica a: `.modal-form input`, `.contact-form input`, y cualquier input nuevo sobre fondo oscuro.

**Solución definitiva para `border-radius` con autofill**: El `border-radius: 12px !important` en el bloque `-webkit-autofill` no es suficiente en todos los casos — Chrome puede ignorarlo. La solución robusta es envolver el input en un `div` con `overflow: hidden; border-radius: 12px`. El contenedor recorta el autofill a nivel del DOM, independientemente de lo que Chrome haga con el pseudo-elemento interno.

El hover/focus ring (`box-shadow`) se mueve al wrapper usando `:hover` y `:focus-within`. Los inputs dentro del wrapper deben tener `box-shadow: none` para no duplicar el efecto.

```css
.modal-input-wrap { border-radius: 12px; overflow: hidden; }
.modal-input-wrap:hover { box-shadow: 0 0 0 2px rgba(182,196,255,.35); }
.modal-input-wrap:focus-within { box-shadow: 0 0 0 2px rgba(182,196,255,.25); }
.modal-input-wrap input:hover,
.modal-input-wrap input:focus { box-shadow: none; }
```

El mismo patrón aplica a `.modal-pw-wrap` (inputs con botón de ojo) — ya tiene `overflow: hidden` por su `position: relative`, solo agregar `border-radius`, `box-sizing: border-box` y los estados `:hover`/`:focus-within`.

```css
.modal-pw-wrap { border-radius: 12px; overflow: hidden; box-sizing: border-box; }
```

Sin `box-sizing: border-box`, el `box-shadow` del hover se calcula diferente al del `.modal-input-wrap` y el glow se ve inconsistente entre inputs.

**Regla: `border-radius: 0` en inputs dentro de wrapper**: Cuando el input vive dentro de `.modal-input-wrap` o `.modal-pw-wrap`, debe tener `border-radius: 0`. El redondeo lo maneja el wrapper con `overflow: hidden` — si el input conserva su propio `border-radius`, se crea un efecto de "doble borde" visible en las esquinas, especialmente en el campo de contraseña donde el wrapper tiene `position: relative`.

```css
.modal-input-wrap input,
.modal-pw-wrap input { border-radius: 0; }
```

**Regla: no usar `.modal-form input:hover` para el glow**: La regla genérica `input:hover` dispara cuando el cursor pasa por cualquier parte del `.modal-field` (incluyendo el label y el espacio exterior al wrapper), no solo sobre el input visible. Como todos los inputs están dentro de un wrapper, el hover debe definirse únicamente en el wrapper. La regla genérica debe eliminarse.

```css
/* ❌ Evitar — activa el glow en toda el área del .modal-field */
.modal-form input:hover { box-shadow: 0 0 0 2px rgba(182,196,255,.35); }

/* ✅ Correcto — el hover solo responde al área visual del wrapper */
.modal-input-wrap:hover { box-shadow: 0 0 0 2px rgba(182,196,255,.35); }
.modal-pw-wrap:hover    { box-shadow: 0 0 0 2px rgba(182,196,255,.35); }
```

**Truco `readonly` — solución definitiva contra autofill en campos de texto/email**: Chrome no aplica autofill a campos `readonly`. Agregar `readonly` + `autocomplete="off"` al input y quitarlo al focus con JS:

```html
<input type="email" autocomplete="off"
       readonly onfocus="this.removeAttribute('readonly')">
```

Esto evita completamente el fondo amarillo sin depender de CSS. Aplica a campos de email y nombre en modales. Los campos de contraseña usan `autocomplete="new-password"` / `autocomplete="current-password"` que Chrome respeta sin aplicar el estilo amarillo.

Regla de contraste para texto en fondos oscuros (`#080e1a` / `#0d131f`):
- Texto de párrafo y subtítulos: usar `#c3c5d8` como mínimo — cumple 4.5:1.
- `#8d90a2` solo es aceptable para labels de inputs (tamaño muy pequeño, contexto de formulario) y texto decorativo/secundario de baja jerarquía.
- Nunca usar `#8d90a2` para texto de cuerpo o descripciones que el usuario necesita leer.

Regla de contraste para paneles del chat (fondos `rgba(15,23,42,...)` / `rgba(30,27,75,...)`):
- Texto de lista, descripciones y estados secundarios: usar `#94a3b8` como mínimo — cumple 4.5:1 sobre estos fondos.
- `#64748b` no cumple contraste suficiente sobre fondos oscuros del chat — reservar solo para texto puramente decorativo o iconos sin valor informativo.
- Aplica a: paneles laterales (Drive, info de contacto), tooltips, listas informativas dentro del chat.

## Manejo de errores en la API

- Toda llamada `fetch` debe tener `try/catch`.
- Los errores de red y los errores de servidor (4xx, 5xx) deben manejarse por separado.
- Nunca mostrar errores técnicos crudos al usuario — traducirlos a mensajes comprensibles.
- Usar el sistema de toasts (`arkToast`) para notificaciones no bloqueantes.
- Usar modales de confirmación para acciones destructivas (borrar, cambiar rol, etc.).

## Estructura de URLs

- Usar guiones (`-`) para separar palabras: `/mis-mensajes`, `/perfil-usuario`.
- Evitar guiones bajos (`_`) y URLs sin separación.
- Rutas nombradas en Laravel con puntos: `users.index`, `chat.show`.

## JavaScript en el proyecto

- Evitar `alert()`, `confirm()` y `prompt()` nativos — reemplazar con modales/toasts custom.
- Los `console.log` de debug deben eliminarse antes de producción.
- Preferir `fetch` con `async/await` sobre callbacks anidados.
- Siempre manejar el estado de carga (deshabilitar botón, mostrar spinner) durante requests.

---

## Accesibilidad NTC 5854 / Resolución 1519 (WCAG 2.1 AA — Colombia)

Este proyecto debe cumplir con la NTC 5854 y la Resolución 1519 de 2020, basadas en WCAG 2.1 nivel AA. Las reglas siguientes son obligatorias en todos los formularios y en la interfaz del chat.

### Formularios (login, registro, recuperación)

- **`<label>` siempre visible**: Nunca usar el `placeholder` como única etiqueta. El `<label>` debe estar visible antes, durante y después de que el usuario escriba. El placeholder es solo ayuda adicional.
- **Contraste mínimo 4.5:1**: Texto normal sobre fondo. Verificar con herramienta antes de usar colores nuevos. `text-slate-300` sobre `bg-gray-900` cumple; `text-gray-500` sobre `bg-gray-900` puede no cumplir para texto de error.
- **Indicador de foco visible**: Nunca usar `outline: none` sin reemplazarlo. Usar `focus:ring-2 focus:ring-indigo-500` en todos los inputs y botones. El anillo debe ser visible en modo oscuro.
- **Errores con ícono + texto**: No depender solo del color rojo para indicar error. Incluir siempre un ícono (⚠️ o SVG) y texto descriptivo. Ejemplo: `⚠️ El correo es obligatorio` — no solo cambiar el borde a rojo.
- **`aria-describedby`**: Asociar el mensaje de error al input con `aria-describedby="campo-error"` para que los lectores de pantalla lo anuncien.
- **`aria-invalid="true"`**: Agregar al input cuando tiene error de validación.
- **`aria-live="polite"`**: En contenedores de errores dinámicos para que el lector de pantalla los anuncie sin interrumpir.
- **Botones con texto o `aria-label`**: Si un botón solo tiene ícono (ojo, enviar), debe tener `aria-label` descriptivo.
- **Tamaño mínimo de área de clic**: 44×44px para botones táctiles. Usar `min-h-[44px] min-w-[44px]` en botones de iconos.

### Interfaz del chat

- **Tamaño de texto escalable**: La interfaz debe funcionar correctamente al 200% de zoom del navegador. No usar `px` fijos para contenedores que contienen texto — usar `rem` o clases de Tailwind que escalen.
- **Botones de acción mínimo 44×44px**: "Adjuntar archivo", "Enviar", "Emoji" — todos deben tener área de clic suficiente en móvil.
- **Identificación de mensajes sin depender del color**: Las burbujas de chat deben diferenciarse por alineación (derecha/izquierda) además del color. No usar solo color para indicar quién envía.
- **Orden lógico para lectores de pantalla**: El historial de mensajes debe leerse antes que la caja de texto. Usar `tabindex` si el orden del DOM no es el correcto.
- **`aria-label` en botones de ícono**: `aria-label="Enviar mensaje"`, `aria-label="Adjuntar archivo"`, `aria-label="Ver opciones"`.
- **`role="log"` en el historial**: El contenedor de mensajes debe tener `role="log" aria-live="polite"` para que los nuevos mensajes sean anunciados.

### Manejo de archivos pesados (uploads)

- **Barra de progreso con porcentaje en texto**: No basta con una animación circular. Usar `<progress>` nativo o un elemento con `role="progressbar" aria-valuenow="{n}" aria-valuemin="0" aria-valuemax="100"` y texto visible `"45% completado"`.
- **Errores de upload persistentes**: Si falla la subida de un archivo grande, el mensaje de error debe permanecer hasta que el usuario lo cierre o reintente. No usar toasts que desaparecen en 2-4 segundos para errores críticos de upload.
- **Nombres de archivo con `text-overflow: ellipsis`**: Los nombres largos no deben romper el layout de la burbuja. Usar `truncate` de Tailwind o `overflow-hidden text-ellipsis whitespace-nowrap max-w-[200px]`.

### Paleta de colores accesible (contraste mínimo 4.5:1)

Estos valores garantizan legibilidad para personas con baja visión o daltonismo. Usarlos como referencia al crear componentes nuevos:

| Rol | Color | Hex |
|---|---|---|
| Fondo principal (light) | Blanco / Gris muy claro | `#FFFFFF` / `#F8F9FA` |
| Texto principal | Gris casi negro | `#212529` |
| Azul de acción (botones/links) | Azul profundo | `#0056b3` |
| Verde de éxito | Verde estándar | `#198754` |
| Rojo de error | Rojo estándar | `#DC3545` |
| Indicador de foco (teclado) | Amarillo vibrante | `#FFC107` |

En dark mode (predeterminado en Elysium Ito): usar `text-slate-300` sobre `bg-gray-900` para texto normal — cumple 4.5:1. Evitar `text-gray-500` para mensajes de error.

### CSS de foco y navegación por teclado (NTC 5854 obligatorio)

El indicador de foco debe ser visible y claro. Aplicar en `app.css` para los elementos del chat:

```css
/* Foco visible para chat — NTC 5854 */
.chat-input:focus,
.btn-send:focus,
.file-upload:focus {
    outline: 3px solid #FFC107;
    outline-offset: 2px;
    box-shadow: 0 0 0 5px rgba(0, 86, 179, 0.2);
}

/* Burbujas de mensaje — escalado al 200% de zoom */
.message-bubble {
    word-wrap: break-word;
    min-height: 44px;
    padding: 12px;
}
```

En formularios de auth usar `focus:ring-2 focus:ring-indigo-500` de Tailwind (ya aplicado). Para el chat usar las clases CSS directas arriba.

### Barra de progreso accesible para uploads (obligatorio en archivos 1GB+)

No basta con una animación circular. El usuario debe saber exactamente cuánto falta:

```html
<div class="upload-container" role="region" aria-live="polite">
    <p id="upload-status">Subiendo archivo pesado (1GB)...</p>

    <progress id="file-progress" value="45" max="100"
              role="progressbar"
              aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"
              aria-labelledby="upload-status">
        45%
    </progress>

    <span class="text-sm">45% completado</span>

    <button type="button" class="btn-cancel" aria-label="Cancelar subida de archivo">
        Cancelar
    </button>
</div>
```

- El texto `"45% completado"` debe ser visible, no solo el `<progress>`.
- `aria-live="polite"` en el contenedor para que el lector de pantalla anuncie los cambios.
- Si falla la subida, el error debe **persistir** hasta que el usuario lo cierre — no usar toasts que desaparecen en 2-4 segundos para errores críticos.

### Regla de oro: sin dependencia del color

Colombia exige que los errores no se comuniquen solo con color. Siempre combinar:

- ❌ Mal: solo borde rojo en el input
- ✅ Bien: borde rojo + ícono SVG/⚠️ + texto descriptivo

Ejemplo para error de upload:
```html
<div role="alert" class="flex items-start gap-2 text-red-400">
    <svg aria-hidden="true"><!-- ícono advertencia --></svg>
    <span>⚠️ Error: Conexión perdida al subir el fragmento 45. Reintenta.</span>
</div>
```

Esto aplica a: errores de formulario, fallos de upload, mensajes de validación, estados de conexión perdida.

### Checklist rápido antes de entregar una vista

- [ ] Todos los inputs tienen `<label>` visible con `for` correcto
- [ ] Todos los botones de ícono tienen `aria-label`
- [ ] Los errores incluyen ícono + texto (no solo color)
- [ ] El foco con Tab es visible en todos los elementos interactivos
- [ ] La vista funciona al 200% de zoom sin elementos superpuestos
- [ ] Imágenes de contenido tienen `alt` descriptivo; iconos decorativos tienen `aria-hidden="true"`
- [ ] Contraste de texto verificado (mínimo 4.5:1)
- [ ] Barras de progreso tienen `aria-valuenow` + texto visible con porcentaje
- [ ] Errores de upload persisten hasta que el usuario los cierre
- [ ] Burbujas de estado Drive usan `role="status"` (processing), `role="alert"` (failed) y `aria-live` apropiado

---

## Estado `error_authorization` en burbujas de Drive

- Cuando `drive_status='error_authorization'`, la burbuja debe mostrar un mensaje diferente a `failed`:
  - Texto: "⚠️ Tu cuenta de Google fue desvinculada. Re-vincula Drive para completar la subida."
  - Botón: "Vincular Drive" (enlace a `/auth/google/drive`), no "Reintentar".
- Este estado usa `role="alert"` igual que `failed` — es un error crítico que no desaparece.
- El listener `channel.bind('drive-upload-updated')` debe manejar los **5 estados** del ENUM: `processing`, `synced`, `failed`, `error_authorization` (y `local` que no se emite). Son flujos distintos: `failed` = reencolar con botón "Reintentar", `error_authorization` = re-vincular cuenta con enlace a `/auth/google/drive`.

## Rate limiting en formularios públicos — feedback y persistencia

- Cuando el servidor responde 429, mostrar un **modal bloqueante** (no toast) con countdown `HH:MM:SS` en tiempo real. Los toasts que desaparecen no son válidos para errores de rate limit — el usuario debe ver claramente cuánto tiempo debe esperar.
- Guardar el timestamp de expiración en `localStorage` (`contact_rate_expires = Date.now() + seconds * 1000`). Al recargar la página, verificar silenciosamente si el bloqueo sigue activo — **no abrir el modal automáticamente**. El modal solo debe aparecer cuando el usuario intenta enviar de nuevo estando bloqueado. Esto evita interrumpir al usuario que solo visita la página sin intención de reenviar.
- Al expirar el countdown, limpiar `localStorage.removeItem('contact_rate_expires')` automáticamente.
- El modal de rate limit usa acento rojo (`rgba(239,68,68,0.6)`) en lugar del azul estándar — diferencia visualmente un bloqueo de seguridad de un modal de autenticación.
- El botón "Entendido" cierra el modal visualmente pero no levanta el bloqueo del servidor — el bloqueo lo controla el backend con `RateLimiter`.

## Bloqueo de uploads por disco lleno

- Si el servidor responde 503 en un chunk upload, mostrar mensaje persistente: "El servidor no tiene espacio disponible. Intenta más tarde."
- No usar toast que desaparece — es un error de infraestructura, no de usuario.

## Consentimiento Ley 1581 en formularios

- El checkbox de consentimiento debe tener `<label>` visible con texto completo de la autorización.
- Incluir referencia explícita a "Ley 1581 de 2012" en el texto del label.
- El error de validación si no se marca debe incluir ícono + texto (no solo borde rojo).
- `aria-describedby` apuntando al texto de descripción del consentimiento.

## Layout defensivo en paneles y componentes flex

- Todo panel con ancho fijo (`width:280px`) debe tener `box-sizing:border-box` para que el padding no desborde el contenedor.
- Los hijos directos de un flex container con ancho fijo deben tener `width:100%` y `box-sizing:border-box`.
- Usar `min-width:0` en divs flex que contienen texto largo — sin esto, el flexbox los expande más allá del contenedor padre.
- Paneles con scroll vertical: `overflow-y:auto; overflow-x:hidden` — nunca solo `overflow:hidden` si el contenido puede crecer.
- El contenedor raíz del chat (`.messenger`) debe tener `width: calc(100vw - 80px)` cuando hay un nav rail de 80px con `margin-left:80px`. Sin esto, `inline-flex` se expande más allá del viewport y los paneles laterales quedan cortados.
- El área de mensajes (`messenger-messagingView`) debe tener `min-width:0; flex:1` para ceder espacio a paneles secundarios. `width:100%` solo no es suficiente en un flex container con hermanos visibles.
- Usar `--topbar-height` como variable CSS en `style.css` para centralizar la altura del top bar. Todos los `calc()` de altura que dependan del top bar deben referenciar esta variable, no valores hardcodeados.
- `.messenger-tab` debe usar `height: calc(100vh - var(--topbar-height) - var(--listView-header-height))` — sin descontar el top bar, la lista de contactos desborda verticalmente.
- Columnas flex del chat (listView, messagingView) deben tener `gap:0` — cualquier `gap` entre header y body crea desalineación visual entre columnas.
- Headers de columnas del chat (`m-header`, `m-header-messaging`) deben tener `height: var(--listView-header-height); flex-shrink:0; box-sizing:border-box` para que todas las columnas queden alineadas horizontalmente.
- `.messenger-messagingView .m-body` debe usar `flex:1; min-height:0` en lugar de `height:100%` — dentro de un flex column, `height:100%` no funciona correctamente para el scroll.
- El `body` no absorbe el offset del top bar con padding — usa `height: 100%; overflow: hidden` limpio. El `.messenger` usa `height: calc(100vh - 64px); margin-top: 64px` para calcular su propia altura. Nunca combinar `padding-top` en el body con `height: 100%` en el hijo — la herencia de altura con padding no es consistente entre navegadores y puede causar que el chat se corte por abajo.
- **`html { height: 100% }` en bloques `<style>` inline de Blade**: Cuando el `body` usa `height: 100%` en un bloque `<style>` inline (como en `app.blade.php`), el `html` también debe tener `height: 100%` en ese mismo bloque. Si solo se define en `style.css` externo pero el inline sobreescribe el `body` sin incluir el `html`, la cadena de herencia de altura se rompe y los elementos flex hijos no calculan su altura correctamente. Regla: si defines `body { height: 100% }` en un `<style>` inline, siempre incluir `html { height: 100% }` antes.
- **Modales con contenido variable**: Todo modal debe tener `max-height: 90vh; overflow-y: auto; overflow-x: hidden` para que no quede cortado en pantallas pequeñas o cuando el contenido crece (campos de error, botones extra, separadores). Nunca usar `overflow: hidden` en un modal — impide el scroll cuando el contenido excede la pantalla. El overlay ya centra verticalmente con `display:flex; align-items:center; justify-content:center` — el modal solo necesita respetar el límite de altura.

## Paneles laterales y layout del chat

- Los paneles secundarios (Drive, info de contacto, etc.) deben desplegarse en el espacio derecho del layout (`#ark-right-panel`), no como overlays flotantes con `position:fixed`.
- El `#ark-right-panel` es parte del flex layout del `.messenger` — al mostrarse, el área de mensajes se reduce automáticamente. No usar `z-index` alto ni `position:fixed` para paneles que tienen espacio natural en el layout.
- El panel derecho se oculta en pantallas `<980px` (`display:none !important`) — no intentar mostrarlo en móvil.
- El toggle del panel se controla con `style.display = 'flex'` / `'none'` desde JS. El botón del nav rail recibe la clase `ark-nav-active` cuando el panel está abierto.
- No agregar listener `click` en `document` para cerrar el panel al hacer clic fuera — el panel derecho es parte del layout, no un dropdown flotante.

## Top Bar fija y alineación del layout

- El layout usa una barra horizontal fija (`#ark-top-bar`) de **64px** de alto en `top:0; left:0; right:0; z-index:210`.
- El nav rail empieza en `top:64px` (no en `top:0`) para no quedar debajo del top bar.
- **Estrategia de altura del `.messenger` (implementada)**: El `body` no tiene `padding-top` — queda limpio (`height: 100%; overflow: hidden`). El `.messenger` calcula su propia altura con `height: calc(100vh - 64px); margin-top: 64px`. Esta estrategia es más predecible entre navegadores que depender de la herencia de `height: 100%` con padding en el padre. No usar `padding-top: 64px` en el body combinado con `height: 100%` en el hijo — la herencia de altura con padding no es consistente entre navegadores.
- Cualquier elemento `position:fixed` nuevo debe respetar `top:64px` si debe quedar debajo del top bar.
- El avatar del usuario en el top bar debe tener fallback de iniciales cuando no hay imagen — nunca mostrar un círculo vacío.
- El nombre del contacto en el `m-header-messaging` debe estar oculto (`display:none`) por defecto y mostrarse solo cuando hay un chat activo. Nunca usar el nombre de la app como placeholder en ese espacio.
- **Alineación del logo con la columna lateral**: El `#ark-top-bar-logo` debe tener `width: 400px` (80px nav rail + 320px listView) para que el logo quede visualmente "atado" a la columna de contactos. El `padding-left` debe ser `94px` (80px del nav rail + 14px para alinear con el contenido interno del header). El borde derecho del logo debe coincidir con el borde derecho de la columna lateral.

## Epilepsia fotosensible y trastornos vestibulares — prefers-reduced-motion

**Regla obligatoria**: Todo archivo CSS que contenga `@keyframes`, `animation` o `transition` debe incluir al final el siguiente bloque:

```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration:        0.01ms !important;
        animation-iteration-count: 1      !important;
        transition-duration:       0.01ms !important;
        scroll-behavior:           auto   !important;
    }
}
```

Esto aplica a: `resources/css/app.css`, `public/css/chatify/style.css`, y cualquier `<style>` inline en vistas Blade que contenga animaciones.

**Por qué `0.01ms` y no `0`**: Usar `0` puede causar saltos bruscos de layout que también son problemáticos para usuarios con sensibilidad al movimiento. `0.01ms` es imperceptible pero evita el salto.

**Criterio de riesgo para nuevas animaciones (WCAG 2.3.1 / NTC 5854):**
- ❌ Prohibido: más de 3 flashes por segundo en cualquier área de la pantalla.
- ❌ Prohibido: flashes rojos puros (cualquier frecuencia).
- ❌ Prohibido: cambios bruscos de contraste alto a bajo en menos de 200ms.
- ✅ Permitido: transiciones suaves ≥ 150ms con `ease` o `ease-out`.
- ✅ Permitido: animaciones de entrada únicas (scale, fade, slide) de duración ≤ 700ms.
- ✅ Permitido: shimmer/skeleton con gradiente continuo (no flash).
