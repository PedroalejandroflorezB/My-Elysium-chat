{{-- Modal QR --}}
<div id="modal-qr-generate"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,0.75);z-index:9999;
            align-items:center;justify-content:center;">
    <div style="background:var(--bg-secondary,#111);border:1px solid var(--border-color,#333);
                border-radius:16px;padding:1.5rem;width:300px;position:relative;
                margin:auto;text-align:center;">

        <button onclick="document.getElementById('modal-qr-generate').style.display='none';document.body.style.overflow='';"
                style="position:absolute;top:0.75rem;right:0.75rem;background:none;border:none;
                       color:var(--text-muted,#888);font-size:1.1rem;cursor:pointer;">&#x2715;</button>

        <h2 style="margin:0 0 1rem;font-size:1rem;font-weight:700;color:var(--text-primary,#fff);">
            Mi Código QR
        </h2>

        <div id="qr-code-display"
             style="width:220px;height:220px;margin:0 auto;background:#fff;
                    border-radius:10px;display:flex;align-items:center;
                    justify-content:center;overflow:hidden;padding:8px;
                    box-sizing:border-box;">
            <p style="color:#999;font-size:0.8rem;">Generando...</p>
        </div>

        <p style="margin:0.75rem 0 1rem;color:var(--text-muted,#888);font-size:0.75rem;">
            Escanea para agregarme como contacto
        </p>

        <div style="display:flex;gap:0.75rem;">
            <button onclick="document.getElementById('modal-qr-generate').style.display='none';document.body.style.overflow='';"
                    style="flex:1;padding:0.65rem;border-radius:8px;
                           border:1px solid var(--border-color,#333);
                           background:none;color:var(--text-primary,#fff);
                           cursor:pointer;font-size:0.85rem;">
                Cerrar
            </button>
            <button onclick="window.copyQRLink()"
                    style="flex:1;padding:0.65rem;border-radius:8px;border:none;
                           background:var(--primary,#a855f7);color:#fff;
                           cursor:pointer;font-size:0.85rem;font-weight:600;">
                Copiar enlace
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var QR_URL = '{{ url("/add/" . (auth()->user()->username ?? "usuario")) }}';

    function waitForQRCode(cb, n) {
        n = n || 0;
        if (window.QRCode && typeof window.QRCode.toDataURL === 'function') {
            cb(window.QRCode);
        } else if (n < 50) {
            setTimeout(function () { waitForQRCode(cb, n + 1); }, 100);
        } else {
            cb(null);
        }
    }

    window.generateQR = function () {
        var modal = document.getElementById('modal-qr-generate');
        if (!modal) { console.error('[QR] modal no encontrado'); return; }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        var display = document.getElementById('qr-code-display');
        display.innerHTML = '<p style="color:#999;font-size:0.8rem;">Generando...</p>';

        waitForQRCode(function (lib) {
            if (!lib) {
                display.innerHTML = '<p style="word-break:break-all;font-size:0.7rem;color:#a855f7;padding:0.5rem;">' + QR_URL + '</p>';
                return;
            }
            lib.toDataURL(QR_URL, { width: 204, margin: 1, errorCorrectionLevel: 'M' }, function (err, url) {
                if (err) { display.innerHTML = '<p style="color:red;font-size:0.8rem;">Error</p>'; return; }
                display.innerHTML = '<img src="' + url + '" style="width:204px;height:204px;display:block;flex-shrink:0;">';
                window._qrUrl = QR_URL;
            });
        });
    };

    window.copyQRLink = function () {
        var url = window._qrUrl || QR_URL;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                if (typeof showToast === 'function') showToast('Copiado', 'Enlace copiado', 'success');
            });
        } else {
            prompt('Copia este enlace:', url);
        }
    };
}());
</script>
