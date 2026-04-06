# 🔄 FLUJOS COMPARATIVOS - Antes vs Después (Axios Integration)

## FLUJO 1: Transferencia Normal (Éxito)

### ❌ ANTES (Sin Axios - fetch() directo)
```
Usuario A                               Servidor                               Usuario B
   │                                       │                                       │
   ├─ Selecciona archivo ────────────────→ /api/p2p/signal (Offer) ────────────→ Recibe Offer
   │  fetch() sin reintentos               │                                       │
   │                                       │ ✓ Oferado recibido                   │
   │                                       │                                       ├─ Crea Answer
   │                                       │                                       │
   │ Recibe Answer ←─ /api/p2p/signal ──← Envía Answer                           │
   │ (fetch manual)                        │                                       │
   │                                       │                                       │
   ├─ WebRTC Offer ──→ [RTCDataChannel] ──→ WebRTC Answer                        │
   │   (directo)                           │                                       │
   │                                       │                                       │
   │ 🔴 FALLA: Buffer lleno (8MB)         │                                       │ 🔴 Espera
   │ "RTCDataChannel send queue full"     │                                       │
   │                                       │                                       │
   │ ❌ CONSECUENCIA:                      │                                       │
   │   • No puede reintentar chunk        │                                       │
   │   • No reintentar automaticamente    │                                       │
   │   • Usuario debe recargar            │                                       │
   └─ ❌ TRANSFERENCIA CANCELADA         └─ ❌ INCOMPLETA                         └─ ❌ NOTHING
```

### ✅ AHORA (Con Axios - Rate Limiting + Reintentos)
```
Usuario A                               Servidor                               Usuario B
   │                                       │                                       │
   ├─ Selecciona archivo ─→ [Axios] ─→ /api/p2p/signal (Offer) ──────────────→ Recibe Offer
   │  (con CSRF, rate limit)              │ [Rate Limit: 10 req/s]                │
   │  Reintentos: 1s→2s→4s                │ [Concurrency: máx 2]                  │
   │                                       │                                       ├─ Crea Answer
   │                                       │                                       │
   │ Recibe Answer ← [Axios] ← /api/p2p/signal                                  │
   │ (con reintentos)                     │ ✓ Respeta rate limit                 │
   │                                       │                                       │
   ├─ WebRTC Offer ──→ [RTCDataChannel] ──→ WebRTC Answer                        │
   │   (directo, rápido)                  │                                       │
   │                                       │                                       │
   │                          CHUNK #800    │                                       │
   │ 🔴 BUFFER SATURATION (80% = 6.4MB)  │                                       │
   │                                       │                                       │
   │ ✅ BACKPRESSURE ACTIVA:              │                                       │
   │   • Pausa automática                 │                                       │
   │   • Espera 2 segundos                │                                       │
   │   • Buffer desciende (Receptor recibe)                                      │
   │                                       │                                       │
   │ ✅ REANUDA (buffer < 50%):          │                                       │
   │   • Continúa con CHUNK #801         │                                       │
   │   • Velocidad adaptativa             │                                       │
   │                                       │                                       │
   │ ... sendChunk #1000 ──→ [Axios retry] → OK (1 intento)                    │
   │ ... sendChunk #1001 ──→ [WebRTC] ───→ OK                                  │
   │                                       │                                       │
   │ ✅ TRANSFERENCIA COMPLETA (100MB)    │                                       │
   └─ ✅ ÉXITO: Toast "Completado"        └─ ✅ CONFIABLE                         └─ ✅ GUARDADO
```

---

## FLUJO 2: WebRTC Falla Completamente

### ❌ ANTES (Sin Axios - Fallo Total)
```
Usuario A                               Servidor                               Usuario B
   │                                       │                                       │
   ├─ COMIENZA OFERTA                     │                                       │
   │  WebRTC Offer ──→ [NAT Traversal]    │                                       │
   │                   ❌ FALLA: No puede conectar (Firewall)                     │
   │                                       │                                       │
   │ ❌ CONSECUENCIA EN CASCADA:          │                                       │
   │   1. OFFER never received             │                                       │
   │   2. Receptor nunca vio modal        │                                       │
   │   3. Emisor espera respuesta (timeout)                                     │
   │   4. P2PFileTransfer se atasca        │                                       │
   │   5. UI congelada                     │                                       │
   │   6. Usuario viendo "cargando..."     │                                       │
   │   7. DEBE RECARGAR LA PÁGINA          │                                       │
   │                                       │                                       │
   │ ❌ TODO MUERE SILENCIOSAMENTE         │                                       │
   └─ ❌ TRANSFERENCIA: NUNCA COMENZÓ      └─ ❌ ERROR INCOMPLETO                  └─ ❌ NADA
```

