self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Interceptar URL virtual de descarga: /p2p-download/{transferId}/{filename}?size={size}
    if (url.pathname.startsWith('/p2p-download/')) {
        const parts = url.pathname.split('/');
        const transferId = parts[2];
        const fileName = decodeURIComponent(parts[3] || 'descarga');
        const totalSize = url.searchParams.get('size');
        
        event.respondWith(handleDownloadStream(transferId, fileName, totalSize));
    }
});

async function handleDownloadStream(transferId, fileName, totalSize) {
    const dbName = `p2p-${transferId}`;
    
    const headers = new Headers({
        'Content-Type': 'application/octet-stream',
        'Content-Disposition': `attachment; filename="${fileName}"`,
    });
    
    if (totalSize) {
        headers.set('Content-Length', totalSize);
    }
    
    const stream = new ReadableStream({
        start(controller) {
            this.currentIndex = 0;
            this.dbName = dbName;
        },
        async pull(controller) {
            try {
                const db = await openDB(this.dbName);
                if (!db) {
                    controller.close();
                    return;
                }
                
                const record = await new Promise((resolve, reject) => {
                    const tx = db.transaction(['chunks'], 'readonly');
                    const store = tx.objectStore('chunks');
                    const req = store.get(this.currentIndex);
                    req.onsuccess = () => resolve(req.result);
                    req.onerror = () => reject(req.error);
                });
                
                db.close();
                
                if (record && record.blob) {
                    const buffer = await record.blob.arrayBuffer();
                    controller.enqueue(new Uint8Array(buffer));
                    this.currentIndex++;
                } else {
                    // Fin del stream, no hay más chunks
                    controller.close();
                    // Limpieza final
                    indexedDB.deleteDatabase(this.dbName);
                    console.log(`[SW] 🧹 Base de datos ${this.dbName} eliminada tras completar descarga`);
                }
            } catch (err) {
                console.error('[SW] Error en stream pull:', err);
                controller.error(err);
            }
        },
        cancel() {
            // Si el usuario cancela la descarga en el navegador
            console.log('[SW] 🛑 Descarga cancelada. Limpiando DB.');
            indexedDB.deleteDatabase(dbName);
        }
    });
    
    return new Response(stream, { headers });
}

function openDB(dbName) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, 1);
        
        request.onsuccess = (event) => {
            resolve(event.target.result);
        };
        
        request.onerror = (event) => {
            reject(event.target.error);
        };
        
        request.onupgradeneeded = (event) => {
            // Si llega aquí, significa que la base de datos no existía con ese nombre
            // abortamos la transacción para no crear una vacía en vano
            event.target.transaction.abort();
            resolve(null);
        };
    });
}
