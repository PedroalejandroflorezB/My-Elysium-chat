/**
 * Elysium P2P - Contact Management
 * Handles 'Agregar' button and confirmation modals
 */

import Logger from '../utils/logger';

export const initContactActions = () => {
    const addBtn = document.getElementById('add-contact-btn');
    if (!addBtn) return;

    if (addBtn.dataset.initialized) return;
    addBtn.dataset.initialized = 'true';

    // Funciones globales para actualizar desde SPA
    window.setContactBtnState = (state) => {
        const icon = addBtn.querySelector('.icon');
        addBtn.classList.remove('hidden', 'added', 'pending');
        addBtn.style.pointerEvents = 'auto'; // Clikeable por defecto
        
        if (state === 'added') {
            addBtn.classList.add('added');
            addBtn.title = 'Eliminar contacto';
            if (icon) icon.textContent = '✅';
        } else if (state === 'pending') {
            addBtn.classList.add('pending');
            addBtn.title = 'Solicitud enviada';
            if (icon) icon.textContent = '⏳';
            addBtn.style.pointerEvents = 'none'; // Prevenir re-clicks
        } else if (state === 'idle') {
            addBtn.title = 'Agregar contacto';
            if (icon) icon.textContent = '➕';
        } else {
            addBtn.classList.add('hidden');
        }
    };

    window.updateContactBtnStatus = async (userId) => {
        if (!userId || userId === 'null') {
            window.setContactBtnState('hide');
            return;
        }
        try {
            const response = await fetch(`/api/contacts/status/${userId}`);
            const data = await response.json();
            if (data.success) {
                if (data.is_contact) window.setContactBtnState('added');
                else window.setContactBtnState('idle'); // Ignoramos "has_pending" para permitir re-enviar la solicitud
            }
        } catch (e) {
            console.error('[CONTACTS] Error checking:', e);
            window.setContactBtnState('hide');
        }
    };

    // Inicializar la carga visual
    window.updateContactBtnStatus(addBtn.dataset.userId);

    // Evento click en el botón del header
    addBtn.onclick = async (e) => {
        e.preventDefault();
        const userId = addBtn.dataset.userId;
        if (!userId || userId === 'null') return;
        if (addBtn.classList.contains('pending')) return;

        // --- FLUJO DE ELIMINAR CONTACTO ---
        if (addBtn.classList.contains('added')) {
            window.showConfirmModal('¿Estás seguro de que deseas eliminar este contacto?', async () => {
                // Revertir a visual pending/processing 
                addBtn.style.opacity = '0.5';
                addBtn.style.pointerEvents = 'none';

                try {
                    const res = await fetch('/api/contacts/remove', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ contact_id: userId })
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        window.setContactBtnState('idle');
                        window.showToast?.('Contacto eliminado', 'success');
                        
                        if (window.p2pTransfer) {
                            window.p2pTransfer.sendSignalingMessage(userId, 'contact.removed', {
                                sender_id: window.currentUserId
                            });
                        }
                        if (typeof window.refreshContacts === 'function') window.refreshContacts();
                    } else {
                        window.showToast?.(data.message || 'Error al eliminar', 'error');
                    }
                } catch (err) {
                    console.error('[CONTACTS] Error eliminando:', err);
                    window.showToast?.('Error de red', 'error');
                } finally {
                    addBtn.style.opacity = '1';
                    addBtn.style.pointerEvents = 'auto';
                }
            });
            return;
        }

        // --- FLUJO DE AGREGAR CONTACTO ---
        window.setContactBtnState('pending');

        try {
            const response = await fetch('/api/contacts/request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ receiver_id: userId })
            });
            const data = await response.json();
            
            if (data.success) {
                Logger.success('Solicitud enviada');
                
                // Notificar en tiempo real
                if (window.p2pTransfer) {
                    window.p2pTransfer.sendSignalingMessage(userId, 'contact.request', {
                        sender_id: window.currentUserId,
                        sender_name: document.querySelector('.current-user-name')?.textContent || 'Alguien',
                        request_id: data.data.id
                    });
                }
            } else {
                window.setContactBtnState('idle');
                window.showToast?.(data.message || 'Error al enviar solicitud', 'error');
            }
        } catch (err) {
            console.error('[CONTACTS] Error:', err);
            window.setContactBtnState('idle');
        }
    };
};

