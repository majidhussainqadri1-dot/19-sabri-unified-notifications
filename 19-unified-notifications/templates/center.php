<?php
/** @var array<string,mixed> $data */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = (array) ( $data['items'] ?? array() );
?>
<section class="sun-center" aria-labelledby="sun-center-title" data-sun-center>
	<header class="sun-center__header">
		<div><h1 id="sun-center-title"><?php esc_html_e( 'Notifications', 'sabri-unified-notifications' ); ?></h1><p><?php esc_html_e( 'Your private, unified notification center.', 'sabri-unified-notifications' ); ?></p></div>
		<div class="sun-center__actions"><a class="sun-button" href="<?php echo esc_url( home_url( '/settings/notifications/' ) ); ?>">⚙ <?php esc_html_e( 'Settings', 'sabri-unified-notifications' ); ?></a><button class="sun-button" type="button" data-sun-bulk-action="read"><?php esc_html_e( 'Mark all read', 'sabri-unified-notifications' ); ?></button></div>
	</header>
	<nav class="sun-filters" aria-label="<?php esc_attr_e( 'Notification filters', 'sabri-unified-notifications' ); ?>"><button type="button" data-sun-filter="all" aria-pressed="true"><?php esc_html_e( 'All', 'sabri-unified-notifications' ); ?></button><button type="button" data-sun-filter="unread" aria-pressed="false"><?php esc_html_e( 'Unread', 'sabri-unified-notifications' ); ?></button><button type="button" data-sun-filter="archived" aria-pressed="false"><?php esc_html_e( 'Archived', 'sabri-unified-notifications' ); ?></button></nav>
	<div class="sun-list" role="list" data-sun-list>
	<?php if ( empty( $items ) ) : ?><div class="sun-empty" role="status">🔔 <strong><?php esc_html_e( 'No notifications yet', 'sabri-unified-notifications' ); ?></strong><span><?php esc_html_e( 'Important updates will appear here.', 'sabri-unified-notifications' ); ?></span></div><?php endif; ?>
	<?php foreach ( $items as $item ) : ?>
		<article class="sun-card is-<?php echo esc_attr( $item['status'] ); ?>" role="listitem" data-sun-id="<?php echo esc_attr( $item['public_id'] ); ?>" data-sun-status="<?php echo esc_attr( $item['status'] ); ?>" data-sun-version="<?php echo esc_attr( $item['version'] ); ?>">
			<div class="sun-card__icon" aria-hidden="true"><?php echo esc_html( 'security' === $item['category'] ? '🛡️' : ( 'messages' === $item['category'] ? '💬' : '🔔' ) ); ?></div>
			<div class="sun-card__body"><a href="<?php echo esc_url( $item['open_url'] ); ?>" class="sun-card__link"><strong><?php echo esc_html( $item['title'] ); ?></strong><span><?php echo esc_html( $item['summary'] ); ?></span></a><time datetime="<?php echo esc_attr( mysql2date( DATE_W3C, $item['created_at'], false ) ); ?>"><?php echo esc_html( human_time_diff( strtotime( $item['created_at'] . ' UTC' ), time() ) ); ?> <?php esc_html_e( 'ago', 'sabri-unified-notifications' ); ?></time></div>
			<div class="sun-card__menu"><button type="button" data-sun-action="<?php echo 'unread' === $item['status'] ? 'read' : 'unread'; ?>"><?php echo 'unread' === $item['status'] ? esc_html__( 'Mark read', 'sabri-unified-notifications' ) : esc_html__( 'Mark unread', 'sabri-unified-notifications' ); ?></button><button type="button" data-sun-action="<?php echo 'archived' === $item['status'] ? 'unarchive' : 'archive'; ?>"><?php echo 'archived' === $item['status'] ? esc_html__( 'Unarchive', 'sabri-unified-notifications' ) : esc_html__( 'Archive', 'sabri-unified-notifications' ); ?></button><button type="button" data-sun-action="report"><?php esc_html_e( 'Report notification', 'sabri-unified-notifications' ); ?></button></div>
		</article>
	<?php endforeach; ?>
	</div>
	<div class="sun-live-region" aria-live="polite" aria-atomic="true" data-sun-status></div>
</section>
