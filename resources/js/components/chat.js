import { setupMessageForm, appendMessageToChat, handleRealTimeMessage } from './messages.js';
import { initializePresence } from './presence.js';
import { subscribeToUserChannel, subscribeToChatChannel } from './websocket.js';
import { updateSidebarOnNewMessage, handleChatDeleted } from './sidebar.js';
import { 
    getValidContainer, 
    updateMessageStatus, 
    syncMessageVisualState,
    appendMessageSafe,
    batchUpdateMessageStatuses,
    escapeHtml as escapeHtmlUtil
} from './render-utils.js';

// ============================================
// INICIALIZACION DEL CHAT - Elysium Ito
// ============================================

console.log('Chat module initialized');

// Variable global para la suscripción de presence
window.chatPresenceChannel = null;

// Contador y UI de mensaje manual
window.chatManualScrollButton = null;
window.chatPreviewSize = 120; // preview fijo en último tramo
window.chatStayAtBottom = false;

/**
 * Sistema de Scroll Inteligente:
 * Si el usuario está cerca del fondo, autoscroll.
 * Si no, mostrar botón de "Nuevos mensajes".
 */
export function updateManualScrollHint() {
    const container = document.getElementById('messages-container');
    if (!container) return;
    const button = document.getElementById('manual-scroll-button');
    if (!button) return;

    const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
    const isAtBottom = distanceFromBottom < 50;

    if (isAtBottom) {
        button.style.display = 'none';
        button.classList.remove('has-new');
        window.chatStayAtBottom = true;
    } else {
        // Solo mostrar si hay contenido que justifique el scroll
        if (container.scrollHeight > container.clientHeight) {
            button.style.display = 'block';
        }
        window.chatStayAtBottom = false;
    }
}

export function scrollToBottom(force = false) {
    const container = document.getElementById('messages-container');
    if (!container) return;

    if (force || window.chatStayAtBottom) {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: force ? 'auto' : 'smooth'
        });
    } else {
        // Indicar visualmente que hay mensajes nuevos si no forzamos el scroll
        const button = document.getElementById('manual-scroll-button');
        if (button) {
            button.classList.add('has-new');
            button.innerHTML = '<i class="fas fa-arrow-down"></i> Mensajes nuevos';
        }
    }
}

export function maybeCreateManualScrollButton() {
    let btn = document.getElementById('manual-scroll-button');
    if (btn) return btn;

    btn = document.createElement('button');
    btn.id = 'manual-scroll-button';
    btn.className = 'manual-scroll-button';
    btn.type = 'button';
    btn.textContent = 'Ir a últimos';
    btn.style.position = 'absolute';
    btn.style.right = '18px';
    btn.style.bottom = '90px';
    btn.style.padding = '0.5rem 0.9rem';
    btn.style.borderRadius = '999px';
    btn.style.background = '#12a8ff';
    btn.style.color = '#fff';
    btn.style.border = 'none';
    btn.style.zIndex = '999';
    btn.style.cursor = 'pointer';
    btn.style.display = 'none';
    btn.style.boxShadow = '0 8px 22px rgba(0,0,0,.32)';

    btn.addEventListener('click', () => {
        const container = document.getElementById('messages-container');
        if (!container) return;

        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
        btn.style.display = 'none';
        btn.classList.remove('has-new');
        btn.innerHTML = '<i class="fas fa-arrow-down"></i>';
        window.chatStayAtBottom = true;
    });

    const chatMain = document.getElementById('chat-main');
    if (chatMain) {
        chatMain.appendChild(btn);
    } else {
        document.body.appendChild(btn);
    }

    return btn;
}

// ============================================
// UTILIDADES DE BÚSQUEDA - INTEGRACIÓN SPA
// ============================================

/**
 * Ocultar resultados de búsqueda y restaurar vista normal
 */
export function hideSearchResults() {
    const results = document.getElementById('search-results');
    const searchInput = document.getElementById('search-input');
    
    if (results) {
        results.style.display = 'none';
        results.innerHTML = '';
        results.classList.remove('has-results');
    }
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Restaurar tabs originales
    restoreSidebarTabs();
    
    console.log('[SEARCH] ✅ Resultados ocultados, vista restaurada');
}

/**
 * Restaurar tabs del sidebar después de búsqueda
 */
function restoreSidebarTabs() {
    const messages = document.getElementById('all-messages-list');
    const contacts = document.getElementById('contacts-list');
    const files = document.getElementById('tab-files');
    
    // Mostrar el tab activo según el botón seleccionado
    const activeBtn = document.querySelector('.sidebar__nav-btn.active');
    const activeTab = activeBtn?.dataset.tab;
    
    if (messages) messages.style.display = (activeTab === 'messages') ? 'block' : 'none';
    if (contacts) contacts.style.display = (activeTab === 'contacts') ? 'block' : 'none';
    if (files) files.style.display = (activeTab === 'files') ? 'block' : 'none';
}