// Modal dinámico altamente estilizado a prueba de fallas
window.showConfirmModal = (message, onConfirm) => {
    const existing = document.getElementById('dynamic-confirm-modal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'dynamic-confirm-modal';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
    overlay.style.backdropFilter = 'blur(5px)';
    overlay.style.zIndex = '99999';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';

    overlay.innerHTML = `
        <div style="background: var(--surface-2, #111827); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); width: 100%; max-width: 320px; text-align: center; animation: modalFadeIn 0.2s ease-out;">
            <div style="font-size: 2rem; margin-bottom: 8px;">⚠️</div>
            <h3 style="color: white; font-size: 1.25rem; margin-bottom: 8px; font-weight: 600;">¿Eliminar usuario?</h3>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 24px; line-height: 1.4;">${message}</p>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button id="dyn-cancel-btn" style="flex:1; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; cursor: pointer; transition: all 0.2s; font-weight: 500;">Cancelar</button>
                <button id="dyn-confirm-btn" style="flex:1; padding: 10px; border-radius: 8px; border: none; background: #ef4444; color: white; cursor: pointer; transition: all 0.2s; font-weight: bold; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">Sí, eliminar</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    document.getElementById('dyn-cancel-btn').onclick = () => overlay.remove();
    document.getElementById('dyn-confirm-btn').onclick = () => {
        overlay.remove();
        onConfirm();
    };
};

// Refresco dinámico de la lista lateral de contactos
window.refreshContacts = async () => {
    try {
        const res = await fetch('/api/contacts');
        const data = await res.json();
        if (data.success) {
            const container = document.querySelector('.contacts-list-container');
            if (!container) return;
            
            if (data.data.length === 0) {
                container.innerHTML = `<div class="empty-state"><p>No tienes contactos aún</p></div>`;
                return;
            }

            let html = '';
            data.data.forEach(item => {
                const c = item.contact;
                if (!c) return;
                const avatarUrl = c.avatar 
                    ? (c.avatar.startsWith('data:') ? c.avatar : '/storage/' + c.avatar) 
                    : null;
                
                const avatarHtml = avatarUrl 
                    ? `<img src="${avatarUrl}" alt="${c.name}">` 
                    : `<div class="avatar-placeholder"><span style="font-weight:bold;font-size:0.9rem;">${c.name.substring(0,1).toUpperCase()}</span></div>`;

                html += `
                    <div class="contact-item" data-username="${c.username}" data-user-id="${c.id}" style="cursor: pointer;" onclick="if(typeof window.navigateToChat==='function'){window.navigateToChat('${c.username}')}else{window.location.href='/@${c.username}'}">
                        <div class="contact-avatar">
                            ${avatarHtml}
                            <div class="status-dot-mini offline" data-status-user-id="${c.id}"></div>
                        </div>
                        <div class="contact-info">
                            <span class="contact-name">${c.name}</span>
                            <span class="contact-status">Desconectado</span>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
    } catch (e) {
        console.error('[CONTACTS] Error refreshing contacts list', e);
    }
};

// Global handler for contact signals (Delegated from p2p-file-transfer.js)
window.handleContactSignal = async (type, payload) => {
    console.log('[CONTACTS] Señal recibida:', type, payload);
    
    if (type === 'contact.request') {
        const existing = document.getElementById('dynamic-request-modal');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'dynamic-request-modal';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100vw';
        overlay.style.height = '100vh';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.backdropFilter = 'blur(5px)';
        overlay.style.zIndex = '99999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';

        overlay.innerHTML = `
            <style>
                @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
            </style>
            <div style="background: var(--surface-2, #111827); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); width: 100%; max-width: 320px; text-align: center; animation: modalFadeIn 0.2s ease-out;">
                <div style="font-size: 2rem; margin-bottom: 8px;">👋</div>
                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 8px; font-weight: 600;">¿Añadir contacto?</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 24px; line-height: 1.4;">¿Deseas añadir a <strong style="color: white;">${payload.sender_name}</strong> a tus contactos?</p>
                <div style="display: flex; justify-content: center; gap: 12px;">
                    <button id="req-reject-btn" style="flex:1; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; cursor: pointer; transition: all 0.2s; font-weight: 500;">Rechazar</button>
                    <button id="req-accept-btn" style="flex:1; padding: 10px; border-radius: 8px; border: none; background: #10b981; color: white; cursor: pointer; transition: all 0.2s; font-weight: bold; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">Aceptar</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        document.getElementById('req-reject-btn').onclick = async () => {
            const btnReject = document.getElementById('req-reject-btn');
            btnReject.disabled = true;
            btnReject.textContent = 'Rechazando...';
            try {
                await window.axiosService.post('/api/contacts/respond', {
                    request_id: payload.request_id,
                    action: 'reject'
                });
                overlay.remove();
                window.showToast?.('Solicitud rechazada', 'info');
            } catch (err) {
                console.error('[CONTACTS] Error rechazando:', err);
                window.showToast?.('Error al rechazar solicitud', 'error');
                overlay.remove(); // Remove modal even on error
            }
        };

        const btnAccept = document.getElementById('req-accept-btn');
        btnAccept.onclick = async () => {
            btnAccept.disabled = true;
            btnAccept.innerHTML = '<span class="spinner" style="width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; margin-right: 8px;"></span>Procesando...';
            try {
                const data = await window.axiosService.post('/api/contacts/respond', {
                    request_id: payload.request_id,
                    action: 'accept'
                });
                if (data.success) {
                    overlay.remove();
                    window.showToast?.('¡Contacto añadido!', 'success');

                    // Notificar aceptación al emisor
                    if (window.p2pTransfer) {
                        window.p2pTransfer.sendSignalingMessage(payload.sender_id, 'contact.accepted', {
                            receiver_id: window.currentUserId
                        });
                    }
                    
                    // Actualizar UI del receptor en tiempo real sin recargar la página
                    if (typeof window.updateContactBtnStatus === 'function') {
                        window.updateContactBtnStatus(payload.sender_id);
                    }
                    
                    // Recargar lista lateral si es necesario
                    if (typeof window.refreshContacts === 'function') {
                        window.refreshContacts();
                    }
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            } catch (err) {
                console.error('[CONTACTS] Error aceptando:', err);
                window.showToast?.('Error al aceptar contacto', 'error');
                overlay.remove(); // Remove modal on error
            }
        };


    } else if (type === 'contact.accepted') {
        window.showToast?.('¡Solicitud aceptada!', 'success');
        
        // Actualizar visualmente el botón (si el chat activo es el de este usuario)
        if (typeof window.updateContactBtnStatus === 'function') {
            window.updateContactBtnStatus(payload.receiver_id);
        }
        
        // Recargar lista lateral
        if (typeof window.refreshContacts === 'function') {
            window.refreshContacts();
        }
    } else if (type === 'contact.removed') {
        // Actualizar visualmente el botón
        if (typeof window.updateContactBtnStatus === 'function') {
            window.updateContactBtnStatus(payload.sender_id);
        }
        
        // Recargar lista lateral
        if (typeof window.refreshContacts === 'function') {
            window.refreshContacts();
        }
    }
};
