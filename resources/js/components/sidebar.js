/**
 * ============================================
 * SIDEBAR SERVICE - MANEJA ACTUALIZACIONES
 * ============================================
 * 
 * Buenas prácticas:
 * - Solo se encarga del sidebar (Single Responsibility)
 * - Funciones puras y predecibles
 * - Fácil de testear
 * ============================================
 */

/**
 * Refrescar lista de conversaciones desde API
 */
export async function refreshConversationsList() {
    // console.log('[SIDEBAR] Refrescando lista de conversaciones...');
    
    try {
        const response = await fetch('/api/conversations-list', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.conversations) {
            renderConversationsList(data.conversations);
            
            // Actualizar badge global de mensajes
            const badgeMessages = document.getElementById('badge-messages');
            if (badgeMessages) {
                badgeMessages.textContent = data.total_unread || 0;
                badgeMessages.style.display = data.total_unread > 0 ? 'flex' : 'none';
            }
        }
    } catch (error) {
        console.error('[SIDEBAR] ❌ Error al refrescar conversaciones:', error);
    }
}

/**
 * Renderizar lista de conversaciones en el DOM
 */
function renderConversationsList(conversations) {
    const allMessagesList = document.getElementById('all-messages-list');
    
    if (!allMessagesList) {
        console.warn('[SIDEBAR] all-messages-list no existe');
        return;
    }
    
    if (!conversations || conversations.length === 0) {
        allMessagesList.innerHTML = `
            <div class="empty-state" style="animation: fadeIn 0.3s ease;">
                No tienes conversaciones aún
            </div>
        `;
        // console.log('[SIDEBAR] ✅ Empty state mostrado');
        return;
    }
    
    allMessagesList.innerHTML = conversations.map(conv => createConversationHTML(conv)).join('');
    console.log('[SIDEBAR] ✅ Lista de conversaciones actualizada:', conversations.length, 'items');
}

/**
 * Crear HTML de un item de conversación
 */
function createConversationHTML(conv) {
    const avatar = conv.avatar 
        ? `<img src="${conv.avatar}" alt="${conv.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">`
        : '';
    
    const avatarPlaceholder = conv.avatar 
        ? `<div class="result-avatar-placeholder" style="display: none">${conv.name.charAt(0).toUpperCase()}</div>`
        : `<div class="result-avatar-placeholder">${conv.name.charAt(0).toUpperCase()}</div>`;
    
    const unreadBadge = conv.unread_count > 0 
        ? `<div class="unread-badge animate-pop">${conv.unread_count}</div>` 
        : '';

    return `
        <div class="conversation-item ${conv.unread_count > 0 ? 'has-unread' : ''}" 
             data-chat-link="true" 
             data-username="${conv.username}" 
             data-user-id="${conv.id}" 
             style="animation: slideInLeft 0.3s ease; cursor: pointer;">
            <div class="conversation-avatar">
                ${avatar}
                ${avatarPlaceholder}
                <div class="status-dot-mini offline" data-status-user-id="${conv.id}"></div>
            </div>
            <div class="conversation-info">
                <div class="conversation-header">
                    <span class="conversation-name">${escapeHtml(conv.name)}</span>
                    <span class="conversation-time">${conv.last_message_time}</span>
                </div>
                <div class="conversation-preview">
                    ${conv.is_own_message ? '<span class="preview-status">Tú: </span>' : `<span class="preview-status">${escapeHtml(conv.name.split(' ')[0])}: </span>`}
                    <span class="preview-text">${escapeHtml(conv.last_message)}</span>
                </div>
            </div>
            ${unreadBadge}
        </div>
    `;
}

/**
 * Escape HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Actualizar sidebar cuando llega nuevo mensaje
 */