/**
 * Manejar selección de resultado de búsqueda
 */
export function handleSearchResultSelect(username, userId) {
    console.log('[SEARCH] ✅ Resultado seleccionado:', username);
    
    // 1. Ocultar resultados
    hideSearchResults();
    
    // 2. Cargar chat
    if (window.navigateToChat) {
        window.navigateToChat(username);
    } else {
        // Fallback tradicional
        window.location.href = `/@${username}`;
    }
    
    // 3. Actualizar estado activo en sidebar
    updateSidebarActiveState(username);
}

// ============================================
// GESTIÓN DE ELIMINACIÓN DE CHAT
// ============================================

export function handleChatDeletedExternally(roomIdOrEvent) {
    const roomId = typeof roomIdOrEvent === 'string' ? roomIdOrEvent : (roomIdOrEvent.room_id || roomIdOrEvent.roomId);
    console.log('[SIDEBAR] 🗑️ handleChatDeletedExternally:', roomId);
    
    const allMessagesList = document.getElementById('all-messages-list');
    if (!allMessagesList) return;
    
    // Buscar conversación por roomId
    const conversationItems = allMessagesList.querySelectorAll('.conversation-item');
    let foundItem = null;
    
    conversationItems.forEach(item => {
        const onclick = item.getAttribute('onclick');
        const match = onclick?.match(/\/chat\/([^'"]+)/);
        if (match && roomId.includes(match[1])) {
            foundItem = item;
        }
    });
    
    if (foundItem) {
        console.log('[SIDEBAR] ✅ Conversación encontrada, eliminando...');
        
        // Animación suave
        foundItem.style.transition = 'all 0.3s ease';
        foundItem.style.opacity = '0';
        foundItem.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            foundItem.remove();
            console.log('[SIDEBAR] ✅ Conversación eliminada del DOM');
            
            // Empty state si no hay más
            if (allMessagesList.querySelectorAll('.conversation-item').length === 0) {
                allMessagesList.innerHTML = '<div class="empty-state">No tienes conversaciones aún</div>';
            }
        }, 300);
    }
    
    // Si estamos en el chat que se borró, redirigir a /chat
    const chatUserId = document.getElementById('chat-user-id');
    if (chatUserId && chatUserId.value) {
        const targetUserId = parseInt(chatUserId.value);
        const currentUserId = window.currentUserId;
        if (currentUserId && targetUserId) {
            const ids = [currentUserId, targetUserId].sort((a,b) => a-b);
            const currentRoomId = `chat_${ids[0]}_${ids[1]}`;
            
            if (currentRoomId === roomId) {
                console.log('[SIDEBAR] 🔄 Redirigiendo a /chat');
                setTimeout(() => {
                    window.location.href = '/chat';
                }, 500);
            }
        }
    }
}

// ============================================
// MARCAR MENSAJES COMO LEÍDOS
// ============================================

function markMessageAsReadAPI(targetUserId) {
    if (!targetUserId) return;
    
    console.log('[CHAT] 📖 Marcando mensajes como leídos:', targetUserId);
    fetch(`/api/messages/read/all/${targetUserId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.updated_count > 0) {
            console.log(`[CHAT] ✅ ${data.updated_count} mensajes marcados como leídos`);
            if (typeof refreshConversationsList === 'function') {
                refreshConversationsList();
            }
        }
    })
    .catch(err => console.error('[CHAT] ❌ Error en markAsReadAPI:', err));
}

// ============================================
// CARGAR CONTACTOS PARA SIDEBAR
// ============================================

export async function loadContacts() {
    try {
        const response = await fetch('/api/contacts-list');
        const json = await response.json();
        const container = document.getElementById('contacts-list');
        if (!container) return;

        if (json.success && json.data && json.data.length > 0) {
            container.innerHTML = json.data.map(contact => {
                const c = contact.contact || contact;
                const name = c.name || 'Usuario';
                const username = c.username || name.toLowerCase().replace(/ /g, '_');
                const avatar = c.avatar ? `/storage/${c.avatar}` : null;
                const initial = name.charAt(0).toUpperCase();
                
                return `
                    <a href="/chat/${username}" class="message-item ${window.location.pathname.includes(username) ? 'active' : ''}">
                        <div class="message-item__avatar">
                            ${avatar ? `<img src="${avatar}" alt="${name}">` : `<span>${initial}</span>`}
                        </div>
                        <div class="message-item__info">
                            <span class="message-item__name">${name}</span>
                            <span class="message-item__preview">@${username}</span>
                        </div>
                    </a>
                `;
            }).join('');
        }
    } catch (err) {
        console.error('Error cargando contactos:', err);
    }
}

window.loadContacts = loadContacts;

// ============================================
// NAVEGACIÓN SPA - Elysium Ito (4-Layer Strategy)
// ============================================

window.navigateToChat = async function(username, options = {}) {
    if (!username) {
        console.warn('[SPA] navigateToChat llamado sin username');
        return;
    }
    
    console.log('[SPA] 🚀 Navegando a chat:', username, options);
    
    const { pushUrl = true, fetchReal = true } = options;
    
    // ✅ Ocultar resultados de búsqueda ANTES de navegar
    hideSearchResults();
    
    const chatMain = document.getElementById('chat-main');
    if (chatMain) {
        chatMain.classList.add('loading-overlay');
        console.log('[SPA] Loading overlay activado');
    }

    try {
        // ✅ FETCH a ruta REAL con header AJAX
        console.log('[SPA] Fetching:', `/chat/${username}`);
        const response = await fetch(`/chat/${username}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });
        
        if (!response.ok) {
            console.error('[SPA] Response not OK:', response.status);
            throw new Error('Chat not found');
        }
        
        const html = await response.text();
        console.log('[SPA] HTML recibido, length:', html.length);
        
        // ✅ Renderizar contenido
        if (chatMain) {
            chatMain.innerHTML = html;
            console.log('[SPA] HTML renderizado en chat-main');
        }

        // ✅ Actualizar URL visible a amigable
        if (pushUrl && history.pushState) {
            history.pushState(
                { type: 'chat', username },
                `Chat con ${username}`,
                `/@${username}`
            );
            console.log('[SPA] URL actualizada a:', `/@${username}`);
        }
        
        // ✅ Gestión de clases activas
        updateSidebarActiveState(username);
        
        // ✅ Toggle Mobile View
        const layout = document.querySelector('.chat-layout');
        if (layout) {
            layout.classList.add('mobile-chat-active');
            console.log('[SPA] Mobile view activado');
        }

        // ✅ Actualizar variables locales desde nuevo DOM
        const chatUserIdInput = document.getElementById('chat-user-id');
        if (chatUserIdInput) {
            window.targetUserId = parseInt(chatUserIdInput.value);
            const currentUserId = window.currentUserId || document.getElementById('current-user-id')?.value;
            if (currentUserId && window.targetUserId) {
                const ids = [parseInt(currentUserId), window.targetUserId].sort((a,b) => a-b);
                const newRoomId = `chat_${ids[0]}_${ids[1]}`;
                
                // 🚫 Manejar transición de WebSocket para prevenir duplicados
                handleWebSocketTransition(newRoomId, parseInt(currentUserId), window.targetUserId);
                
                console.log('[SPA] ✅ RoomID actualizado:', window.roomId);
            }
        }

        // ✅ CRÍTICO: Esperar un tick para que el DOM se estabilice
        await new Promise(resolve => setTimeout(resolve, 50));

        // ✅ Re-inicializar features (incluye búsqueda)
        initializeChatFeatures();
        
        // ✅ Modo MANUAL: no hacer scroll automático al abrir chat.
        // Se crea el botón de scroll manual para ir a los mensajes recientes.
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            maybeCreateManualScrollButton();
            // Scroll al fondo inicial al cargar un chat
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                window.chatStayAtBottom = true;
                updateManualScrollHint();
            }, 100);
            messagesContainer.addEventListener('scroll', updateManualScrollHint, { passive: true });
        }
        
        console.log('[SPA] ✅ Navegación completada para:', username);
        
    } catch (error) {
        console.error('[SPA] ❌ Error en navegación:', error);
        if (fetchReal) {
            console.log('[SPA] Fallback a navegación tradicional');
            window.location.href = `/@${username}`;
        }
    } finally {
        if (chatMain) {
            chatMain.classList.remove('loading-overlay');
            console.log('[SPA] Loading overlay desactivado');
        }
    }
};

