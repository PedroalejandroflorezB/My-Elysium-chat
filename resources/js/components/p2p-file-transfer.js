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
        this.chunkSize = 65536; // 64 KB (Punto dulce recomendado)
        this.maxRetries = 3;
        this.connections = new Map();
        this.pendingFile = null; 
        this.processedSignals = new Set(); // 🔑 Evitar duplicados (Echo + Polling)
        
        // Generar ID único para este peer
        this.myPeerId = this.generatePeerId();
        console.log('[P2P] ✅ Peer ID generado:', this.myPeerId);
        window.myPeerId = this.myPeerId;

        // 🔑 Obtener UserID de meta tags si no está global
        if (!window.currentUserId) {
            window.currentUserId = document.querySelector('meta[name="user-id"]')?.content;
        }

        this.signalingMode = window.realtimeDisabled ? 'http-polling' : 'websocket';
        
        // Suscribirse a signaling
        this.subscribeToSignaling();
        
        // Inicializar UI
        this.initUI();

        // Configuración adaptativa por red (se actualiza en detectNetworkCondition)
        const network = this.detectNetworkCondition();
        this.baseChunkSize = network.chunkSize;
        this.maxParallelStreams = network.streams;
        this.bufferLimit = 8 * 1024 * 1024; // 8MB base
        console.log(`[P2P] 🌐 Red detectada: ${network.streams} streams, ${network.chunkSize / 1024}KB chunks`);
    }

    /**
     * Inicializar event listeners
     */
    initUI() {
        // [ANDROID FIX]: Usar delegación de eventos y soportar touch/click
        const handleP2PClick = (e) => {
            if (e.type === 'touchstart') window._isTouchP2P = true;
            if (e.type === 'click' && window._isTouchP2P) return;
            
            const target = e.target.closest('button, .p2p-modal-overlay, .p2p-notification-close');
            if (!target) return;

            // Cerrar modales clickeando afuera (en el overlay)
            if (target.classList.contains('p2p-modal-overlay')) {
                const modal = target.closest('.p2p-modal');
                if (modal) modal.classList.add('hidden');
                return;
            }

            // BOTONES DEL EMISOR (Preview)
            if (target.id === 'p2p-preview-cancel' || target.id === 'p2p-preview-close') {
                e.preventDefault();
                document.getElementById('p2p-preview-modal')?.classList.add('hidden');
                this.pendingFiles = [];
            } 
            else if (target.id === 'p2p-preview-send') {
                e.preventDefault();
                if (this.pendingFiles && this.pendingFiles.length > 0) {
                    document.getElementById('p2p-preview-modal')?.classList.add('hidden');
                    this.startMultipleTransfers(this.pendingFiles);
                    this.pendingFiles = [];
                }
            }

            // BOTONES DEL RECEPTOR (Acceptance)
            else if (target.id === 'p2p-accept-reject') {
                e.preventDefault();
                document.getElementById('p2p-accept-modal')?.classList.add('hidden');
                this.rejectIncomingFile();
            } 
            else if (target.id === 'p2p-accept-accept') {
                e.preventDefault();
                document.getElementById('p2p-accept-modal')?.classList.add('hidden');
                this.acceptIncomingFile();
            }

            // NOTIFICACIÓN COMPLETADO
            else if (target.id === 'p2p-complete-download') {
                e.preventDefault();
                this.downloadCompletedFile();
            } 
            else if (target.classList.contains('p2p-notification-close')) {
                e.preventDefault();
                document.getElementById('p2p-complete-notification')?.classList.add('hidden');
            }
        };

        // Delegación de eventos global
        document.body.addEventListener('click', handleP2PClick);
        document.body.addEventListener('touchstart', handleP2PClick, { passive: true });
        
        console.log('[P2P] 🔌 UI Global listeners vinculados');
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
        // ✅ SIEMPRE ACTIVAR BACKUP (Polling) como red de seguridad
        // Esto garantiza que si Echo falla silenciosamente, el receptor aún vea el modal.
        this.iniciarPollingSignaling();

        if (this.signalingMode !== 'websocket' || !window.Echo) {
            return;
        }

        const userId = window.currentUserId || document.querySelector('meta[name="user-id"]')?.content;
        if (!userId) {
            console.warn('[P2P] ⚠️ No se puede suscribir a signaling: UserID no encontrado');
            return;
        }

        console.log('[P2P] 📡 Suscribiendo a signaling (User Channel):', userId);
        
        // Canal de usuario para este peer
        try {
            window.Echo.private(`user.${userId}`)
                .listen('.p2p.signal', (data) => {
                    console.log('[P2P] 📢 Signal recibido via Echo (.p2p.signal):', data);
                    const envelope = data.signal || data;
                    if (envelope.type) {
                        this.handleSignalingMessage(envelope.type, envelope);
                    }
                })
                .listen('p2p.signal', (data) => {
                    console.log('[P2P] 📢 Signal recibido via Echo (p2p.signal):', data);
                    const envelope = data.signal || data;
                    if (envelope.type) {
                        this.handleSignalingMessage(envelope.type, envelope);
                    }
                })
                .on('subscription_error', (status) => {
                    console.error('[P2P] ❌ Subscription error:', status);
                    window.showToast('Error P2P', 'No se pudo conectar al canal de señales', 'error');
                });
            console.log('[P2P] ✅ Canal de señales configurado correctamente');
        } catch (e) {
            console.error('[P2P] ❌ Error configurando Echo:', e);
        }
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
        
        // 🎯 AUTO-SELECCIONAR TAB FILES y mostrar progreso inmediatamente
        if (typeof window.switchSidebarTab === 'function') {
            window.switchSidebarTab('files');
        }
        
        const transfer = this.pendingIncomingTransfer;
        
        // 🚀 PREVENCIÓN DE "DOBLE ACEPTACIÓN" Y ADVERTENCIA DE NAVEGADOR
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
                    return;
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
        
        this.showProgress(transfer.transferId, 0, transfer.fileInfo.size, this.getStatusLabel('connecting'));
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
        
        // ✅ SIEMPRE usar IndexedDB para no acumular en RAM (fallback si no hay showSaveFilePicker)
        transfer.useIndexedDB = true;
        transfer.dbName = `p2p-${transferId}`;
        await this.initIndexedDB(transferId);
        
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
        if (window.sidebarTransfers) {
            window.sidebarTransfers.complete(transferId);
        }
        
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
            try { transfer.dataChannel.close(); } catch(e){}
        }
        if (transfer.peerConnection) {
            try { transfer.peerConnection.close(); } catch(e){}
        }
        this.abortIncompleteStorage(transfer);
        this.stopPerformanceMonitor(transferId);
        // NO HACEMOS delete(transferId) de this.activeTransfers 
        // para que la vista del sidebar pueda leer tr.uiFinalized = true. 
        // Solo eliminaremos cuando el usuario cierre la card en el sidebar.
        console.log('[P2P] ✅ Limpieza de recursos de red completada');
    }

    async abortIncompleteStorage(transfer) {
        if (!transfer) return;

        if (transfer.fileStream) {
            try {
                if (typeof transfer.fileStream.abort === 'function') {
                    await transfer.fileStream.abort();
                    console.log('[P2P] 🧹 File stream abortado para transferencia incompleta');
                } else if (typeof transfer.fileStream.close === 'function') {
                    await transfer.fileStream.close();
                }
            } catch (err) {
                console.warn('[P2P] ⚠️ Error abortando file stream:', err);
            }
        }

        if (transfer.useIndexedDB) {
            try {
                if (transfer.indexedDB) {
                    transfer.indexedDB.close();
                }
                const request = indexedDB.deleteDatabase(`p2p-${transfer.id}`);
                request.onsuccess = () => console.log('[P2P] 🧹 IndexedDB eliminada tras fallo de transferencia:', transfer.id);
                request.onerror = () => console.warn('[P2P] ⚠️ No se pudo eliminar IndexedDB tras el fallo:', transfer.id);
            } catch (err) {
                console.warn('[P2P] ⚠️ Error limpiando IndexedDB tras fallo:', err);
            }
        }
    }

    handleTransferFailure(transferId, error) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer || transfer.failed || transfer.cancelled) return;

        transfer.failed = true;
        transfer.cancelled = true;
        transfer.error = error;

        console.error('[P2P] ❌ Transferencia fallida:', error);
        this.showNotification(`Transferencia fallida: ${error?.message || 'Error desconocido'}`, 'error');
        this.setUIStatus(transferId, 'error');

        if (transfer.dataChannel) {
            try { transfer.dataChannel.close(); } catch (e) {}
        }
        if (transfer.peerConnection) {
            try { transfer.peerConnection.close(); } catch (e) {}
        }
        if (transfer.timeout) clearTimeout(transfer.timeout);
        this.stopPerformanceMonitor(transferId);
        this.abortIncompleteStorage(transfer);
        this.hideProgress(transferId);

        this.activeTransfers.delete(transferId);
        if (window.sidebarTransfers) {
            window.sidebarTransfers.remove(transferId);
        }
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

        // 1. Configuración adaptativa por red y tamaño
        const config = this.getAdaptiveConfig(file.size);
        const safeChunkSize = this._safeChunkSize(file.size, config.chunkSize);
        const totalChunks = Math.ceil(file.size / safeChunkSize);

        // 2. Crear conexión WebRTC
        const peerConnection = await this.createPeerConnection(receiverId);

        const transferObj = {
            id: transferId,
            transferId: transferId,
            file: file,
            receiverId: receiverId,
            role: 'sender',
            sentChunks: 0,
            chunkSize: safeChunkSize,
            totalChunks: totalChunks,
            bufferLimit: config.bufferLimit,
            backpressure: config.backpressure,
            peerConnection: peerConnection,
            speedHistory: [],
            checksum: 0,
            checksumAlgorithm: 'crc32',
            expectedChecksum: null,
            largeFile: file.size >= 10 * 1024 * 1024 * 1024
        };
        this.activeTransfers.set(transferId, transferObj);

        try {
            // 3. Crear data channel con ordered: true (confiable, integridad garantizada)
            const dataChannel = peerConnection.createDataChannel('fileTransfer', {
                ordered: true  // ✅ Confiable para archivos - no usar false (riesgo corrupción)
            });
            dataChannel.binaryType = 'arraybuffer';
            dataChannel.bufferedAmountLowThreshold = config.backpressure.resume;
            transferObj.dataChannel = dataChannel;
            this.setupDataChannel(dataChannel, transferId);

            // 4. Crear oferta SDP
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            // 5. Enviar oferta con info del archivo (chunk_size sincronizado con receptor)
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
                    chunk_size: config.chunkSize // ✅ Sincronizar con receptor
                },
                sdp: offer,
            });

            this.showProgress(transferId, 0, file.size, this.getStatusLabel('requesting'));
            this.setUIStatus(transferId, 'connecting');
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
            this.updateProgressTitle(data.transfer_id, this.getStatusLabel('tunneling'));

            // 🔑 FIX #2: Solo enviar si el DataChannel YA está abierto.
            if (transfer.dataChannel && transfer.dataChannel.readyState === 'open') {
                console.log('[P2P] ✅ Canal ya abierto. Iniciando envío...');
                this.setUIStatus(data.transfer_id, 'streaming');
                this.sendFileChunks(transfer.file, transfer.dataChannel, data.transfer_id);
            } else {
                console.log('[P2P] ⏳ Esperando canal P2P...');
                this.updateProgressTitle(data.transfer_id, this.getStatusLabel('tunneling'));
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
            this.showNotification('Transferencia completada: el receptor recibió el archivo', 'success');
            
            transfer.uiFinalized = true;
            this.setUIStatus(data.transfer_id, 'success');
            this.updateProgressTitle(data.transfer_id, this.getStatusLabel('completed'));
            
            // Forzar actualización al 100% para el sidebar
            this.updateProgress(data.transfer_id, 100, transfer.file.size || transfer.fileInfo.size, this.getStatusLabel('completed'));
            
            if (window.sidebarTransfers) {
                window.sidebarTransfers.complete(data.transfer_id);
            }
            
            // Limpiar recursos pero no borrar de map para que Sidebar aún lo vea
            this.cleanupTransfer(data.transfer_id);
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
            const transfer = Array.from(this.activeTransfers.values()).find(t => t.peerConnection === peerConnection);
            if (!transfer || transfer.uiFinalized || transfer.cancelled) return;

            if (peerConnection.connectionState === 'disconnected') {
                // Disconnected puede ser temporal. Esperar unos segundos antes de cancelar.
                if (transfer.disconnectTimeout) clearTimeout(transfer.disconnectTimeout);
                transfer.disconnectTimeout = setTimeout(() => {
                    if (peerConnection.connectionState === 'disconnected' && !transfer.uiFinalized && !transfer.cancelled) {
                        this.handleTransferFailure(transfer.transferId || transfer.id, new Error('Conexión WebRTC desconectada de forma persistente'));
                    }
                }, 5000);
                return;
            }

            if (transfer.disconnectTimeout) {
                clearTimeout(transfer.disconnectTimeout);
                transfer.disconnectTimeout = null;
            }

            if (['failed', 'closed'].includes(peerConnection.connectionState)) {
                this.handleTransferFailure(transfer.transferId || transfer.id, new Error('Conexión WebRTC cerrada o fallida: ' + peerConnection.connectionState));
            }
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
            const transferComplete = (transfer.sentChunks >= transfer.totalChunks) || (transfer.receivedCount >= transfer.totalChunks);
            if (!transfer.uiFinalized && !transfer.cancelled && !transferComplete) {
                this.handleTransferFailure(transferId, new Error('El canal de datos se cerró antes de completar la transferencia'));
            }
        };

        dataChannel.onerror = (error) => {
            console.error('[P2P] ❌ Error en DataChannel:', error);
            transfer.dataChannelReady = false;
            this.showNotification('Error en canal de datos P2P', 'error');
            this.handleTransferFailure(transferId, error instanceof Error ? error : new Error('Error en DataChannel'));
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

        const config = transfer.chunkSize ? transfer : this.getAdaptiveConfig(file.size);
        const chunkSize = transfer.chunkSize || config.chunkSize;
        const bufferLimit = config.bufferLimit;
        const pauseAt = config.backpressure?.pause || bufferLimit;
        const resumeAt = config.backpressure?.resume || (bufferLimit / 2);

        transfer.startTime = Date.now();
        transfer.lastProgressTime = Date.now();
        transfer.lastSentCount = 0;
        this.startPerformanceMonitor(transferId);

        try {
            this.setUIStatus(transferId, 'streaming');
            this.updateProgress(transfer.id, transfer.progress || 0, file.size, this.getStatusLabel('streaming'));
            console.log(`[P2P] 📤 Enviando: ${chunkSize / 1024}KB chunks, buffer ${bufferLimit / 1024 / 1024}MB`);

            let offset = 0;
            let chunkIndex = 0;

            while (offset < file.size && !transfer.cancelled) {
                if (dataChannel.readyState !== 'open') throw new Error('DataChannel closed');

                // Backpressure: pausar si buffer lleno, reanudar cuando baje
                if (dataChannel.bufferedAmount > pauseAt) {
                    await new Promise(r => {
                        const check = () => {
                            if (dataChannel.bufferedAmount <= resumeAt) r();
                            else setTimeout(check, 5);
                        };
                        check();
                    });
                }

                const chunk = file.slice(offset, offset + chunkSize);
                const arrayBuffer = await chunk.arrayBuffer();

                // Actualizar checksum incremental en cada chunk
                transfer.checksum = this.updateCRC32(transfer.checksum, new Uint8Array(arrayBuffer));

                // Protocolo: [4 bytes índice] + [datos del chunk]
                const packet = new Uint8Array(arrayBuffer.byteLength + 4);
                const view = new DataView(packet.buffer);
                view.setUint32(0, chunkIndex);
                packet.set(new Uint8Array(arrayBuffer), 4);

                // ✅ Enviar ArrayBuffer directamente (zero-copy en Chrome/Edge modernos)
                let retryCount = 0;
                const maxChunkRetries = transfer.largeFile ? 3 : 2;
                while (retryCount <= maxChunkRetries) {
                    try {
                        dataChannel.send(packet.buffer);
                        break;
                    } catch (sendError) {
                        retryCount += 1;
                        console.warn('[P2P] ⚠️ Reintentando chunk', chunkIndex, 'retry', retryCount, sendError);
                        await new Promise(r => setTimeout(r, 50 * retryCount));
                        if (retryCount > maxChunkRetries) {
                            throw sendError;
                        }
                    }
                }

                offset += chunkSize;
                chunkIndex++;
                transfer.sentChunks = chunkIndex;

                const progress = (offset / file.size) * 100;
                
                // 🚀 OPTIMIZACIÓN: Actualizar UI solo cada 15 chunks o al finalizar 
                // Esto reduce drásticamente los Reflows del DOM que pueden bloquear la interfaz
                if (chunkIndex % 15 === 0 || offset >= file.size) {
                    this.updateProgress(transfer.id, progress, file.size);
                }

                // 🚀 YIELD: Ceder control al event loop para evitar congelar la UI y WebSockets.
                // setTimeout(0) libera el hilo principal, permitiendo que lleguen mensajes del chat.
                // Ceder cada 3 chunks permite velocidades de ~40-50 MB/s en redes LAN, manteniendo el balance.
                if (chunkIndex % 3 === 0) {
                    await new Promise(r => setTimeout(r, 0));
                }
            }

            // Esperar vaciado del buffer antes de notificar completado
            while (dataChannel.readyState === 'open' && dataChannel.bufferedAmount > 0) {
                await new Promise(r => setTimeout(r, 100));
            }

            if (!transfer.cancelled && dataChannel.readyState === 'open') {
                this.sendSignalingMessage(transfer.receiverId, 'transfer.complete', {
                    transfer_id: transferId,
                    total_chunks: transfer.totalChunks,
                    checksum: transfer.checksum,
                    checksum_algorithm: transfer.checksumAlgorithm,
                    target_peer_id: transfer.receiverPeerId
                });
                console.log('[P2P] ✅ Notificación de completado enviada con checksum.');
            }
        } catch (err) {
            console.error('[P2P] ❌ Error enviando chunks:', err);
            if (!transfer.cancelled) {
                this.showNotification(`Error de red: ${err.message}`, 'error');
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
                this.updateProgress(transferId, 0, transfer.fileInfo.size, this.getStatusLabel('streaming'));
            }

            // ✅ Actualizar checksum incremental en cada chunk recibido
            transfer.checksum = this.updateCRC32(transfer.checksum || 0, new Uint8Array(chunkData));

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
                this.handleTransferFailure(transfer.id, err);
            });
        } else {
            // Fallback original para Safari o Mobile sin API
            const blob = new Blob([data]);
            if (transfer.useIndexedDB) {
                // Guardar en IndexedDB de forma asíncrona sin await
                this.saveChunkToIndexedDB(transfer.id, index, blob).catch(err => {
                    console.error('[P2P] Error guardando en IndexedDB:', err);
                    this.handleTransferFailure(transfer.id, err);
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
        
        // 🔄 MOSTRAR "FINALIZANDO" EN EL SIDEBAR
        this.updateProgress(transfer.id, 100, transfer.fileInfo.size, this.getStatusLabel('finalizing'));

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

                const checksumVerified = this.verifyTransferChecksum(transfer);
                if (!checksumVerified) {
                    this.showNotification('Error de integridad: checksum no coincide', 'error');
                    this.sendTransferCompleteAck(transfer, 'checksum_mismatch');
                    this.setUIStatus(transfer.id, 'error');
                    if (window.sidebarTransfers) {
                        window.sidebarTransfers.remove(transfer.id);
                    }
                    return;
                }

                this.sendTransferCompleteAck(transfer, 'checksum_verified');
                transfer.uiFinalized = true;
                this.showNotification('Transferencia completada: archivo recibido y guardado', 'success');
                this.setUIStatus(transfer.id, 'success');
                if (window.sidebarTransfers) {
                    window.sidebarTransfers.complete(transfer.id);
                }
                this.cleanupTransfer(transfer.id);

            } else if (transfer.useIndexedDB) {
                const checksumVerified = this.verifyTransferChecksum(transfer);
                if (!checksumVerified) {
                    this.showNotification('Error de integridad: checksum no coincide', 'error');
                    this.sendTransferCompleteAck(transfer, 'checksum_mismatch');
                    this.setUIStatus(transfer.id, 'error');
                    if (window.sidebarTransfers) {
                        window.sidebarTransfers.remove(transfer.id);
                    }
                    return;
                }

                this.sendTransferCompleteAck(transfer, 'checksum_verified');
                this.triggerServiceWorkerDownload(transfer);
            } else {
                console.log(`[P2P] 🔧 Ensamblando ${transfer.chunksArray.length} fragmentos...`);
                
                const assembleInBackground = async () => {
                    const completeBlob = new Blob(transfer.chunksArray, {
                        type: transfer.fileInfo.mime_type || transfer.fileInfo.type || 'application/octet-stream'
                    });
                    
                    console.log(`[P2P] ✅ Archivo ensamblado: ${(completeBlob.size / 1024 / 1024).toFixed(2)} MB`);
                    transfer.chunksArray = null;

                    const checksumVerified = this.verifyTransferChecksum(transfer);
                    if (!checksumVerified) {
                        this.showNotification('Error de integridad: checksum no coincide', 'error');
                        this.sendTransferCompleteAck(transfer, 'checksum_mismatch');
                        this.setUIStatus(transfer.id, 'error');
                        if (window.sidebarTransfers) {
                            window.sidebarTransfers.remove(transfer.id);
                        }
                        return;
                    }

                    this.sendTransferCompleteAck(transfer, 'checksum_verified');
                    this.forceDownload(completeBlob, transfer.fileInfo.name, transfer.id);
                    
                    transfer.uiFinalized = true;
                    this.setUIStatus(transfer.id, 'success');
                };
                
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(assembleInBackground, { timeout: 1000 });
                } else {
                    setTimeout(assembleInBackground, 0);
                }
            }

            if (window.sidebarTransfers) {
                window.sidebarTransfers.complete(transfer.id);
            }
        } catch (error) {
            console.error('[P2P] ❌ Error finalizando:', error);
            this.setUIStatus(transfer.id, 'error');
            this.stopPerformanceMonitor(transfer.id);
        }
    }

    /**
     * Mostrar notificación de completado
     */
    showCompleteNotification(file, fileInfo) {
        const notification = document.getElementById('p2p-complete-notification');
        if (!notification) return;
        
        document.getElementById('p2p-complete-title').textContent = '✅ Transferencia completada';
        document.getElementById('p2p-complete-message').textContent = `${fileInfo.name} • ${this.formatFileSize(fileInfo.size)} listo para descargar.`;
        
        // Guardar referencia al archivo para descargar
        this.completedFile = { file, name: fileInfo.name };
        
        notification.classList.remove('hidden');
        
        const downloadBtn = document.getElementById('p2p-complete-download');
        if (downloadBtn) {
            downloadBtn.textContent = 'Guardar archivo';
            downloadBtn.onclick = () => this.downloadCompletedFile();
        }
        
        // Auto-ocultar después de 10 segundos si el usuario no interactúa
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
     * Obtener texto estándar para cada estado de transferencia
     */
    getStatusLabel(state) {
        const labels = {
            requesting: 'Esperando aceptación...',
            connecting: 'Conectando...',
            tunneling: 'Estableciendo túnel P2P...',
            streaming: 'Transfiriendo...',
            finalizing: 'Finalizando...',
            completed: 'Completado',
            error: 'Error',
        };
        return labels[state] || state || 'Procesando...';
    }

    /**
     * Mostrar/actualizar barra de progreso
     */
    showProgress(transferId, percent, totalBytes, title = 'Procesando...') {
        // ✅ El progreso SE MUESTRA SÓLO EN EL SIDEBAR "Files"
        // El #p2p-progress-container permanece oculto (inline en chat = eliminado)
        if (this.activeTransfers.has(transferId)) {
            this.updateProgress(transferId, percent, totalBytes, title);
            
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
    updateProgress(transferId, percent, totalBytes, status = null) {
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;
        
        const percentRounded = Math.round(percent);
        
        // 🚀 NUEVO: Suavizado de velocidad y tiempo (Media Móvil)
        const now = Date.now();
        if (!transfer.lastProgressTime) transfer.lastProgressTime = now;
        
        const timeDiff = (now - transfer.lastProgressTime) / 1000;
        
        // Determinar estado por defecto si no viene uno
        const currentStatus = status || transfer.currentStatus || this.getStatusLabel('streaming');
        if (status) {
            transfer.currentStatus = status;
        } else if (!transfer.currentStatus) {
            transfer.currentStatus = currentStatus;
        }

        const bytesNow = totalBytes * (percent / 100);
        const bytesDiff = bytesNow - (transfer.lastBytesProcess || 0);
        const instantSpeed = bytesDiff / (timeDiff || 1) / 1024;
        
        if (!transfer.speedHistory) transfer.speedHistory = [];
        transfer.speedHistory.push(instantSpeed);
        if (transfer.speedHistory.length > 10) transfer.speedHistory.shift();
        
        const avgSpeed = transfer.speedHistory.reduce((a, b) => a + b, 0) / (transfer.speedHistory.length || 1);
        
        transfer.lastProgressTime = now;
        transfer.lastBytesProcess = bytesNow;
        transfer.progress = percentRounded;
        transfer.totalBytes = totalBytes;

        // Velocidad formateada
        let speedText = '';
        if (avgSpeed > 1024) {
            speedText = `${(avgSpeed / 1024).toFixed(2)} MB/s`;
        } else {
            speedText = `${avgSpeed.toFixed(1)} KB/s`;
        }
        transfer.lastSpeedText = speedText;
        
        // Tiempo restante
        let timeText = '';
        if (percent > 1 && percent < 100 && avgSpeed > 0) {
            const bytesRemaining = totalBytes - bytesNow;
            const remainingSecs = bytesRemaining / (avgSpeed * 1024);
            timeText = this.formatTimeRemaining(remainingSecs);
        }
        transfer.lastTimeText = timeText;

        // ✅ Actualizar en sidebar con el estado (ej. "Finalizando...")
        if (window.sidebarTransfers) {
            window.sidebarTransfers.update(transferId, percentRounded, speedText, timeText, currentStatus);
        }

        if (timeDiff >= 0.5 || percentRounded === 100 || status) { 
            const bytesNow = totalBytes * (percent / 100);
            const bytesDiff = bytesNow - (transfer.lastBytesProcess || 0);
            const instantSpeed = bytesDiff / (timeDiff || 1) / 1024;
            
            if (!transfer.speedHistory) transfer.speedHistory = [];
            transfer.speedHistory.push(instantSpeed);
            if (transfer.speedHistory.length > 10) transfer.speedHistory.shift();
            
            const avgSpeed = transfer.speedHistory.reduce((a, b) => a + b, 0) / (transfer.speedHistory.length || 1);
            
            transfer.lastProgressTime = now;
            transfer.lastBytesProcess = bytesNow;

            // Velocidad formateada
            let speedText = '';
            if (avgSpeed > 1024) {
                speedText = `${(avgSpeed / 1024).toFixed(2)} MB/s`;
            } else {
                speedText = `${avgSpeed.toFixed(1)} KB/s`;
            }
            
            // Tiempo restante
            let timeText = '';
            if (percent > 1 && percent < 100 && avgSpeed > 0) {
                const bytesRemaining = totalBytes - bytesNow;
                const remainingSecs = bytesRemaining / (avgSpeed * 1024);
                timeText = this.formatTimeRemaining(remainingSecs);
            }

            // ✅ Actualizar en sidebar con el estado (ej. "Finalizando...")
            if (window.sidebarTransfers) {
                window.sidebarTransfers.update(transferId, percent, speedText, timeText, currentStatus);
            }
        }
    }

    /**
     * Formatear tiempo de forma amigable (ej. 1m 20s)
     */
    formatTimeRemaining(seconds) {
        if (!seconds || seconds < 1) return '';

        const totalSecs = Math.round(seconds);
        if (totalSecs < 60) {
            return `${totalSecs}s`;
        }

        const totalMins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;

        if (totalMins < 60) {
            return secs > 0 ? `${totalMins}m ${secs}s` : `${totalMins}m`;
        }

        const hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
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
        const transfer = this.activeTransfers.get(transferId);
        if (!transfer) return;

        transfer.currentStatus = title;
        const percent = transfer.progress ?? 0;
        const totalBytes = transfer.totalBytes || transfer.fileInfo?.size || transfer.file?.size || 0;
        const speedText = transfer.lastSpeedText || '';
        const timeText = transfer.lastTimeText || '';

        if (window.sidebarTransfers) {
            window.sidebarTransfers.update(transferId, percent, speedText, timeText, title);
        }
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
                this.abortIncompleteStorage(t);
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
     * Polling de Señalización (Contingencia)
     * Consulta señales P2P cada 5-8 segundos con Axios
     * Incluye reintentos automáticos y rate limiting
     */
    iniciarPollingSignaling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        
        const poll = async () => {
            try {
                const response = await window.axiosService.get('/api/p2p/signals/new');
                const data = response.data;
                
                if (data.success && data.signals) {
                    data.signals.forEach(signal => {
                        console.log(`[P2P-POLLING] Signal [${signal.type}] recibido`);
                        this.handleSignalingMessage(signal.type, signal);
                    });
                }
            } catch (err) {
                // Log silencioso para polling (es esperado si no hay señales)
                if (err.response?.status !== 404) {
                    console.warn('[P2P-POLLING] Error en consulta de señales:', err.message);
                }
            }
        };

        poll();
        this.pollingInterval = setInterval(poll, 7000);
        console.log('[P2P] 📡 Polling de signaling iniciado (cada 7s)');
    }

    /**
     * Mostrar notificación genérica
     */
    showNotification(message, type = 'info') {
        console.log(`[P2P] 📢 ${type.toUpperCase()}: ${message}`);
    }

    /**
     * Enviar mensaje de signaling vía API con Axios
     * Incluye reintentos automáticos, rate limiting y manejo de errores mejorado
     */
    async sendSignalingMessage(toUserId, type, data) {
        if (!toUserId) return;
        
        const payload = {
            from: this.myPeerId,
            to_id: String(toUserId),
            type: type,
            data: {
                ...data,
                target_peer_id: data.target_peer_id || null // Para filtrar en el receptor
            },
        };

        try {
            const response = await window.axiosService.post('/api/p2p/signal', payload);
            
            // Confirmación visual solo para solicitudes críticas
            if (type === 'transfer.request') {
                console.log('[P2P] 🚀 Solicitud de transferencia enviada correctamente');
            } else if (type === 'transfer.accepted') {
                console.log('[P2P] ✅ Aceptación de transferencia confirmada');
            }
            
            return response.data;
        } catch (error) {
            // Manejo detallado de errores
            const status = error.response?.status;
            const errorMsg = error.response?.data?.message || error.message;
            
            if (status === 419) {
                console.error(`🔐 Token CSRF expirado en signaling. Recarga la página.`);
                window.showToast?.('Sesión Expirada', 'Por favor, recarga la página', 'error');
            } else if (status === 422) {
                console.error(`⚠️ Validación fallida en signaling:`, error.response?.data);
                window.showToast?.('Datos Inválidos', errorMsg || 'Error en los datos enviados', 'error');
            } else if (status === 429) {
                console.warn(`⏸️ Rate limit: Espera antes de reintentar`);
                window.showToast?.('Sistema Ocupado', 'Demasiadas solicitudes. Esperando...', 'warning');
            } else if (!status || status >= 500) {
                console.error(`❌ Error de servidor en signaling [${type}]:`, error);
                // El sistema de reintentos de Axios manejará esto
            }
            
            throw error;
        }
    }

    /**
     * Manejar mensajes de signaling
     */
    async handleSignalingMessage(type, data) {
        // 🔑 DEDUPLICACIÓN: Evitar procesar lo mismo via Echo y Polling
        const signalId = data.id || (data.signal && data.signal.id);
        if (signalId && this.processedSignals.has(signalId)) return;
        if (signalId) {
            this.processedSignals.add(signalId);
            // Limpiar set para no consumir RAM infinita (solo guardamos los últimos 100)
            if (this.processedSignals.size > 100) {
                const firstId = this.processedSignals.values().next().value;
                this.processedSignals.delete(firstId);
            }
        }

        const payload = data && data.data ? data.data : data;
        const transferId = payload ? payload.transfer_id : null;
        
        console.log(`[P2P] 🛠 Procesando handleSignalingMessage [${type}]. SignalID: ${signalId}, TransferID: ${transferId}`);
        
        // Filtrar por Peer ID si está presente para no procesar mensajes de otras pestañas
        if (payload && payload.target_peer_id && payload.target_peer_id !== this.myPeerId) {
            console.log(`[P2P] ⏭ Ignorando señal para otro Peer: ${payload.target_peer_id} (Yo soy: ${this.myPeerId})`);
            return;
        }

        try {
            // Intentar obtener la transferencia por ID directo (más fiable)
            let transfer = transferId ? this.activeTransfers.get(transferId) : null;

            if (type === 'transfer.request') {
                // 🛑 PREVENCIÓN DE DUPLICADOS: No procesar si ya hay un modal abierto o transferencia activa con el mismo ID
                if (!document.getElementById('p2p-accept-modal').classList.contains('hidden')) {
                    console.log('[P2P] ⏭ Ignorando solicitud duplicada (modal ya visible)');
                    return;
                }
                if (transfer) {
                    console.log('[P2P] ⏭ Ignorando solicitud (transferencia ya en curso)');
                    return;
                }
                
                console.log('[P2P] 📨 Solicitud de transferencia:', payload);
                this.showAcceptModal(payload);
            } else if (type === 'transfer.accepted') {
                console.log('[P2P] ✅ Transferencia aceptada por receptor / Handshake fase 1');
                this.onReceiverAccepted(payload);
            } else if (type === 'transfer.ready_to_receive') {
                console.log('[P2P] 🔓 Semáforo recibido: Receptor listo para el stream');
                this.onReadyToReceive(payload);
            } else if (type === 'transfer.rejected') {
                console.log('[P2P] ❌ Transferencia rechazada por receptor');
                this.showNotification('Transferencia rechazada por el receptor', 'error');
                this.hideProgress(transferId);
            } else if (type.startsWith('contact.')) {
                // 🤝 DELEGACIÓN: Manejar señales de relación de contacto (Agregar/Aceptar)
                if (typeof window.handleContactSignal === 'function') {
                    window.handleContactSignal(type, payload);
                }
            } else if (type === 'transfer.complete') {
                console.log('[P2P] ✅ Signal de completado recibido:', payload);
                if (transfer && transfer.role === 'receiver') {
                    console.log('[P2P] ✅ Receptor procesando finalización. Esperando últimos chunks...');
                    transfer.completedBySender = true;
                    if (payload.checksum !== undefined) {
                        transfer.expectedChecksum = payload.checksum;
                        transfer.checksumAlgorithm = payload.checksum_algorithm || transfer.checksumAlgorithm || 'crc32';
                        console.log('[P2P] 🔐 Checksum esperado recibido:', transfer.expectedChecksum);
                    }
                    if (transfer.receivedCount >= transfer.totalChunks) {
                        this.assembleFile(transfer);
                    } else {
                        this.updateProgressTitle(transferId, this.getStatusLabel('finalizing'));
                    }
                } else if (transfer && transfer.role === 'sender') {
                    if (payload.status === 'checksum_mismatch') {
                        console.error('[P2P] ❌ El receptor detectó checksum inválido');
                        this.showNotification('Integridad del archivo falló en el receptor', 'error');
                        this.setUIStatus(transferId, 'error');
                        if (window.sidebarTransfers) {
                            window.sidebarTransfers.remove(transferId);
                        }
                    } else {
                        console.log('[P2P] ✅ El receptor confirma recepción completa.');
                        this.onTransferComplete(payload);
                    }
                }
            } else if (type === 'chunk') {
                this.receiveChunk(payload);
            } else if (type === 'offer') {
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
            this.updateProgressTitle(transferId, this.getStatusLabel('connecting'));
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
     * Motor Adaptativo: configuración optimizada para producción
     */
    getAdaptiveConfig(fileSize) {
        if (fileSize >= 10 * 1024 * 1024 * 1024) {
            return {
                chunkSize: 16 * 1024,             // 16 KB para archivos muy grandes
                bufferLimit: 3145728,             // 3 MB
                backpressure: {
                    pause: 3145728,               // Pausar a 3 MB
                    resume: 1572864                // Reanudar a 1.5 MB
                }
            };
        }
        if (fileSize >= 4 * 1024 * 1024 * 1024) {
            return {
                chunkSize: 24 * 1024,
                bufferLimit: 3145728,             // 3 MB
                backpressure: {
                    pause: 3145728,
                    resume: 1572864
                }
            };
        }
        return {
            chunkSize: 65536,                    // 64 KB - Punto dulce ✅
            bufferLimit: 3145728,                // 3 MB - Buffer aumentado para mejor rendimiento
            backpressure: {
                pause: 3145728,                  // Pausar a 3 MB
                resume: 1572864                  // Reanudar a 1.5 MB
            }
        };
    }

    /**
     * Chunk size seguro según tamaño de archivo
     * Archivos grandes necesitan chunks pequeños para evitar drops de conexión
     */
    _safeChunkSize(fileSize, networkChunk) {
        if (fileSize >= 10 * 1024 * 1024 * 1024) return 16 * 1024;  // >= 10GB → 16KB (más seguro)
        if (fileSize >= 4 * 1024 * 1024 * 1024) return 24 * 1024;   // >= 4GB → 24KB
        if (fileSize >= 1 * 1024 * 1024 * 1024) return 32 * 1024;   // >= 1GB → 32KB
        if (fileSize >= 500 * 1024 * 1024)       return 64 * 1024;   // >= 500MB → 64KB
        return networkChunk; // < 500MB → usar detección de red (hasta 256KB)
    }

    /**
     * Detectar condición de red
     */
    detectNetworkCondition() {
        if (navigator.connection) {
            const { effectiveType, saveData } = navigator.connection;
            if (saveData || effectiveType === 'slow-2g' || effectiveType === '2g') {
                return { streams: 1, chunkSize: 16 * 1024 };
            }
            if (effectiveType === '3g') {
                return { streams: 2, chunkSize: 64 * 1024 };
            }
        }
        if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            return { streams: 2, chunkSize: 64 * 1024 };
        }
        return { streams: 4, chunkSize: 256 * 1024 }; // Desktop buena red, archivos pequeños
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
            this.updateProgressTitle(transferId, this.getStatusLabel('completed'));
            if (window.sidebarTransfers) {
                window.sidebarTransfers.complete(transferId);
            }
        } else if (state === 'error') {
            // Remover del sidebar en error
            if (window.sidebarTransfers) {
                window.sidebarTransfers.remove(transferId);
            }
            showToast('Error P2P', 'Error en la conexión de transferencia', 'error');
        } else {
            // Sincronizar texto de estado para etapas intermedias
            this.updateProgressTitle(transferId, this.getStatusLabel(state));
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

    getCRC32Table() {
        if (this._crc32Table) return this._crc32Table;
        const table = new Uint32Array(256);
        for (let i = 0; i < 256; i++) {
            let c = i;
            for (let j = 0; j < 8; j++) {
                c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            }
            table[i] = c >>> 0;
        }
        this._crc32Table = table;
        return table;
    }

    updateCRC32(crc, bytes) {
        const table = this.getCRC32Table();
        let c = (crc ^ -1) >>> 0;
        for (let i = 0; i < bytes.length; i++) {
            c = (c >>> 8) ^ table[(c ^ bytes[i]) & 0xFF];
        }
        return (c ^ -1) >>> 0;
    }

    verifyTransferChecksum(transfer) {
        if (!transfer || transfer.expectedChecksum == null) {
            return true;
        }
        return transfer.checksum === transfer.expectedChecksum;
    }

    sendTransferCompleteAck(transfer, status) {
        if (!transfer || transfer.role !== 'receiver' || !transfer.senderId) return;
        this.sendSignalingMessage(transfer.senderId, 'transfer.complete', {
            transfer_id: transfer.id,
            status: status,
            checksum: transfer.checksum,
            checksum_algorithm: transfer.checksumAlgorithm,
            target_peer_id: transfer.senderPeerId
        });
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
