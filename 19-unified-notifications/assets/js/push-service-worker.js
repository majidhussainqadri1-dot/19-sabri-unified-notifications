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
  // Treat provider payload links as untrusted: a URI can be HTML-safe but still navigate off-site.
  // The dedicated File 19 worker scope is nested under the WordPress home path, so ../notifications/
  // preserves installations whose WordPress public URL is not at the host root.
  const fallback = new URL('../notifications/', self.registration.scope).href;
  let target = fallback;
  try {
    const raw = String(event.notification.data?.url || fallback);
    // Reject ambiguous backslash forms before URL normalisation can silently rewrite them.
    if (raw.includes('\\\\')) throw new Error('ambiguous-push-url');
    const candidate = new URL(raw, self.location.origin);
    if ((candidate.protocol === 'https:' || candidate.protocol === 'http:') &&
        candidate.origin === self.location.origin) target = candidate.href;
  } catch (_) { /* Keep the safe local fallback for malformed provider data. */ }
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
    const existing = windows.find((client) => client.url === target);
    return existing ? existing.focus() : clients.openWindow(target);
  }));
});
