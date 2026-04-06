/**
 * ============================================
 * TRANSFERENCIA P2P CON UI COMPLETA
 * Preview + Modal de Aceptación + Progreso en ambos lados
 * Optimizado para t3.micro - 0 almacenamiento
 * ============================================
 */

class P2PFileTransfer {
    constructor() {
        this.myPeerId = null;
        this.activeTransfers = new Map();
        this.pendingCandidates = new Map(); // 🔑 NUEVO: Cola para candidatos que llegan antes que la conexión
        this.chunkSize = 16384; // 16 KB (Margen seguro recomendado AWS t3.micro)
        this.maxRetries = 3;
        this.connections = new Map();
        this.pendingFile = null; // Archivo pendiente de confirmación
        
        // Generar ID único para este peer
        this.myPeerId = this.generatePeerId();
        console.log('[P2P] ✅ Peer ID generado:', this.myPeerId);
        window.myPeerId = this.myPeerId;
        
        // Suscribirse a signaling vía Reverb
        this.subscribeToSignaling();
        
        // Inicializar UI
        this.initUI();

        // 🚀 NUEVO: Configuración adaptativa por defecto
        this.baseChunkSize = 16384; // 16 KB base (Límite seguro verificado)
        this.bufferLimit = 2 * 1024 * 1024; // 2 MB base
    }

