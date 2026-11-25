// Service worker: hanya menyimpan aset statis (CSS/JS/font/ikon).
// Halaman aplikasi (yang berisi data pasien) TIDAK pernah disimpan di cache perangkat.
const CACHE_NAME = 'binatama-care-v1';
const STATIC_ASSETS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
];

self.addEventListener('install', event => {
    event.waitUntil(
        // Gagal mengunduh satu aset tidak boleh membatalkan pemasangan
        caches.open(CACHE_NAME).then(cache =>
            Promise.allSettled(STATIC_ASSETS.map(url => cache.add(url)))
        )
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const req = event.request;
    if (req.method !== 'GET') return;

    // Hanya aset statis; permintaan halaman (navigate) dibiarkan langsung ke jaringan
    const isStatic = ['style', 'script', 'font', 'image'].includes(req.destination);
    if (!isStatic) return;

    event.respondWith(
        fetch(req)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
                }
                return response;
            })
            .catch(() => caches.match(req))
    );
});
