# 🚀 Sistema de Intercambio de Archivos P2P

Este documento resume el funcionamiento actual de la transferencia de archivos en **Elysium P2P**, detallando tanto la arquitectura base como los **últimos ajustes de rendimiento y estabilidad** realizados para optimizar la experiencia del usuario.

## 📋 Resumen del Sistema
El intercambio de archivos es **P2P Puro con Cero Almacenamiento**. El servidor no guarda ni procesa los archivos; solo actúa como un "casamentero" (Handshake) para conectar dos navegadores directamente.

### Capacidades Actuales:
- **Archivos de cualquier tamaño**: Soporte para archivos de varios GB mediante *Stream-to-Disk*.
- **Sin límite de RAM**: El uso de memoria se mantiene constante (~100MB) sin importar el peso del archivo.
- **Privacidad Total**: Los datos viajan cifrados de navegador a navegador sin pasar por el disco del servidor.

---

## 🛠️ Últimos Ajustes: Optimización "Safe & Fast"

Recientemente, hemos refinado el motor de transferencia para maximizar la velocidad sin comprometer la estabilidad del navegador.

### 1. Motor de Rendimiento Adaptativo
Hemos ajustado el "Cerebro" de la transferencia (`getAdaptiveConfig`) con parámetros equilibrados:
- **Búfer de Transmisión (8MB)**: Hemos subido el límite de datos "en vuelo" a 8MB. Esto llena el canal de banda ancha de forma más eficiente.
- **Límite de Seguridad**: Se ha capado a 8MB para evitar el error `RTCDataChannel send queue is full`, que ocurre cuando el navegador se satura (límite físico de ~16MB).
- **Backpressure Inteligente**: El emisor ahora pausa el envío cuando el búfer supera los 8MB y reanuda automáticamente cuando baja de 4MB.

### 2. Limpieza de Interfaz (UX)
Para evitar distracciones y una sensación de "lentitud visual":
- **Sidebar Limpia**: Se ha eliminado el progreso del campo de búsqueda. Ahora puedes buscar contactos mientras envías archivos sin que el texto parpadee o cambie.
- **Barra de Progreso Unificada**: Toda la información se concentra en el *Toast* inferior, que es el punto de referencia oficial.
- **Leyendas de Rol**: El sistema ahora distingue si eres **Emisor** ("Enviando...") o **Receptor** ("Recibiendo..."), con mensajes de "Casi terminado..." personalizados.

---

## 🧬 Comparativa Técnica (Para Depuración)

Si el sistema llegara a fallar, estos son los valores que comparamos para diagnosticar:

| Característica | Configuración Anterior | Ajuste Actual (Optimizado) | Razón del Cambio |
| :--- | :--- | :--- | :--- |
| **Búfer de Datos** | 1MB (Muy lento) | **8MB (Veloz)** | Maximizar throughput de red. |
| **Pausas de Envío** | Frecuentes | **Mínimas** | El búfer más grande permite un flujo constante. |
| **Error Queue Full** | Riesgo alto (>16MB) | **Protegido (<8MB)** | Evitar crasheos en Chrome/Edge. |
| **Indicadores UI** | Múltiples (Confuso) | **Único (Limpio)** | Mejorar la concentración del usuario. |

---

## 🔍 Guía de Diagnóstico Rápido

Si una transferencia se detiene o falla, verifica estos puntos basándote en los últimos ajustes:

1.  **¿Se queda en 0%?**: Probablemente el receptor aún no ha aceptado el diálogo de "Guardar como" del navegador. El emisor espera a esta señal por seguridad (*Semáforo*).
2.  **¿Error de canal cerrado?**: Puede ser una saturación de red. El búfer de 8MB está diseñado para prevenir esto, pero en redes muy inestables podría requerir bajar a 2MB.
3.  **¿Barra "congelada"?**: Verifica la consola del navegador (F12). Si ves `RTCDataChannel send queue is full`, el búfer debe reducirse aún más.

---

> [!TIP]
> **Recomendación de Uso:** Para archivos de más de 500MB, asegúrate de que ambos usuarios mantengan la pestaña del chat activa y en primer plano para evitar que el navegador suspenda el proceso por ahorro de energía.
