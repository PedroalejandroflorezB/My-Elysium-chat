/**
 * ============================================
 * WEB SOCKET SERVICE - CENTRALIZA CONEXIONES
 * ============================================
 * 
 * Buenas prácticas:
 * - Single point of truth para suscripciones
 * - Manejo centralizado de errores
 * - Fácil de testear y mantener
 * ============================================
 */

let userChannel = null;
let chatChannel = null;
let pollingInterval = null;

/**
 * Suscribirse al canal de notificaciones del usuario
 * ✅ Fallback HTTP incluido
 */
export function subscribeToUserChannel(currentUserId) {
    if (!currentUserId) return null;

    if (window.realtimeDisabled || !window.Echo) {
        console.log('[WEBSOCKET] Real-time deshabilitado, iniciando polling HTTP para usuario:', currentUserId);
        iniciarPollingHttp(currentUserId);
        return { name: `user.${currentUserId}`, polling: true };
    }
    
    // Evitar suscripciones duplicadas
    if (userChannel) {
        return userChannel;
    }
    
    console.log('[WEBSOCKET] Suscribiéndose a user.' + currentUserId);
    
    userChannel = window.Echo.private(`user.${currentUserId}`)
        .listen('.message.sent', (event) => {
            console.log('[WEBSOCKET] 📨 Mensaje recibido en user channel:', event);
            
            // Si estamos en un chat y el mensaje es de esa sala, 
            // el canal de chat ya lo procesó o lo procesará.
            if (window.roomId && (event.room_id === window.roomId || event.roomId === window.roomId)) {
                console.log('[WEBSOCKET] ⏭️ Ignorando en user channel, procesado por chat channel.');
                return;
            }

            if (typeof window.onNewMessage === 'function') {
                window.onNewMessage(event.message || event);
            }
        })
        .listen('.chat.deleted', (event) => {
            if (typeof window.onChatDeleted === 'function') {
                window.onChatDeleted(event.room_id || event.roomId);
            }
        })
        .listen('.contact.request.sent', (event) => {
            if (typeof window.showPendingRequestModal === 'function') {
                window.showPendingRequestModal(event);
            }
        })
        .error((error) => {
            console.error('[WEBSOCKET] ❌ Error en user channel:', error);
            if (!userChannel?.polling) iniciarPollingHttp(currentUserId);
        });
    
    return userChannel;
}

/**
 * Suscribirse al canal de chat específico
 * ✅ Fallback HTTP incluido
 */
export function subscribeToChatChannel(roomId) {
    if (!roomId) return null;

    if (window.realtimeDisabled || !window.Echo) {
        console.log('[WEBSOCKET] Real-time deshabilitado, iniciando polling HTTP para chat:', roomId);
        iniciarPollingHttp(null, roomId);
        return { name: `chat.${roomId}`, polling: true };
    }
    
    // Evitar re-suscripción a la misma sala (clave en SPA)
    if (chatChannel && chatChannel.name === `private-chat.${roomId}`) {
        console.log('[WEBSOCKET] ✅ Ya suscrito al canal de chat:', roomId);
        return chatChannel;
    }

    // Limpiar suscripción anterior si existe
    if (chatChannel) {
        console.log('[WEBSOCKET] 👋Saliendo de canal anterior:', chatChannel.name);
        window.Echo.leave(chatChannel.name.replace('private-', '')); // Echo espera el nombre sin prefijo
        chatChannel = null;
    }
    
    const channelName = `chat.${roomId}`;
    chatChannel = window.Echo.private(channelName)
        .listen('.message.sent', (event) => {
            if (typeof window.onNewMessage === 'function') {
                window.onNewMessage(event.message || event);
            }
        })
        .listen('.messages.read', (event) => {
            if (typeof window.onMessagesRead === 'function') {
                window.onMessagesRead(event);
            }
        })
        .error((error) => {
            console.error('[WEBSOCKET] ❌ Error en chat channel:', error);
        });
    
    return chatChannel;
}

/**
 * Sistema de Polling HTTP (Contingencia)
 * Consulta nuevos mensajes cada 8-10 segundos
 */
export function iniciarPollingHttp(currentUserId, roomId) {
    if (pollingInterval) clearInterval(pollingInterval);
    
    console.log('[POLLING] 🔄 Sistema de polling activado');
    
    const poll = async () => {
        try {
            const rId = roomId || window.roomId;
            const url = rId ? `/api/messages/new?room_id=${rId}` : '/api/messages/new';
            
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                console.log(`[POLLING] 📩 ${data.messages.length} nuevos mensajes encontrados`);
                data.messages.forEach(msg => {
                    if (typeof window.onNewMessage === 'function') {
                        window.onNewMessage(msg);
                    }
                });
            }
        } catch (err) {
            console.warn('[POLLING] Error en consulta HTTP:', err);
        }
    };

    // Primer disparo inmediato
    poll();
    
    // Intervalo de 9 segundos (promedio entre 8 y 10)
    pollingInterval = setInterval(poll, 9000);
}

/**
 * Limpiar suscripciones
 */
export function unsubscribeFromChannels() {
    if (chatChannel) {
        const name = chatChannel.name.replace('private-', '');
        window.Echo.leave(name);
        chatChannel = null;
        console.log('[WEBSOCKET] 👋 Chat channel liberado:', name);
    }
}

/**
 * Limpiar todo (logout)
 */
export function unsubscribeFromAll() {
    unsubscribeFromChannels();
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    
    if (userChannel && !userChannel.polling) {
        window.Echo.leave(userChannel.name);
        userChannel = null;
    }
}

// Exportar funciones globalmente
window.subscribeToUserChannel = subscribeToUserChannel;
window.subscribeToChatChannel = subscribeToChatChannel;
window.unsubscribeFromChannels = unsubscribeFromChannels;
window.unsubscribeFromAll = unsubscribeFromAll;
window.iniciarPollingHttp = iniciarPollingHttp;

