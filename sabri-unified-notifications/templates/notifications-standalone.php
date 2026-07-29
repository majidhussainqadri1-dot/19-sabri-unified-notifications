<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Notifications — <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="sun-standalone-body">
<div class="sun-standalone-shell">
    <a class="sun-back-link" href="<?php echo esc_url(home_url('/')); ?>">← Back to website</a>
    <?php echo do_shortcode('[sabri_notifications]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
