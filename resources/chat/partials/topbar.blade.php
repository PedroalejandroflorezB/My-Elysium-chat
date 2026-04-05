<header class="top-bar">
    <div class="top-bar-left">
        <a href="{{ url('/chat') }}" class="brand">
            <!-- Logo Icons -->
            <div class="brand-icons">
                <div class="brand-icon brand-icon--primary"></div>
                <div class="brand-icon brand-icon--secondary"></div>
            </div>
            
            <!-- Brand Name -->
            <span class="brand-name">Elysium Ito</span>
            
            <!-- Status Indicator -->
            <div class="status-indicator">
                <div class="status-dot status-dot--online"></div>
            </div>
        </a>
    </div>
    
    <div class="top-bar-right">
        <button class="btn-logout" onclick="logout()">
            Salir
        </button>
    </div>
</header>

<script>
async function logout() {
    const confirmed = await showConfirm({
        title: '¿Cerrar sesión?',
        message: 'Se cerrará tu sesión actual en Elysium.',
        confirmText: 'Cerrar sesión',
        cancelText: 'Cancelar',
        type: 'info',
        icon: '👋'
    });
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("logout") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
