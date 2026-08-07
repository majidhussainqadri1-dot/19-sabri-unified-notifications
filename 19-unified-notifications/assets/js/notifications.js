(() => {
  'use strict';
  const cfg = window.SUNNotifications || {};
  const statusNodes = () => document.querySelectorAll('[data-sun-status]');
  const announce = (message) => statusNodes().forEach((node) => { node.textContent = message; });
  const api = async (path, options = {}) => {
    const response = await fetch(`${cfg.restUrl || ''}${path}`, {
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '', ...(options.headers || {}) },
      ...options,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.message || cfg.i18n?.error || 'Request failed');
    return data;
  };
  const updateCount = (count) => {
    document.querySelectorAll('[data-sun-unread-count]').forEach((node) => {
      const value = Number(count || 0);
      node.textContent = value > 99 ? '99+' : String(value);
      node.classList.toggle('is-empty', value < 1);
    });
  };
  document.addEventListener('click', async (event) => {
    const action = event.target.closest('[data-sun-action]');
    if (action) {
      const card = action.closest('[data-sun-id]');
      if (!card) return;
      action.disabled = true;
      try {
        const result = await api(`notifications/${encodeURIComponent(card.dataset.sunId)}`, {
          method: 'POST',
          body: JSON.stringify({ action: action.dataset.sunAction, version: Number(card.dataset.sunVersion || 0) }),
        });
        updateCount(result.unread_count);
        if (action.dataset.sunAction === 'archive') card.hidden = true;
        else window.location.reload();
      } catch (error) { announce(error.message); action.disabled = false; }
      return;
    }
    const removeSubscription = event.target.closest('[data-sun-delete-subscription]');
    if (removeSubscription) {
      removeSubscription.disabled = true;
      try {
        await api(`subscriptions/${encodeURIComponent(removeSubscription.dataset.sunDeleteSubscription)}`, { method: 'DELETE' });
        announce(cfg.i18n?.removed || 'Removed.');
        const form = removeSubscription.closest('[data-sun-subscription]');
        if (form) form.remove();
      } catch (error) { announce(error.message); removeSubscription.disabled = false; }
      return;
    }
    const bulk = event.target.closest('[data-sun-bulk-action]');
    if (bulk) {
      bulk.disabled = true;
      try {
        const result = await api('notifications/bulk', { method: 'POST', body: JSON.stringify({ action: bulk.dataset.sunBulkAction }) });
        updateCount(result.unread_count); window.location.reload();
      } catch (error) { announce(error.message); bulk.disabled = false; }
      return;
    }
    const filter = event.target.closest('[data-sun-filter]');
    if (filter) {
      const selected = filter.dataset.sunFilter;
      document.querySelectorAll('[data-sun-filter]').forEach((button) => button.setAttribute('aria-pressed', button === filter ? 'true' : 'false'));
      document.querySelectorAll('.sun-card[data-sun-status]').forEach((card) => {
        card.hidden = selected !== 'all' && card.dataset.sunStatus !== selected;
      });
    }
  });
  document.addEventListener('submit', async (event) => {
    const preferenceForm = event.target.closest('[data-sun-preference]');
    const subscriptionForm = event.target.closest('[data-sun-subscription]');
    const form = preferenceForm || subscriptionForm;
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    const values = Object.fromEntries(new FormData(form).entries());
    if (preferenceForm) {
      values.enabled = preferenceForm.elements.enabled?.checked || false;
      values.quiet_enabled = preferenceForm.elements.quiet_enabled?.checked || false;
      values.version = Number(values.version || 0);
      try {
        const result = await api('preferences', { method: 'POST', body: JSON.stringify(values) });
        preferenceForm.elements.version.value = result.version;
        announce(cfg.i18n?.saved || 'Saved.');
      } catch (error) { announce(error.message); }
      finally { if (button) button.disabled = false; }
      return;
    }
    values.enabled = subscriptionForm.elements.enabled?.checked || false;
    if ('version' in values) values.version = Number(values.version || 0);
    try {
      const result = await api('subscriptions', { method: 'POST', body: JSON.stringify(values) });
      announce(cfg.i18n?.saved || 'Saved.');
      if (subscriptionForm.elements.version) subscriptionForm.elements.version.value = result.version;
      else window.location.reload();
    } catch (error) { announce(error.message); }
    finally { if (button) button.disabled = false; }
  });
  const base64ToBytes = (value) => {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const raw = atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
  };
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-sun-enable-push]');
    if (!button) return;
    button.disabled = true;
    try {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) throw new Error('Browser notifications are not supported on this device.');
      if (!cfg.pushPublicKey) throw new Error('Browser notification provider is not configured.');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Browser notification permission was not granted.');
      const registration = await navigator.serviceWorker.register(cfg.workerUrl, { scope: '/' });
      const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64ToBytes(cfg.pushPublicKey) });
      await api('devices', { method: 'POST', body: JSON.stringify({ provider: 'webpush', platform: 'web', token: subscription.toJSON() }) });
      announce(cfg.i18n?.saved || 'Saved.');
      button.textContent = 'Browser notifications enabled';
    } catch (error) { announce(error.message); button.disabled = false; }
  });
  const pollCount = async () => {
    if (document.hidden || !cfg.restUrl) return;
    try { const result = await api('unread-count'); updateCount(result.count); } catch (_) { /* fail silently */ }
  };
  if (document.querySelector('[data-sun-bell]')) window.setInterval(pollCount, Math.max(30000, Number(cfg.pollMs || 60000)));
})();
