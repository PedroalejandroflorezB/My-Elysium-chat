/**
 * P2PFileSaver - Guardado directo a disco o fallback a memoria
 * ✅ File System Access API (Chrome/Edge/Chromium)
 * ✅ Fallback a IndexedDB para archivos > 500MB
 * ✅ Fallback a memoria para archivos pequeños (Safari/Firefox/móvil)
 */
export class P2PFileSaver {
    constructor(filename, totalSize, mimeType) {
        this.filename = filename;
        this.totalSize = totalSize;
        this.mimeType = mimeType || 'application/octet-stream';
        this.writer = null;
        this.memoryChunks = [];
        this.useDisk = false;
        this.useIndexedDB = false;
        this.db = null;
        this.bytesWritten = 0;
    }

    async initialize() {
        // Opción 1: File System Access API (Chrome/Edge desktop y Android Chromium)
        if ('showSaveFilePicker' in window) {
            try {
                this.fileHandle = await window.showSaveFilePicker({
                    suggestedName: this.filename
                });
                this.writer = await this.fileHandle.createWritable();
                this.useDisk = true;
                console.log('[P2PFileSaver] ✅ Guardado directo a disco activado');
                return 'disk';
            } catch (err) {
                if (err.name === 'AbortError') {
                    throw new Error('Usuario canceló el guardado');
                }
                console.warn('[P2PFileSaver] ⚠️ File System API falló, usando fallback:', err.message);
            }
        }

        // Opción 2: IndexedDB para archivos grandes (> 500MB)
        if (this.totalSize > 500 * 1024 * 1024) {
            try {
                await this._initIndexedDB();
                this.useIndexedDB = true;
                console.log('[P2PFileSaver] ✅ IndexedDB activado para archivo grande');
                return 'indexeddb';
            } catch (err) {
                console.warn('[P2PFileSaver] ⚠️ IndexedDB falló, usando memoria:', err.message);
            }
        }

        // Opción 3: Memoria (Safari, Firefox, archivos pequeños)
        console.log('[P2PFileSaver] ⚠️ Usando memoria (fallback)');
        return 'memory';
    }

    async appendChunk(index, arrayBuffer) {
        this.bytesWritten += arrayBuffer.byteLength;

        if (this.useDisk && this.writer) {
            // Escribir directo a disco — RAM: ~0 bytes
            await this.writer.write(arrayBuffer);
        } else if (this.useIndexedDB && this.db) {
            await this._saveToIndexedDB(index, arrayBuffer);
        } else {
            // Guardar en array de memoria
            this.memoryChunks[index] = arrayBuffer;
        }
    }

    async finalize() {
        if (this.useDisk && this.writer) {
            await this.writer.close();
            console.log(`[P2PFileSaver] 💾 Guardado en disco: ${this.filename}`);
            return;
        }

        if (this.useIndexedDB && this.db) {
            await this._assembleFromIndexedDB();
            return;
        }

        // Ensamblar desde memoria
        const validChunks = this.memoryChunks.filter(c => c !== undefined);
        const blob = new Blob(validChunks, { type: this.mimeType });
        this.memoryChunks = []; // Liberar RAM inmediatamente
        this._downloadBlob(blob);
        console.log(`[P2PFileSaver] ⚠️ Descargado vía memoria: ${this.filename}`);
    }

    _downloadBlob(blob) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = this.filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    _initIndexedDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(`p2p-saver-${Date.now()}`, 1);
            req.onupgradeneeded = e => {
                e.target.result.createObjectStore('chunks', { keyPath: 'index' });
            };
            req.onsuccess = e => { this.db = e.target.result; resolve(); };
            req.onerror = e => reject(e.target.error);
        });
    }

    _saveToIndexedDB(index, arrayBuffer) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['chunks'], 'readwrite');
            tx.objectStore('chunks').put({ index, data: arrayBuffer });
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
    }

    async _assembleFromIndexedDB() {
        const tx = this.db.transaction(['chunks'], 'readonly');
        const store = tx.objectStore('chunks');
        const all = await new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });

        all.sort((a, b) => a.index - b.index);
        const blob = new Blob(all.map(c => c.data), { type: this.mimeType });
        this._downloadBlob(blob);

        // Limpiar IndexedDB
        this.db.close();
        indexedDB.deleteDatabase(this.db.name);
        console.log(`[P2PFileSaver] 💾 Ensamblado desde IndexedDB: ${this.filename}`);
    }
}

export default P2PFileSaver;
