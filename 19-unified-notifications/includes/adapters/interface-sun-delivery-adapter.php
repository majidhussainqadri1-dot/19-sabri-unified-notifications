<?php
/**
 * Delivery adapter contract.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SUN_Delivery_Adapter {
	/**
	 * @param array<string,mixed> $delivery Delivery row.
	 * @param array<string,mixed> $notification Notification row and rendered data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send( array $delivery, array $notification );

	/** @return string */
	public function channel();

	/** @return array<string,mixed> */
	public function health();
}
