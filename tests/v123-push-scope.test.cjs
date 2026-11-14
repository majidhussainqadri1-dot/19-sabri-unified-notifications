'use strict';
/**
 * Cross-file V12.3 regression: File 20 owns the root PWA worker.
 * File 19 push registration must use a separate non-navigation scope.
 * Browser simulation uses only local mocks; no actual push sends or registrations.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const script = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/assets/js/notifications.js'), 'utf8');
const renderer = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/includes/class-sun-renderer.php'), 'utf8');
const handlers = {};
const registrations = [];
const devicePosts = [];
const button = { disabled: false, textContent: '' };
const announcement = { textContent: '' };
const document = {
  hidden: false,
  addEventListener: (name, callback) => (handlers[name] ||= []).push(callback),
  querySelector: () => null,
  querySelectorAll: (name) => name === '.sun-live-region[data-sun-status]' ? [announcement] : [],
};
const window = {
  SUNNotifications: {
    restUrl: '/wp-json/sabri-notifications/v1/',
    nonce: 'test-nonce',
    pushPublicKey: 'AQ',
    workerUrl: '/sabri-notifications-service-worker.js',
    pushWorkerScope: '/sabri-notifications-push/',
    homeUrl: '/',
    i18n: { saved: 'SAVED_LOCALE', pushEnabled: 'PUSH_ENABLED_LOCALE' },
  },
  location: { origin: 'https://sabrihomeopathy.com', href: 'https://sabrihomeopathy.com/notifications/' },
  PushManager: function() {},
  setInterval: () => { throw Error('Unexpected timer'); },
};
const navigator = { serviceWorker: {
  register: async (url, options) => {
    registrations.push({ url, scope: options.scope });
    return { pushManager: {
      subscribe: async () => ({ toJSON: () => ({ endpoint: 'synthetic-never-sent', keys: {} }) }),
    }};
  },
}};
const fetch = async (url, options) => {
  if (url.endsWith('devices')) devicePosts.push(options.body);
  return { ok: true, json: async () => ({ success: true }) };
};
const Notification = { requestPermission: async () => 'granted' };
vm.runInNewContext(script, { window, document, navigator, fetch, Notification, URL, Date, Uint8Array,
  Number, String, Math, Boolean, atob: () => '\x01', FormData: function() {} },
{ filename: 'notifications.js', timeout: 3000 });

(async function main() {
  assert.ok(handlers.click?.length, 'click handlers registered');
  const event = { target: { closest: (selector) => selector === '[data-sun-enable-push]' ? button : null } };
  for (const callback of handlers.click) await callback(event);
  assert.equal(registrations.length, 1, 'exactly one subscription worker registration');
  assert.equal(registrations[0].url, '/sabri-notifications-service-worker.js');
  assert.equal(registrations[0].scope, '/sabri-notifications-push/', 'File 19 cannot replace the File 20 root PWA worker');
  assert.equal(devicePosts.length, 1, 'push device registration still functions');
  assert.equal(button.textContent, 'PUSH_ENABLED_LOCALE', 'localized success status preserved');
  assert.equal(announcement.textContent, 'SAVED_LOCALE', 'localized accessible announcement preserved');
  assert.ok(renderer.includes("'pushWorkerScope'=>esc_url_raw(home_url('/sabri-notifications-push/'))"), 'WordPress must publish its own home_url-aware scoped worker path');
  assert.ok(!script.includes("register(cfg.workerUrl,{scope:'/'})"), 'legacy root service-worker registration must be absent');
  console.log('PASS: 7 V12.3 isolated push scope + retained subscription assertions; NO real notifications sent');
})().catch((error) => { console.error(error); process.exitCode = 1; });
