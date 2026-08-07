'use strict';
self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (_) { data = {}; }
  const title = data.title || 'New private update';
  const options = { body: data.body || 'Sign in to review it securely.', tag: data.tag || 'sun-private-update', data: { url: data.url || '/notifications/' }, icon: data.icon || undefined, badge: data.badge || undefined };
  event.waitUntil(self.registration.showNotification(title, options));
});
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL(event.notification.data?.url || '/notifications/', self.location.origin).href;
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
    const existing = windows.find((client) => client.url === target);
    return existing ? existing.focus() : clients.openWindow(target);
  }));
});
