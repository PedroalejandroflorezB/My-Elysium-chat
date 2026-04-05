// Cola de solicitudes pendientes
const pendingRequestQueue = [];
let modalIsOpen = false;

// Función para encolar y mostrar solicitudes
function showPendingRequestModal(userData) {
    // Normalizar: soportar tanto request_id como requestId
    const normalized = {
        id:        userData.id,
        name:      userData.name,
        username:  userData.username,
        avatar:    userData.avatar || null,
        requestId: userData.requestId || userData.request_id,
    };

    pendingRequestQueue.push(normalized);

    if (!modalIsOpen) {
        _displayNextRequest();
    }
}

function _displayNextRequest() {
    if (pendingRequestQueue.length === 0) {
        modalIsOpen = false;
        return;
    }

    const userData = pendingRequestQueue.shift();
    const modal = document.getElementById('modal-contact-request-pending');
    if (!modal) return;

    document.getElementById('pendingName').textContent = userData.name || userData.username;
    document.getElementById('pendingUsername').textContent = '@' + (userData.username || '');

    const avatarContainer = document.getElementById('pendingAvatar');
    if (userData.avatar) {
        avatarContainer.innerHTML = '<img src="' + userData.avatar + '" alt="' + (userData.name || '') + '">';
    } else {
        avatarContainer.innerHTML = (userData.name || userData.username || 'U').charAt(0).toUpperCase();
    }

    modal.dataset.userId    = userData.id;
    modal.dataset.requestId = userData.requestId;
    modal.style.display     = 'flex';
    modalIsOpen = true;
}

// Función para cerrar el modal y mostrar el siguiente en cola
function closePendingRequestModal() {
    const modal = document.getElementById('modal-contact-request-pending');
    if (modal) modal.style.display = 'none';
    modalIsOpen = false;
    _displayNextRequest();
}

// Función para aceptar solicitud de contacto con Axios
async function acceptPendingRequest() {
    const modal = document.getElementById('modal-contact-request-pending');
    const requestId = modal.dataset.requestId;

    if (!requestId || requestId === 'undefined') {
        console.error('No request ID found');
        showToast('Error', 'No se encontró la solicitud', 'error');
        return;
    }

    try {
        const response = await window.axiosService.post('/api/contacts/request/respond', {
            request_id: parseInt(requestId),
            action: 'accept'
        });

        const data = response.data;
        if (data.success) {
            showToast('Contacto agregado', data.message || 'Solicitud aceptada', 'success');
            const userId = modal.dataset.userId;
            closePendingRequestModal();

            if (typeof loadContacts === 'function') loadContacts();
            if (typeof window.loadPendingRequests === 'function') window.loadPendingRequests();

            window.dispatchEvent(new CustomEvent('contactRequestAccepted', {
                detail: { userId, requestId }
            }));
        } else {
            showToast('Error', data.message || 'No se pudo aceptar la solicitud', 'error');
        }
    } catch (error) {
        console.error('Error aceptando solicitud:', error);
        const message = error.response?.data?.message || 'Error de conexión al servidor';
        showToast('Error', message, 'error');
    }
}

// Función para denegar solicitud de contacto con Axios
async function denyPendingRequest() {
    const modal = document.getElementById('modal-contact-request-pending');
    const requestId = modal.dataset.requestId;

    if (!requestId || requestId === 'undefined') {
        console.error('No request ID found');
        showToast('Error', 'No se encontró la solicitud', 'error');
        return;
    }

    try {
        const response = await window.axiosService.post('/api/contacts/request/respond', {
            request_id: parseInt(requestId),
            action: 'deny'
        });

        const data = response.data;
        if (data.success) {
            showToast('Solicitud denegada', data.message || 'Solicitud rechazada', 'info');
            closePendingRequestModal();

            if (typeof window.loadPendingRequests === 'function') window.loadPendingRequests();

            window.dispatchEvent(new CustomEvent('contactRequestDenied', {
                detail: { requestId }
            }));
        } else {
            showToast('Error', data.message || 'No se pudo procesar la solicitud', 'error');
        }
    } catch (error) {
        console.error('Error denegando solicitud:', error);
        const message = error.response?.data?.message || 'Error de conexión al servidor';
        showToast('Error', message, 'error');
    }
}

// Exponer globalmente
window.showPendingRequestModal  = showPendingRequestModal;
window.closePendingRequestModal = closePendingRequestModal;
window.acceptPendingRequest     = acceptPendingRequest;
window.denyPendingRequest       = denyPendingRequest;
