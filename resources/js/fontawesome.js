/**
 * Font Awesome SVG Configuration - Elysium Ito (MINIMAL)
 * Solo los íconos que realmente usamos - tree-shaking óptimo
 */

import { library } from '@fortawesome/fontawesome-svg-core';
import { 
  // === CHAT & MENSAJERÍA ===
  faPaperPlane,    // enviar mensaje
  faComments,      // placeholder chat vacío
  faPaperclip,     // adjuntar archivo
  faSmile,         // emoji
  faPhone,         // llamada de voz
  faVideo,         // llamada de video
  faEllipsisVertical, // más opciones (ellipsis-v)
  
  // === CONTACTOS & BÚSQUEDA ===
  faUser,          // avatar, usuario
  faUserPlus,      // enviar solicitud
  faUsers,         // lista de contactos
  faSearch,        // búsqueda
  faArrowLeft,     // volver
  
  // === ESTADOS & ACCIONES ===
  faCheck,         // aceptado, enviado
  faCheckCircle,   // éxito
  faTimes,         // cerrar, denegar (alias: faXmark)
  faXmark,         // cerrar (nombre oficial)
  faExclamationCircle, // error
  faInfoCircle,    // información
  faSpinner,       // cargando
  
  // === UI GENERAL ===
  faBars,          // menú hamburguesa
  faBell,          // notificaciones
  faCog,           // configuración
  faPowerOff,      // cerrar sesión
  faQrcode,        // código QR
  faImage,         // imagen/avatar
  faLock,          // autenticación
  faUnlock,        // desbloquear
  faPalette,       // selector de temas
  
  // === ARCHIVOS ===
  faFile,          // archivo genérico
  faDownload,      // descargar
  faUpload,        // subir
  faTrash,         // eliminar
  
} from '@fortawesome/free-solid-svg-icons';

import {
  // === REGULAR (OUTLINE) - SOLO LOS NECESARIOS ===
  faBell as farBell,
  faClock as farClock,
  faUser as farUser,
} from '@fortawesome/free-regular-svg-icons';

import {
  // === BRANDS - SOLO LOS NECESARIOS ===
  faGithub,
  faGoogle,
} from '@fortawesome/free-brands-svg-icons';

// ============================================
// REGISTRAR ÍCONOS EN LA LIBRERÍA
// ============================================

// Solid icons (los que más usamos)
library.add(
  faPaperPlane, faComments, faPaperclip, faSmile,
  faPhone, faVideo, faEllipsisVertical,
  faUser, faUserPlus, faUsers, faSearch, faArrowLeft,
  faCheck, faCheckCircle, faTimes, faXmark, faExclamationCircle,
  faInfoCircle, faSpinner,
  faBars, faBell, faCog, faPowerOff, faQrcode, faImage,
  faLock, faUnlock, faPalette, faFile, faDownload, faUpload, faTrash
);

// Regular icons
library.add(farBell, farClock, farUser);

// Brands icons
library.add(faGithub, faGoogle);

// ============================================
// FUNCIONES HELPERS PARA SVG
// ============================================

/**
 * Crear elemento SVG para un ícono
 * @param {string} name - Nombre del ícono (ej: 'paper-plane')
 * @param {string} prefix - Prefijo: 'fas' | 'far' | 'fab'
 * @param {Object} options - { title, size, class, spin }
 * @returns {SVGElement|null}
 */
export function createIcon(name, prefix = 'fas', options = {}) {
  const icons = library.definitions[prefix];
  const iconDef = icons?.[name];
  
  if (!iconDef) {
    console.warn(`⚠️ Icon "${prefix} ${name}" not registered. Available:`, Object.keys(icons || {}));
    return null;
  }
  
  // Crear elemento SVG
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 512 512');
  svg.setAttribute('aria-hidden', options.title ? 'false' : 'true');
  svg.setAttribute('role', options.title ? 'img' : 'presentation');
  
  // Título para accesibilidad
  if (options.title) {
    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
    title.textContent = options.title;
    svg.appendChild(title);
  }
  
  // Path del ícono: iconDef[4] contiene el path data
  const pathData = iconDef[4];
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('fill', 'currentColor');
  path.setAttribute('d', pathData);
  svg.appendChild(path);
  
  // Clases CSS para tamaño y estilo
  const sizeClass = { sm: 'fa-icon--sm', md: 'fa-icon--md', lg: 'fa-icon--lg', xl: 'fa-icon--xl' }[options.size || 'md'];
  svg.classList.add('fa-icon', sizeClass);
  
  if (options.class) {
    svg.classList.add(...options.class.split(' '));
  }
  if (options.spin) {
    svg.classList.add('fa-icon--spin');
  }
  
  return svg;
}

/**
 * Reemplazar placeholders <span class="icon-placeholder" data-icon="xxx"> con SVG
 * @param {HTMLElement} container - Elemento donde buscar placeholders
 */
export function migrateLegacyIcons(container = document.body) {
  container.querySelectorAll('.icon-placeholder[data-icon]').forEach(placeholder => {
    const iconName = placeholder.dataset.icon;
    const prefix = placeholder.dataset.prefix || 'fas';
    const size = placeholder.dataset.size;
    const title = placeholder.closest('[title]')?.title || placeholder.getAttribute('title') || '';
    const extraClass = placeholder.dataset.class || '';
    const spin = placeholder.dataset.spin === 'true';
    
    const svg = createIcon(iconName, prefix, {
      title,
      size,
      class: extraClass,
      spin
    });
    
    if (svg) {
      // Copiar atributos importantes
      const attrs = ['id', 'style', 'onclick', 'data-action'];
      attrs.forEach(attr => {
        if (placeholder.hasAttribute(attr)) {
          svg.setAttribute(attr, placeholder.getAttribute(attr));
        }
      });
      placeholder.replaceWith(svg);
    }
  });
}

// Exportar para uso global
export { library };
export default { createIcon, migrateLegacyIcons, library };