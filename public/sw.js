// Minimal service worker for PWA "Add to Home Screen" support
self.addEventListener('install', (event) => {
    // Skip waiting to activate immediately
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Take control of all pages immediately
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // This is a network-first/bypass service worker
    // It exists primarily to satisfy Chrome's PWA criteria
});
