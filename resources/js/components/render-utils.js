/**
 * Utilidades de Renderizado Mejorado para Chat
 * Soluciona problemas de sincronización y performance
 */

// ============================================
// AUTO-SCROLL INTELIGENTE
// ============================================

let scrollTimeout;
let isUserScrolling = false;
let lastScrollTop = 0;
const SCROLL_DEBOUNCE_MS = 50;

/**
 * Scroll debounced al final del chat
 * Detecta si el usuario está scrolleando y respeta su posición
 */
export function debouncedScrollToBottom(container, forceScroll = false) {
    if (!container) return;
    
    clearTimeout(scrollTimeout);
    
    // Si no forzamos scroll y el usuario está scrolleando, no interrumpir
    if (!forceScroll && isUserScrolling) {
        console.log('[RENDER] ⏸️ Usuario está scrolleando, respetando posición');
        return;
    }
    
    scrollTimeout = setTimeout(() => {
        requestAnimationFrame(() => {
            if (container && container.scrollHeight > 0) {
                const scrollHeight = container.scrollHeight;
                const clientHeight = container.clientHeight;
                const currentScroll = container.scrollTop;
                
                // Scroll suave al final
                container.scrollTo({
                    top: scrollHeight,
                    behavior: 'smooth'
                });
                
                console.log(`[RENDER] 📍 Scroll al final: ${scrollHeight}px`);
            }
        });
    }, SCROLL_DEBOUNCE_MS);
}

/**
 * Scroll inmediato al final (sin debounce)
 * Usado para cargas iniciales
 */
export function scrollToBottom(container) {
    if (!container) return;
    
    requestAnimationFrame(() => {
        if (container && container.scrollHeight > 0) {
            container.scrollTop = container.scrollHeight;
            console.log('[RENDER] ⬇️ Scroll inmediato al final');
        }
    });
}

/**
 * Detectar si el usuario está al final del chat
 * Útil para decidir si auto-scrollear o no
 */
export function isAtBottom(container, threshold = 100) {
    if (!container) return false;
    
    const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
    return distanceFromBottom < threshold;
}

/**
 * Configurar listeners para detectar scroll del usuario
 * Permite pausar auto-scroll mientras el usuario navega
 */
export function setupAutoScrollDetection(container) {
    if (!container) return;
    
    let scrollTimeout;
    
    container.addEventListener('scroll', () => {
        // Marcar como scrolleando
        isUserScrolling = true;
        lastScrollTop = container.scrollTop;
        
        // Limpiar timeout anterior
        clearTimeout(scrollTimeout);
        
        // Reanudar auto-scroll después de 2 segundos de inactividad
        scrollTimeout = setTimeout(() => {
            isUserScrolling = false;
            console.log('[RENDER] 🔄 Reanudando auto-scroll');
            
            // Si estamos cerca del final, auto-scrollear
            if (isAtBottom(container, 150)) {
                debouncedScrollToBottom(container, true);
            }
        }, 2000);
    }, { passive: true });
    
    console.log('[RENDER] ✅ Auto-scroll detection activado');
}

// ============================================
// LIMPIEZA DE LISTENERS PREVIOS
// ============================================

export function cleanupOldListeners(elementId, newElement) {
    const oldElement = document.getElementById(elementId);
    if (oldElement && oldElement.parentNode) {
        // Clonar para remover todos los listeners
        const cloned = oldElement.cloneNode(true);
        oldElement.parentNode.replaceChild(cloned, oldElement);
        console.log(`[RENDER] ✅ Listeners antiguos removidos de ${elementId}`);
        return cloned;
    }
    return newElement;
}

// ============================================
// VALIDACIÓN DE CONTENEDORES ROBUSTA
// ============================================

export function getValidContainer(containerId, fallbackId = null) {
    let container = document.getElementById(containerId);
    
    if (!container && fallbackId) {
        console.warn(`[RENDER] ⚠️ ${containerId} no encontrado, usando fallback: ${fallbackId}`);
        container = document.getElementById(fallbackId);
    }
    
    if (!container) {
        console.error(`[RENDER] ❌ Contenedor no encontrado: ${containerId}`);
        return null;
    }
    
    return container;
}

// ============================================
// VERIFICAR SI ELEMENTO EXISTE
// ============================================

export function elementExists(elementId) {
    return document.getElementById(elementId) !== null;
}

// ============================================
// ESCAPE HTML CENTRALIZADO
// ============================================

export function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// ============================================
// ACTUALIZAR STATUS DE MENSAJE
// ============================================

export function updateMessageStatus(messageId, isRead = false) {
    const messageElement = document.getElementById(`message-${messageId}`);
    if (!messageElement) {
        console.warn(`[RENDER] ⚠️ Mensaje no encontrado: ${messageId}`);
        return false;
    }
    
    const statusElement = messageElement.querySelector('.message-status');
    if (!statusElement) {
        console.warn(`[RENDER] ⚠️ Status no encontrado en mensaje: ${messageId}`);
        return false;
    }
    
    const newStatus = isRead ? '✓✓' : '✓';
    if (statusElement.textContent !== newStatus) {
        statusElement.textContent = newStatus;
        statusElement.classList.toggle('message-status--read', isRead);
        console.log(`[RENDER] ✅ Status actualizado: ${messageId} -> ${newStatus}`);
        return true;
    }
    
    return false;
}

// ============================================
// LOTE DE ACTUALIZACIONES OPTIMIZADO
// ============================================

export function batchUpdateMessageStatuses(messageIds, isRead = false) {
    if (!Array.isArray(messageIds) || messageIds.length === 0) return;
    
    // Usar requestAnimationFrame para agrupar cambios de DOM
    requestAnimationFrame(() => {
        messageIds.forEach(id => {
            updateMessageStatus(id, isRead);
        });
        console.log(`[RENDER] ✅ ${messageIds.length} mensajes actualizados en lote`);
    });
}

