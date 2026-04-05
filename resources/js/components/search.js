/**
 * Búsqueda de usuarios
 */

let searchTimeout;

export function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const resultsContainer = document.getElementById('search-results');
    const clearBtn = document.getElementById('search-clear');
    
    if (!searchInput) {
        console.warn('[SEARCH] Input de búsqueda no encontrado');
        return;
    }
    
    console.log('[SEARCH] Inicializado correctamente');
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        const query = this.value.trim();
        
        // Mostrar/ocultar botón de limpiar
        if (clearBtn) {
            clearBtn.style.display = query.length > 0 ? 'flex' : 'none';
        }
        
        if (query.length < 2) {
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                
                // Restaurar tab activo
                const activeBtn = document.querySelector('.sidebar__nav-btn.active');
                if (window.switchSidebarTab) {
                    window.switchSidebarTab(activeBtn ? activeBtn.dataset.tab : 'messages');
                }
            }
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            await performSearch(query, resultsContainer);
        }, 300);
    });
    
    // Funcionalidad del botón limpiar
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                
                // Restaurar tab activo
                const activeBtn = document.querySelector('.sidebar__nav-btn.active');
                if (window.switchSidebarTab) {
                    window.switchSidebarTab(activeBtn ? activeBtn.dataset.tab : 'messages');
                }
            }
            
            searchInput.focus();
        });
    }
}

async function performSearch(query, resultsContainer) {
    try {
        console.log('[SEARCH] Buscando:', query);
        
        const response = await fetch('/api/contacts/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ query: query })
        });
        
        console.log('[SEARCH] Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[SEARCH] Resultados:', data);
        
        // ✅ NUEVO: Llamar a renderResults()
        if (data.success && data.results && data.results.length > 0) {
            hideOtherTabs();
            renderResults(data.results, resultsContainer);
        } else {
            if (resultsContainer) {
                hideOtherTabs();
                resultsContainer.innerHTML = `
                    <div class="search-no-results">
                        <span>🔍 No se encontraron usuarios</span>
                    </div>
                `;
                resultsContainer.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('[SEARCH] Error:', error);
    }
}

/**
 * Ocultar otros tabs mientras se busca
 */
function hideOtherTabs() {
    const messages = document.getElementById('all-messages-list');
    const contacts = document.getElementById('contacts-list');
    const files = document.getElementById('tab-files');
    if(messages) messages.style.display = 'none';
    if(contacts) contacts.style.display = 'none';
    if(files) files.style.display = 'none';
}

/**
 * ============================================
 * RENDERIZAR RESULTADOS DE BÚSQUEDA
 * ============================================
 */

function renderResults(results, container) {
    console.log('[SEARCH] renderResults:', results.length, 'resultados');
    
    if (!container) return;
    
    // Forzar visibilidad
    container.style.display = 'block';
    container.classList.add('has-results');
    container.innerHTML = '';
    
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="search-no-results">🔍 No se encontraron usuarios</div>';
        return;
    }
    
    // Renderizar cada resultado
    results.forEach(user => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.innerHTML = `
            <div class="result-avatar">
                ${user.avatar
                    ? `<img src="${user.avatar}" alt="${user.name}">`
                    : `<div class="result-avatar-placeholder">
                           <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                               <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                           </svg>
                       </div>`
                }
            </div>
            <div class="result-info">
                <div class="result-name">${escapeHtml(user.name || user.username)}</div>
                <div class="result-username">@${escapeHtml(user.username)}</div>
            </div>
        `;
        
        // ✅ Click navega directamente al chat
        item.onclick = () => {
            // Limpiar búsqueda
            const searchInput = document.getElementById('search-input');
            const clearBtn = document.getElementById('search-clear');
            if (searchInput) searchInput.value = '';
            if (clearBtn) clearBtn.style.display = 'none';

            // Ocultar resultados
            if (container) {
                container.style.display = 'none';
                container.innerHTML = '';
                container.classList.remove('has-results');
            }

            // Restaurar tab activo
            const activeBtn = document.querySelector('.sidebar__nav-btn.active');
            if (window.switchSidebarTab) {
                window.switchSidebarTab(activeBtn ? activeBtn.dataset.tab : 'messages');
            }

            // Navegar al chat
            if (typeof window.navigateToChat === 'function') {
                window.navigateToChat(user.username);
            } else {
                window.location.href = `/@${user.username}`;
            }
        };

        
        container.appendChild(item);
    });
    
    console.log('[SEARCH] ✅ Resultados renderizados con modal');
}

/**
 * Escape HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Exportar globalmente
window.initializeSearch = initializeSearch;
window.renderResults = renderResults;
let currentModalUser = null;

/**
 * Navegar directamente al chat del usuario (sin modal)
 */
export function openContactModal(user) {
    currentModalUser = user;
    if (typeof window.navigateToChat === 'function') {
        window.navigateToChat(user.username);
    } else {
        window.location.href = `/@${user.username}`;
    }
}

export function closeContactModal() {
    currentModalUser = null;
}

export function startChat() {
    const user = currentModalUser || window.currentModalContact;
    if (!user) return;
    if (typeof window.hideSearchResults === 'function') window.hideSearchResults();
    if (typeof window.navigateToChat === 'function') {
        window.navigateToChat(user.username);
    } else {
        window.location.href = `/@${user.username}`;
    }
}

// Exportar funciones globalmente
window.openContactModal = openContactModal;
window.closeContactModal = closeContactModal;
window.startChat = startChat;