console.log('[SPA] ✅ navigateToChat registrado globalmente');

// ============================================
// ACTUALIZAR DOM CON DATOS DEL CHAT
// ============================================

function updateChatDOM(data) {
    const { contact, messages } = data;
    
    // 0. Intercambiar Vistas
    const activeView = document.getElementById('active-chat-view');
    const emptyView = document.getElementById('empty-chat-view');
    if (activeView && emptyView) {
        activeView.style.display = 'flex';
        emptyView.style.display = 'none';
    }

    // A. Actualizar Inputs y Variables
    const chatUserIdInput = document.getElementById('chat-user-id');
    if (chatUserIdInput) chatUserIdInput.value = contact.id;
    window.targetUserId = contact.id;
    
    // B. Actualizar Header
    const chatName = document.getElementById('chat-username');
    if (chatName) chatName.textContent = contact.name;
    
    const chatHeaderAvatar = document.querySelector('.chat-header__avatar');
    if (chatHeaderAvatar) {
        chatHeaderAvatar.innerHTML = contact.avatar 
            ? `<img src="${contact.avatar}" alt="${contact.name}">`
            : `<span class="user-avatar-initials">${contact.initials}</span>`;
    }

    const addContactBtn = document.getElementById('add-contact-btn');
    if (addContactBtn) {
        addContactBtn.dataset.userId = contact.id;
        if (window.updateContactBtnStatus) {
            window.updateContactBtnStatus(contact.id);
        }
    }
    
    // C. Limpiar y llenar mensajes
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
        
        if (messages.length === 0) {
            messagesContainer.innerHTML = `
                <div class="messages-empty-state">
                    <div class="empty-icon">💬</div>
                    <p class="empty-text">No hay mensajes aún. ¡Di hola!</p>
                </div>
            `;
        } else {
            messages.forEach(m => appendMessageToChat(m, m.is_own));
        }
    }
    
    // D. Actualizar Modal de Perfil
    const modalTitle = document.getElementById('modal-contact-title');
    if (modalTitle) modalTitle.textContent = contact.name;
    
    // E. Re-inicializar formulario
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.value = '';
        messageInput.focus();
    }
}

