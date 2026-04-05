/**
 * QR Generator Component
 * Maneja la generación y visualización de códigos QR
 */

import QRCode from 'qrcode';

class QRGenerator {
    constructor() {
        this.currentQR = null;
        this.init();
    }

    init() {
        // generateQR y shareQR ahora son manejados por el modal blade
        // Solo exponer QRCode globalmente para que el modal lo use
        window.QRCode = QRCode;
    }

    async generateQR() {
        const modal = document.getElementById('modal-qr-generate');
        if (!modal) return;

        // Mostrar modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Obtener datos del usuario
        const username = document.querySelector('meta[name="user-username"]')?.content || 
                        document.getElementById('current-username')?.textContent?.trim() || 
                        '@usuario';
        
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        const baseUrl = window.location.origin;

        // Crear datos para el QR
        const qrData = {
            type: 'elysium_contact',
            username: username.replace('@', ''),
            userId: userId,
            addUrl: `${baseUrl}/add/${username.replace('@', '')}`,
            timestamp: Date.now()
        };

        const qrString = JSON.stringify(qrData);

        try {
            // Generar QR code
            const qrDisplay = document.getElementById('qr-code-display');
            if (qrDisplay) {
                qrDisplay.innerHTML = '<div class="qr-loading"><i class="fas fa-spinner fa-spin"></i><p>Generando código QR...</p></div>';

                const qrCodeDataURL = await QRCode.toDataURL(qrString, {
                    width: 256,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    },
                    errorCorrectionLevel: 'M'
                });

                qrDisplay.innerHTML = `
                    <div class="qr-code-generated">
                        <img src="${qrCodeDataURL}" alt="Código QR" class="qr-image" />
                    </div>
                `;

                this.currentQR = {
                    dataURL: qrCodeDataURL,
                    username: username,
                    data: qrData
                };
            }
        } catch (error) {
            console.error('Error generando QR:', error);
            const qrDisplay = document.getElementById('qr-code-display');
            if (qrDisplay) {
                qrDisplay.innerHTML = `
                    <div class="qr-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error al generar el código QR</p>
                        <button class="btn btn-sm btn-primary" onclick="generateQR()">Reintentar</button>
                    </div>
                `;
            }
        }
    }

    async shareQR() {
        if (!this.currentQR) {
            console.warn('No hay QR generado para compartir');
            return;
        }

        const { username, data } = this.currentQR;
        const shareText = `¡Agrégame en Elysium P2P! Mi usuario es ${username}`;
        const shareUrl = data.addUrl;

        try {
            if (navigator.share && navigator.canShare) {
                // Usar Web Share API si está disponible
                await navigator.share({
                    title: 'Elysium P2P - Agregar Contacto',
                    text: shareText,
                    url: shareUrl
                });
            } else if (navigator.clipboard) {
                // Fallback: copiar al portapapeles
                const textToCopy = `${shareText}\n${shareUrl}`;
                await navigator.clipboard.writeText(textToCopy);
                
                if (window.showToast) {
                    showToast('Copiado', 'Información de contacto copiada al portapapeles', 'success');
                } else {
                    alert('Información copiada al portapapeles');
                }
            } else {
                // Último recurso: mostrar información
                const textToShow = `${shareText}\n${shareUrl}`;
                prompt('Copia esta información:', textToShow);
            }
        } catch (error) {
            console.error('Error compartiendo QR:', error);
            if (window.showToast) {
                showToast('Error', 'No se pudo compartir el código QR', 'error');
            }
        }
    }

    // Método para limpiar QR actual
    clearQR() {
        this.currentQR = null;
        const qrDisplay = document.getElementById('qr-code-display');
        if (qrDisplay) {
            qrDisplay.innerHTML = `
                <div class="qr-placeholder">
                    <i class="fas fa-qrcode"></i>
                    <p>Generando código QR...</p>
                </div>
            `;
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new QRGenerator();
});

export default QRGenerator;