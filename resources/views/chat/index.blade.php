@extends('layouts.chat')

@section('title', 'Elysium P2P - Chat')

@section('content')
<!-- Top Bar -->
@include('chat.partials.topbar')

<!-- Content Wrapper -->
<div class="chat-layout">
    {{-- Sidebar --}}
    @include('chat.partials.sidebar')
    
    {{-- Área Principal - Empty State --}}
    <main class="chat-main" id="chat-main">
        @include('chat.partials.empty-state')
        
        <!-- Modal Añadir Contacto (Preservado) -->
        <div id="add-contact-modal" class="modal" style="display:none;">
            <div class="modal-backdrop" onclick="closeAddContactModal()"></div>
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Buscar y Añadir Contacto</h3>
                    <button class="modal-close" onclick="closeAddContactModal()">✕</button>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom:1rem;">
                        <input 
                            type="text" 
                            id="modal-search-input" 
                            class="admin-input" 
                            placeholder="Busca por nombre o @usuario..."
                            autocomplete="off"
                        >
                    </div>
                    <div id="modal-search-results" style="max-height:300px;overflow-y:auto;">
                        <p style="text-align:center;color:#888;padding:2rem;">Escribe al menos 2 caracteres para buscar</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- QR Modals movidos al layout principal (chat.blade.php) -->

<script>
function copyUsername() {
    const username = '{{ "@" . auth()->user()->username }}';
    navigator.clipboard.writeText(username).then(() => {
        showToast('Copiado', '@usuario copiado al portapapeles', 'success');
    }).catch(err => {
        showToast('Error', 'No se pudo copiar al portapapeles', 'error');
    });
}

function openAddContactModal() {
    const modal = document.getElementById('add-contact-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('modal-search-input')?.focus(), 100);
    }
}

function closeAddContactModal() {
    const modal = document.getElementById('add-contact-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('modal-search-input').value = '';
        document.getElementById('modal-search-results').innerHTML = '<p style="text-align:center;color:#888;padding:2rem;">Escribe al menos 2 caracteres para buscar</p>';
    }
}

function openQRScanner() {
    showToast('Próximamente', 'Escáner QR estará disponible pronto', 'info');
}

function showQRModal() {
    const modal = document.getElementById('modal-qr');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Limpiar cámara si es el modal QR scanner
        if (modalId === 'modal-qr' && typeof closeQRScanner === 'function') {
            closeQRScanner();
        }
    }
}

// Hacer funciones disponibles globalmente
window.showQRModal = showQRModal;
window.closeModal = closeModal;

// Platform detection
function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function isAndroid() {
    return /Android/i.test(navigator.userAgent);
}

// Make functions globally available
window.isMobile = isMobile;
window.isAndroid = isAndroid;

// Búsqueda en el modal
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('modal-search-input');
    if (!input) return;
    
    let searchTimeout;
    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        const results = document.getElementById('modal-search-results');
        
        if (query.length < 2) {
            results.innerHTML = '<p style="text-align:center;color:#888;padding:2rem;">Escribe al menos 2 caracteres para buscar</p>';
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            results.innerHTML = '<p style="text-align:center;color:#888;padding:1rem;">🔍 Buscando...</p>';
            try {
                const resp = await fetch('/api/contacts/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ query })
                });
                const data = await resp.json();
                
                if (data.success && data.results && data.results.length > 0) {
                    results.innerHTML = data.results.map(user => `
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;border-bottom:1px solid var(--border-color);">
                            <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.25rem;flex-shrink:0;">
                                ${user.avatar ? `<img src="${user.avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` : user.name.charAt(0).toUpperCase()}
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;">${escapeH(user.name)}</div>
                                <div style="font-size:0.8rem;opacity:0.7;">@${escapeH(user.username)}</div>
                            </div>
                            <div style="display:flex;gap:0.5rem;">
                                <button onclick="sendContactReq(${user.id}, this)" style="background:none;border:1px solid var(--primary);color:var(--primary);border-radius:6px;padding:0.35rem 0.75rem;cursor:pointer;font-size:0.8rem;transition:all 0.2s;"
                                    onmouseover="this.style.background='var(--primary)';this.style.color='white'" onmouseout="this.style.background='none';this.style.color='var(--primary)'">
                                    Agregar
                                </button>
                                <button onclick="navigateToChat('${user.username}')" style="background:none;border:1px solid var(--border-color);color:var(--text-primary);border-radius:6px;padding:0.35rem 0.75rem;cursor:pointer;font-size:0.8rem;">
                                    Chat
                                </button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    results.innerHTML = '<p style="text-align:center;color:#888;padding:1.5rem;">No se encontraron usuarios</p>';
                }
            } catch (e) {
                results.innerHTML = '<p style="text-align:center;color:#ef4444;padding:1rem;">Error al buscar, intenta de nuevo</p>';
            }
        }, 300);
    });
});

async function sendContactReq(userId, btn) {
    btn.disabled = true;
    btn.textContent = 'Enviando...';
    try {
        const resp = await fetch('/api/contacts/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ target_user_id: userId })
        });
        const data = await resp.json();
        if (data.success) {
            btn.textContent = '✅ Enviada';
            btn.style.borderColor = '#10b981';
            btn.style.color = '#10b981';
        } else {
            btn.textContent = data.message || 'Error';
            btn.style.borderColor = '#ef4444';
            btn.style.color = '#ef4444';
            btn.disabled = false;
        }
    } catch(e) {
        btn.textContent = 'Error';
        btn.disabled = false;
    }
}

function escapeH(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

// Cerrar con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAddContactModal();
});
</script>
@endsection