// ============================================
// GESTIÓN DE WEBSOCKETS EN SPA
// ============================================

function handleWebSocketTransition(newRoomId, currentUserId, targetId) {
    // 1. Salir del canal anterior
    if (window.roomId && window.roomId !== newRoomId) {
        console.log('[SPA] 👋 Saliendo de sala:', window.roomId);
        if (typeof window.Echo !== 'undefined') {
            window.Echo.leave(`chat.${window.roomId}`);
        }
        if (window.chatPresenceChannel) {
            window.Echo.leave(window.chatPresenceChannel.name);
        }
        
        // 🚫 Limpiar roomId anterior para prevenir conflictos
        window.roomId = null;
        window.targetUserId = null;
    }
    
    // 2. Unirse al nuevo canal
    window.roomId = newRoomId;
    window.targetUserId = targetId;
    if (typeof subscribeToChatChannel === 'function') {
        subscribeToChatChannel(newRoomId);
    }
    
    // 3. Reiniciar Presence
    if (typeof initializePresence === 'function' && currentUserId && targetId) {
        initializePresence(currentUserId, targetId);
    }
}

// ============================================
// ACTUALIZAR ESTADO ACTIVO EN SIDEBAR
// ============================================

function updateSidebarActiveState(username) {
    // Remover clase active de todos los items
    document.querySelectorAll('.conversation-item, .contact-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Agregar clase active al item correspondiente
    document.querySelectorAll(`[data-username="${username}"]`).forEach(item => {
        if (item.classList.contains('conversation-item') || item.classList.contains('contact-item')) {
            item.classList.add('active');
        }
    });
}

// ============================================
// MANEJAR BOTÓN ATRÁS DEL NAVEGADOR
// ============================================

window.addEventListener('popstate', (event) => {
    if (event.state?.type === 'chat' && event.state?.username) {
        // ✅ Ocultar búsqueda al navegar atrás
        hideSearchResults();
        
        // ✅ Recargar chat desde ruta real
        window.navigateToChat(event.state.username, { 
            pushUrl: false, 
            fetchReal: true 
        });
    } else if (window.location.pathname === '/chat') {
        // Volver a empty state
        hideSearchResults();
        window.location.reload();
    }
});

// ============================================
// INTERCEPTAR CLICKS EN ENLACES Y CONVERSACIONES
// ============================================

// ✅ IMPORTANTE: Registrar INMEDIATAMENTE, no esperar DOMContentLoaded
document.addEventListener('click', (e) => {
    // ✅ Click en conversation-item (delegación de eventos)
    const conversationItem = e.target.closest('.conversation-item');
    if (conversationItem && conversationItem.dataset.username) {
        e.preventDefault();
        e.stopPropagation();
        const username = conversationItem.dataset.username;
        
        console.log('[CLICK] Conversation item clicked:', username);
        
        // Feedback visual inmediato
        conversationItem.style.opacity = '0.6';
        conversationItem.style.pointerEvents = 'none';
        
        // Ocultar búsqueda antes de navegar
        hideSearchResults();
        
        // Navegar al chat
        window.navigateToChat(username).finally(() => {
            conversationItem.style.opacity = '';
            conversationItem.style.pointerEvents = '';
        });
        return;
    }
    
    // ✅ Click en contact-item (delegación de eventos)
    const contactItem = e.target.closest('.contact-item');
    if (contactItem && contactItem.dataset.username) {
        e.preventDefault();
        e.stopPropagation();
        const username = contactItem.dataset.username;
        
        console.log('[CLICK] Contact item clicked:', username);
        
        // Feedback visual inmediato
        contactItem.style.opacity = '0.6';
        contactItem.style.pointerEvents = 'none';
        
        // Ocultar búsqueda antes de navegar
        hideSearchResults();
        
        // Navegar al chat
        window.navigateToChat(username).finally(() => {
            contactItem.style.opacity = '';
            contactItem.style.pointerEvents = '';
        });
        return;
    }
    
    // Click en enlace de chat con data-chat-link
    const link = e.target.closest('[data-chat-link]');
    if (link && !link.classList.contains('conversation-item') && !link.classList.contains('contact-item')) {
        e.preventDefault();
        e.stopPropagation();
        const username = link.dataset.username;
        
        console.log('[CLICK] Chat link clicked:', username);
        
        // Ocultar búsqueda antes de navegar
        hideSearchResults();
        
        window.navigateToChat(username);
        return;
    }
    
    // Click FUERA del input de búsqueda → ocultar resultados
    const searchInput = document.getElementById('search-input');
    const resultsContainer = document.getElementById('search-results');
    
    if (searchInput && resultsContainer && 
        !searchInput.contains(e.target) && 
        !resultsContainer.contains(e.target)) {
        
        if (resultsContainer.style.display === 'block') {
            hideSearchResults();
        }
    }
}, true); // ✅ USAR CAPTURE PHASE para interceptar antes que otros handlers

console.log('[CHAT] ✅ Event listeners registered for SPA navigation');

// ============================================
// INICIALIZACIÓN DE FEATURES DEL CHAT
// ============================================

export function initializeChatFeatures() {
    console.log('[CHAT] 🔧 Inicializando features...');
    
    // ✅ 0. Limpiar listeners previos para prevenir duplicados en SPA
    window.onNewMessage = null;
    window.onMessagesRead = null;
    
    // ✅ 1. Setup del formulario de mensajes
    setupMessageForm();
    
    // ✅ 2. Suscribirse al canal de usuario (siempre)
    const currentUserId = window.currentUserId || document.getElementById('current-user-id')?.value;
    
    // ✅ 3. Calcular roomId si no existe
    const chatUserIdInput = document.getElementById('chat-user-id');
    if (!window.roomId && chatUserIdInput?.value && currentUserId) {
        const targetId = parseInt(chatUserIdInput.value);
        const ids = [parseInt(currentUserId), targetId].sort((a,b) => a-b);
        window.roomId = `chat_${ids[0]}_${ids[1]}`;
        window.targetUserId = targetId;
        console.log('[CHAT] ✅ RoomID calculado:', window.roomId);
    }

    // ✅ 4. Suscribirse al canal de chat (si hay chat activo)
    if (window.roomId) {
        subscribeToChatChannel(window.roomId);
        if (chatUserIdInput?.value && currentUserId) {
            initializePresence(currentUserId, parseInt(chatUserIdInput.value));
        }
        
        // 📖 Marcar como leídos
        markMessageAsReadAPI(parseInt(chatUserIdInput.value));

        // ✅ Modo MANUAL de scroll: no iniciar con el scroll al fondo
        const container = document.getElementById('messages-container');
        if (container) {
            maybeCreateManualScrollButton();
            // Scroll inicial
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
                window.chatStayAtBottom = true;
                updateManualScrollHint();
            }, 100);

            // Evitar duplicar el listener en SPA
            if (!container.dataset.scrollBound) {
                container.addEventListener('scroll', updateManualScrollHint, { passive: true });
                container.dataset.scrollBound = 'true';
                console.log('[CHAT] ✅ Scroll listener vinculado');
            }
        }
    }
    
    // ✅ 5. Listener global para mensajes leídos (ÚNICO)
    window.onMessagesRead = function(event) {
        console.log('[CHAT] 👀 Sincronizando visto:', event);
        const senderId = event.sender_id;
        const currentUserId = window.currentUserId || document.getElementById('current-user-id')?.value;
        
        if (parseInt(senderId) === parseInt(currentUserId)) {
            // ✅ MEJORADO: Usar batch updates para mejor performance
            const ticks = document.querySelectorAll('.message-status:not(.message-status--read)');
            const messageIds = Array.from(ticks).map(tick => {
                const bubble = tick.closest('.message-bubble');
                return bubble?.id?.replace('message-', '');
            }).filter(Boolean);
            
            if (messageIds.length > 0) {
                batchUpdateMessageStatuses(messageIds, true);
            }
        }
    };
    
    // ✅ 6. Configurar UI P2P (Solo si el objeto global ya existe)
    if (window.p2pTransfer) {
        const p2p = window.p2pTransfer;
        const fileInput = document.getElementById('file-input');
        const sendFileBtn = document.getElementById('send-file-btn');
        
        if (fileInput && sendFileBtn) {
            // ✅ Limpiar listeners previos (Seguro para navegación SPA)
            const newSendFileBtn = sendFileBtn.cloneNode(true);
            const newFileInput = fileInput.cloneNode(true);
            
            sendFileBtn.parentNode?.replaceChild(newSendFileBtn, sendFileBtn);
            fileInput.parentNode?.replaceChild(newFileInput, fileInput);
            
            newSendFileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                newFileInput.click();
            });
            
            newFileInput.addEventListener('change', async (e) => {
                const files = Array.from(e.target.files);
                if (files.length === 0) return;
                
                if (files.length > 10) {
                    window.showToast?.('Límite excedido', 'Solo puedes enviar hasta 10 archivos a la vez', 'warning');
                }
                
                const chatUserIdInput = document.getElementById('chat-user-id');
                if (!chatUserIdInput?.value) {
                    window.showToast?.('Error', 'No hay chat activo', 'error');
                    newFileInput.value = '';
                    return;
                }
                
                try {
                    if (typeof window.p2pTransfer?.showPreviewModal === 'function') {
                        window.p2pTransfer.showPreviewModal(files.slice(0, 10)); // Mostrar preview del emisor
                    }
                } catch (err) {
                    console.error('[P2P] Error:', err);
                    window.showToast?.('Error P2P', 'Error al preparar transferencia', 'error');
                }
                newFileInput.value = '';
            });

            // Arrastrar y soltar
            const dropZone = document.getElementById('messages-container');
            if (dropZone) {
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('drag-active');
                });

                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('drag-active');
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag-active');
                    
                    const files = Array.from(e.dataTransfer.files);
                    if (files.length === 0) return;
                    
                    if (typeof p2p.showPreviewModal === 'function') {
                        p2p.showPreviewModal(files.slice(0, 10));
                    }
                });
            }
        }
    }
    
    // ✅ 7. Listeners globales para mensajes en tiempo real (ÚNICO)
    window.onNewMessage = function(message) {
        console.log('[CHAT] 📨 onNewMessage:', message);
        
        const chatUserIdInput = document.getElementById('chat-user-id');
        if (chatUserIdInput && parseInt(chatUserIdInput.value) === message.sender_id) {
            markMessageAsReadAPI(message.sender_id);
        }

        if (typeof window.handleRealTimeMessage === 'function') {
            window.handleRealTimeMessage(message);
        }
    };
    
    window.onChatDeleted = (roomId) => handleChatDeleted(roomId);
    
    // ✅ 8. Sincronización inicial del sidebar
    if (typeof refreshConversationsList === 'function') {
        refreshConversationsList();
    }
    
    // ✅ 9. Re-inicializar búsqueda si existe el módulo
    if (typeof window.initializeSearch === 'function') {
        window.initializeSearch();
    }

    // ✅ 10. Enlazar scroll con teclado y rueda de mouse
    enableScrollControls();

    // ✅ 11. Refrescar estado de presencia (EN TIEMPO REAL)
    if (window.PresenceService && PresenceService.isInitialized) {
        PresenceService.refreshAfterNavigation();
        console.log('[CHAT] ✅ Estado de presencia refrescado');
    }

    console.log('[CHAT] ✅ Features inicializados correctamente');
}