    /**
     * Inicializar event listeners de UI
     */
    initUI() {
        // [ANDROID FIX]: Usar delegación de eventos y soportar touch/click
        // Esto resuelve problemas de botones "muertos" por Z-Index o carga dinámica
        const handleP2PClick = (e) => {
            // Soporte para evitar doble disparo en dispositivos mixtos
            if (e.type === 'touchstart') window._isTouchP2P = true;
            if (e.type === 'click' && window._isTouchP2P) return;

            const target = e.target.closest('button, .p2p-notification-close');
            if (!target) return;

            // Preview Modal
            if (target.id === 'p2p-preview-cancel') {
                e.preventDefault();
                this.hidePreviewModal();
                this.pendingFile = null;
            } 
            else if (target.id === 'p2p-preview-send') {
                e.preventDefault();
                if (this.pendingFiles && this.pendingFiles.length > 0) {
                    this.hidePreviewModal();
                    this.startMultipleTransfers(this.pendingFiles);
                    this.pendingFiles = [];
                }
            }
            // Accept Modal
            else if (target.id === 'p2p-accept-reject') {
                e.preventDefault();
                this.rejectIncomingFile();
            } 
            else if (target.id === 'p2p-accept-accept') {
                e.preventDefault();
                this.acceptIncomingFile();
            }
            // Progress Bar
            else if (target.id === 'p2p-progress-cancel-btn') {
                e.preventDefault();
                this.cancelTransfer();
            }
            // Complete Notification
            else if (target.id === 'p2p-complete-download') {
                e.preventDefault();
                this.downloadCompletedFile();
            } 
            else if (target.closest('.p2p-notification-close')) {
                e.preventDefault();
                document.getElementById('p2p-complete-notification')?.classList.add('hidden');
            }
        };

        // Escuchar tanto clics como toques directamente en el document
        document.addEventListener('click', handleP2PClick);
        document.addEventListener('touchstart', handleP2PClick, { passive: false });
        
        // File input change - mostrar preview (múltiple)
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                if (files.length > 0) {
                    const items = files.slice(0, 10).map(file => ({
                        file: file,
                        receiverId: document.getElementById('chat-user-id')?.value,
                        recipientName: document.querySelector('.chat-header__name')?.textContent?.trim() || 'usuario'
                    }));
                    
                    if (files.length > 10) {
                        showToast('Límite excedido', 'Solo se están enviando los primeros 10 archivos', 'warning');
                    }
                    
                    // Enviar de forma inmediata y automática sin modal
                    this.startMultipleTransfers(items);
                }
                // Reset input para permitir seleccionar el mismo archivo nuevamente
                e.target.value = '';
            });
        }
    }

    /**
     * Generar ID de par único con alta entropía
     */
    generatePeerId() {
        const randomValues = new Uint32Array(2);
        window.crypto.getRandomValues(randomValues);
        return 'peer_' + randomValues[0].toString(36) + randomValues[1].toString(36) + '_' + Date.now();
    }

    /**
     * Suscribirse a canal de signaling vía Reverb
     */
    subscribeToSignaling() {
        if (!window.Echo) {
            console.error('[P2P] ❌ Echo no está disponible');
            return;
        }

        console.log('[P2P] 📡 Suscribiendo a signaling (User Channel):', window.currentUserId);
        
        // Canal de usuario para este peer
        window.Echo.private(`user.${window.currentUserId}`)
            .listen('.p2p.offer', (data) => {
                this.handleSignalingMessage('offer', data);
            })
            .listen('.p2p.answer', (data) => {
                this.handleSignalingMessage('answer', data);
            })
            .listen('.p2p.candidate', (data) => {
                this.handleSignalingMessage('candidate', data);
            })
            .listen('.p2p.transfer.request', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                console.log('[P2P] 📨 Solicitud de transferencia:', payload);
                this.showAcceptModal(payload);
            })
            .listen('.p2p.transfer.accepted', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                console.log('[P2P] ✅ Transferencia aceptada por receptor / Handshake fase 1');
                this.onReceiverAccepted(payload);
            })
            .listen('.p2p.transfer.ready_to_receive', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                console.log('[P2P] 🔓 Semáforo recibido: Receptor listo para el stream');
                this.onReadyToReceive(payload);
            })
            .listen('.p2p.transfer.rejected', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                console.log('[P2P] ❌ Transferencia rechazada por receptor');
                this.showNotification('Transferencia rechazada por el receptor', 'error');
                this.hideProgress();
            })
            .listen('.p2p.chunk', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                this.receiveChunk(payload);
            })
            .listen('.p2p.transfer.complete', (data) => {
                const payload = data.data || data;
                if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;
                console.log('[P2P] ✅ Transferencia completada por emisor:', payload);
                
                const transfer = this.activeTransfers.get(payload.transfer_id);
                if (transfer && transfer.role === 'receiver') {
                    console.log('[P2P] ✅ Signal [complete] recibido. Esperando últimos chunks...');
                    transfer.completedBySender = true;
                    
                    // Solo ensamblar si YA tenemos todos los chunks
                    if (transfer.receivedCount >= transfer.totalChunks) {
                        this.assembleFile(transfer);
                    } else {
                        this.updateProgressTitle(payload.transfer_id, 'Recibiendo últimos datos...');
                    }
                }
            });
    }

    /**
     * Mostrar modal de preview antes de enviar
     */
    showPreviewModal(files) {
        if (!Array.isArray(files)) files = [files];
        
        const recipientName = document.querySelector('.chat-header__name')?.textContent?.trim() || 'usuario';
        
        this.pendingFiles = files.map(file => ({
            file: file,
            receiverId: document.getElementById('chat-user-id')?.value,
            recipientName: recipientName
        }));
        
        // Actualizar lista en el modal
        const listContainer = document.getElementById('p2p-preview-list');
        if (listContainer) {
            listContainer.innerHTML = files.map(file => `
                <div class="p2p-file-item">
                    <div class="p2p-file-icon text-2xl">${this.getFileIcon(file.type)}</div>
                    <div class="p2p-file-details">
                        <div class="p2p-file-name font-bold text-sm truncate">${file.name}</div>
                        <div class="p2p-file-meta text-xs opacity-70">${this.formatFileSize(file.size)} • ${this.getFileTypeLabel(file.type)}</div>
                    </div>
                </div>
            `).join('');
        }
        
        const recipientEl = document.getElementById('p2p-preview-recipient');
        if (recipientEl) recipientEl.textContent = recipientName;
        
        // Mostrar modal
        document.getElementById('p2p-preview-modal').classList.remove('hidden');
        
        // Focus en botón enviar
        setTimeout(() => {
            document.getElementById('p2p-preview-send')?.focus();
        }, 100);
    }

    /**
     * Iniciar múltiples transferencias en paralelo
     */
    async startMultipleTransfers(pendingItems) {
        console.log(`[P2P] 🚀 Iniciando ${pendingItems.length} transferencias...`);
        
        // 🎯 AUTO-SELECCIONAR TAB FILES cuando se envía archivo
        if (typeof window.switchSidebarTab === 'function') {
            window.switchSidebarTab('files');
        }
        
        for (const item of pendingItems) {
            await this.startTransfer(item);
            // Pequeño delay entre inicios para no saturar el canal de señalización
            await new Promise(r => setTimeout(r, 200));
        }
    }

    /**
     * Ocultar modal de preview
     */
    hidePreviewModal() {
        document.getElementById('p2p-preview-modal').classList.add('hidden');
    }

    /**
     * Mostrar modal de aceptación para receptor
     */
    showAcceptModal(data) {
        const fileInfo = data.file_info || data;
        
        // Actualizar UI del modal
        document.getElementById('p2p-accept-name').textContent = fileInfo.name;
        document.getElementById('p2p-accept-size').textContent = this.formatFileSize(fileInfo.size);
        document.getElementById('p2p-accept-type').textContent = this.getFileTypeLabel(fileInfo.mime_type);
        document.getElementById('p2p-accept-sender').textContent = data.sender_name || 'Usuario';
        document.getElementById('p2p-accept-icon').textContent = this.getFileIcon(fileInfo.mime_type);
        
        // Guardar datos para después
        this.pendingIncomingTransfer = {
            transferId: data.transfer_id || crypto.randomUUID(),
            senderId: data.sender_id,
            senderPeerId: data.sender_peer_id,
            fileInfo: fileInfo,
            chunkSize: fileInfo.chunk_size || this.chunkSize, // 🔑 Usar el enviado por el emisor
            totalChunks: fileInfo.total_chunks || Math.ceil(fileInfo.size / (fileInfo.chunk_size || this.chunkSize)),
            sdp: data.sdp || null 
        };
        
        // Mostrar modal
        document.getElementById('p2p-accept-modal').classList.remove('hidden');
        
        // Focus en botón aceptar
        setTimeout(() => {
            document.getElementById('p2p-accept-accept')?.focus();
        }, 100);
    }

    /**
     * Rechazar archivo entrante
     */
    rejectIncomingFile() {
        if (!this.pendingIncomingTransfer) return;
        
        // Notificar al emisor
        this.sendSignalingMessage(this.pendingIncomingTransfer.senderId, 'transfer.rejected', {
            transfer_id: this.pendingIncomingTransfer.transferId,
            sender_peer_id: this.pendingIncomingTransfer.senderPeerId
        });
        
        // Ocultar modal
        document.getElementById('p2p-accept-modal').classList.add('hidden');
        this.pendingIncomingTransfer = null;
        
        // Feedback visual
        this.showNotification('Archivo rechazado', 'info');
    }

    /**
     * Aceptar archivo entrante - FLUJO PROFESIONAL GATED (Seguro para Gigabytes)
     */
    async acceptIncomingFile() {
        if (!this.pendingIncomingTransfer) return;
        
        // 🎯 AUTO-SELECCIONAR TAB FILES cuando se recibe archivo
        if (typeof window.switchSidebarTab === 'function') {
            window.switchSidebarTab('files');
        }
        
        const transfer = this.pendingIncomingTransfer;
        
        // 🚀 PREVENCIÓN OOM (CERO RAM): Intentar usar API File System Access
        if ('showSaveFilePicker' in window) {
            try {
                transfer.fileHandle = await window.showSaveFilePicker({
                    suggestedName: transfer.fileInfo.name
                });
                transfer.fileStream = await transfer.fileHandle.createWritable();
                console.log('[P2P] 💾 FileSystem API activada. Stream Cero-RAM interactivo.');
            } catch (err) {
                if (err.name === 'AbortError') {
                    console.log('[P2P] 🚫 Guardado cancelado por el usuario.');
                    this.rejectIncomingFile();
                    return; // Abortamos la transferencia por completo
                }
                console.warn('[P2P] ⚠️ Falló FileSystem API, usando fallback:', err);
            }
        }
        
        // 1. INICIALIZAR HANDSHAKE: Ya no usamos buffer temporal primario si hay fileStream.
        // Avisamos que "queremos" el archivo para conectar los navegadores.
        this.sendSignalingMessage(transfer.senderId, 'transfer.accepted', {
            transfer_id: transfer.transferId,
            receiver_peer_id: this.myPeerId,
            sender_peer_id: transfer.senderPeerId
        });
        
        // Ocultar modal e iniciar conexión WebRTC inmediata (sin datos aún)
        document.getElementById('p2p-accept-modal').classList.add('hidden');
        
        transfer.receivedCount = 0;
        transfer.startTime = Date.now();
        transfer.lastProgressTime = Date.now();
        transfer.lastReceivedCount = 0;
        transfer.speedHistory = [];
        transfer.role = 'receiver';
        transfer.id = transfer.transferId;
        this.activeTransfers.set(transfer.transferId, transfer);
        
        this.showProgress(transfer.transferId, 0, transfer.fileInfo.size, 'Estableciendo conexión...');
        await this.setupReceiverConnection(transfer);

        // 2. PARALELO: Iniciar stream en disco, o fallback a memoria/IndexedDB
        if (transfer.fileStream) {
            console.log('[P2P] 🔓 Semáforo (Directo a Disco) disparado.');
            this.sendReadyToReceive(transfer);
        } else {
            this.initFileStream(transfer.transferId, transfer.fileInfo);
        }
    }

    /**
     * Inicializar descarga directa en memoria o IndexedDB
     */
    async initFileStream(transferId, fileInfo) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        
        if (fileInfo.size > 500 * 1024 * 1024) {
            // ✅ Usar IndexedDB para archivos pesados
            transfer.useIndexedDB = true;
            transfer.dbName = `p2p-${transferId}`;
            await this.initIndexedDB(transferId);
        } else {
            // ✅ Usar RAM para archivos pequeños
            transfer.useIndexedDB = false;
            transfer.chunksArray = [];
        }
        
        console.log(`[P2P] 📥 Modo descarga directa activado (IndexedDB: ${transfer.useIndexedDB})`);
        
        // 🔓 SEMÁFORO: Ahora sí, el emisor tiene permiso.
        this.sendReadyToReceive(transfer);
    }

    /**
     * Inicializar IndexedDB
     */
    initIndexedDB(transferId) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(`p2p-${transferId}`, 1);
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains('chunks')) {
                    db.createObjectStore('chunks', { keyPath: 'index' });
                }
            };
            
            request.onsuccess = (event) => {
                const transfer = this.activeTransfers.get(transferId);
                if (transfer) transfer.indexedDB = event.target.result;
                resolve(event.target.result);
            };
            
            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Guardar chunk en IndexedDB
     */
    async saveChunkToIndexedDB(transferId, index, blob) {
        const transfer = this.activeTransfers.get(transferId);
        const db = transfer.indexedDB;
        if (!db) return;
        
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(['chunks'], 'readwrite');
            const store = transaction.objectStore('chunks');
            
            store.put({ index, blob });
            
            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error);
        });
    }

    /**
     * Disparar la descarga del archivo pesado mediante el Service Worker
     */
    triggerServiceWorkerDownload(transfer) {
        console.log('[P2P] 🚀 Iniciando descarga en streaming vía Service Worker...');
        
        const fileName = encodeURIComponent(transfer.fileInfo.name);
        const downloadUrl = `/p2p-download/${transfer.id}/${fileName}?size=${transfer.fileInfo.size}`;
        
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = transfer.fileInfo.name;
        a.style.display = 'none';
        
        document.body.appendChild(a);
        a.click();
        
        setTimeout(() => {
            if (a.parentNode) document.body.removeChild(a);
        }, 1000);
        
        // Finalizar UI de progreso y listado
        this.hideProgress(transfer.id);
        
        // Limpiamos los recursos de WebRTC, pero NO borramos IndexedDB 
        // porque el Service Worker lo necesita para streamear el archivo.
        if (transfer.dataChannel) transfer.dataChannel.close();
        if (transfer.peerConnection) transfer.peerConnection.close();
        this.stopPerformanceMonitor(transfer.id);
        this.activeTransfers.delete(transfer.id);
    }

    /**
     * Forzar descarga del archivo
     */
    forceDownload(blob, fileName, transferId) {
        console.log(`[P2P] 🚀 Iniciando descarga directa: ${fileName}`);
        
        const downloadUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = fileName;
        a.style.display = 'none';
        
        document.body.appendChild(a);
        a.click();
        
        setTimeout(() => {
            if (a.parentNode) document.body.removeChild(a);
            URL.revokeObjectURL(downloadUrl);
            console.log('[P2P] 🧹 URL de descarga limpiada');
        }, 1000);
        
        // Finalizar visualmente
        this.hideProgress(transferId);
        
        // Limpiar memoria
        this.cleanupTransfer(transferId);
    }
    
    /**
     * Liberar recursos inmediatamente
     */
    cleanupTransfer(transferId) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        
        if (transfer.chunksArray) {
            transfer.chunksArray = []; // Liberar RAM
        }
        if (transfer.dataChannel) {
            transfer.dataChannel.close();
        }
        if (transfer.peerConnection) {
            transfer.peerConnection.close();
        }
        this.stopPerformanceMonitor(transferId);
        this.activeTransfers.delete(transferId);
        console.log('[P2P] ✅ Limpieza de recursos completada');
    }

    /**
     * Notifica que estamos listos para recibir chunks (Semáforo)
     */
    sendReadyToReceive(transfer) {
        this.sendSignalingMessage(transfer.senderId, 'transfer.ready_to_receive', {
            transfer_id: transfer.id,
            receiver_peer_id: this.myPeerId
        });
    }

    /**
     * Configurar conexión de receptor (WebRTC / Data Channels)
     */
    async setupReceiverConnection(transfer) {
        try {
            console.log('[P2P] 🛠️ Preparando RTC para receptor:', transfer.senderPeerId);
            const peerConnection = await this.createPeerConnection(transfer.senderPeerId);
            transfer.peerConnection = peerConnection;

            // Cuando recibamos data channel entrante (Mover ANTES de setRemoteDescription para evitar race conditions)
            peerConnection.ondatachannel = (event) => {
                console.log('[P2P] 📥 Data channel entrante establecido');
                const dataChannel = event.channel;
                dataChannel.binaryType = 'arraybuffer'; // 🔑 FUERZA ARRAYBUFFER
                transfer.dataChannel = dataChannel;
                
                dataChannel.onmessage = (e) => {
                    if (e.data instanceof ArrayBuffer) {
                        // 🔑 PROTOCOLO ROBUSTO: Leer índice (primeros 4 bytes)
                        const view = new DataView(e.data);
                        const chunkIndex = view.getUint32(0);
                        const chunkData = e.data.slice(4);
                        
                        this.receiveChunk({
                            transfer_id: transfer.transferId,
                            chunk_index: chunkIndex,
                            data: chunkData,
                            from_data_channel: true
                        });
                    } else {
                        console.warn('[P2P] ⚠️ Recibido mensaje no binario en data channel');
                    }
                };

                dataChannel.onopen = () => {
                    console.log('[P2P] ✅ DataChannel receptor abierto');
                };
            };
            
            // Si el mensaje inicial (request) ya traía el SDP (offer)
            if (transfer.sdp) {
                console.log('[P2P] 📄 Procesando oferta SDP inicial');
                
                // Saneamiento de SDP para asegurar compatibilidad
                const sdpInit = this.sanitizeSDP(transfer.sdp);
                await peerConnection.setRemoteDescription(new RTCSessionDescription(sdpInit));
                
                // 🔑 FIX: Aplicar candidatos pendientes SOLO DESPUÉS de setRemoteDescription
                await this.applyPendingCandidates(transfer.transferId, peerConnection);

                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                
                // Enviar respuesta vía Reverb
                this.sendSignalingMessage(transfer.senderId, 'answer', {
                    transfer_id: transfer.transferId,
                    sdp: { type: answer.type, sdp: answer.sdp },
                    target_peer_id: transfer.senderPeerId
                });
            }
        } catch (err) {
            console.error('[P2P] Error preparando conexión receptora:', err);
            // Fallback a signaling ocurrirá automáticamente
        }
    }

    async startTransfer({ file, receiverId }) {
        console.log('[P2P] 📤 Iniciando transferencia:', file.name, file.size, 'bytes');

        const transferId = crypto.randomUUID();

        // 1. Configuración adaptativa INMEDIATA
        const config = this.getAdaptiveConfig(file.size);
        const totalChunks = Math.ceil(file.size / config.chunkSize);

        // 2. Crear conexión WebRTC
        const peerConnection = await this.createPeerConnection(receiverId);
        
        // Guardar referencia activa local
        const transferObj = {
            id: transferId,
            transferId: transferId,
            file: file,
            receiverId: receiverId,
            role: 'sender',
            sentChunks: 0,
            chunkSize: config.chunkSize,
            totalChunks: totalChunks,
            bufferLimit: config.bufferLimit,
            peerConnection: peerConnection,
            speedHistory: [] // Para suavizado de velocidad
        };
        this.activeTransfers.set(transferId, transferObj);
        
        try {
            // 3. Crear data channel
            const dataChannel = peerConnection.createDataChannel('fileTransfer');
            dataChannel.binaryType = 'arraybuffer';
            transferObj.dataChannel = dataChannel;
            this.setupDataChannel(dataChannel, transferId);

            // 4. Crear oferta SDP
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            // 5. Enviar oferta vía Reverb CON INFO DEL ARCHIVO SINCRONIZADA
            await this.sendSignalingMessage(receiverId, 'transfer.request', {
                transfer_id: transferId,
                sender_id: window.currentUserId,
                sender_peer_id: this.myPeerId,
                sender_name: document.querySelector('.current-user-name')?.textContent?.trim() || 'Tú',
                file_info: {
                    name: file.name,
                    size: file.size,
                    type: this.getFileType(file.type),
                    mime_type: file.type,
                    total_chunks: totalChunks,
                    chunk_size: config.chunkSize // 🔑 CRUCIAL: Sincronizar tamaño de fragmento
                },
                sdp: offer,
            });

            this.showProgress(transferId, 0, file.size, 'Solicitando permiso...');
            this.setUIStatus(transferId, 'connecting');
            
            // 6. Esperar aceptación con timeout de 30s
            // Se hace async fire-and-forget, la subscripcion a 'transfer.accepted' lo gestiona
            this.setupAcceptanceTimeout(transferId, receiverId);
            
            return { success: true, transferId };
        } catch (err) {
            console.error('[P2P] Error iniciando:', err);
            this.hideProgress();
            this.showNotification('Error al iniciar: ' + err.message, 'error');
            this.activeTransfers.delete(transferId);
            throw err;
        }
    }

    /**
     * Timeout de aceptación
     */
    setupAcceptanceTimeout(transferId, receiverId) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        
        transfer.timeout = setTimeout(() => {
            const currentTransfer = this.activeTransfers.get(transferId);
            if (currentTransfer && !currentTransfer.accepted) {
                console.log('[P2P] ⏰ Timeout esperando aceptación');
                this.hideProgress();
                this.showNotification('Timeout: El receptor no respondió', 'error');
                this.cancelTransfer(transferId);
            }
        }, 120000); // 2 minutos para dar margen al diálogo de Windows
    }

    /**
     * Callback cuando el receptor acepta
     */
    async onReceiverAccepted(data) {
        const transfer = this.activeTransfers.get(data.transfer_id);
        if (transfer && transfer.role === 'sender') {
            // Limpiar timeout
            clearTimeout(transfer.timeout);
            transfer.accepted = true;
            transfer.receiverPeerId = data.receiver_peer_id;

            // Actualizar UI de progreso
            this.setUIStatus(data.transfer_id, 'connecting');
            this.updateProgressTitle(data.transfer_id, 'Estableciendo túnel P2P...');

            // 🔑 FIX #2: Solo enviar si el DataChannel YA está abierto.
            if (transfer.dataChannel && transfer.dataChannel.readyState === 'open') {
                console.log('[P2P] ✅ Canal ya abierto. Iniciando envío...');
                this.setUIStatus(data.transfer_id, 'streaming');
                this.sendFileChunks(transfer.file, transfer.dataChannel, data.transfer_id);
            } else {
                console.log('[P2P] ⏳ Esperando canal P2P...');
                this.updateProgressTitle(data.transfer_id, 'Sincronizando canal...');
            }
        }
    }

    /**
     * Callback cuando se completó enviarlo TODO (o el receptor notifica que lo recibió)
     */
    onTransferComplete(data) {
        // Receptor nos dice que recibió todo
        const transfer = this.activeTransfers.get(data.transfer_id);
        if (transfer && transfer.role === 'sender') {
            this.showNotification('Archivo enviado con éxito', 'success');
            
            this.setUIStatus(data.transfer_id, 'success');
            this.updateProgressTitle(data.transfer_id, '¡Archivo enviado con éxito!');
            
            /*
            // Auto-ocultar después de 5s para que vea el mensaje
            setTimeout(() => {
                const container = document.getElementById('p2p-progress-container');
                if (container && !container.classList.contains('hidden')) {
                    container.classList.add('hidden');
                }
            }, 5000);
            */
            
            this.activeTransfers.delete(data.transfer_id);
        }
    }

    /**
     * Crear conexión WebRTC
     */
    async createPeerConnection(peerId) {
        const peerConnection = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
            ]
        });

        // Guardar conexión
        this.connections.set(peerId, peerConnection);

        // 🔑 FIX #4: Manejar ICE candidates con transfer_id correcto
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                console.log('[P2P] 📤 ICE candidate generado');
                // Encontrar transferencia activa asociada a este peer para incluir transfer_id
                let destUserId = peerId;
                let transferId = null;
                this.activeTransfers.forEach(t => {
                    if (t.peerConnection === peerConnection) {
                        destUserId = t.receiverId || t.senderId || peerId;
                        transferId = t.transferId || t.id;
                    }
                });
                this.sendSignalingMessage(destUserId, 'candidate', {
                    candidate: event.candidate,
                    transfer_id: transferId,
                    dest_peer_id: peerId
                });
            }
        };

        peerConnection.onconnectionstatechange = () => {
            console.log('[P2P] 🔗 Estado de conexión:', peerConnection.connectionState);
        };

        return peerConnection;
    }

    /**
     * Configurar data channel
     */
    setupDataChannel(dataChannel, transferId) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        
        transfer.dataChannelReady = false;
        
        dataChannel.onopen = () => {
            console.log('[P2P] ✅✅✅ DATACHANNEL ABIERTO (TransferID:', transferId, ')');
            transfer.dataChannelReady = true;

            // 🔑 REGLA DE ORO: No enviamos datos hasta tener el permiso 'ready_to_receive'
            if (transfer.isReadyToReceive && transfer.role === 'sender' && transfer.file) {
                console.log('[P2P] 🚀 Semáforo en verde. Iniciando envío...');
                this.setUIStatus(transferId, 'streaming');
                this.sendFileChunks(transfer.file, dataChannel, transferId);
            }
        };

        dataChannel.onclose = () => {
            console.log('[P2P] 🔚 Data channel cerrado');
            transfer.dataChannelReady = false;
        };

        dataChannel.onerror = (error) => {
            console.error('[P2P] ❌ Error en DataChannel:', error);
            transfer.dataChannelReady = false;
            this.showNotification('Error en canal de datos P2P', 'error');
        };

        dataChannel.onmessage = (event) => {
            this.receiveChunk({
                transfer_id: transferId,
                data: event.data,
                chunk_index: transfer.receivedCount,
                from_data_channel: true
            });
        };

        // Buffer: notificar cuando hay espacio disponible
        dataChannel.bufferedAmountLowThreshold = 1024 * 1024; // 1MB
    }

    /**
     * Enviar chunks de archivo
     * 
     * ⚠️ DOCUMENTACIÓN AWS t3.micro & WebRTC:
     * - El tamaño de fragmento (chunkSize) se ha capado a 16 KB estrictos.
     * - Si se excede el buffer (burst de red superior a la capacidad del t3.micro o ancho de banda local), 
     *   el backpressure pausará dinámicamente el envío.
     * - Errores de OOM (Out of Memory) en navegadores serán capturados en el bloque catch.
     */
    async sendFileChunks(file, dataChannel, transferId) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;

        // Configuración adaptativa
        const config = transfer.chunkSize ? transfer : this.getAdaptiveConfig(file.size);
        const chunkSize = config.chunkSize;
        const bufferLimit = config.bufferLimit;

        transfer.startTime = Date.now();
        transfer.lastProgressTime = Date.now();
        transfer.lastSentCount = 0;
        this.startPerformanceMonitor(transferId);
        
        try {
            this.setUIStatus(transferId, 'streaming');
            console.log(`[P2P] 📤 Enviando (Modo: ${chunkSize/1024}KB chunks). Buffer Limit: ${bufferLimit/1024/1024}MB`);
            
            let offset = 0;
            let chunkIndex = 0;
            
            while (offset < file.size && !transfer.cancelled) {
                // 🛑 COMPROBACIÓN DE ESTADO
                if (dataChannel.readyState !== 'open') throw new Error('DataChannel closed');

                // 🛑 BACKPRESSURE ADAPTATIVO
                if (dataChannel.bufferedAmount > (config.backpressure?.pause || bufferLimit)) {
                    await new Promise(r => {
                        const check = () => {
                            const resumeTarget = config.backpressure?.resume || (bufferLimit / 2);
                            if (dataChannel.bufferedAmount <= resumeTarget) r();
                            else setTimeout(check, 5); // Más agresivo (5ms) reanudación rápida
                        };
                        check();
                    });
                }

                const chunk = file.slice(offset, offset + chunkSize);
                const arrayBuffer = await chunk.arrayBuffer();
                
                // 🔑 PROTOCOLO ROBUSTO: Añadir índice al inicio del chunk (4 bytes)
                const packet = new Uint8Array(arrayBuffer.byteLength + 4);
                const view = new DataView(packet.buffer);
                view.setUint32(0, chunkIndex);
                packet.set(new Uint8Array(arrayBuffer), 4);
                
                dataChannel.send(packet.buffer);
                
                offset += chunkSize;
                chunkIndex++;
                transfer.sentChunks = chunkIndex; // 🔑 Actualizar para el monitor de rendimiento
                
                const progress = (offset / file.size) * 100;
                this.updateProgress(transfer.id, progress, file.size);
            }

            // Esperar a que el buffer se vacíe antes de notificar completado
            while (dataChannel.readyState === 'open' && dataChannel.bufferedAmount > 0) {
                await new Promise(r => setTimeout(r, 100));
            }

            if (!transfer.cancelled && dataChannel.readyState === 'open') {
                this.sendSignalingMessage(
                    transfer.receiverId,
                    'transfer.complete',
                    {
                        transfer_id: transferId,
                        total_chunks: transfer.totalChunks,
                        target_peer_id: transfer.receiverPeerId
                    }
                );

                console.log('[P2P] ✅ Notificación de completado enviada.');
            }
        } catch (err) {
            console.error('[P2P] ❌ Error enviando chunks:', err);
            if (!transfer.cancelled) {
                this.showNotification(`Error de red o buffer saturado: ${err.message}`, 'error');
                this.cancelTransfer(transferId);
            }
        }
    }

    /**
     * Recibir chunk y procesar ensamblaje progresivo
     */
    async receiveChunk(data) {
        let transferId;
        try {
            // En DataChannel llega un ArrayBuffer directo. En Signaling llega `{ transfer_id, chunk_index, ... }`
            let chunkIndex, chunkData;
            
            if (data.from_data_channel) {
                transferId = data.transfer_id;
                chunkIndex = data.chunk_index;
                chunkData = data.data;
            } else {
                const payload = data.data || data;
                transferId = payload.transfer_id;
                chunkIndex = payload.chunk_index;
                chunkData = payload.data;
            }
            
            const transfer = this.activeTransfers.get(transferId);
            if (!transfer || transfer.role !== 'receiver') {
                console.warn('[P2P] ⚠️ Chunk recibido pero transferencia no encontrada o no es receiver');
                return;
            }
            
            // 🚀 NUEVO: Activar estado 'streaming' en el primer chunk si no estaba ya
            if (transfer.receivedCount === 0) {
                console.log('[P2P] 📥 Primer chunk recibido. Iniciando stream...');
                this.setUIStatus(transferId, 'streaming');
                this.startPerformanceMonitor(transferId);
            }

            // ✅ OPTIMIZACIÓN: Guardar chunk de forma no bloqueante
            this.saveChunkNonBlocking(transfer, chunkIndex, chunkData);
            transfer.receivedCount++;
            
            // ✅ OPTIMIZACIÓN: Actualizar progreso cada 10 chunks para reducir reflows
            if (transfer.receivedCount % 10 === 0 || transfer.receivedCount >= transfer.totalChunks) {
                const progress = (transfer.receivedCount / transfer.totalChunks) * 100;
                this.updateProgress(transferId, progress, transfer.fileInfo.size);
            }
            
            // 🔑 SINCRONIZACIÓN ROBUSTA: Si ya tenemos todo y llegó el aviso de 'complete'
            if (transfer.receivedCount >= transfer.totalChunks) {
                console.log('[P2P] ✅ Todos los chunks recibidos localmente');
                if (transfer.completedBySender) {
                    // Pequeño delay para asegurar que todos los chunks se guardaron
                    setTimeout(() => this.assembleFile(transfer), 100);
                }
            }
        } catch (err) {
            console.error('[P2P] ❌ Fallo crítico al escribir o recibir buffer:', err);
            if (typeof this.showNotification === 'function') {
                this.showNotification('Error guardando en memoria/disco. Buffer saturado.', 'error');
            }
            if (transferId && this.activeTransfers.has(transferId)) {
                this.cancelTransfer(transferId);
            }
        }
    }

    /**
     * Guardar chunk de forma no bloqueante
     */
    saveChunkNonBlocking(transfer, index, data) {
        if (transfer.fileStream) {
            // 🚀 STREAM DIRECTO A DISCO (Cero RAM OOM Proof)
            transfer.fileStream.write(data).catch(err => {
                console.error('[P2P] Error escribiendo a disco:', err);
            });
        } else {
            // Fallback original para Safari o Mobile sin API
            const blob = new Blob([data]);
            if (transfer.useIndexedDB) {
                // Guardar en IndexedDB de forma asíncrona sin await
                this.saveChunkToIndexedDB(transfer.id, index, blob).catch(err => {
                    console.error('[P2P] Error guardando en IndexedDB:', err);
                });
            } else {
                // Guardar en array (más rápido)
                transfer.chunksArray[index] = blob;
            }
        }
    }

    /**
     * Guardar chunk de forma eficiente (Disco o RAM) - DEPRECATED, usar saveChunkNonBlocking
     */
    async saveChunk(transfer, index, data) {
        if (transfer.fileStream) {
            // 🚀 STREAM DIRECTO A DISCO (Cero RAM OOM Proof)
            await transfer.fileStream.write(data);
        } else {
            // Fallback original para Safari o Mobile sin API
            const blob = new Blob([data]);
            if (transfer.useIndexedDB) {
                await this.saveChunkToIndexedDB(transfer.id, index, blob);
            } else {
                transfer.chunksArray[index] = blob;
            }
        }
    }

    /**
     * Ensamblado y finalización de archivo recibido
     */
    async assembleFile(transfer) {
        if (transfer.isAssembling) return;
        transfer.isAssembling = true;

        console.log('[P2P] 📦 Finalizando recepción del archivo...');
        this.updateProgressTitle(transfer.id, 'Finalizando descarga...');
        
        try {
            if (transfer.role === 'receiver') {
                this.sendSignalingMessage(transfer.senderId, 'transfer.complete', {
                    transfer_id: transfer.id
                });
            }

            if (transfer.fileStream) {
                // 🚀 CIERRE NATIVO DEL FLUJO. El O.S consolida el archivo sin tocar la RAM.
                await transfer.fileStream.close();
                console.log(`[P2P] ✅ Archivo 100% consolidado en disco por el Sistema Operativo.`);
                
                // Finalizamos UI localmente porque ya está descargado en ubicación física
                const notification = document.getElementById('p2p-complete-notification');
                if (notification) {
                    document.getElementById('p2p-complete-title').textContent = '✅ Guardado Exitosamente';
                    document.getElementById('p2p-complete-message').textContent = transfer.fileInfo.name;
                    notification.classList.remove('hidden');
                    setTimeout(() => notification.classList.add('hidden'), 5000);
                }
                
                if (typeof this.hideProgress === 'function') this.hideProgress(transfer.id);
                this.cleanupTransfer(transfer.id);

            } else if (transfer.useIndexedDB) {
                // Descarga mágica conectando al Service Worker
                this.triggerServiceWorkerDownload(transfer);
            } else {
                // ✅ OPTIMIZACIÓN: Ensamblar en chunks para evitar bloqueo del UI
                console.log(`[P2P] 🔧 Ensamblando ${transfer.chunksArray.length} fragmentos...`);
                
                // Usar requestIdleCallback o setTimeout para no bloquear el UI
                const assembleInBackground = async () => {
                    // Crear blob de forma más eficiente
                    const completeBlob = new Blob(transfer.chunksArray, {
                        type: transfer.fileInfo.mime_type || transfer.fileInfo.type || 'application/octet-stream'
                    });
                    
                    console.log(`[P2P] ✅ Archivo ensamblado: ${(completeBlob.size / 1024 / 1024).toFixed(2)} MB`);
                    
                    // Liberar memoria de chunks inmediatamente
                    transfer.chunksArray = null;
                    
                    // Iniciar descarga
                    this.forceDownload(completeBlob, transfer.fileInfo.name, transfer.id);
                };
                
                // Ejecutar en el próximo frame para no bloquear
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(assembleInBackground, { timeout: 1000 });
                } else {
                    setTimeout(assembleInBackground, 0);
                }
            }

        } catch (error) {
            console.error('[P2P] ❌ Error finalizando:', error);
            this.setUIStatus(transfer.id, 'error');
            this.updateProgressTitle(transfer.id, 'Error al procesar descarga local');
            this.stopPerformanceMonitor(transfer.id);
        }
    }

    /**
     * Mostrar notificación de completado
     */
    showCompleteNotification(file, fileInfo) {
        const notification = document.getElementById('p2p-complete-notification');
        if (!notification) return;
        
        document.getElementById('p2p-complete-title').textContent = '✅ Archivo recibido';
        document.getElementById('p2p-complete-message').textContent = `${fileInfo.name} (${this.formatFileSize(fileInfo.size)})`;
        
        // Guardar referencia al archivo para descargar
        this.completedFile = { file, name: fileInfo.name };
        
        notification.classList.remove('hidden');
        
        // 🔑 AUTO-DOWNLOAD (Opcional, pero mejora la UX si falla el click)
        const downloadBtn = document.getElementById('p2p-complete-download');
        if (downloadBtn) {
            downloadBtn.onclick = () => this.downloadCompletedFile();
        }
        
        // Auto-ocultar después de 10 segundos
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 10000);
    }

    /**
     * Descargar archivo completado
     */
    downloadCompletedFile() {
        if (!this.completedFile) return;
        
        const { file, name } = this.completedFile;
        const url = URL.createObjectURL(file);
        const a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        this.showNotification('Descarga iniciada', 'success');
        document.getElementById('p2p-complete-notification')?.classList.add('hidden');
    }

    /**
     * Mostrar/actualizar barra de progreso
     */
    showProgress(transferId, percent, totalBytes, title = 'Procesando...') {
        // ✅ El progreso SE MUESTRA SÓLO EN EL SIDEBAR "Files"
        // El #p2p-progress-container permanece oculto (inline en chat = eliminado)
        if (this.activeTransfers.has(transferId)) {
            this.updateProgress(transferId, percent, totalBytes);
            
            // ✅ Agregar card al sidebar si no existe aún
            if (window.sidebarTransfers && !window.sidebarTransfers.hasAddedId(transferId)) {
                const tr = this.activeTransfers.get(transferId);
                const fileInfo = tr.fileInfo || tr.file;
                window.sidebarTransfers.add(transferId, {
                    name: fileInfo.name,
                    size: totalBytes,
                    type: fileInfo.type || fileInfo.mime_type
                }, tr.role);
            }
        }
    }

    /**
     * Actualizar solo el porcentaje
     */
    updateProgress(transferId, percent, totalBytes) {
        // EL PROGRESO FLOTANTE ESTÁ DESACTIVADO
        
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        const percentRounded = Math.round(percent);
        
        if (percentRounded >= 100) {
            if (transfer.perfInterval) {
                clearInterval(transfer.perfInterval);
                transfer.perfInterval = null;
            }
            if (!transfer.uiFinalized) {
                transfer.uiFinalized = true;
                this.setUIStatus(transferId, 'success');
            }
        }

        // 🚀 NUEVO: Suavizado de velocidad y tiempo (Media Móvil)
        const now = Date.now();
        if (!transfer.lastProgressTime) transfer.lastProgressTime = now;
        
        const timeDiff = (now - transfer.lastProgressTime) / 1000; // segundos desde el último update UI
        
        if (timeDiff >= 0.5 || percentRounded === 100) { // Actualizar stats cada 0.5s para suavizar
            const bytesNow = totalBytes * (percent / 100);
            const bytesDiff = bytesNow - (transfer.lastBytesProcess || 0);
            
            const instantSpeed = bytesDiff / timeDiff / 1024; // KB/s
            
            if (!transfer.speedHistory) transfer.speedHistory = [];
            transfer.speedHistory.push(instantSpeed);
            if (transfer.speedHistory.length > 10) transfer.speedHistory.shift();
            
            const avgSpeed = transfer.speedHistory.reduce((a, b) => a + b, 0) / transfer.speedHistory.length;
            
            transfer.lastProgressTime = now;
            transfer.lastBytesProcess = bytesNow;

            // Mostrar velocidad en el sidebar (ya no en el flotante)
            let speedText = '';
            if (avgSpeed > 1024) {
                speedText = `${(avgSpeed / 1024).toFixed(2)} MB/s`;
            } else {
                speedText = `${avgSpeed.toFixed(1)} KB/s`;
            }
            
            // Mostrar tiempo restante
            let timeText = '';
            if (percent > 0 && percent < 100 && avgSpeed > 0) {
                const bytesRemaining = totalBytes - bytesNow;
                const remainingSecs = bytesRemaining / (avgSpeed * 1024);
                
                timeText = this.formatTimeRemaining(remainingSecs);
            } else if (percentRounded === 100) {
                timeText = '0s restante';
            }
            
            // ✅ Actualizar en sidebar
            if (window.sidebarTransfers) {
                window.sidebarTransfers.update(transferId, percent, speedText, timeText);
            }
        }
    }

    /**
     * Formatear tiempo de forma amigable (ej. 1m 20s)
     */
    formatTimeRemaining(seconds) {
        if (!seconds || seconds < 1) return '0s restante';
        const mins = Math.floor(seconds / 60);
        const secs = Math.round(seconds % 60);
        
        if (mins > 0) {
            return `${mins}m ${secs}s restante`;
        }
        return `${secs}s restante`;
    }

    /**
     * Motor de Monitoreo de Rendimiento (Consola)
     */
    startPerformanceMonitor(transferId) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer || transfer.perfInterval) return;

        console.log(`[P2P-PERF] 📊 Iniciando monitoreo para: ${transferId}`);
        
        transfer.perfInterval = setInterval(() => {
            const t = this.activeTransfers.get(transferId);
            if (!t) {
                this.stopPerformanceMonitor(transferId);
                return;
            }

            const elapsed = (Date.now() - t.startTime) / 1000;
            const bytesSent = t.role === 'sender' ? (t.sentChunks || 0) * (t.chunkSize || 32768) : (t.receivedCount || 0) * (t.chunkSize || 32768);
            const speedMbps = (bytesSent * 8) / (elapsed * 1024 * 1024);
            const buffer = t.dataChannel ? t.dataChannel.bufferedAmount : 0;
            const bufferPercent = t.bufferLimit ? (buffer / t.bufferLimit) * 100 : 0;

            console.log(
                `%c[P2P-STATS] %cVelocidad: ${speedMbps.toFixed(2)} Mbps | %cBuffer: ${(buffer/1024).toFixed(0)}KB (${bufferPercent.toFixed(1)}%) | %cProgreso: ${((bytesSent / (t.file?.size || t.fileInfo?.size)) * 100).toFixed(1)}%`,
                'color: #3b82f6; font-weight: bold;',
                'color: #10b981;',
                bufferPercent > 80 ? 'color: #ef4444;' : 'color: #f59e0b;',
                'color: #ffffff;'
            );
        }, 2000); // Cada 2 segundos para máxima eficiencia
    }

    stopPerformanceMonitor(transferId) {
        const transfer = this.activeTransfers.get(transferId);
        if (transfer && transfer.perfInterval) {
            clearInterval(transfer.perfInterval);
            transfer.perfInterval = null;
            console.log(`[P2P-PERF] 📈 Monitoreo finalizado para: ${transferId}`);
        }
    }

    /**
     * Actualizar título del progreso (Omitido - Flotante desactivado)
     */
    updateProgressTitle(transferId, title) {
        // Desactivado
    }

    /**
     * Ocultar barra de progreso
     */
    hideProgress(transferId = null) {
        // HIDE FLOATING PROGRESS (AS REQUESTED)
        // document.getElementById('p2p-progress-container')?.classList.add('hidden');
        if (transferId && window.sidebarTransfers) {
            window.sidebarTransfers.complete(transferId);
        }
    }

    /**
     * Cancelar transferencia
     */
    cancelTransfer(transferId = null) {
        if (transferId) {
            const t = this.activeTransfers.get(transferId);
            if(t) {
                if (t.timeout) clearTimeout(t.timeout);
                if (t.dataChannel) t.dataChannel.close();
                if (t.peerConnection) t.peerConnection.close();
                this.stopPerformanceMonitor(transferId);
                t.cancelled = true;
                this.activeTransfers.delete(transferId);
                
                if (window.sidebarTransfers) {
                    window.sidebarTransfers.remove(transferId);
                }
            }
        } else {
            // Limpiar transferencias activas
            this.activeTransfers.forEach((transfer, id) => {
                if (transfer.timeout) clearTimeout(transfer.timeout);
                if (transfer.dataChannel) transfer.dataChannel.close();
                if (transfer.peerConnection) transfer.peerConnection.close();
                transfer.cancelled = true;
                if (window.sidebarTransfers) {
                    window.sidebarTransfers.remove(id);
                }
            });
            this.activeTransfers.clear();
        }
        
        this.hideProgress();
        this.showNotification('Transferencia cancelada', 'info');
    }

    /**
     * Mostrar notificación genérica
     */
    showNotification(message, type = 'info') {
        console.log(`[P2P] 📢 ${type.toUpperCase()}: ${message}`);
        // Se podría agregar un div toast si se desea
    }

    /**
     * Enviar mensaje de signaling vía API
     */
    async sendSignalingMessage(toUserId, type, data) {
        if (!toUserId) return;
        try {
            const response = await fetch('/api/p2p/signal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({
                    from: this.myPeerId,
                    to: String(toUserId),
                    type: type,
                    data: {
                        ...data,
                        target_peer_id: data.target_peer_id || null // Para filtrar en el receptor
                    },
                })
            });
            
            if (!response.ok) {
                console.warn('[P2P] ⚠️ Error enviando signaling:', response.status);
            }
        } catch (error) {
            console.error('[P2P] ❌ Error en signaling:', error);
        }
    }

    /**
     * Manejar mensajes de signaling
     */
    async handleSignalingMessage(type, data) {
        const payload = data.data || data;
        const transferId = payload.transfer_id;
        
        // Filtrar por Peer ID si está presente para no procesar mensajes de otras pestañas
        if (payload.target_peer_id && payload.target_peer_id !== this.myPeerId) return;

        console.log(`[P2P] 📨 Signal [${type}] recibido. TransferID:`, transferId);

        try {
            // Intentar obtener la transferencia por ID directo (más fiable)
            let transfer = transferId ? this.activeTransfers.get(transferId) : null;

            if (type === 'offer') {
                // ... manejar si es necesario ...
            } else if (type === 'answer') {
                // Si no hay id, buscar por receptor (fallback para viejas versiones)
                if (!transfer) {
                    transfer = Array.from(this.activeTransfers.values()).find(t => t.receiverId == window.targetUserId);
                }

                if (transfer && transfer.peerConnection) {
                    console.log('[P2P] ✅ Procesando respuesta SDP (answer)');
                    const sdpInit = this.sanitizeSDP(payload.sdp);
                    await transfer.peerConnection.setRemoteDescription(new RTCSessionDescription(sdpInit));

                    // 🔑 FIX: Aplicar candidatos pendientes del receptor ahora que tenemos RemoteDescription
                    await this.applyPendingCandidates(transferId, transfer.peerConnection);
                }
            } else if (type === 'candidate') {
                // Búsqueda exhaustiva si falló el directo
                if (!transfer) {
                    transfer = Array.from(this.activeTransfers.values()).find(t => 
                        t.id === transferId || t.transferId === transferId
                    );
                }

                if (transfer && transfer.peerConnection && transfer.peerConnection.remoteDescription) {
                    // console.log('[P2P] ❄️ Procesando ICE candidate');
                    await transfer.peerConnection.addIceCandidate(new RTCIceCandidate(payload.candidate));
                } else {
                    // 🔑 Guardar candidato como pendiente si la transferencia aún no existe localmente 
                    // O si aún no tenemos remoteDescription (offer/answer)
                    if (transferId) {
                        if (!this.pendingCandidates.has(transferId)) {
                            this.pendingCandidates.set(transferId, []);
                        }
                        this.pendingCandidates.get(transferId).push(payload.candidate);
                        console.log('[P2P] ⏳ Candidate guardado como pendiente (esperando RemoteDescription) para ID:', transferId);
                    } else {
                        console.warn('[P2P] ⚠️ Candidate recibido sin TransferID');
                    }
                }
            }
        } catch(e) {
            console.error('[P2P] Handler error:', e);
        }
    }

    /**
     * El receptor ha aceptado la transferencia (Fase 1: Handshake)
     */
    onReceiverAccepted(data) {
        const transferId = data.transfer_id;
        const transfer = this.activeTransfers.get(transferId);
        
        if (transfer) {
            // 🛑 LIGAR TIMEOUT: El receptor ya respondió, detenemos el reloj de cancelación inicial.
            if (transfer.timeout) {
                clearTimeout(transfer.timeout);
                console.log('[P2P] ⏰ Timeout de aceptación limpiado.');
            }

            transfer.accepted = true;
            console.log('[P2P] El receptor ha aceptado la transferencia. Esperando permiso de disco...');
            
            // Actualizar vista
            this.updateProgressTitle(transferId, 'Estableciendo conexión...');
        }
    }

    /**
     * El receptor está listo para el stream (Fase 2: Semáforo)
     */
    onReadyToReceive(data) {
        const transferId = data.transfer_id;
        const transfer = this.activeTransfers.get(transferId);
        
        if (transfer) {
            console.log('[P2P] ✅ Receptor listo. Iniciando...');
            transfer.isReadyToReceive = true;
            this.setUIStatus(transferId, 'streaming');
            
            if (transfer.dataChannelReady && transfer.dataChannel) {
                this.sendFileChunks(transfer.file, transfer.dataChannel, transferId);
            }
        }
    }

    /**
     * Motor Adaptativo: Retorna configuración según peso del archivo
     */
    getAdaptiveConfig(fileSize) {
        // Optimización balanceada para máxima velocidad y estabilidad
        if (fileSize < 10 * 1024 * 1024) { // < 10MB
            return { 
                chunkSize: 32768, 
                bufferLimit: 4194304, // 4MB
                backpressure: { pause: 4194304, resume: 2097152 } // Pausa 4MB, Reanuda 2MB
            };
        } else if (fileSize < 100 * 1024 * 1024) { // < 100MB
            return { 
                chunkSize: 32768, 
                bufferLimit: 8388608, // 8MB
                backpressure: { pause: 8388608, resume: 4194304 } // Pausa 8MB, Reanuda 4MB
            };
        } else { // > 100MB
            // 12MB buffer para saturar red sin colapsar Chromium (limite 16MB)
            return { 
                chunkSize: 32768, // 32KB
                bufferLimit: 12582912, // 12MB
                backpressure: { 
                    pause: 12582912, // Pausar a 12MB
                    resume: 6291456  // Reanudar a 6MB
                }
            };
        }
    }

    /**
     * Gestión de Estados de UI
     */
    setUIStatus(transferId, state) {
        // ✅ El estado visual se muestra SÓLO en el sidebar
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;

        if (state === 'success') {
            // Marcar como completada en sidebar
            if (window.sidebarTransfers) {
                window.sidebarTransfers.complete(transferId);
            }
        } else if (state === 'error') {
            // Remover del sidebar en error
            if (window.sidebarTransfers) {
                window.sidebarTransfers.remove(transferId);
            }
            showToast('Error P2P', 'Error en la conexión de transferencia', 'error');
        }
    }

    /**
     * Utilidades
     */
    
    /**
     * Aplicar candidatos que llegaron antes de tener RemoteDescription set
     */
    async applyPendingCandidates(transferId, peerConnection) {
        if (!transferId || !peerConnection || !this.pendingCandidates.has(transferId)) return;
        
        const candidates = this.pendingCandidates.get(transferId);
        console.log(`[P2P] ❄️ Aplicando ${candidates.length} candidatos pendientes para:`, transferId);
        
        for (const candidateData of candidates) {
            try {
                if (peerConnection.remoteDescription) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidateData));
                }
            } catch (e) {
                console.error('[P2P] Error aplicando candidato pendiente:', e);
            }
        }
        
        this.pendingCandidates.delete(transferId);
    }
    /**
     * Sanear cadena SDP para compatibilidad entre navegadores
     * Asegura saltos de línea \r\n y estructura correcta
     */
    sanitizeSDP(sdpData) {
        if (!sdpData || !sdpData.sdp) return sdpData;
        
        let sdp = sdpData.sdp;
        
        // Normalizar finales de línea a \r\n
        sdp = sdp.split(/\r?\n/).filter(line => line.trim() !== '').join('\r\n') + '\r\n';
        
        return {
            type: sdpData.type,
            sdp: sdp
        };
    }

    arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    base64ToBlob(base64, mimeType) {
        const binary = window.atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new Blob([bytes], { type: mimeType });
    }

    getFileType(mimeType) {
        if (mimeType?.startsWith('image/')) return 'image';
        if (mimeType?.startsWith('video/')) return 'video';
        if (mimeType?.startsWith('audio/')) return 'audio';
        if (mimeType === 'application/pdf') return 'pdf';
        return 'document';
    }

    getFileTypeLabel(mimeType) {
        const types = {
            'image': 'Imagen',
            'video': 'Video',
            'audio': 'Audio',
            'pdf': 'PDF',
            'document': 'Documento',
        };
        return types[this.getFileType(mimeType)] || 'Archivo';
    }

    getFileIcon(mimeType) {
        const icons = {
            'image': '🖼️',
            'video': '🎬',
            'audio': '🎵',
            'pdf': '📕',
            'document': '📄',
        };
        return icons[this.getFileType(mimeType)] || '📎';
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }
        return size.toFixed(unit === 0 ? 0 : 2) + ' ' + units[unit];
    }
}

window.P2PFileTransfer = P2PFileTransfer;
