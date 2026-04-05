/**
 * resources/js/p2p/connection.js
 * Conexión WebRTC + Laravel Echo para señalización P2P
 */

import Echo from 'laravel-echo';
import axiosService from '../services/axios-service.js';

class P2PConnection {
    constructor(userId, echo) {
        this.userId = userId;
        this.echo = echo;
        this.peerConnection = null;
        this.dataChannel = null;
        this.targetUserId = null;
        this.sessionId = null;
        
        // Configuración STUN (Google, gratuito)
        this.rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };
    }

    /**
     * Iniciar conexión peer-to-peer (como iniciador)
     */
    async initiateCall(targetUserId, sessionId) {
        this.targetUserId = targetUserId;
        this.sessionId = sessionId;
        
        this.setupPeerConnection();
        
        // Crear canal de datos para transferencia de archivos
        this.dataChannel = this.peerConnection.createDataChannel('fileTransfer', {
            ordered: true,
            maxRetransmits: 10
        });
        this.setupDataChannel(this.dataChannel);
        
        // Crear oferta SDP
        const offer = await this.peerConnection.createOffer();
        await this.peerConnection.setLocalDescription(offer);
        
        // Enviar oferta al receptor vía API
        await this.sendSignal('offer', offer);
        
        return { status: 'offer_sent', sessionId };
    }

    /**
     * Responder a una oferta (como receptor)
     */
    async acceptCall(offer, sessionId, senderId) {
        this.targetUserId = senderId;
        this.sessionId = sessionId;
        
        this.setupPeerConnection();
        
        // Escuchar cuando se crea el canal de datos (somos receptor)
        this.peerConnection.ondatachannel = (event) => {
            this.dataChannel = event.channel;
            this.setupDataChannel(this.dataChannel);
        };
        
        // Establecer oferta remota y crear respuesta
        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
        const answer = await this.peerConnection.createAnswer();
        await this.peerConnection.setLocalDescription(answer);
        
        // Enviar respuesta al iniciador
        await this.sendSignal('answer', answer);
        
        return { status: 'answer_sent' };
    }

    /**
     * Configurar RTCPeerConnection
     */
    setupPeerConnection() {
        this.peerConnection = new RTCPeerConnection(this.rtcConfig);
        
        // Manejar candidatos ICE encontrados
        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.sendSignal('ice-candidate', event.candidate);
            }
        };
        
        // Manejar cambios de estado de conexión
        this.peerConnection.onconnectionstatechange = () => {
            console.log('🔗 Estado de conexión:', this.peerConnection.connectionState);
            
            if (this.peerConnection.connectionState === 'connected') {
                console.log('✅ Conexión P2P establecida');
            }
            if (this.peerConnection.connectionState === 'failed') {
                console.error('❌ Conexión P2P fallida');
            }
        };
    }

    /**
     * Configurar canal de datos para transferencia
     */
    setupDataChannel(channel) {
        channel.onopen = () => {
            console.log('✅ Canal de datos abierto - listo para transferir');
        };
        
        channel.onmessage = (event) => {
            this.handleDataMessage(event.data);
        };
        
        channel.onclose = () => {
            console.log('🔒 Canal de datos cerrado');
        };
        
        channel.onerror = (error) => {
            console.error('❌ Error en canal de datos:', error);
        };
    }

    /**
     * Enviar señal WebRTC vía API HTTP con Axios
     * Incluye reintentos automáticos, rate limiting y manejo mejorado de errores
     */
    async sendSignal(type, payload) {
        const endpoint = `/p2p/${type}`;
        
        // Preparar payload según el tipo de señal
        const data = {
            targetUserId: this.targetUserId,
            sessionId: this.sessionId,
        };
        
        if (type === 'offer') data.offer = payload;
        else if (type === 'answer') data.answer = payload;
        else if (type === 'ice-candidate') data.candidate = payload;

        try {
            const response = await axiosService.post(endpoint, data);
            return response.data;
        } catch (error) {
            // Manejo detallado de errores
            const errorMsg = error.response?.data?.message || error.message || `Error enviando ${type}`;
            
            if (error.response?.status === 419) {
                console.error(`🔐 Token CSRF expirado en ${type}. Recarga la página.`);
                window.showToast?.('Sesión Expirada', 'Por favor, recarga la página', 'error');
            } else if (error.response?.status === 422) {
                console.error(`⚠️ Validación fallida en ${type}:`, error.response.data);
                window.showToast?.('Datos Inválidos', errorMsg, 'error');
            } else {
                console.error(`❌ Error enviando ${type}:`, error);
                window.showToast?.('Error de Conexión', `No se pudo enviar ${type}. Reintentando...`, 'error');
            }
            
            throw error;
        }
    }

    /**
     * Manejar mensaje recibido por DataChannel
     */
    handleDataMessage(data) {
        if (typeof data === 'string') {
            // Metadata del archivo
            try {
                const metadata = JSON.parse(data);
                console.log('📦 Metadata recibida:', metadata);
                return { type: 'metadata', data: metadata };
            } catch (e) {
                console.warn('⚠️ No es JSON válido:', data);
            }
        } else {
            // Datos binarios del archivo (chunk)
            console.log('📥 Chunk recibido:', data.byteLength, 'bytes');
            return { type: 'chunk', data };
        }
    }

    /**
     * Enviar archivo fragmentado por chunks
     */
    async sendFile(file, onProgress) {
        if (!this.dataChannel || this.dataChannel.readyState !== 'open') {
            throw new Error('El canal de datos no está listo');
        }

        // Enviar metadata primero
        const metadata = {
            name: file.name,
            size: file.size,
            type: file.type,
            chunks: Math.ceil(file.size / 16384) // 16KB chunks
        };
        this.dataChannel.send(JSON.stringify(metadata));

        // Fragmentar y enviar archivo
        const chunkSize = 16384; // 16KB
        let offset = 0;
        let chunksSent = 0;

        return new Promise((resolve, reject) => {
            const sendNextChunk = () => {
                if (offset >= file.size) {
                    console.log('✅ Archivo enviado completamente');
                    resolve();
                    return;
                }

                // Control de presión de buffer (evitar saturar)
                if (this.dataChannel.bufferedAmount > 65536) {
                    setTimeout(sendNextChunk, 50);
                    return;
                }

                const chunk = file.slice(offset, offset + chunkSize);
                this.dataChannel.send(chunk);
                
                offset += chunkSize;
                chunksSent++;
                
                // Callback para progreso
                if (onProgress) {
                    onProgress({
                        sent: offset,
                        total: file.size,
                        percent: Math.round((offset / file.size) * 100),
                        chunksSent
                    });
                }
                
                // Continuar con siguiente chunk
                setTimeout(sendNextChunk, 0);
            };
            
            sendNextChunk();
        });
    }

    /**
     * Cerrar conexión
     */
    close() {
        if (this.dataChannel) this.dataChannel.close();
        if (this.peerConnection) this.peerConnection.close();
        console.log('🔚 Conexión P2P cerrada');
    }
}

export default P2PConnection; 