/**
 * Habilitar scroll con teclas y rueda del mouse
 */
function enableScrollControls() {
    if (window.chatScrollControlsInitialized) {
        return;
    }

    const container = document.getElementById('messages-container');
    if (!container) return;

    // rueda ya debería funcionar por overflow-y:auto, pero refuerzo precisión
    container.addEventListener('wheel', event => {
        if (Math.abs(event.deltaY) > 0) {
            event.preventDefault();
            container.scrollBy({ top: event.deltaY, behavior: 'auto' });
        }
    }, { passive: false });

    // teclas arriba/abajo/pagup/pagedown/home/end
    window.addEventListener('keydown', event => {
        if (document.activeElement && ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
            return; // no bloquear typing
        }

        const step = 60;
        const page = container.clientHeight - 20;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                container.scrollBy({ top: step, behavior: 'smooth' });
                break;
            case 'ArrowUp':
                event.preventDefault();
                container.scrollBy({ top: -step, behavior: 'smooth' });
                break;
            case 'PageDown':
                event.preventDefault();
                container.scrollBy({ top: page, behavior: 'smooth' });
                break;
            case 'PageUp':
                event.preventDefault();
                container.scrollBy({ top: -page, behavior: 'smooth' });
                break;
            case 'Home':
                event.preventDefault();
                container.scrollTo({ top: 0, behavior: 'smooth' });
                break;
            case 'End':
                event.preventDefault();
                container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                break;
            default:
                break;
        }
    });

    window.chatScrollControlsInitialized = true;
}

