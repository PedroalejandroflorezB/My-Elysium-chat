<div id="modal-qr" class="modal-overlay" style="display: none;">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-qr')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h2 class="modal-title">Escanear QR</h2>
        </div>
        <div class="modal-body">
            <div class="qr-scanner-container">
                <!-- Video para la cámara -->
                <div class="qr-camera-container" id="qr-camera-container" style="display: none;">
                    <video id="qr-video" autoplay muted playsinline></video>
                    <div class="qr-overlay">
                        <div class="qr-target-box"></div>
                    </div>
                    <canvas id="qr-canvas" style="display: none;"></canvas>
                </div>
                
                <!-- Estado inicial -->
                <div class="qr-preview" id="qr-preview">
                    <i class="fas fa-qrcode"></i>
                    <p class="qr-instructions">
                        Apunta la cámara al código QR de tu contacto para agregarlo automáticamente.
                    </p>
                </div>
                
                <!-- Resultado del escaneo -->
                <div class="qr-result" id="qr-result" style="display: none;">
                    <div class="qr-result-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <p class="qr-result-text" id="qr-result-text"></p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeQRScanner()">
                Cerrar
            </button>
            <button class="btn btn-primary" id="qr-camera-btn" onclick="toggleCamera()">
                <i class="fas fa-camera"></i> <span id="camera-btn-text">Activar Cámara</span>
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales para el scanner QR
let qrStream = null;
let qrScanning = false;
let qrScanInterval = null;

async function toggleCamera() {
    const video = document.getElementById('qr-video');
    const cameraContainer = document.getElementById('qr-camera-container');
    const preview = document.getElementById('qr-preview');
    const btn = document.getElementById('qr-camera-btn');
    const btnText = document.getElementById('camera-btn-text');
    
    if (!qrStream) {
        // Activar cámara
        try {
            btn.disabled = true;
            btnText.textContent = 'Iniciando...';
            
            qrStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: 'environment', // Cámara trasera preferida
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
            
            video.srcObject = qrStream;
            cameraContainer.style.display = 'block';
            preview.style.display = 'none';
            
            btnText.textContent = 'Detener Cámara';
            btn.disabled = false;
            
            // Iniciar escaneo
            startQRScanning();
            
        } catch (error) {
            console.error('Error accediendo a la cámara:', error);
            btnText.textContent = 'Activar Cámara';
            btn.disabled = false;
            
            if (typeof showToast === 'function') {
                showToast('Error', 'No se pudo acceder a la cámara', 'error');
            } else {
                alert('No se pudo acceder a la cámara');
            }
        }
    } else {
        // Detener cámara
        stopCamera();
    }
}

function stopCamera() {
    const video = document.getElementById('qr-video');
    const cameraContainer = document.getElementById('qr-camera-container');
    const preview = document.getElementById('qr-preview');
    const btn = document.getElementById('qr-camera-btn');
    const btnText = document.getElementById('camera-btn-text');
    
    if (qrStream) {
        qrStream.getTracks().forEach(track => track.stop());
        qrStream = null;
    }
    
    if (qrScanInterval) {
        clearInterval(qrScanInterval);
        qrScanInterval = null;
    }
    
    qrScanning = false;
    video.srcObject = null;
    cameraContainer.style.display = 'none';
    preview.style.display = 'block';
    
    btnText.textContent = 'Activar Cámara';
    btn.disabled = false;
}

function startQRScanning() {
    const video = document.getElementById('qr-video');
    const canvas = document.getElementById('qr-canvas');
    const ctx = canvas.getContext('2d');
    
    qrScanning = true;
    
    // Escanear cada 500ms
    qrScanInterval = setInterval(() => {
        if (!qrScanning || !video.videoWidth || !video.videoHeight) return;
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const qrCode = scanQRFromImageData(imageData);
        
        if (qrCode) {
            handleQRDetected(qrCode);
        }
    }, 500);
}

function scanQRFromImageData(imageData) {
    // Implementación simple de detección QR usando jsQR si está disponible
    if (typeof jsQR !== 'undefined') {
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        return code ? code.data : null;
    }
    
    // Fallback: buscar patrones básicos de QR en los datos
    // Esta es una implementación muy básica, en producción usarías una librería como jsQR
    return null;
}

function handleQRDetected(qrData) {
    console.log('QR detectado:', qrData);
    
    try {
        // Intentar parsear como JSON (formato Elysium)
        const data = JSON.parse(qrData);
        
        if (data.type === 'elysium_contact' && data.username) {
            processElysiumContact(data);
        } else {
            // QR genérico, intentar extraer username
            processGenericQR(qrData);
        }
    } catch (e) {
        // No es JSON, procesar como texto plano
        processGenericQR(qrData);
    }
}

async function processElysiumContact(data) {
    stopCamera();
    showQRResult(`¡Contacto encontrado! @${data.username}`, 'success');
    
    // Intentar agregar el contacto
    try {
        const response = await fetch('/api/contacts/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                username: data.username
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof showToast === 'function') {
                showToast('Éxito', `@${data.username} agregado a tus contactos`, 'success');
            }
            
            // Cerrar modal después de 2 segundos
            setTimeout(() => {
                closeQRScanner();
                // Recargar la página o actualizar la lista de contactos
                window.location.reload();
            }, 2000);
        } else {
            showQRResult(`Error: ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Error agregando contacto:', error);
        showQRResult('Error al agregar el contacto', 'error');
    }
}

function processGenericQR(qrData) {
    // Buscar patrones de usuario en el texto
    const usernameMatch = qrData.match(/@([a-zA-Z0-9_]+)/);
    const urlMatch = qrData.match(/\/add\/([a-zA-Z0-9_]+)/);
    
    let username = null;
    if (usernameMatch) {
        username = usernameMatch[1];
    } else if (urlMatch) {
        username = urlMatch[1];
    }
    
    if (username) {
        processElysiumContact({ username: username, type: 'generic' });
    } else {
        stopCamera();
        showQRResult('QR no reconocido como contacto de Elysium', 'warning');
    }
}

function showQRResult(message, type) {
    const result = document.getElementById('qr-result');
    const resultText = document.getElementById('qr-result-text');
    const cameraContainer = document.getElementById('qr-camera-container');
    const preview = document.getElementById('qr-preview');
    
    resultText.textContent = message;
    result.className = `qr-result qr-result--${type}`;
    
    cameraContainer.style.display = 'none';
    preview.style.display = 'none';
    result.style.display = 'block';
}

function closeQRScanner() {
    stopCamera();
    
    // Resetear UI
    const result = document.getElementById('qr-result');
    const preview = document.getElementById('qr-preview');
    
    result.style.display = 'none';
    preview.style.display = 'block';
    
    // Cerrar modal
    closeModal('modal-qr');
}

// Hacer funciones disponibles globalmente
window.toggleCamera = toggleCamera;
window.closeQRScanner = closeQRScanner;
window.handleQRAction = handleQRAction;
window.showQRScanner = showQRScanner;

// Limpiar al cerrar modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-qr');
        if (modal && modal.style.display === 'flex') {
            closeQRScanner();
        }
    }
});

// Debug: Verificar que las funciones estén disponibles
console.log('QR Scanner functions loaded:', {
    toggleCamera: typeof window.toggleCamera,
    closeQRScanner: typeof window.closeQRScanner,
    handleQRAction: typeof window.handleQRAction,
    showQRScanner: typeof window.showQRScanner
});
</script>