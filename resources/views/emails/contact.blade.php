<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #0a0f0f; color: #e2e8f0; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #111a1a; border: 1px solid #1e3a3a; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0d9488, #6366f1); padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 1.25rem; color: white; letter-spacing: 2px; }
        .body { padding: 28px 32px; }
        .field { margin-bottom: 20px; }
        .label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .value { font-size: 0.95rem; color: #e2e8f0; background: #0d1a1a; border: 1px solid #1e3a3a; border-radius: 8px; padding: 12px 16px; line-height: 1.6; }
        .footer { padding: 16px 32px; border-top: 1px solid #1e3a3a; font-size: 0.75rem; color: #475569; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NUEVO MENSAJE DE CONTACTO</h1>
        </div>
        <div class="body">
            <div class="field">
                <div class="label">Nombre</div>
                <div class="value">{{ $senderName }}</div>
            </div>
            <div class="field">
                <div class="label">Correo</div>
                <div class="value">{{ $senderEmail }}</div>
            </div>
            <div class="field">
                <div class="label">Mensaje</div>
                <div class="value">{{ $message }}</div>
            </div>
        </div>
        <div class="footer">
            {{ config('app.name') }} · {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
</body>
</html>
