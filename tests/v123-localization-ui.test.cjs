'use strict';
/**
 * File 19 V12.3 negative/positive browser-side regression.
 * Runs in Node without WordPress, browser permissions, credentials, network or user data.
 * This tests the actual browser JavaScript through a minimal DOM/fetch harness.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/assets/js/notifications.js'), 'utf8');
const localized = {
  pin: 'PIN_LOCALE',
  unpin: 'UNPIN_LOCALE',
  snoozeOneHour: 'SNOOZE_LOCALE',
  needsAction: 'NEEDS_ACTION_LOCALE',
  done: 'DONE_LOCALE',
  whyButton: 'WHY_LOCALE',
  noMatching: 'EMPTY_LOCALE',
  source: 'SOURCE_LOCALE',
  verified: 'VERIFIED_LOCALE',
  matchedPolicy: 'MATCHED_LOCALE',
  pushUnsupported: 'PUSH_UNSUPPORTED_LOCALE',
  pushNotConfigured: 'PUSH_NOT_CONFIGURED_LOCALE',
  pushDenied: 'PUSH_DENIED_LOCALE',
  pushEnabled: 'PUSH_ENABLED_LOCALE',
  error: 'ERROR_LOCALE',
  saved: 'SAVED_LOCALE',
  removed: 'REMOVED_LOCALE',
};

function loadApp({ items = [], why = null } = {}) {
  const list = { innerHTML: '' };
  const announcement = { textContent: '' };
  const callbacks = {};
  const urls = [];
  const document = {
    hidden: false,
    referrer: '',
    querySelector: (query) => query === '[data-sun-list]' ? list : null,
    querySelectorAll: (query) => query === '.sun-live-region[data-sun-status]' ? [announcement] : [],
    addEventListener: (kind, handler) => (callbacks[kind] ||= []).push(handler),
  };
  const window = {
    SUNNotifications: { restUrl: '/wp-json/sabri-notifications/v1/', homeUrl: '/', i18n: localized },
    location: { href: 'https://sabrihomeopathy.com/notifications/', origin: 'https://sabrihomeopathy.com' },
    history: { length: 1 },
    setInterval: () => { throw Error('Unexpected timer'); },
  };
  const fetch = async (url) => {
    urls.push(url);
    return { ok: true, json: async () => url.includes('/why') ? (why || {}) : ({ items }) };
  };
  vm.runInNewContext(source, {
    window, document, fetch, URL, Date, Number, Math, String, Boolean,
    navigator: {}, Notification: {}, Uint8Array,
    atob: () => '', FormData: function() {},
  }, { filename: 'notifications.js', timeout: 3000 });
  assert.ok(callbacks.click?.length, 'click handlers must be registered');
  const trigger = async (matchedSelector, element) => {
    const event = {
      target: { closest: (selector) => selector === matchedSelector ? element : null },
    };
    for (const handler of callbacks.click) await handler(event);
  };
  return { list, announcement, trigger, urls };
}

const sample = (overrides = {}) => ({
  status: 'unread',
  category: 'learning',
  public_id: '12345678-1234-1234-1234-123456789012',
  open_url: '/notifications/open/12345678-1234-1234-1234-123456789012',
  title: 'Sample',
  summary: 'Generic safe summary',
  pinned: false,
  action_state: 'needs_action',
  ...overrides,
});

(async function main() {
  let count = 0;
  const check = (ok, message) => { assert.ok(ok, message); count += 1; };
  {
    const app = loadApp({ items: [sample()] });
    await app.trigger('[data-sun-smart-filter]', { dataset: { sunSmartFilter: 'priority' } });
    for (const key of ['pin', 'snoozeOneHour', 'done', 'whyButton']) {
      check(app.list.innerHTML.includes(localized[key]), 'dynamic notification control not localized: ' + key);
    }
    check(app.list.innerHTML.includes('href="https://sabrihomeopathy.com/notifications/open/'), 'same-origin notification URL must remain usable');
  }
  {
    const app = loadApp({ items: [sample({ pinned: true, action_state: 'done' })] });
    await app.trigger('[data-sun-smart-filter]', { dataset: { sunSmartFilter: 'priority' } });
    for (const key of ['unpin', 'needsAction']) check(app.list.innerHTML.includes(localized[key]), 'dynamic state not localized: ' + key);
  }
  {
    const app = loadApp({ items: [] });
    await app.trigger('[data-sun-smart-filter]', { dataset: { sunSmartFilter: 'priority' } });
    check(app.list.innerHTML.includes(localized.noMatching), 'empty-state text must be localized');
  }
  for (const open_url of ['javascript:alert(1)', 'https://evil.example/steal', '//evil.example/steal', 'data:text/html,attack']) {
    const app = loadApp({ items: [sample({ open_url })] });
    await app.trigger('[data-sun-smart-filter]', { dataset: { sunSmartFilter: 'priority' } });
    check(!/href="(?:javascript:|data:|https:\/\/evil|\/\/evil)/i.test(app.list.innerHTML), 'unsafe notification URL accepted: ' + open_url);
  }
  {
    const app = loadApp({ items: [], why: { attention_reason: '', source: { verified: true, label: 'Research' } } });
    await app.trigger('[data-sun-why]', { disabled: false, closest: () => ({ dataset: { sunId: sample().public_id } }) });
    check(app.announcement.textContent.includes(localized.source), 'source explanation label not localized');
    check(app.announcement.textContent.includes(localized.verified), 'verified-source text not localized');
    check(app.announcement.textContent.includes(localized.matchedPolicy), 'fallback explanation not localized');
  }
  const php = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/includes/class-sun-renderer.php'), 'utf8');
  for (const key of Object.keys(localized)) {
    check(new RegExp("'" + key + "'\\s*=>").test(php), 'PHP-to-JS i18n dictionary missing: ' + key);
  }
  const css = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/assets/css/notifications.css'), 'utf8');
  check(/--sun-green:var\\(--sabri-color-primary,#087A4E\\)/.test(css), 'File 19 primary fallback must match V12.3 while leaving File 25 as owner');
  console.log('PASS: ' + count + ' V12.3 dynamic localization and safe-link assertions');
})().catch((error) => { console.error(error); process.exitCode = 1; });
