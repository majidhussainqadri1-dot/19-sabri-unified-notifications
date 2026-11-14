'use strict';
/**
 * V12.3 Web Push click-time URL allowlist regression.
 * The push payload can be untrusted even when a producer normally emits a safe wrapper.
 * This synthetic test never registers a worker and never opens an actual browser window.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname, '../19-unified-notifications/assets/js/push-service-worker.js'), 'utf8');

async function exercise(url, scope = 'https://sabrihomeopathy.com/sabri-notifications-push/') {
  const listeners = {};
  const visited = [];
  const worker = {
    location: { origin: 'https://sabrihomeopathy.com' },
    registration: { scope, showNotification: async () => {} },
    addEventListener: (name, cb) => { listeners[name] = cb; },
  };
  const clients = {
    matchAll: async () => [],
    openWindow: async (target) => { visited.push(target); return { focused: true }; },
  };
  vm.runInNewContext(code, { self: worker, clients, URL }, { filename: 'push-service-worker.js', timeout: 1000 });
  assert.ok(listeners.notificationclick, 'must register click handler');
  let job = null, closed = false;
  listeners.notificationclick({
    notification: { data: { url }, close: () => { closed = true; } },
    waitUntil: (promise) => { job = promise; },
  });
  assert.ok(job, 'worker should keep click task alive');
  await job;
  assert.equal(closed, true, 'click must close notification');
  assert.equal(visited.length, 1, 'one navigation at most');
  return visited[0];
}

(async () => {
  let assertions = 0;
  for (const unsafe of [
    'javascript:alert(1)',
    'data:text/html,<h1>malicious</h1>',
    'https://evil.example/private',
    '//evil.example/private',
    'http://sabrihomeopathy.com/insecure',
    'https://sabrihomeopathy.com.evil.example/private',
    '\\evil.example\\private',
  ]) {
    const visited = await exercise(unsafe);
    assert.equal(visited, 'https://sabrihomeopathy.com/notifications/', 'unsafe click must use in-site fallback: ' + unsafe);
    ++assertions;
  }
  for (const safe of ['/notifications/open/1234', 'https://sabrihomeopathy.com/notifications/open/1234']) {
    const visited = await exercise(safe);
    assert.equal(visited, 'https://sabrihomeopathy.com/notifications/open/1234', 'same-origin route must remain intact');
    ++assertions;
  }
  const subdirectory = await exercise('javascript:bad()', 'https://sabrihomeopathy.com/community/sabri-notifications-push/');
  assert.equal(subdirectory, 'https://sabrihomeopathy.com/community/notifications/', 'fallback must preserve WordPress subdirectory');
  ++assertions;
  console.log('PASS: ' + assertions + ' V12.3 Web Push click URL safety assertions; no external navigation performed');
})().catch((error) => { console.error(error); process.exitCode = 1; });
