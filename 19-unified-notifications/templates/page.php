<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
nocache_headers();
$route = get_query_var( 'sun_notifications_route' );
header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
get_header();
$context_controls = apply_filters(
	'sabri_file20_context_controls_markup_v1',
	'',
	array(
		'surface' => 'file19_notifications',
		'route'   => $route,
		'back'    => true,
		'home'    => true,
		'forward' => false,
	)
);
$fallback = 'settings' === $route ? home_url( '/notifications/' ) : home_url( '/' );
?>
<main id="primary" class="sun-page" role="main">
	<?php if ( is_string( $context_controls ) && '' !== trim( $context_controls ) ) : ?>
		<?php echo $context_controls; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted File 20 contract markup. ?>
	<?php else : ?>
		<nav class="sun-page__nav" aria-label="<?php esc_attr_e( 'Page navigation', 'sabri-unified-notifications' ); ?>">
			<button type="button" data-sun-safe-back data-sun-fallback="<?php echo esc_url( $fallback ); ?>">← <?php esc_html_e( 'Back', 'sabri-unified-notifications' ); ?></button>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">⌂ <?php esc_html_e( 'Home', 'sabri-unified-notifications' ); ?></a>
		</nav>
	<?php endif; ?>
	<?php echo 'settings' === $route ? sun_notifications()->renderer()->render_settings() : sun_notifications()->renderer()->render_center(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php get_footer(); ?>