### ✅ AHORA (Con Axios - Fallback Automático)
```
Usuario A                               Servidor                               Usuario B
   │                                       │                                       │
   ├─ COMIENZA OFERTA                     │                                       │
   │  WebRTC Offer ──→ [NAT Traversal]    │                                       │
   │  PERO ALMACENA EN [Axios Queue]      │                                       │
   │                                       │                                       │
   │  ❌ FALLA: No puede conectar         │                                       │
   │                                       │                                       │
   │  ✅ FALLBACK AUTOMÁTICO:             │                                       │
   │     [Axios] detecta WebRTC muerto    │                                       │
   │     └─ PLAN B: HTTP Polling (Axios) → /api/p2p/signals/new               │
   │                                       │ [Rate Limit: 10 req/s]                │
   │                                       │ [Reintentos: 1s→2s→4s]               │
   │                                       │                                       │
   │ ✅ Axios reintenta 3 veces:          │                                       │
   │    Intento 1: /api/p2p/signal → 200 OK                                   │
   │    (o timeout, pero sigue)           │                                       │
   │                                       │ ✅ OFERTA LLEGA via HTTP               │
   │                                       │                                       │
   │                                       │ Toast: "Oferta recibida vía HTTP"   ├─ ✅ RECIBE
   │                                       │ (velocidad: más lenta, pero funciona) │
   │                                       │                                       │
   │                                       │                                       ├─ Crea Answer
   │                                       │                                       │
   │ ✅ Answer via HTTP Polling (Axios)  ← /api/p2p/signal ← Envía Answer       │
   │    Reintentos: 1s→2s→4s              │ (Rate limit respetado)                │
   │                                       │                                       │
   │ Toast: "Fichero enviado vía HTTP"    │                                       │
   │ (más lento pero 100% funcional)      │                                       │
   │                                       │                                       │
   │ ✅ TRANSFERENCIA COMPLETA            │                                       │
   │    Mecanismo 1: WebRTC (primario)    │                                       │
   │    Mecanismo 2: HTTP Polling (fallback)                                    │
   │                                       │                                       │
   └─ ✅ ÉXITO: No requiere intervención  └─ ✅ RESILIENTE                        └─ ✅ COMPLETADO
```

---

## FLUJO 3: Rate Limit (Múltiples transferencias simultáneas)

### ❌ ANTES (Sin Axios - Saturación)
```
Usuario A envía a B, C, D, E simultáneamente
   │
   ├─ /api/p2p/signal (A→B) ────→ fetch() directo ─→ 200 OK
   ├─ /api/p2p/signal (A→C) ────→ fetch() directo ─→ 200 OK
   ├─ /api/p2p/signal (A→D) ────→ fetch() directo ─→ 🔴 429 TOO MANY REQUESTS
   ├─ /api/p2p/signal (A→E) ────→ fetch() directo ─→ 🔴 429 TOO MANY REQUESTS
   │
   ❌ CONSECUENCIA:
   • D y E nunca reciben la oferta
   • Usuarios esperan (confundidos)
   • Sistema saturado
   └─ ❌ CASCADA DE FALLOS
```

### ✅ AHORA (Con Axios - Queue Inteligente)
```
Usuario A envía a B, C, D, E simultáneamente
   │
   ├─ /api/p2p/signal (A→B) ────→ [Axios Queue] ─→ 200 OK ✅
   │                              [Concurrency: 2/2]
   │
   ├─ /api/p2p/signal (A→C) ────→ [Axios Queue] ─→ 200 OK ✅
   │                              [Concurrency: 2/2] ← LLENA
   │
   ├─ /api/p2p/signal (A→D) ────→ [Axios Queue] ─→ ENCOLADA (espera)
   │                              [Queue: 1, Wait: 200ms]
   │
   ├─ /api/p2p/signal (A→E) ────→ [Axios Queue] ─→ ENCOLADA (espera)
   │                              [Queue: 2, Wait: 400ms]
   │
   │ (Cuando A→B completa)
   │
   ├─ /api/p2p/signal (A→D) ────→ [Axios Queue] ─→ 200 OK ✅
   │                              [Concurrency: 2/2]
   │
   └─ /api/p2p/signal (A→E) ────→ [Axios Queue] ─→ 200 OK ✅
                                 [Concurrency: 1/2]

✅ RESULTADO:
   • Todos reciben oferta (sin perder ninguno)
   • Sistema respeta rate limit
   • No hay 429 TOO MANY REQUESTS
   • Espera inteligente (automática)
   └─ ✅ ESCALABLE
```