// ============================================
// LIMPIAR CONTENEDOR SEGURAMENTE
// ============================================

export function clearContainer(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn(`[RENDER] ⚠️ Contenedor no existe: ${containerId}`);
        return false;
    }
    
    // Remover todos los listeners removiendo y re-insertando
    const parent = container.parentNode;
    if (parent) {
        const clone = container.cloneNode(false);
        parent.replaceChild(clone, container);
        console.log(`[RENDER] ✨ Contenedor limpiado: ${containerId}`);
        return true;
    }
    
    return false;
}

// ============================================
// SINCRONIZAR ESTADO VISUAL DE MENSAJES
// ============================================

export function syncMessageVisualState(message) {
    const messageDiv = document.getElementById(`message-${message.id}`);
    if (!messageDiv) return;
    
    const timeElement = messageDiv.querySelector('.message-time');
    const statusElement = messageDiv.querySelector('.message-status');
    const textElement = messageDiv.querySelector('.message-text');
    
    // Actualizar texto
    if (textElement && textElement.textContent !== message.message) {
        textElement.innerHTML = escapeHtml(message.message);
    }
    
    // Actualizar timestamp
    if (timeElement) {
        const time = new Date(message.created_at).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        if (timeElement.textContent !== time) {
            timeElement.textContent = time;
        }
    }
    
    // Actualizar status si es mensaje propio
    if (statusElement && message.is_read !== undefined) {
        const newStatus = message.is_read ? '✓✓' : '✓';
        if (statusElement.textContent !== newStatus) {
            statusElement.textContent = newStatus;
            statusElement.classList.toggle('message-status--read', message.is_read);
        }
    }
}

// ============================================
// AGREGAR MENSAJE CON VALIDACIONES
// ============================================

export function appendMessageSafe(container, message, isSender = false) {
    if (!container) {
        console.error('[RENDER] ❌ Contenedor inválido para agregar mensaje');
        return false;
    }
    
    // Verificar que el mensaje sea válido
    if (!message?.id || !message?.message) {
        console.warn('[RENDER] ⚠️ Mensaje inválido:', message);
        return false;
    }
    
    // Prevenir duplicados
    if (document.getElementById(`message-${message.id}`)) {
        console.log(`[RENDER] ⚠️ Mensaje duplicado evitado: ${message.id}`);
        return false;
    }
    
    const time = new Date(message.created_at).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const statusHtml = isSender
        ? `<span class="message-status ${message.is_read ? 'message-status--read' : ''}" id="status-${message.id}">
             ${message.is_read ? '✓✓' : '✓'}
           </span>`
        : '';
    
    const wrapper = document.createElement('div');
    wrapper.className = `message-wrapper ${isSender ? 'message-wrapper--sent' : 'message-wrapper--received'}`;
    
    wrapper.innerHTML = `
        <div class="message-bubble ${isSender ? 'message-bubble--sent' : 'message-bubble--received'}" id="message-${message.id}">
            <div class="message-content">
                <div class="message-text">${escapeHtml(message.message)}</div>
                <div class="message-meta">
                    <span class="message-time">${time}</span>
                    ${statusHtml}
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(wrapper);
    console.log(`[RENDER] ✅ Mensaje agregado: ${message.id}`);
    return true;
}

// ============================================
// RENDERIZAR MÚLTIPLES MENSAJES EFICIENTEMENTE
// ============================================

export function renderMessagesBatch(container, messages, currentUserId) {
    if (!container || !Array.isArray(messages)) {
        console.error('[RENDER] ❌ Contenedor o mensajes inválidos');
        return 0;
    }
    
    // Usar DocumentFragment para mejor performance
    const fragment = document.createDocumentFragment();
    let renderedCount = 0;
    
    messages.forEach(message => {
        // Verificar duplicados
        if (document.getElementById(`message-${message.id}`)) {
            console.log(`[RENDER] ⚠️ Saltando duplicado: ${message.id}`);
            return;
        }
        
        const isSender = message.sender_id === parseInt(currentUserId);
        const time = new Date(message.created_at).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const statusHtml = isSender
            ? `<span class="message-status ${message.is_read ? 'message-status--read' : ''}" id="status-${message.id}">
                 ${message.is_read ? '✓✓' : '✓'}
               </span>`
            : '';
        
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper ${isSender ? 'message-wrapper--sent' : 'message-wrapper--received'}`;
        
        wrapper.innerHTML = `
            <div class="message-bubble ${isSender ? 'message-bubble--sent' : 'message-bubble--received'}" id="message-${message.id}">
                <div class="message-content">
                    <div class="message-text">${escapeHtml(message.message)}</div>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        ${statusHtml}
                    </div>
                </div>
            </div>
        `;
        
        fragment.appendChild(wrapper);
        renderedCount++;
    });
    
    // Agregar todo de una vez (reflow único)
    requestAnimationFrame(() => {
        container.appendChild(fragment);
        console.log(`[RENDER] ✅ ${renderedCount} mensajes renderizados en lote`);
    });
    
    return renderedCount;
}

// ============================================
// EXPORTAR FUNCIONES GLOBALES
// ============================================

window.debouncedScrollToBottom = debouncedScrollToBottom;
window.scrollToBottom = scrollToBottom;
window.isAtBottom = isAtBottom;
window.setupAutoScrollDetection = setupAutoScrollDetection;
window.cleanupOldListeners = cleanupOldListeners;
window.updateMessageStatus = updateMessageStatus;
window.syncMessageVisualState = syncMessageVisualState;
