<?php
if(!defined('ABSPATH')){exit;}nocache_headers();get_header();$route=get_query_var('sun_notifications_route');
?>
<main id="primary" class="sun-page" role="main"><nav class="sun-page__nav" aria-label="<?php esc_attr_e('Page navigation','sabri-unified-notifications');?>"><button type="button" onclick="history.length>1?history.back():location.assign('<?php echo esc_js(home_url('/'));?>')">← <?php esc_html_e('Back','sabri-unified-notifications');?></button><a href="<?php echo esc_url(home_url('/'));?>">⌂ <?php esc_html_e('Home','sabri-unified-notifications');?></a></nav><?php echo 'settings'===$route?sun_notifications()->renderer()->render_settings():sun_notifications()->renderer()->render_center(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></main>
<?php get_footer(); ?>