window.enableScrollControls = enableScrollControls;

export const initChat = initializeChatFeatures;

// ============================================
// DROPDOWN MENU - 3 PUNTOS
// ============================================

window.toggleChatMenu = function() {
    const menu = document.getElementById('chat-dropdown-menu');
    if (menu) {
        menu.classList.toggle('show');
    }
};

window.viewContactProfile = function() {
    const modal = document.getElementById('contact-profile-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    window.toggleChatMenu();
};

window.closeContactProfileModal = function() {
    const modal = document.getElementById('contact-profile-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

window.closeCurrentChat = function() {
    window.location.href = '/chat';
    window.toggleChatMenu();
};

window.deleteChatForMe = async function() {
    const chatUserId = document.getElementById('chat-user-id');
    const receiverId = chatUserId?.value;
    
    if (!receiverId) {
        showToast?.('Error', 'No hay chat para borrar', 'error');
        return;
    }
    
    const confirmed = await showConfirm?.({
        title: '¿Borrar chat para ti?',
        message: 'Solo se eliminará de tu cuenta. El otro usuario conservará su copia.',
        confirmText: 'Borrar',
        cancelText: 'Cancelar',
        type: 'warning',
        icon: '🗑️'
    });
    
    if (confirmed) {
        removeConversationFromSidebarOptimistic(receiverId);
        deleteChatAPI('me', receiverId);
    }
    window.toggleChatMenu();
};

window.deleteChatForAll = async function() {
    const chatUserId = document.getElementById('chat-user-id');
    const receiverId = chatUserId?.value;
    
    if (!receiverId) {
        showToast?.('Error', 'No hay chat para borrar', 'error');
        return;
    }
    
    const confirmed = await showConfirm?.({
        title: '¿Borrar para todos?',
        message: 'Esta acción eliminará el chat para ambos usuarios y no se puede deshacer.',
        confirmText: 'Borrar para todos',
        cancelText: 'Cancelar',
        type: 'danger',
        icon: '⚠️'
    });
    
    if (confirmed) {
        removeConversationFromSidebarOptimistic(receiverId);
        deleteChatAPI('all', receiverId);
    }
    window.toggleChatMenu();
};

async function deleteChatAPI(type, receiverId) {
    try {
        const response = await fetch('/api/chat/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ receiver_id: parseInt(receiverId), type })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('[ERROR] Error al borrar:', data.message);
            if (typeof refreshConversationsList === 'function') {
                refreshConversationsList();
            }
            showToast?.('Error', data.message || 'Error al borrar el chat', 'error');
        } else {
            const currentChatUserId = document.getElementById('chat-user-id')?.value;
            if (currentChatUserId == receiverId) {
                setTimeout(() => { window.location.href = '/chat'; }, 500);
            }
        }
    } catch (error) {
        console.error('[ERROR] Error de red:', error);
        if (typeof refreshConversationsList === 'function') {
            refreshConversationsList();
        }
        showToast?.('Error', 'Error de conexión al borrar chat', 'error');
    }
}

function removeConversationFromSidebarOptimistic(receiverId) {
    const allMessagesList = document.getElementById('all-messages-list');
    if (!allMessagesList) return;
    
    const conversationItems = Array.from(allMessagesList.querySelectorAll('.conversation-item'));
    const targetItem = conversationItems.find(item => {
        const onclick = item.getAttribute('onclick');
        return onclick && onclick.includes('/chat/');
    });
    
    if (targetItem) {
        targetItem.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        targetItem.style.opacity = '0';
        targetItem.style.transform = 'translateX(-20px) scale(0.95)';
        targetItem.style.pointerEvents = 'none';
        
        setTimeout(() => {
            targetItem.remove();
            const remainingItems = allMessagesList.querySelectorAll('.conversation-item');
            if (remainingItems.length === 0) {
                allMessagesList.innerHTML = '<div class="empty-state">No tienes conversaciones aún</div>';
            }
        }, 300);
    }
}

// Cleanup al salir
window.addEventListener('beforeunload', function() {
    if (window.chatPresenceChannel) {
        window.Echo.leave(window.chatPresenceChannel.name);
        console.log('[PRESENCE] Desuscrito del canal de presencia');
    }
});

// ============================================
// ACTUALIZACIÓN EN TIEMPO REAL DEL SIDEBAR
// ============================================

export function updateSidebarOnNewMessageInternal(message) {
    const allMessagesList = document.getElementById('all-messages-list');
    if (!allMessagesList) return;
    
    const currentUserId = window.currentUserId || message.sender_id;
    const otherUser = message.sender_id === currentUserId ? message.receiver : message.sender;
    
    if (!otherUser) return;
    
    let conversationItem = findConversationItem(otherUser.username || otherUser.username);
    
    if (conversationItem) {
        updateExistingConversation(conversationItem, message, otherUser);
    } else {
        createNewConversationItem(otherUser, message, allMessagesList);
    }
    
    if (conversationItem) {
        allMessagesList.insertBefore(conversationItem, allMessagesList.firstChild);
    }
    
    const emptyState = allMessagesList.querySelector('.empty-state');
    if (emptyState) emptyState.remove();
}

function findConversationItem(username) {
    const conversationItems = document.querySelectorAll('.conversation-item');
    for (let item of conversationItems) {
        const onclick = item.getAttribute('onclick');
        if (onclick && onclick.includes(`/chat/${username}`)) {
            return item;
        }
    }
    return null;
}

function updateExistingConversation(item, message, otherUser) {
    const previewText = item.querySelector('.conversation-preview');
    const conversationTime = item.querySelector('.conversation-time');
    const conversationName = item.querySelector('.conversation-name');
    
    if (previewText) {
        const messagePreview = message.message.length > 40 ? message.message.substring(0, 40) + '...' : message.message;
        const isOwnMessage = message.sender_id === window.currentUserId;
        previewText.innerHTML = `
            ${isOwnMessage ? '<span class="preview-status">Tú: </span>' : ''}
            <span class="preview-text">${escapeHtml(messagePreview)}</span>
        `;
    }
    
    if (conversationTime) {
        const time = new Date(message.created_at);
        conversationTime.textContent = time.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
    
    if (conversationName && otherUser.name) {
        conversationName.textContent = otherUser.name;
    }
}

function createNewConversationItem(otherUser, message, container) {
    const time = new Date(message.created_at);
    const timeStr = time.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    const messagePreview = message.message.length > 40 ? message.message.substring(0, 40) + '...' : message.message;
    const isOwnMessage = message.sender_id === window.currentUserId;
    
    const avatar = otherUser.avatar 
        ? `<img src="${otherUser.avatar}" alt="${otherUser.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">`
        : '';
    
    const avatarPlaceholder = otherUser.avatar 
        ? `<div class="result-avatar-placeholder" style="display: none">${otherUser.name.charAt(0).toUpperCase()}</div>`
        : `<div class="result-avatar-placeholder">${otherUser.name.charAt(0).toUpperCase()}</div>`;
    
    const html = `
        <div class="conversation-item" data-chat-link="true" data-user-id="${otherUser.id}" data-username="${otherUser.username}" style="animation: slideInLeft 0.3s ease; cursor: pointer;">
            <div class="conversation-avatar">
                ${avatar}
                ${avatarPlaceholder}
                <div class="status-dot-mini offline" data-status-user-id="${otherUser.id}"></div>
            </div>
            <div class="conversation-info">
                <div class="conversation-header">
                    <span class="conversation-name">${escapeHtmlUtil(otherUser.name)}</span>
                    <span class="conversation-time">${timeStr}</span>
                </div>
                <div class="conversation-preview">
                    ${isOwnMessage ? '<span class="preview-status">Tú: </span>' : ''}
                    <span class="preview-text">${escapeHtmlUtil(messagePreview)}</span>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('afterbegin', html);
}

// appendMessageToChat vive en messages.js — importado arriba
// Scroll lógicos: showNewMessagesBadge / hideNewMessagesBadge

// Las funciones showNewMessagesBadge/hideNewMessagesBadge ahora viven en messages.js


// handleRealTimeMessage vive en messages.js


// ============================================
// EXPORTAR FUNCIONES GLOBALES
// ============================================

window.appendMessageToChat = appendMessageToChat;
window.handleRealTimeMessage = handleRealTimeMessage;
window.hideSearchResults = hideSearchResults;
window.handleSearchResultSelect = handleSearchResultSelect;
window.updateSidebarOnNewMessageInternal = updateSidebarOnNewMessageInternal;
window.handleChatDeletedExternally = handleChatDeletedExternally;
window.initializeChatFeatures = initializeChatFeatures;
window.removeConversationFromSidebarOptimistic = removeConversationFromSidebarOptimistic;

// ============================================
// MOBILE: VOLVER A LISTA DE CHATS
// ============================================

window.backToChatList = function() {
    console.log('[MOBILE] Volviendo a la lista de chats');
    const layout = document.querySelector('.chat-layout');
    if (layout) {
        layout.classList.remove('mobile-chat-active');
        window.roomId = null;
    }
};