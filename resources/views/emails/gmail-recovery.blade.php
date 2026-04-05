<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #0a0f0f; color: #e2e8f0; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 40px auto; background: #111a1a; border: 1px solid #1e3a3a; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0d9488, #6366f1); padding: 28px 32px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.1rem; color: white; letter-spacing: 2px; }
        .body { padding: 32px; text-align: center; }
        .code { font-size: 2.5rem; font-weight: 900; letter-spacing: 0.5rem; color: #0d9488; background: #0d1a1a; border: 2px solid #0d9488; border-radius: 12px; padding: 1rem 2rem; display: inline-block; margin: 1.5rem 0; font-family: 'Courier New', monospace; }
        .note { font-size: 0.8rem; color: #64748b; margin-top: 1rem; line-height: 1.6; }
        .warning { font-size: 0.75rem; color: #f59e0b; margin-top: 1rem; }
        .footer { padding: 16px 32px; border-top: 1px solid #1e3a3a; font-size: 0.7rem; color: #475569; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CÓDIGO DE RECUPERACIÓN</h1>
        </div>
        <div class="body">
            <p style="margin: 0 0 0.5rem; color: #94a3b8; font-size: 0.9rem;">Hola, <strong style="color: #e2e8f0;">{{ $userName }}</strong></p>
            <p style="margin: 0; color: #64748b; font-size: 0.85rem;">Tu código de recuperación es:</p>

            <div class="code">{{ $code }}</div>

            <p class="note">
                Este código es válido por <strong style="color: #e2e8f0;">10 minutos</strong>.<br>
                Si no solicitaste este código, ignora este mensaje.
            </p>
            <p class="warning">⚠️ No compartas este código con nadie.</p>
        </div>
        <div class="footer">
            {{ config('app.name') }} · {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
</body>
</html>
