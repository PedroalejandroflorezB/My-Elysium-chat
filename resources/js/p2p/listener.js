/**
 * resources/js/p2p/listener.js
 * Escucha señales WebRTC entrantes vía Laravel Echo + Reverb
 */

import Echo from 'laravel-echo';
import P2PConnection from './connection';

class P2PListener {
    constructor(userId, echo) {
        this.userId = userId;
        this.echo = echo;
        this.activeConnections = new Map();
        this.onOfferReceived = null;
        this.onAnswerReceived = null;
        this.onIceCandidateReceived = null;
        this.onFileReceived = null;
    }

    /**
     * Iniciar escucha de señales en canal privado
     */
    startListening() {
        console.log(`👂 Escuchando señales en canal: chat.${this.userId}`);
        
        this.echo.private(`chat.${this.userId}`)
            .listen('.webrtc.offer', (e) => {
                console.log('📩 Oferta recibida:', e);
                if (this.onOfferReceived) {
                    this.onOfferReceived(e);
                }
            })
            .listen('.webrtc.answer', (e) => {
                console.log('📩 Respuesta recibida:', e);
                if (this.onAnswerReceived) {
                    this.onAnswerReceived(e);
                }
            })
            .listen('.webrtc.ice-candidate', (e) => {
                console.log('📩 ICE Candidate recibido:', e);
                if (this.onIceCandidateReceived) {
                    this.onIceCandidateReceived(e);
                }
            });
    }

    /**
     * Configurar callback cuando llega una oferta
     */
    onOffer(callback) {
        this.onOfferReceived = callback;
        return this;
    }

    /**
     * Configurar callback cuando llega una respuesta
     */
    onAnswer(callback) {
        this.onAnswerReceived = callback;
        return this;
    }

    /**
     * Configurar callback cuando llega un candidato ICE
     */
    onIceCandidate(callback) {
        this.onIceCandidateReceived = callback;
        return this;
    }

    /**
     * Detener escucha
     */
    stopListening() {
        this.echo.leave(`chat.${this.userId}`);
        console.log('🔇 Dejando de escuchar señales');
    }
}

export default P2PListener;