(() => {
  'use strict';
  const C = window.SUN_CONFIG || {};
  if (!C.isLoggedIn || !C.restUrl) return;

  const state = {
    unread: 0,
    latestId: 0,
    initialized: false,
    appPage: 1,
    category: 'all',
    archived: false,
    busy: false,
    prefs: null,
    lastFocused: null,
    timer: null,
    lastHiddenPoll: 0
  };

  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  const focusable = root => qsa('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])', root);

  async function api(path, options = {}) {
    const response = await fetch(C.restUrl + path, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': C.nonce, 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
      body: options.body && typeof options.body !== 'string' ? JSON.stringify(options.body) : options.body
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.message || C.strings?.error || 'Request failed.');
    return data;
  }

  function el(tag, className = '', text = null) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== null && text !== undefined) node.textContent = String(text);
    return node;
  }

  function truncate(value, length) {
    const text = String(value || '');
    return text.length > length ? `${text.slice(0, length - 1)}…` : text;
  }

  function labelFor(category) {
    return C.categories?.[category] || category || 'System';
  }

  function notificationNode(notification, compact = false) {
    const item = el('article', `sun-notification ${notification.isRead ? 'is-read' : 'is-unread'} priority-${notification.priority || 'normal'}`);
    item.dataset.id = notification.id;
    item.tabIndex = 0;
    item.setAttribute('role', 'group');
    item.setAttribute('aria-label', notification.title || 'Notification');

    const icon = el('div', 'sun-notification-icon', notification.icon || '🔔');
    if (notification.actor?.avatar) {
      const image = document.createElement('img');
      image.src = notification.actor.avatar;
      image.alt = '';
      image.loading = 'lazy';
      icon.textContent = '';
      icon.append(image);
    }

    const content = el('div', 'sun-notification-content');
    const head = el('div', 'sun-notification-head');
    head.append(el('strong', '', notification.title || 'Notification'), el('time', '', notification.relativeTime || ''));
    content.append(head);
    if (notification.body) content.append(el('p', '', compact ? truncate(notification.body, 110) : notification.body));

    const meta = el('div', 'sun-notification-meta');
    meta.append(el('span', `sun-category-pill category-${notification.category}`, labelFor(notification.category)));
    if ((notification.groupCount || 1) > 1) meta.append(el('span', 'sun-group-count', `${notification.groupCount} updates`));
    if (['critical', 'high'].includes(notification.priority)) meta.append(el('span', `sun-priority-label ${notification.priority}`, notification.priority));
    content.append(meta);

    if (!compact) {
      const actions = el('div', 'sun-notification-actions');
      if (notification.link) {
        const openButton = el('button', 'sun-action-button', 'Open');
        openButton.type = 'button';
        openButton.addEventListener('click', event => { event.stopPropagation(); openNotification(notification); });
        actions.append(openButton);
      }
      const archiveButton = el('button', 'sun-action-button', notification.isArchived ? 'Restore' : 'Archive');
      archiveButton.type = 'button';
      archiveButton.addEventListener('click', async event => {
        event.stopPropagation();
        archiveButton.disabled = true;
        try {
          await setArchive([notification.id], !notification.isArchived);
          item.remove();
          await loadApp(true);
          await loadBells();
        } catch (error) {
          showSimpleToast(error.message);
          archiveButton.disabled = false;
        }
      });
      actions.append(archiveButton);
      content.append(actions);
    }

    item.append(icon, content, el('span', 'sun-unread-dot'));
    item.addEventListener('dblclick', () => openNotification(notification));
    item.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        openNotification(notification);
      }
    });
    return item;
  }

  async function openNotification(notification) {
    if (!notification.isRead) await markRead([notification.id]).catch(() => {});
    if (notification.link) window.location.assign(notification.link);
  }

  function updateCounts(unread, total = null, categoryCounts = null) {
    state.unread = Number(unread || 0);
    qsa('[data-sun-count]').forEach(node => {
      node.textContent = state.unread > 99 ? '99+' : String(state.unread);
      node.hidden = state.unread < 1;
    });
    qsa('[data-sun-menu-count]').forEach(node => { node.textContent = state.unread ? `(${state.unread})` : ''; });
    qsa('[data-sun-filter-count="unread"]').forEach(node => { node.textContent = String(state.unread); });
    if (total !== null) qsa('[data-sun-filter-count="all"]').forEach(node => { node.textContent = String(total); });
    if (categoryCounts) Object.entries(categoryCounts).forEach(([category, count]) => {
      qsa(`[data-sun-filter-count="${category}"]`).forEach(node => { node.textContent = String(count); });
    });
    document.title = state.unread ? `(${state.unread}) ${document.title.replace(/^\(\d+\+?\)\s*/, '')}` : document.title.replace(/^\(\d+\+?\)\s*/, '');
  }

  async function loadBells() {
    const data = await api('notifications?per_page=8');
    state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
    updateCounts(data.unread, data.total, data.categoryCounts);
    qsa('[data-sun-mini-list]').forEach(list => {
      list.textContent = '';
      if (!data.notifications?.length) list.append(el('div', 'sun-empty-mini', C.strings?.empty || 'No notifications yet.'));
      else data.notifications.forEach(notification => list.append(notificationNode(notification, true)));
    });
  }

  async function loadApp(reset = true) {
    const app = qs('[data-sun-app]');
    if (!app || state.busy) return;
    state.busy = true;
    if (reset) state.appPage = 1;
    const list = qs('[data-sun-list]', app);
    if (reset) {
      list.textContent = '';
      list.append(el('div', 'sun-loading-card', 'Loading notifications…'));
    }
    try {
      const params = new URLSearchParams({ per_page: '30', page: String(state.appPage) });
      if (state.category === 'unread') params.set('unread', 'true');
      else if (!['all', 'archived'].includes(state.category)) params.set('category', state.category);
      if (state.archived) params.set('archived', 'true');
      const data = await api(`notifications?${params.toString()}`);
      state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
      updateCounts(data.unread, state.archived ? null : data.total, state.archived ? null : data.categoryCounts);
      if (reset) list.textContent = '';
      if (!data.notifications?.length && reset) list.append(emptyState(state.archived));
      else data.notifications.forEach(notification => list.append(notificationNode(notification)));
      const loadMore = qs('[data-sun-load-more]', app);
      loadMore.hidden = !data.notifications || data.notifications.length < 30;
      qs('[data-sun-summary]', app).textContent = state.archived ? `${data.total} archived notification${data.total === 1 ? '' : 's'}` : `${data.unread} unread notification${data.unread === 1 ? '' : 's'}`;
    } catch (error) {
      if (reset) {
        list.textContent = '';
        list.append(el('div', 'sun-error-card', error.message));
      }
    } finally {
      state.busy = false;
    }
  }

  function emptyState(archived = false) {
    const box = el('div', 'sun-empty-state');
    box.append(el('div', 'sun-empty-icon', '✓'), el('h3', '', archived ? 'No archived notifications' : 'You are all caught up'), el('p', '', archived ? 'Notifications you archive will appear here.' : 'New messages and important activity will appear here.'));
    return box;
  }

  async function markRead(ids = [], all = false) {
    await api('notifications/read', { method: 'POST', body: { ids, all } });
    if (all) {
      qsa('.sun-notification.is-unread').forEach(node => { node.classList.remove('is-unread'); node.classList.add('is-read'); });
      updateCounts(0);
    } else {
      ids.forEach(id => qsa(`.sun-notification[data-id="${id}"]`).forEach(node => { node.classList.remove('is-unread'); node.classList.add('is-read'); }));
      updateCounts(Math.max(0, state.unread - ids.length));
    }
  }

  async function setArchive(ids, archive) {
    await api(`notifications/${archive ? 'archive' : 'unarchive'}`, { method: 'POST', body: { ids } });
  }

  async function poll() {
    if (document.hidden) {
      const now = Date.now();
      if (now - state.lastHiddenPoll < 120000) return;
      state.lastHiddenPoll = now;
    }
    try {
      const after = state.initialized ? state.latestId : 0;
      const data = await api(`notifications?per_page=20${after ? `&after_id=${after}` : ''}`);
      updateCounts(data.unread, data.total, data.categoryCounts);
      if (state.initialized && data.notifications?.length) {
        [...data.notifications].reverse().forEach(announce);
        await loadBells();
        if (qs('[data-sun-app]') && !state.archived) await loadApp(true);
      }
      state.latestId = Math.max(state.latestId, Number(data.latestId || 0));
      state.initialized = true;
    } catch (_) {}
  }

  function announce(notification) {
    showToast(notification);
    if (state.prefs?.sound_enabled !== false) beep();
    if (C.browserAlerts && state.prefs?.browser_enabled !== false && 'Notification' in window && Notification.permission === 'granted' && document.hidden) {
      const browser = new Notification(notification.externalTitle || 'Private notification', {
        body: truncate(notification.externalBody || 'Sign in to view this notification.', 180),
        tag: `sun-${notification.id}`
      });
      browser.onclick = () => { window.focus(); if (notification.link) window.location.assign(notification.link); browser.close(); };
    }
  }

  function showToast(notification) {
    const region = qs('[data-sun-toasts]') || createGlobalToastRegion();
    const toast = el('button', `sun-toast priority-${notification.priority || 'normal'}`);
    toast.type = 'button';
    toast.append(el('span', 'sun-toast-icon', notification.icon || '🔔'));
    const text = el('span', 'sun-toast-text');
    text.append(el('strong', '', notification.externalTitle || notification.title || 'Notification'), el('small', '', truncate(notification.externalBody || notification.body || '', 100)));
    toast.append(text);
    toast.addEventListener('click', async () => { if (notification.id) await markRead([notification.id]).catch(() => {}); if (notification.link) window.location.assign(notification.link); });
    region.append(toast);
    setTimeout(() => toast.classList.add('is-visible'), 20);
    setTimeout(() => { toast.classList.remove('is-visible'); setTimeout(() => toast.remove(), 300); }, 7000);
  }

  function createGlobalToastRegion() {
    const region = el('div', 'sun-toast-region');
    region.dataset.sunToasts = '';
    region.setAttribute('aria-live', 'polite');
    document.body.append(region);
    return region;
  }

  function beep() {
    try {
      const Audio = window.AudioContext || window.webkitAudioContext;
      if (!Audio) return;
      const context = new Audio();
      const oscillator = context.createOscillator();
      const gain = context.createGain();
      oscillator.frequency.value = 660;
      gain.gain.setValueAtTime(0.04, context.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.18);
      oscillator.connect(gain).connect(context.destination);
      oscillator.start();
      oscillator.stop(context.currentTime + 0.18);
    } catch (_) {}
  }

  function closeBell(bell, restore = true) {
    const button = qs('.sun-bell-button', bell);
    const panel = qs('.sun-bell-panel', bell);
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    if (restore) button.focus();
  }

  function initBells() {
    qsa('[data-sun-bell]').forEach(bell => {
      const button = qs('.sun-bell-button', bell);
      const panel = qs('.sun-bell-panel', bell);
      button.addEventListener('click', async event => {
        event.stopPropagation();
        const willOpen = panel.hidden;
        qsa('[data-sun-bell]').forEach(other => { if (other !== bell) closeBell(other, false); });
        panel.hidden = !willOpen;
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (willOpen) {
          await loadBells().catch(() => {});
          panel.focus();
        }
      });
      qs('[data-sun-close]', bell)?.addEventListener('click', () => closeBell(bell));
      panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); closeBell(bell); }
        if (event.key === 'Tab') trapFocus(event, panel);
      });
    });
    document.addEventListener('click', event => {
      if (!event.target.closest('[data-sun-bell]')) qsa('[data-sun-bell]').forEach(bell => closeBell(bell, false));
    });
  }

  function trapFocus(event, root) {
    const nodes = focusable(root);
    if (!nodes.length) return;
    const first = nodes[0];
    const last = nodes[nodes.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  }

  function initApp() {
    const app = qs('[data-sun-app]');
    if (!app) return;
    qsa('[data-sun-category]', app).forEach(button => button.addEventListener('click', () => {
      qsa('[data-sun-category]', app).forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      state.category = button.dataset.sunCategory;
      state.archived = state.category === 'archived';
      qs('[data-sun-heading]', app).textContent = button.querySelector('span')?.textContent || 'Notifications';
      loadApp(true);
    }));
    qs('[data-sun-load-more]', app)?.addEventListener('click', () => { state.appPage += 1; loadApp(false); });
    qs('[data-sun-refresh]', app)?.addEventListener('click', () => loadApp(true));
    qsa('[data-sun-mark-all]').forEach(button => button.addEventListener('click', async () => {
      button.disabled = true;
      await markRead([], true).catch(error => showSimpleToast(error.message));
      await loadBells().catch(() => {});
      await loadApp(true);
      button.disabled = false;
    }));
    qsa('[data-sun-browser-enable]').forEach(button => button.addEventListener('click', requestBrowserPermission));
    initSettings(app);
    loadApp(true);
  }

  async function requestBrowserPermission() {
    if (!('Notification' in window)) return showSimpleToast('Browser alerts are not supported by this browser.');
    const result = await Notification.requestPermission();
    showSimpleToast(result === 'granted' ? 'Browser alerts are enabled.' : (C.strings?.browserBlocked || 'Browser alerts were not enabled.'));
  }

  function showSimpleToast(text) {
    showToast({ id: 0, externalTitle: text, externalBody: '', icon: '🔔', priority: 'normal' });
  }

  function initSettings(app) {
    const dialog = qs('[data-sun-settings]', app);
    if (!dialog) return;
    qsa('[data-sun-settings-open]', app).forEach(button => button.addEventListener('click', async () => {
      state.lastFocused = document.activeElement;
      await loadPreferences(dialog);
      if (dialog.showModal) dialog.showModal(); else dialog.setAttribute('open', '');
      focusable(dialog)[0]?.focus();
    }));
    const close = () => {
      if (dialog.close) dialog.close(); else dialog.removeAttribute('open');
      state.lastFocused?.focus?.();
    };
    qsa('[data-sun-settings-close]', dialog).forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('keydown', event => { if (event.key === 'Tab') trapFocus(event, dialog); });
    qs('[data-sun-settings-save]', dialog)?.addEventListener('click', async event => {
      event.currentTarget.disabled = true;
      try {
        const data = await api('preferences', { method: 'POST', body: collectPreferences(dialog) });
        state.prefs = data.preferences;
        close();
        showSimpleToast('Notification settings saved.');
      } catch (error) {
        showSimpleToast(error.message);
      } finally {
        event.currentTarget.disabled = false;
      }
    });
  }

  async function loadPreferences(dialog) {
    const data = await api('preferences');
    state.prefs = data.preferences || {};
    qsa('[data-pref]', dialog).forEach(input => {
      const key = input.dataset.pref;
      if (input.type === 'checkbox') input.checked = Boolean(state.prefs[key]);
      else input.value = state.prefs[key] ?? '';
    });
    qsa('[data-pref-category]', dialog).forEach(input => { input.checked = state.prefs.categories?.[input.dataset.prefCategory] !== false; });
  }

  function collectPreferences(dialog) {
    const output = { categories: {} };
    qsa('[data-pref]', dialog).forEach(input => { output[input.dataset.pref] = input.type === 'checkbox' ? input.checked : input.value; });
    qsa('[data-pref-category]', dialog).forEach(input => { output.categories[input.dataset.prefCategory] = input.checked; });
    return output;
  }

  async function bootstrap() {
    initBells();
    initApp();
    try { state.prefs = (await api('preferences')).preferences; } catch (_) {}
    await loadBells().catch(() => {});
    await poll();
    const interval = Math.max(15, Number(C.pollSeconds || 30)) * 1000;
    state.timer = window.setInterval(poll, interval);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  else bootstrap();
})();
