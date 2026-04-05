@extends('layouts.chat')

@section('title', 'Chat con ' . ($contact?->name ?? 'Usuario'))

@section('content')
<!-- Top Bar -->
@include('chat.partials.topbar')

<!-- Content Wrapper -->
<div class="chat-layout">
    {{-- Sidebar --}}
    @include('chat.partials.sidebar')
    
    <main class="chat-main" id="chat-main">
        @include('chat.partials.chat-content')
    </main>
</div>

<!-- ============================================
     MODAL: PERFIL DEL CONTACTO
     Muestra datos de: {{ $contact?->name }} - NO del usuario actual
     ============================================ -->
<div id="contact-profile-modal" class="contact-profile-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-contact-title">
    <div class="modal-backdrop" onclick="closeContactProfileModal()"></div>
    
    <div class="modal-content">
        
        <!-- Cabecera con gradiente y avatar grande -->
        <div class="contact-profile-header">
            <button onclick="closeContactProfileModal()" class="contact-profile-close">×</button>
            
            {{-- Avatar del CONTACTO --}}
            <div class="contact-profile-avatar">
                @if($contact?->avatar)
                    <img src="{{ asset('storage/' . $contact->avatar) }}" alt="{{ $contact->name }}">
                @else
                    <span class="contact-profile-avatar-initials">{{ strtoupper(substr($contact?->name ?? 'U', 0, 1)) }}</span>
                @endif
            </div>
            
            {{-- Nombre del CONTACTO --}}
            <h2 id="modal-contact-title" class="contact-profile-name">{{ $contact?->name ?? 'Usuario' }}</h2>
            <p class="contact-profile-username">{{ '@' . ($contact->username ?? 'usuario') }}</p>
        </div>
        
        <!-- Cuerpo con datos del contacto -->
        <div class="contact-profile-body">
            <div class="contact-profile-info">
                
                <div class="contact-info-row">
                    <span class="contact-info-label">Nombre completo</span>
                    <span class="contact-info-value">{{ $contact?->name ?? '—' }}</span>
                </div>
                
                <div class="contact-info-row">
                    <span class="contact-info-label">Usuario</span>
                    <span class="contact-info-value contact-info-value--primary">{{ '@' . ($contact->username ?? '—') }}</span>
                </div>
                
                <div class="contact-info-row">
                    <span class="contact-info-label">Miembro desde</span>
                    <span class="contact-info-value">{{ $contact?->created_at?->format('d M Y') ?? 'N/A' }}</span>
                </div>
                
                <div class="contact-info-row">
                    <span class="contact-info-label">Estado</span>
                    <span class="contact-info-value contact-info-value--success">● En línea</span>
                </div>
                
            </div>
            
            <!-- Acciones -->
            <div class="contact-profile-actions">
                <button onclick="closeContactProfileModal()" class="contact-profile-btn contact-profile-btn--secondary">
                    Cerrar
                </button>
                <button onclick="blockContact()" class="contact-profile-btn contact-profile-btn--danger">
                    Bloquear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Modals movidos al layout principal (chat.blade.php) -->

@push('scripts')
<script>
// Datos globales del contexto de chat (necesarios para el primer load)
window.currentUserId = {{ auth()->id() }};
window.targetUserId = {{ $contact?->id ?? 'null' }};

// El resto de la inicialización (RoomID, P2P, Eventos) lo maneja chat.js 
// automáticamente al cargar la página a través de initializeChatFeatures().
</script>
@endpush
@endsection