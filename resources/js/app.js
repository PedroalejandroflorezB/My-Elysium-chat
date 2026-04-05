/**
 * Elysium Ito - Entry Point
 * Optimizado para t3.micro
 */

// ============================================
// CORE GLOBALS - DEBE IR PRIMERO
// ============================================
import './core/globals.js';
import './core/theme-manager.js';

// ============================================
// SERVICIOS CENTRALIZADOS
// ============================================
import axiosService from './services/axios-service.js';
import './services/progress-styles.js';
import { chunkedTransferManager } from './services/chunked-transfer.js';
import { transferErrorHandler, progressVisualizer } from './services/transfer-errors.js';

window.axiosService = axiosService;
window.chunkedTransferManager = chunkedTransferManager;
window.transferErrorHandler = transferErrorHandler;
window.progressVisualizer = progressVisualizer;

// ============================================
// FONT AWESOME SVG
// ============================================
import { createIcon, migrateLegacyIcons } from './fontawesome';
window.createIcon = createIcon;
window.migrateLegacyIcons = migrateLegacyIcons;

// ============================================
// QR CODE LIBRARIES
// ============================================
import jsQR from 'jsqr';
import QRCode from 'qrcode';
window.jsQR = jsQR;
window.QRCode = QRCode;

// ============================================
// LOGGER CENTRALIZADO
// ============================================
import Logger from './utils/logger';

// ============================================
// RESTO DE IMPORTS
// ============================================
import './bootstrap';
import Alpine from 'alpinejs';

// Componentes
import './components/chat';
import './components/search';
import './components/contact-modal';
import './components/qr-generator';  // ← QR Generator
import './components/p2p-file-transfer';  // ← Transparencia P2P Activa
import { PresenceService } from './components/presence';

window.Alpine = Alpine;

if (typeof Alpine !== 'undefined') {
    Alpine.start();
}

import { initializeSearch } from './components/search.js';
import { initOnboarding } from './components/onboarding.js';
import { initModals } from './components/modals.js';
import { initTheme } from './components/theme.js';
import { initializeChatFeatures } from './components/chat.js';
import { initProfile } from './components/profile.js';
import { initUtils } from './utils/helpers.js';
import { initToast } from './components/toast.js';
import { subscribeToUserChannel } from './components/websocket.js';
import { initContactActions } from './components/contacts.js';

document.addEventListener('DOMContentLoaded', () => {
    Logger.success('Elysium Ito inicializado');
    initToast();
    
    initOnboarding();
    initModals();
    initTheme();
    
    // Registrar Service Worker para descargas P2P
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => Logger.success('Service Worker registrado'))
            .catch(err => Logger.error('Error registrando Service Worker: ' + err.message));
    }
    
    // ✅ INICIALIZACIÓN GLOBAL DE P2P: Activa para recibir en cualquier vista
    if (typeof window.P2PFileTransfer !== 'undefined' && !window.p2pTransfer) {
        window.p2pTransfer = new window.P2PFileTransfer();
    }

    // ✅ Suscribirse al canal de usuario globalmente si está disponible
    const currentUserId = document.querySelector('meta[name="user-id"]')?.content || (window.Elysium && window.Elysium.currentUserId);
    if (currentUserId && typeof window.subscribeToUserChannel === 'function') {
        window.subscribeToUserChannel(currentUserId);
    }
    
    // Inicializar features de chat (si estamos en página de chat)
    if (document.getElementById('chat-user-id') || document.getElementById('current-user-id')) {
        initializeChatFeatures();
        initContactActions();
    }
    
    initializeSearch();
    if (window.PresenceService) window.PresenceService.init();
    
    initProfile();
    initUtils();
});