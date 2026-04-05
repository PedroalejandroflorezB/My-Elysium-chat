/**
 * Manejo de mensajes
 * — FUENTE ÚNICA DE VERDAD para appendMessageToChat y handleRealTimeMessage
 */

import { escapeHtml } from './render-utils.js';

export function setupMessageForm() {
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const receiverIdInput = document.getElementById('chat-user-id');
    
    if (!messageForm || !messageInput || !receiverIdInput) {
        // console.warn('⚠️ No se encontró el formulario o input de mensaje (normal en Home)');
        return;
    }
    
    console.log('[MESSAGES] Formulario de mensajes inicializado');
    
    // Función para enviar mensaje
    const sendMessage = async () => {
        const message = messageInput.value.trim();
        const receiverId = receiverIdInput.value;
        
        if (!message || !receiverId) return;
        
        // 🏆 REMOVER EMPTY STATE INMEDIATAMENTE al enviar (no esperar WebSocket)
        removeEmptyState();
        
        // Limpiar input inmediatamente (optimismo)
        messageInput.value = '';
        messageInput.style.height = 'auto'; // Reset height
        
        try {
            const response = await fetch('/api/messages/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    receiver_id: receiverId,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('[MESSAGES] ✅ Mensaje enviado al servidor');
                // Al enviar nosotros, forzamos scroll al fondo
                scrollToBottom(true);
                
                if (typeof window.refreshConversationsList === 'function') {
                    window.refreshConversationsList();
                }
            } else {
                console.error('[MESSAGES] ❌ Error enviando:', data.message);
                messageInput.value = message; // Restaurar si falla
            }
        } catch (error) {
            console.error('[MESSAGES] ❌ Error de red:', error);
            messageInput.value = message;
        }
    };

    // Evento Submit (Botón)
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Evento Enter (Teclado)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize del textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

/**
 * ============================================
 * REMOVER EMPTY STATE (CENTRALIZADO)
 * ============================================
 */
function removeEmptyState() {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) return;
    
    const states = messagesContainer.querySelectorAll('.messages-empty-state');
    if (states.length > 0) {
        states.forEach(el => el.remove());
        console.log('[MESSAGES] ✨ Empty state removido');
    }
}

// Exponer globalmente para uso desde otros módulos
window.removeEmptyState = removeEmptyState;

/**
 * ============================================
 * AGREGAR MENSAJE AL CHAT (VISUALMENTE)
 * ============================================
 */

export function appendMessageToChat(message, isOwnMessage = false) {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) {
        console.warn('[MESSAGES] ⚠️ messages-container no existe');
        return;
    }

    // 🚫 PREVENIR DUPLICADOS por ID
    if (document.getElementById(`message-${message.id}`)) {
        console.log('[MESSAGES] ⚠️ Mensaje ya existe, ignorando duplicado:', message.id);
        return;
    }

    // 🏆 REMOVER EMPTY STATE
    removeEmptyState();

    const currentUserId = window.currentUserId || message.sender_id;
    const isSender = message.sender_id === parseInt(currentUserId);

    const name = isSender ? 'Tú' : (message.sender?.name || message.sender_name || 'Usuario');
    const avatarUrl = !isSender && message.sender?.avatar
        ? (message.sender.avatar.startsWith('http') ? message.sender.avatar : `/storage/${message.sender.avatar}`)
        : null;
    const initials = name.charAt(0).toUpperCase();

    const wrapper = document.createElement('div');
    wrapper.className = `message-wrapper ${isSender ? 'message-wrapper--sent' : 'message-wrapper--received'}`;
    wrapper.id = `msg-wrapper-${message.id}`;

    let avatarHtml = '';
    if (!isSender) {
        avatarHtml = `
            <div class="message-avatar" title="${name}">
                ${avatarUrl
                    ? `<img src="${avatarUrl}" alt="${name}">`
                    : `<div class="message-avatar-placeholder">${initials}</div>`
                }
            </div>`;
    }

    const time = new Date(message.created_at).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
    const statusHtml = isSender
        ? `<span class="message-status ${message.is_read ? 'message-status--read' : ''}" id="status-${message.id}">${message.is_read ? '✓✓' : '✓'}</span>`
        : '';

    wrapper.innerHTML = `
        ${avatarHtml}
        <div class="message-bubble ${isSender ? 'message-bubble--sent' : 'message-bubble--received'}" id="message-${message.id}">
            <div class="message-content ${isSender ? 'message-content--sent' : 'message-content--received'}">
                <div class="message-text">${escapeHtml(message.message)}</div>
                <div class="message-meta">
                    <span class="message-time">${time}</span>
                    ${statusHtml}
                </div>
            </div>
        </div>`;

    // 📊 Leer posición del scroll ANTES del append
    const prevHeight    = messagesContainer.scrollHeight;
    const prevTop       = messagesContainer.scrollTop;
    const clientHeight  = messagesContainer.clientHeight;
    const distFromBottom = prevHeight - prevTop - clientHeight;
    const isNearBottom   = distFromBottom < 150;

    messagesContainer.appendChild(wrapper);

    // Limitar preview a 120 mensajes
    const PREVIEW_SIZE = 120;
    if (messagesContainer.children.length > PREVIEW_SIZE) {
        messagesContainer.removeChild(messagesContainer.firstElementChild);
    }

    // 🔄 SCROLL HÍBRIDO
    if (isSender || isNearBottom) {
        messagesContainer.scrollTo({
            top: messagesContainer.scrollHeight,
            behavior: isSender ? 'auto' : 'smooth'
        });
        _hideNewMessagesBadge();
    } else {
        _showNewMessagesBadge();
    }

    console.log('[MESSAGES] ✅ Mensaje agregado:', message.id);
}

