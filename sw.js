/* Service worker désactivé — se désinstalle lui-même et vide les caches
   des visiteurs qui avaient l'ancienne version installée (source du
   problème de contenu périmé en navigation normale, absent en privé). */

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    (async () => {
      const keys = await caches.keys();
      await Promise.all(keys.map(k => caches.delete(k)));
      await self.registration.unregister();
      const clientsList = await self.clients.matchAll({ type: 'window' });
      clientsList.forEach(client => client.navigate(client.url));
    })()
  );
});
