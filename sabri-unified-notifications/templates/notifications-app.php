<?php defined('ABSPATH') || exit; ?>
<div class="sun-app" data-sun-app>
    <header class="sun-app-hero">
        <div><p class="sun-eyebrow">Unified Communication Center</p><h1>Notifications</h1><p>Messages, marketplace activity, appointments, social interactions, security alerts and institutional updates in one private center.</p></div>
        <div class="sun-hero-actions"><button class="sun-secondary-button" type="button" data-sun-browser-enable>Enable browser alerts</button><button class="sun-primary-button" type="button" data-sun-mark-all>Mark all as read</button></div>
    </header>
    <div class="sun-app-layout">
        <aside class="sun-sidebar" aria-label="Notification filters">
            <button class="is-active" data-sun-category="all"><span>All notifications</span><b data-sun-filter-count="all">0</b></button>
            <?php foreach (SUN_Utils::allowed_categories() as $key => $label): ?><button data-sun-category="<?php echo esc_attr($key); ?>"><span><?php echo esc_html($label); ?></span><b data-sun-filter-count="<?php echo esc_attr($key); ?>">0</b></button><?php endforeach; ?>
            <button data-sun-category="unread"><span>Unread</span><b data-sun-filter-count="unread">0</b></button>
            <button data-sun-category="archived"><span>Archived history</span><b aria-hidden="true">↺</b></button>
            <button data-sun-settings-open><span>Notification settings</span><span aria-hidden="true">⚙</span></button>
        </aside>
        <main class="sun-main">
            <div class="sun-toolbar"><div><strong data-sun-heading>All notifications</strong><small data-sun-summary>Loading your notification history…</small></div><div class="sun-toolbar-actions"><button type="button" data-sun-refresh aria-label="Refresh notifications">↻</button></div></div>
            <div class="sun-list" data-sun-list aria-live="polite"><div class="sun-loading-card">Loading notifications…</div></div>
            <button class="sun-load-more" type="button" data-sun-load-more hidden>Load more</button>
        </main>
    </div>
    <dialog class="sun-settings-dialog" data-sun-settings aria-labelledby="sun-settings-title">
        <form method="dialog" class="sun-settings-card">
            <header><div><h2 id="sun-settings-title">Notification settings</h2><p>Choose optional channels and categories. Critical security and institutional notices remain available.</p></div><button type="button" data-sun-settings-close aria-label="Close settings">×</button></header>
            <section><h3>On-device experience</h3><label><input type="checkbox" data-pref="browser_enabled"> Browser alerts while using the site</label><label><input type="checkbox" data-pref="sound_enabled"> Notification sound</label><label><input type="checkbox" data-pref="do_not_disturb"> Do not disturb for optional alerts</label></section>
            <section><h3>External delivery</h3><label>Email<select data-pref="email_mode"><option value="off">Off</option><option value="important">Important only</option><option value="immediate">All immediately</option><option value="daily">Daily digest</option><option value="weekly">Weekly digest</option></select></label><label>SMS<select data-pref="sms_mode"><option value="off">Off</option><option value="critical">Critical only</option></select></label><label>Mobile / provider push<select data-pref="push_mode"><option value="off">Off</option><option value="important">Important only</option><option value="all">All</option></select></label></section>
            <section><h3>Quiet hours</h3><label><input type="checkbox" data-pref="quiet_enabled"> Enable quiet hours</label><div class="sun-time-grid"><label>From <input type="time" data-pref="quiet_start"></label><label>Until <input type="time" data-pref="quiet_end"></label></div></section>
            <section><h3>Categories</h3><div class="sun-category-grid"><?php foreach (SUN_Utils::allowed_categories() as $key => $label): ?><label><input type="checkbox" data-pref-category="<?php echo esc_attr($key); ?>"> <?php echo esc_html($label); ?></label><?php endforeach; ?></div></section>
            <footer><button type="button" class="sun-secondary-button" data-sun-settings-close>Cancel</button><button type="button" class="sun-primary-button" data-sun-settings-save>Save settings</button></footer>
        </form>
    </dialog>
    <div class="sun-toast-region" data-sun-toasts aria-live="polite" aria-atomic="false"></div>
</div>