/** Badge flotante — “Nuevos mensajes” */
function _showNewMessagesBadge() {
    let badge = document.getElementById('new-messages-floating-badge');
    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'new-messages-floating-badge';
        badge.className = 'new-messages-badge';
        badge.style.cssText = [
            'position:absolute', 'bottom:100px', 'left:50%',
            'transform:translateX(-50%)',
            'background:var(--primary,#12a8ff)', 'color:#fff',
            'padding:8px 16px', 'border-radius:20px', 'cursor:pointer',
            'box-shadow:0 4px 12px rgba(0,0,0,.3)', 'z-index:1000',
            'font-size:12px', 'font-weight:bold',
            'transition:all .3s ease'
        ].join(';');
        badge.onclick = () => {
            const c = document.getElementById('messages-container');
            if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
            _hideNewMessagesBadge();
        };
        const chatMain = document.getElementById('chat-main');
        if (chatMain) chatMain.appendChild(badge);
    }
    badge.innerHTML = '↓ Nuevos mensajes';
    badge.style.display = 'block';
    badge.style.opacity  = '1';
}

function _hideNewMessagesBadge() {
    const badge = document.getElementById('new-messages-floating-badge');
    if (badge) {
        badge.style.opacity = '0';
        setTimeout(() => { badge.style.display = 'none'; }, 300);
    }
}

/**
 * ============================================
 * MANEJAR MENSAJE RECIBIDO EN TIEMPO REAL
 * ============================================
 */

export function handleRealTimeMessage(message) {
    console.log('[MESSAGES] 📨 handleRealTimeMessage:', message);

    const currentUserId = window.currentUserId;
    const isOwnMessage = message.sender_id === currentUserId;

    // 🚫 Filtro de conversación activa
    const chatUserIdInput = document.getElementById('chat-user-id');
    if (chatUserIdInput && chatUserIdInput.value) {
        const targetUserId      = parseInt(chatUserIdInput.value);
        const messageSenderId   = parseInt(message.sender_id);
        const messageReceiverId = parseInt(message.receiver_id);

        if (isNaN(targetUserId)) return;

        if (isOwnMessage && messageReceiverId !== targetUserId) {
            console.log('[MESSAGES] ⚠️ Mensaje propio para otro chat, ignorando');
            return;
        }
        if (!isOwnMessage && messageSenderId !== targetUserId) {
            console.log('[MESSAGES] ⚠️ Mensaje de otro usuario, ignorando');
            return;
        }
    }

    // 1. Render visual
    appendMessageToChat(message, isOwnMessage);

    // 2. Sidebar
    if (typeof window.updateSidebarOnNewMessage === 'function') {
        window.updateSidebarOnNewMessage(message);
    }

    // 3. Badge contador (mensajes no leídos en otros chats)
    if (!isOwnMessage) {
        const chatUserId    = document.getElementById('chat-user-id');
        const isActiveChat  = chatUserId && chatUserId.value == message.sender_id;
        if (!isActiveChat) {
            const badge = document.getElementById('badge-messages');
            if (badge) {
                let current = parseInt(badge.textContent) || 0;
                badge.textContent = current + 1;
                badge.style.display = 'flex';
                badge.classList.remove('animate-pop');
                void badge.offsetWidth;
                badge.classList.add('animate-pop');
            }
        }
    }

    // 4. Doble check después de 1s si es mensaje propio
    if (isOwnMessage) {
        setTimeout(() => {
            const el = document.getElementById(`message-${message.id}`);
            if (el) {
                const status = el.querySelector('.message-status');
                if (status) {
                    status.textContent = '✓✓';
                    status.classList.add('message-status--read');
                }
            }
        }, 1000);
    }
}

/**
 * Escape HTML para prevenir XSS
 */
// Ya viene de render-utils.js - evitar duplicación

// Exportar globalmente
window.setupMessageForm = setupMessageForm;
window.appendMessageToChat = appendMessageToChat;
window.handleRealTimeMessage = handleRealTimeMessage;

