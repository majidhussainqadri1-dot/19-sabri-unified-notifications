<?php
/** @var int $count */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<a class="sun-bell" href="<?php echo esc_url( home_url( '/notifications/' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( _n( '%d unread notification', '%d unread notifications', $count, 'sabri-unified-notifications' ), $count ) ); ?>" data-sun-bell>
	<span class="sun-icon" aria-hidden="true">🔔</span>
	<span class="sun-bell__label"><?php esc_html_e( 'Notifications', 'sabri-unified-notifications' ); ?></span>
	<span class="sun-bell__count<?php echo $count ? '' : ' is-empty'; ?>" data-sun-unread-count aria-live="polite"><?php echo esc_html( min( 99, $count ) ); ?></span>
</a>