export function updateSidebarOnNewMessage(message) {
    console.log('[SIDEBAR] Actualizando con nuevo mensaje:', message);
    
    // Si estamos en empty state, refrescar lista completa
    if (!window.roomId) {
        refreshConversationsList();
        return;
    }
    
    // Si hay chat activo, usar lógica existente
    if (typeof window.updateSidebarOnNewMessageInternal === 'function') {
        window.updateSidebarOnNewMessageInternal(message);
    }
}

/**
 * Manejar chat eliminado
 */
export function handleChatDeleted(roomId) {
    console.log('[SIDEBAR] Chat eliminado:', roomId);
    
    if (typeof window.handleChatDeletedExternally === 'function') {
        window.handleChatDeletedExternally(roomId);
    }
}

// Exportar funciones globalmente
window.refreshConversationsList = refreshConversationsList;
window.updateSidebarOnNewMessage = updateSidebarOnNewMessage;
window.handleChatDeleted = handleChatDeleted;

/**
 * ============================================
 * GESTIÓN DE TRANSFERENCIAS EN SIDEBAR
 * ============================================
 */

// Almacenar transferencias activas
window.activeTransfersSidebar = new Map();

/**
 * Agregar transferencia al sidebar
 */
function addTransferToSidebar(transferId, fileInfo, role = 'sender') {
    const transfer = {
        id: transferId,
        name: fileInfo.name,
        size: fileInfo.size,
        type: fileInfo.type,
        role: role,
        progress: 0,
        speed: 0,
        timeRemaining: 0,
        status: 'active'
    };
    
    window.activeTransfersSidebar.set(transferId, transfer);
    
    // Crear card en el DOM
    createTransferCard(transfer);
    
    // Actualizar contador
    updateTransfersCount();
    
    // Ocultar empty state
    const emptyState = document.getElementById('no-active-transfers');
    if(emptyState) emptyState.style.display = 'none';
    
    console.log(`[SIDEBAR] Transferencia agregada: ${fileInfo.name}`);
}

/**
 * Crear card de transferencia
 */
function createTransferCard(transfer) {
    const container = document.getElementById('active-transfers');
    if(!container) return;
    
    const card = document.createElement('div');
    card.className = `transfer-card active`;
    card.id = `transfer-${transfer.id}`;
    
    card.innerHTML = `
        <div class="transfer-card__header">
            <div class="transfer-card__icon">${getFileIcon(transfer.type)}</div>
            <div class="transfer-card__info">
                <div class="transfer-card__name" title="${transfer.name}">${transfer.name}</div>
                <div class="transfer-card__meta">${formatFileSize(transfer.size)} • ${transfer.role === 'sender' ? 'Enviando' : 'Recibiendo'}</div>
            </div>
        </div>
        
        <div class="transfer-card__progress-bar">
            <div class="transfer-card__progress-fill" style="width: 0%"></div>
        </div>
        
        <div class="transfer-card__stats">
            <span class="transfer-card__speed">⚡ 0 MB/s</span>
            <span class="transfer-card__time">⏱️ Calculando...</span>
        </div>
        
        <div class="transfer-card__actions">
            <button class="transfer-card__btn transfer-card__btn--danger" onclick="cancelTransfer('${transfer.id}')">
                Cancelar
            </button>
        </div>
    `;
    
    // Opcional pausar:
    // ${transfer.role === 'sender' ? `
    //     <button class="transfer-card__btn" onclick="pauseTransfer('${transfer.id}')">
    //         Pausar
    //     </button>
    // ` : ''}

    container.appendChild(card);
}

/**
 * Actualizar progreso de transferencia
 */
