/**
 * ============================================
 * PRESENCE SERVICE - GESTIÓN DE ESTADO ONLINE EN TIEMPO REAL
 * ============================================
 * 
 * Selectores reales del DOM:
 *   Sidebar contactos:  .contact-item[data-user-id]  →  .status-dot-mini[data-status-user-id]  +  .contact-status
 *   Chat header:        #chat-header-status-badge  +  #chat-header-status-text  +  #chat-user-id (hidden input)
 */

export const PresenceService = {
    onlineUsers: new Map(),
    presenceChannel: null,
    isInitialized: false,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectDelay: 2000,

    /**
     * Inicializar canal de presencia global (con reconexión)
     */
    init() {
        if (this.isInitialized) return;

        if (window.realtimeDisabled || !window.Echo) {
            console.log('[PRESENCE] Real-time deshabilitado, usando modo polling para estados');
            this.loadStatusViaHttp();
            this.isInitialized = true;
            return;
        }

        console.log('[PRESENCE] 📡 Uniéndose al canal global de presencia...');

        try {
            this.presenceChannel = window.Echo.join('presence-global')
                .here((users) => {
                    console.log('[PRESENCE] 👥 Usuarios en línea:', users.length);
                    this.onlineUsers.clear();
                    users.forEach(user => this.onlineUsers.set(user.id, user));
                    this.updateAllStatusIndicators();
                    this.reconnectAttempts = 0;
                })
                .joining((user) => {
                    this.onlineUsers.set(user.id, user);
                    this.updateStatusIndicator(user.id, true);
                })
                .leaving((user) => {
                    this.onlineUsers.delete(user.id);
                    this.updateStatusIndicator(user.id, false);
                })
                .error((error) => {
                    console.error('[PRESENCE] ❌ Error en canal:', error);
                    this._attemptReconnect();
                });

            this.isInitialized = true;
        } catch (err) {
            console.error('[PRESENCE] ❌ Error:', err);
            this._attemptReconnect();
        }
    },

    /**
     * Cargar estado de presencia vía HTTP (Fallback)
     */
    async loadStatusViaHttp() {
        try {
            const response = await fetch('/api/presence/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            
            if (data.success && data.status) {
                this.onlineUsers.clear();
                // data.status es un objeto { userId: { online: true, last_seen: ... }, ... }
                Object.entries(data.status).forEach(([userId, status]) => {
                    if (status.online) {
                        this.onlineUsers.set(parseInt(userId), { id: parseInt(userId), ...status });
                    }
                });
                this.updateAllStatusIndicators();
            }
        } catch (err) {
            console.warn('[PRESENCE] Error cargando estados vía HTTP:', err);
        }

        // Programar siguiente consulta si sigue en modo polling
        if (window.realtimeDisabled) {
            setTimeout(() => this.loadStatusViaHttp(), 30000); // Cada 30s es suficiente para presencia
        }
    },

    /**
     * Intentar reconectar si se pierde la conexión
     */
    _attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('[PRESENCE] ❌ Máximo de intentos de reconexión alcanzado');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * this.reconnectAttempts;
        
        console.log(`[PRESENCE] 🔄 Intento de reconexión #${this.reconnectAttempts} en ${delay}ms...`);
        
        setTimeout(() => {
            this.isInitialized = false;
            this.init();
        }, delay);
    },

    /**
     * Mostrar animación transitoria al cambiar estado
     */
    _showStatusAnimation(userId, isOnline) {
        const elements = document.querySelectorAll(`[data-status-user-id="${userId}"]`);
        elements.forEach(el => {
            if (isOnline) {
                el.classList.add('status-transition-online');
                setTimeout(() => {
                    el.classList.remove('status-transition-online');
                }, 600);
            } else {
                el.classList.add('status-transition-offline');
                setTimeout(() => {
                    el.classList.remove('status-transition-offline');
                }, 600);
            }
        });
    },

    /**
     * Verificar si un usuario está en línea
     */
    isOnline(userId) {
        return this.onlineUsers.has(parseInt(userId));
    },

    /**
     * Actualizar todos los indicadores en la UI
     */
    updateAllStatusIndicators() {
        // 1. Sidebar contactos: cada .contact-item[data-user-id]
        document.querySelectorAll('.contact-item[data-user-id]').forEach(el => {
            const userId = parseInt(el.dataset.userId);
            this._updateContactItem(el, this.isOnline(userId));
        });

        // 2. Sidebar conversaciones: cada .conversation-item[data-user-id]
        document.querySelectorAll('.conversation-item[data-user-id]').forEach(el => {
            const userId = parseInt(el.dataset.userId);
            const dot = el.querySelector('.status-dot-mini');
            if (dot) {
                dot.classList.toggle('online', this.isOnline(userId));
                dot.classList.toggle('offline', !this.isOnline(userId));
            }
        });

        // 3. Chat Header activo
        const headerUserId = document.getElementById('chat-user-id')?.value;
        if (headerUserId) {
            this._updateChatHeader(parseInt(headerUserId), this.isOnline(headerUserId));
        }

        console.log('[PRESENCE] ✅ UI sincronizada –', this.onlineUsers.size, 'usuarios en línea');
    },

    /**
     * Actualizar indicador para un userId específico (EN TIEMPO REAL)
     */
    updateStatusIndicator(userId, isOnline) {
        userId = parseInt(userId);

        // A. Status dots en sidebar (contactos y conversaciones)
        document.querySelectorAll(`[data-status-user-id="${userId}"]`).forEach(dot => {
            // Animación de transición
            dot.style.transition = 'all 0.3s ease';
            
            dot.classList.remove('online', 'offline');
            dot.classList.add(isOnline ? 'online' : 'offline');
            
            // Feedback visual
            dot.style.transform = 'scale(1.2)';
            setTimeout(() => {
                dot.style.transform = 'scale(1)';
            }, 200);
        });

        // B. Texto de estado en contactos
        document.querySelectorAll(`.contact-item[data-user-id="${userId}"]`).forEach(el => {
            this._updateContactItem(el, isOnline);
        });

        // C. Chat Header (si el chat abierto es con este usuario)
        const headerUserId = document.getElementById('chat-user-id')?.value;
        if (headerUserId && parseInt(headerUserId) === userId) {
            this._updateChatHeader(userId, isOnline);
        }

        console.log(`[PRESENCE] 🔄 Usuario ${userId} → ${isOnline ? '🟢 ONLINE' : '🔴 OFFLINE'}`);
    },

    /**
     * Actualizar un .contact-item con estado
     */
    _updateContactItem(el, isOnline) {
        // Dot
        const dot = el.querySelector('.status-dot-mini');
        if (dot) {
            dot.classList.remove('online', 'offline');
            dot.classList.add(isOnline ? 'online' : 'offline');
        }

        // Texto
        const statusText = el.querySelector('.contact-status');
        if (statusText) {
            statusText.textContent = isOnline ? 'En línea' : 'Desconectado';
            statusText.style.color = isOnline ? 'var(--success, #10b981)' : 'var(--text-muted)';
            statusText.style.fontWeight = isOnline ? '600' : '500';
        }
    },

    /**
     * Actualizar el header del chat activo (EN TIEMPO REAL)
     */
    _updateChatHeader(userId, isOnline) {
        const badge = document.getElementById('chat-header-status-badge');
        const label = document.getElementById('chat-header-status-text');

        if (badge) {
            badge.classList.remove('online', 'offline');
            badge.classList.add(isOnline ? 'online' : 'offline');
            
            // Animación de pulse
            badge.style.animation = isOnline ? 'pulse 2s infinite' : 'none';
        }

        if (label) {
            label.textContent = isOnline ? 'En línea' : 'Desconectado';
            label.style.opacity = isOnline ? '1' : '0.6';
            label.style.color = isOnline ? 'var(--success, #10b981)' : 'var(--text-muted)';
            label.style.fontWeight = isOnline ? '600' : '500';
            label.style.transition = 'all 0.3s ease';
        }
    },

    /**
     * Forzar actualización después de navegación SPA
     */
    refreshAfterNavigation() {
        console.log('[PRESENCE] 🔄 Refrescando después de navegación SPA...');
        this.updateAllStatusIndicators();
    },

    /**
     * Desuscribirse del canal (limpieza)
     */
    destroy() {
        if (this.presenceChannel) {
            window.Echo.leave('presence-global');
            this.presenceChannel = null;
            this.isInitialized = false;
            console.log('[PRESENCE] 🛑 Canal de presencia cerrado');
        }
    }
};

// Exponer globalmente
window.PresenceService = PresenceService;

// Re-exportar para import { initializePresence } from './presence.js'
export function initializePresence(currentUserId, targetUserId) {
    // Asegurar que PresenceService está inicializado
    if (!PresenceService.isInitialized) {
        PresenceService.init();
    } else {
        // Si ya estaba inicializado, solo refrescar UI
        PresenceService.updateAllStatusIndicators();
    }
}
