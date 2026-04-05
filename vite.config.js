import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS - Archivos base
                'resources/css/app.css',
                'resources/css/auth.css', // CSS - Autenticación
                'resources/css/dashboard.css', // CSS - Dashboard/Admin
                
                // CSS - Componentes
                'resources/css/components/layout.css',
                'resources/css/components/topbar.css',
                'resources/css/components/sidebar.css',
                'resources/css/components/chat.css',
                'resources/css/components/buttons.css',
                'resources/css/components/modals.css',
                'resources/css/components/onboarding.css',
                'resources/css/components/contact-modal.css',
                'resources/css/components/toast.css',
                'resources/css/components/qr-generator.css',
                
                // CSS - Utils
                'resources/css/utils/animations.css',
                'resources/css/utils/responsive.css',
                
                // JavaScript
                'resources/js/app.js',
                'resources/js/components/chat.js',
                'resources/js/components/contact-modal.js',
                'resources/js/components/qr-generator.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
    },
});