function updateTransferProgress(transferId, progress, speedText, timeText, statusText = null) {
    const transfer = window.activeTransfersSidebar.get(transferId);
    if (!transfer) return;
    
    transfer.progress = progress;
    
    const card = document.getElementById(`transfer-${transferId}`);
    if (!card) return;
    
    // Actualizar barra de progreso
    const progressBar = card.querySelector('.transfer-card__progress-fill');
    if(progressBar) progressBar.style.width = `${progress}%`;
    
    // Actualizar velocidad
    const speedEl = card.querySelector('.transfer-card__speed');
    if(speedEl) speedEl.textContent = `⚡ ${speedText}`;
    
    // Actualizar tiempo restante
    const timeEl = card.querySelector('.transfer-card__time');
    if(timeEl) {
        timeEl.textContent = progress >= 100 ? '' : `⏱️ ${timeText}`;
    }
    
    // Actualizar estado (ej. "Finalizando...")
    const metaEl = card.querySelector('.transfer-card__meta');
    if(metaEl && statusText) {
        metaEl.textContent = `${formatFileSize(transfer.size)} • ${statusText}`;
        metaEl.classList.add('status-highlight');
    }
    
    // Si está completo
    if (progress >= 100 && transfer.status !== 'completed') {
        const p2pTransfer = window.p2pTransfer || Object.values(window).find(x => x instanceof window.P2PFileTransfer);
        if(p2pTransfer && p2pTransfer.activeTransfers.has(transferId)) {
            const tr = p2pTransfer.activeTransfers.get(transferId);
            if(tr.uiFinalized) markTransferComplete(transferId);
        }
    }
}

/**
 * Marcar transferencia como completada
 */
function markTransferComplete(transferId) {
    const transfer = window.activeTransfersSidebar.get(transferId);
    if (!transfer) return;
    
    transfer.status = 'completed';
    
    const card = document.getElementById(`transfer-${transferId}`);
    if (!card) return;
    
    // Cambiar a estado completado
    card.classList.remove('active');
    card.classList.add('completed');
    
    // Actualizar UI
    const progressBar = card.querySelector('.transfer-card__progress-fill');
    if(progressBar) {
        progressBar.style.width = '100%';
        progressBar.style.background = '#10b981';
    }
    
    const metaEl = card.querySelector('.transfer-card__meta');
    if(metaEl) {
        metaEl.textContent = `${formatFileSize(transfer.size)} • ✅ Completado`;
        metaEl.classList.remove('status-highlight');
        metaEl.style.color = '#10b981';
    }
    
    const speedEl = card.querySelector('.transfer-card__speed');
    if(speedEl) speedEl.textContent = '';
    
    const timeEl = card.querySelector('.transfer-card__time');
    if(timeEl) timeEl.textContent = '';
    
    // Botón Cerrar (Solo limpia la UI)
    const actionsEl = card.querySelector('.transfer-card__actions');
    if(actionsEl) {
        actionsEl.innerHTML = `
            <button class="transfer-card__btn transfer-card__btn--success" onclick="removeTransfer('${transferId}')">
                Cerrar
            </button>
        `;
    }
    
    updateTransfersCount();
}

/**
 * Mover transferencia completada al historial
 */
function moveToHistory(transferId) {
    const transfer = window.activeTransfersSidebar.get(transferId);
    if (!transfer) return;
    
    const card = document.getElementById(`transfer-${transferId}`);
    if (!card) return;
    
    const historyList = document.getElementById('completed-list');
    if(historyList) {
        historyList.appendChild(card);
    }
    
    // Eliminar del mapa después de 1 minuto
    setTimeout(() => {
        window.activeTransfersSidebar.delete(transferId);
        if(card.parentNode) card.remove();
        updateTransfersCount();
    }, 60000);
}

/**
 * Actualizar contador de transferencias activas
 */