---

## FLUJO 4: CSRF Token Expirado (Session Timeout)

### ❌ ANTES (Sin Axios)
```
Usuario A inicia sesión hace 2 horas
Intenta enviar archivo
   │
   ├─ fetch('/api/p2p/signal', {
   │    headers: { 'X-CSRF-TOKEN': oldToken }
   │  })
   │
   └─ 419 UNAUTHORIZED (Token expirado)
      ❌ Error genérico: "Error en la solicitud"
      ❌ Usuario confundido
      ❌ Debe recargar página manualmente
      └─ ❌ EXPERIENCIA POBRE
```

### ✅ AHORA (Con Axios)
```
Usuario A inicia sesión hace 2 horas
Intenta enviar archivo
   │
   ├─ axiosService.post('/api/p2p/signal', ...)
   │  [Axios obtiene CSRF token NUEVO de meta tag]
   │
   └─ 419 UNAUTHORIZED (El servidor sigue rechazando)
      ✅ Interceptor detecta 419
      ✅ ERROR HANDLER: "Sesión Expirada"
      ✅ Toast claro: "Por favor, recarga la página"
      ✅ NO REINTENTA (sabe que es permanente)
      ✅ Usuario entiende qué hacer
      └─ ✅ EXPERIENCIA CLARA
```

---

## CAPAS DE DEFENSA (Multi-Layer Resilience)

```
┌────────────────────────────────────────────────────────────────┐
│                    APLICACIÓN (UI)                             │
│  ✅ Toast mensajes claros                                       │
│  ✅ Barra de progreso animada                                   │
│  ✅ Reintentos visuales                                         │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│              ERROR HANDLER + VISUALIZER                        │
│  ✅ Clasificación de errores                                   │
│  ✅ Reintentos con backoff                                     │
│  ✅ Logging automático                                         │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│            CHUNKED TRANSFER MANAGER                            │
│  ✅ Backpressure (pausa/reanuda)                              │
│  ✅ Estadísticas en tiempo real                               │
│  ✅ Adaptabilidad por red                                      │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│              AXIOS SERVICE                                     │
│  ✅ Rate limiting (1-3 concurrentes)                           │
│  ✅ Reintentos (1s→2s→4s)                                     │
│  ✅ CSRF automático                                            │
│  ✅ Error classification                                       │
│  ✅ Queue inteligente                                          │
└────────────────────────────────────────────────────────────────┘
                            ↓
        ┌───────────────────┬───────────────────┐
        ↓                   ↓                   ↓
   [WebRTC Primary]   [HTTP Polling]    [Direct Axios]
   • RTCDataChannel   • Fallback        • Signaling
   • P2P Peer        • Polling 5-7s    • Status checks
```

---

## RESUMEN: CÓMO AXIOS SOLUCIONA CADA PROBLEMA

| Problema de WebRTC | Causa | Solución Axios |
|---|---|---|
| Buffer overflow | Envío demasiado rápido | ✅ Backpressure (pausa 80%, reanuda 50%) |
| Conexión inestable | NAT/Firewall | ✅ Fallback HTTP + Polling + Reintentos |
| Rate limit 429 | Servidor saturado | ✅ Queue + Rate limiting automático |
| CSRF 419 | Token expirado | ✅ Token automático + Mensaje claro |
| Error silencioso | Echo desconexión | ✅ Axios polling cada 7s |
| Concurrencia | Múltiples transfers | ✅ Limiting 1-3 concurrentes |
| Debugging difícil | Sin logs | ✅ 50 errores logged automáticamente |

---

## 🎯 CONCLUSIÓN

**Antes (fetch puro):** 1 problema = colapso en cascada
**Ahora (Axios):** 1 problema = fallback automático + recovery

**Arquitectura Mejorada:** Responsabilidades bien separadas = Sistema resiliente

