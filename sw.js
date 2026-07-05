const CACHE_NAME = 'density-altitude-v1';

// Core local assets and the external dependencies used by index.html.
const PRECACHE_URLS = [
  './index.html',
  './manifest.json',
  './icon-192.png',
  './icon-512.png',
  'https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.24.0/babel.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss-browser/4.1.13/index.global.js',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap',
  'https://xaxero.com/dlog/tracker.js',
  'https://xaxero.com/images/XaxeroMainLogoFinal.png'
];

// Try to cache a URL with a CORS request first, then fall back to no-cors
// so cross-origin resources without CORS headers (e.g. some images/scripts)
// can still be stored as opaque responses.
async function addToCache(cache, url) {
  try {
    await cache.add(url);
  } catch (err) {
    try {
      const response = await fetch(url, { mode: 'no-cors', credentials: 'omit' });
      if (response) {
        await cache.put(url, response);
      }
    } catch (err2) {
      console.warn('[SW] Failed to precache:', url, err2.message);
    }
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => Promise.all(PRECACHE_URLS.map((url) => addToCache(cache, url))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Skip non-GET requests.
  if (request.method !== 'GET') return;

  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      const fetchAndCache = async () => {
        try {
          const networkResponse = await fetch(request);
          if (networkResponse && (networkResponse.ok || networkResponse.type === 'opaque')) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
          }
          return networkResponse;
        } catch (err) {
          // Network failed; return cached response if we have one.
          if (cachedResponse) return cachedResponse;
          throw err;
        }
      };

      return cachedResponse || fetchAndCache();
    })
  );
});
