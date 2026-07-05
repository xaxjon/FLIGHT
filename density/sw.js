const CACHE_NAME = 'density-alt-v1';

// Local app shell and icons.
const LOCAL_ASSETS = [
  './index.html',
  './manifest.json',
  './icon-192.png',
  './icon-512.png'
];

// External dependencies used by the Density Altitude app.
const CDN_ASSETS = [
  'https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.24.0/babel.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss-browser/4.1.13/index.global.js',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap',
  'https://xaxero.com/dlog/tracker.js',
  'https://xaxero.com/images/XaxeroMainLogoFinal.png'
];

const PRECACHE_URLS = [...LOCAL_ASSETS, ...CDN_ASSETS];

// Try a normal CORS cache.add first, then fall back to no-cors fetch+put
// for resources that don't send CORS headers.
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
          if (cachedResponse) return cachedResponse;
          throw err;
        }
      };

      return cachedResponse || fetchAndCache();
    })
  );
});
