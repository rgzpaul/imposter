self.addEventListener('install', (event) => {
  console.log('Service Worker installed.');
});

self.addEventListener('activate', (event) => {
  console.log('Service Worker activated.');
});

self.addEventListener('fetch', (event) => {
  // This fetch event listener is required for the PWA to be recognized, but we don't do anything with the requests.
  console.log('Fetching:', event.request.url);
});
