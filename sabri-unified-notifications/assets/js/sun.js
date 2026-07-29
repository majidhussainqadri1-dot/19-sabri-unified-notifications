(() => {
  'use strict';
  const C = window.SUN_CONFIG || {};
  if (!C.isLoggedIn || !C.restUrl) return;

  const state = { unread: 0, latestId: 0, initialized: false, appPage: 1, category: 'all', busy: false, prefs: null };
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];

  async function api(path, options = {}) {
    const res = await fetch(C.restUrl + path, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': C.nonce, 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
      body: options.body && typeof options.body !== 'string' ? JSON.stringify(options.body) : options.body
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || C.strings?.error || 'Request failed.');
    return data;
  }

  function el(tag, cls, text) {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (text !== undefined && text !== null) node.textContent = String(text);
    return node;
  }

  function notificationNode(n, compact = false) {
    const item = el('article', `sun-notification ${n.isRead ? 'is-read' : 'is-unread'} priority-${n.priority || 'normal'}`);
    item.dataset.id = n.id;
    item.tabIndex = 0;
    item.setAttribute('role', 'link');
    const icon = el('div', 'sun-notification-icon', n.icon || '🔔');
    if (n.actor?.avatar) {
      const img = document.createElement('img'); img.src = n.actor.avatar; img.alt = ''; img.loading = 'lazy'; icon.textContent = ''; icon.append(img);
    }
    const content = el('div', 'sun-notification-content');
    const head = el('div', 'sun-notification-head');
    const title = el('strong', '', n.title || 'Notification');
    const time = el('time', '', n.relativeTime || '');
    head.append(title, time);
    content.append(head);
    if (n.body) content.append(el('p', '', compact ? truncate(n.body, 110) : n.body));
    const meta = el('div', 'sun-notification-meta');
    meta.append(el('span', `sun-category-pill category-${n.category}`, labelFor(n.category)));
    if ((n.groupCount || 1) > 1) meta.append(el('span', 'sun-group-count', `${n.groupCount} updates`));
    if (n.priority === 'critical' || n.priority === 'high') meta.append(el('span', `sun-priority-label ${n.priority}`, n.priority));
    content.append(meta);
    const dot = el('span', 'sun-unread-dot');
    item.append(icon, content, dot);
    const open = async () => {
      if (!n.isRead) await markRead([n.id]).catch(() => {});
      if (n.link) window.location.href = n.link;
    };
    item.addEventListener('click', open);
    item.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    return item;
  }

  function truncate(s, n) { return s.length > n ? s.slice(0, n - 1) + '…' : s; }
  function labelFor(category) { return C.categories?.[category] || category || 'System'; }

  function updateCounts(unread) {
    state.unread = Number(unread || 0);
    qsa('[data-sun-count]').forEach(node => {
      node.textContent = state.unread > 99 ? '99+' : String(state.unread);
      node.hidden = state.unread < 1;
    });
    qsa('[data-sun-menu-count]').forEach(node => node.textContent = state.unread ? `(${state.unread})` : '');
    qsa('[data-sun-filter-count="unread"]').forEach(node => node.textContent = state.unread);
    document.title = state.unread ? `(${state.unread}) ${document.title.replace(/^\(\d+\+?\)\s*/, '')}` : document.title.replace(/^\(\d+\+?\)\s*/, '');
  }

  async function loadBells() {
    const data = await api('notifications?per_page=8');
    state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
    updateCounts(data.unread);
    qsa('[data-sun-mini-list]').forEach(list => {
      list.textContent = '';
      if (!data.notifications?.length) list.append(el('div', 'sun-empty-mini', C.strings?.empty || 'No notifications yet.'));
      else data.notifications.forEach(n => list.append(notificationNode(n, true)));
    });
  }

  async function loadApp(reset = true) {
    const app = qs('[data-sun-app]');
    if (!app || state.busy) return;
    state.busy = true;
    if (reset) state.appPage = 1;
    const list = qs('[data-sun-list]', app);
    if (reset) { list.textContent = ''; list.append(el('div', 'sun-loading-card', 'Loading notifications…')); }
    try {
      const params = new URLSearchParams({ per_page: '30', page: String(state.appPage) });
      if (state.category === 'unread') params.set('unread', 'true');
      else if (state.category !== 'all') params.set('category', state.category);
      const data = await api('notifications?' + params.toString());
      state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
      updateCounts(data.unread);
      if (reset) list.textContent = '';
      if (!data.notifications?.length && reset) list.append(emptyState());
      else data.notifications.forEach(n => list.append(notificationNode(n)));
      const loadMore = qs('[data-sun-load-more]', app);
      loadMore.hidden = !data.notifications || data.notifications.length < 30;
      qs('[data-sun-summary]', app).textContent = `${data.unread} unread notification${data.unread === 1 ? '' : 's'}`;
      qsa('[data-sun-filter-count="all"]', app).forEach(n => n.textContent = data.notifications?.length || 0);
    } catch (err) {
      if (reset) { list.textContent = ''; list.append(el('div', 'sun-error-card', err.message)); }
    } finally { state.busy = false; }
  }

  function emptyState() {
    const box = el('div', 'sun-empty-state');
    box.append(el('div', 'sun-empty-icon', '✓'), el('h3', '', 'You are all caught up'), el('p', '', 'New messages and important activity will appear here.'));
    return box;
  }

  async function markRead(ids = [], all = false) {
    await api('notifications/read', { method: 'POST', body: { ids, all } });
    if (all) {
      qsa('.sun-notification.is-unread').forEach(n => { n.classList.remove('is-unread'); n.classList.add('is-read'); });
      updateCounts(0);
    } else {
      ids.forEach(id => {
        qsa(`.sun-notification[data-id="${id}"]`).forEach(n => { n.classList.remove('is-unread'); n.classList.add('is-read'); });
      });
      updateCounts(Math.max(0, state.unread - ids.length));
    }
  }

  async function poll() {
    try {
      const after = state.initialized ? state.latestId : 0;
      const data = await api(`notifications?per_page=20${after ? `&after_id=${after}` : ''}`);
      updateCounts(data.unread);
      if (state.initialized && data.notifications?.length) {
        [...data.notifications].reverse().forEach(n => announce(n));
        await loadBells();
        if (qs('[data-sun-app]')) loadApp(true);
      }
      state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
      state.initialized = true;
    } catch (_) {}
  }

  function announce(n) {
    showToast(n);
    if (state.prefs?.sound_enabled !== false) beep();
    if (C.browserAlerts && state.prefs?.browser_enabled !== false && 'Notification' in window && Notification.permission === 'granted' && document.hidden) {
      const notification = new Notification(n.title || C.siteName, { body: truncate(n.body || '', 180), icon: n.actor?.avatar || undefined, tag: `sun-${n.id}` });
      notification.onclick = () => { window.focus(); if (n.link) window.location.href = n.link; notification.close(); };
    }
  }

  function showToast(n) {
    const region = qs('[data-sun-toasts]') || createGlobalToastRegion();
    const toast = el('button', `sun-toast priority-${n.priority || 'normal'}`);
    toast.type = 'button';
    toast.append(el('span', 'sun-toast-icon', n.icon || '🔔'));
    const text = el('span', 'sun-toast-text'); text.append(el('strong', '', n.title), el('small', '', truncate(n.body || '', 100))); toast.append(text);
    toast.addEventListener('click', async () => { await markRead([n.id]).catch(() => {}); if (n.link) window.location.href = n.link; });
    region.append(toast); setTimeout(() => toast.classList.add('is-visible'), 20); setTimeout(() => { toast.classList.remove('is-visible'); setTimeout(() => toast.remove(), 300); }, 7000);
  }

  function createGlobalToastRegion() { const d = el('div', 'sun-toast-region'); d.dataset.sunToasts = ''; d.setAttribute('aria-live', 'polite'); document.body.append(d); return d; }
  function beep() {
    try { const A = window.AudioContext || window.webkitAudioContext; if (!A) return; const ctx = new A(); const osc = ctx.createOscillator(); const gain = ctx.createGain(); osc.frequency.value = 660; gain.gain.setValueAtTime(0.04, ctx.currentTime); gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18); osc.connect(gain).connect(ctx.destination); osc.start(); osc.stop(ctx.currentTime + 0.18); } catch (_) {}
  }

  function initBells() {
    qsa('[data-sun-bell]').forEach(bell => {
      const button = qs('.sun-bell-button', bell), panel = qs('.sun-bell-panel', bell);
      button.addEventListener('click', async e => {
        e.stopPropagation(); const open = panel.hidden; qsa('.sun-bell-panel').forEach(p => p.hidden = true); panel.hidden = !open; button.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open) await loadBells().catch(() => {});
      });
      qs('[data-sun-close]', bell)?.addEventListener('click', () => { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); });
    });
    document.addEventListener('click', e => { if (!e.target.closest('[data-sun-bell]')) qsa('.sun-bell-panel').forEach(p => p.hidden = true); });
  }

  function initApp() {
    const app = qs('[data-sun-app]'); if (!app) return;
    qsa('[data-sun-category]', app).forEach(button => button.addEventListener('click', () => {
      qsa('[data-sun-category]', app).forEach(b => b.classList.remove('is-active')); button.classList.add('is-active'); state.category = button.dataset.sunCategory; qs('[data-sun-heading]', app).textContent = button.querySelector('span')?.textContent || 'Notifications'; loadApp(true);
    }));
    qs('[data-sun-load-more]', app)?.addEventListener('click', () => { state.appPage += 1; loadApp(false); });
    qs('[data-sun-refresh]', app)?.addEventListener('click', () => loadApp(true));
    qsa('[data-sun-mark-all]').forEach(button => button.addEventListener('click', async () => { button.disabled = true; await markRead([], true).catch(() => {}); await loadBells().catch(() => {}); await loadApp(true); button.disabled = false; }));
    qsa('[data-sun-browser-enable]').forEach(button => button.addEventListener('click', requestBrowserPermission));
    initSettings(app); loadApp(true);
  }

  async function requestBrowserPermission() {
    if (!('Notification' in window)) return showSimpleToast('Browser alerts are not supported by this browser.');
    const result = await Notification.requestPermission();
    showSimpleToast(result === 'granted' ? 'Browser alerts are enabled.' : (C.strings?.browserBlocked || 'Browser alerts were not enabled.'));
  }
  function showSimpleToast(text) { showToast({ id: 0, title: text, body: '', icon: '🔔', priority: 'normal' }); }

  function initSettings(app) {
    const dialog = qs('[data-sun-settings]', app); if (!dialog) return;
    qsa('[data-sun-settings-open]', app).forEach(b => b.addEventListener('click', async () => { await loadPreferences(dialog); dialog.showModal ? dialog.showModal() : dialog.setAttribute('open', ''); }));
    qsa('[data-sun-settings-close]', dialog).forEach(b => b.addEventListener('click', () => dialog.close ? dialog.close() : dialog.removeAttribute('open')));
    qs('[data-sun-settings-save]', dialog)?.addEventListener('click', async e => {
      e.currentTarget.disabled = true; const payload = collectPreferences(dialog); const data = await api('preferences', { method: 'POST', body: payload }); state.prefs = data.preferences; e.currentTarget.disabled = false; dialog.close ? dialog.close() : dialog.removeAttribute('open'); showSimpleToast('Notification settings saved.');
    });
  }

  async function loadPreferences(dialog) {
    const data = await api('preferences'); state.prefs = data.preferences || {};
    qsa('[data-pref]', dialog).forEach(input => { const key = input.dataset.pref; if (input.type === 'checkbox') input.checked = !!state.prefs[key]; else input.value = state.prefs[key] ?? ''; });
    qsa('[data-pref-category]', dialog).forEach(input => input.checked = state.prefs.categories?.[input.dataset.prefCategory] !== false);
  }
  function collectPreferences(dialog) {
    const out = { categories: {} };
    qsa('[data-pref]', dialog).forEach(input => out[input.dataset.pref] = input.type === 'checkbox' ? input.checked : input.value);
    qsa('[data-pref-category]', dialog).forEach(input => out.categories[input.dataset.prefCategory] = input.checked);
    return out;
  }

  async function bootstrap() {
    initBells(); initApp();
    try { const p = await api('preferences'); state.prefs = p.preferences; } catch (_) {}
    await loadBells().catch(() => {});
    await poll();
    setInterval(poll, Math.max(5, Number(C.pollSeconds || 8)) * 1000);
  }
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', bootstrap) : bootstrap();
})();