function updateTransfersCount() {
    const allTransfers = Array.from(window.activeTransfersSidebar.values());
    const activeTransfers = allTransfers.filter(t => t.status === 'active');
    const count = activeTransfers.length;
    
    const counterEl = document.getElementById('active-transfers-count');
    const tabCounterEl = document.getElementById('badge-files');
    const fillEl = document.getElementById('files-icon-fill');
    const btnFiles = document.querySelector('.sidebar__nav-btn--files');
    
    if (counterEl) {
        counterEl.textContent = `${count} activa${count !== 1 ? 's' : ''}`;
    }
    
    if (tabCounterEl) {
        tabCounterEl.textContent = count;
        tabCounterEl.style.display = count > 0 ? 'flex' : 'none';
    }

    // 🌊 EFECTO LÍQUIDO
    if (fillEl && btnFiles) {
        if (count > 0) {
            btnFiles.classList.add('is-transferring');
            // Calcular progreso promedio
            const totalProgress = activeTransfers.reduce((sum, t) => sum + (t.progress || 0), 0);
            const avgProgress = totalProgress / count;
            
            // Mínimo 10% para que se vea el "líquido" desde el inicio
            const fillHeight = Math.max(10, avgProgress);
            fillEl.style.height = `${fillHeight}%`;
        } else {
            btnFiles.classList.remove('is-transferring');
            fillEl.style.height = '0%';
        }
    }
}

/**
 * Cancelar transferencia
 */
window.cancelTransfer = async function(transferId) {
    const confirmed = await showConfirm({
        title: '¿Cancelar transferencia?',
        message: 'Se interrumpirá la transferencia en curso.',
        confirmText: 'Cancelar transferencia',
        cancelText: 'Continuar',
        type: 'warning',
        icon: '⛔'
    });
    
    if (confirmed) {
        const p2pTransfer = window.P2PFileTransferInstance || Object.values(window).find(x => x instanceof window.P2PFileTransfer);
        if (p2pTransfer) {
            p2pTransfer.cancelTransfer(transferId);
        }
        removeTransfer(transferId);
    }
}

/**
 * Pausar transferencia
 */
window.pauseTransfer = function(transferId) {
    // Pendiente
}

/**
 * Descargar archivo completado (función legacy - el guardado ahora es automático)
 */
window.downloadFile = function(transferId) {
    // Esta función ya no es necesaria porque el guardado es automático
    // Se mantiene por compatibilidad pero no hace nada
    console.log('[SIDEBAR] downloadFile llamada - el guardado es automático');
}

/**
 * Eliminar transferencia del sidebar
 */
window.removeTransfer = function(transferId) {
    const card = document.getElementById(`transfer-${transferId}`);
    if (card) {
        card.remove();
    }
    
    window.activeTransfersSidebar.delete(transferId);
    
    // Eliminar también de la clase principal P2P si existe para evitar memory leaks
    const p2pTransfer = window.P2PFileTransferInstance || window.p2pTransfer || Object.values(window).find(x => x && typeof x === 'object' && x.activeTransfers !== undefined && typeof x.startTransfer === 'function');
    if (p2pTransfer && p2pTransfer.activeTransfers.has(transferId)) {
        p2pTransfer.activeTransfers.delete(transferId);
    }
    
    updateTransfersCount();
    
    // Mostrar empty state si no hay más
    const count = Array.from(window.activeTransfersSidebar.values()).filter(t => t.status === 'active').length;
    if (count === 0 && document.getElementById('no-active-transfers')) {
        document.getElementById('no-active-transfers').style.display = 'block';
    }
}

/**
 * Utilidades
 */
function getFileIcon(type) {
    if (!type) return '📄';
    if (type.startsWith('image/')) return '🖼️';
    if (type.startsWith('video/')) return '🎬';
    if (type.startsWith('audio/')) return '🎵';
    if (type === 'application/pdf') return '📕';
    if (type.includes('zip') || type.includes('compressed')) return '📦';
    if (type.includes('exe')) return '💿';
    return '📄';
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }
    return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}

// Exponer funciones globalmente
window.sidebarTransfers = {
    add: addTransferToSidebar,
    update: updateTransferProgress,
    complete: markTransferComplete,
    remove: window.removeTransfer,
    hasAddedId: (id) => window.activeTransfersSidebar.has(id)
};
