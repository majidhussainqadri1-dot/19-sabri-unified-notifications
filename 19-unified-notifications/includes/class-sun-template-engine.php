<?php
/**
 * Versioned locale/channel template registry with safe variables and redaction.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Template_Engine {
	/**
	 * Resolve an active template with locale and wildcard fallbacks.
	 *
	 * @param string $event_type Event type.
	 * @param string $channel Channel.
	 * @param string $locale Locale.
	 * @param string $requested_key Requested key.
	 * @return array<string,mixed>
	 */
	public function resolve( $event_type, $channel, $locale, $requested_key = '' ) {
		global $wpdb;
		$table   = SUN_Database::table( 'templates' );
		$locale  = sanitize_locale_name( $locale ?: 'en_US' );
		$channel = sanitize_key( $channel );
		$row     = null;
		if ( $requested_key ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE template_key=%s AND event_type IN (%s,'*') AND channel=%s AND locale IN (%s,'en_US') AND status='active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY (event_type=%s) DESC,(locale=%s) DESC,id DESC LIMIT 1",
					sanitize_key( $requested_key ), $event_type, $channel, $locale, SUN_Database::now(), $event_type, $locale
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
		if ( ! $row ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE event_type IN (%s,'*') AND channel=%s AND locale IN (%s,'en_US') AND status='active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY (event_type=%s) DESC,(locale=%s) DESC,id DESC LIMIT 1",
					$event_type, $channel, $locale, SUN_Database::now(), $event_type, $locale
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
		if ( ! $row ) {
			$row = array(
				'template_key'      => 'safe-generic-' . $channel,
				'version'           => 'builtin-1',
				'title_template'    => '{{action_name}}',
				'body_template'     => '{{summary}}',
				'allowed_variables' => wp_json_encode( array( 'action_name', 'summary', 'site_name' ) ),
			);
		}
		return $row;
	}

	/**
	 * Render a template. Unknown variables become empty and all output is plain text.
	 *
	 * @param array<string,mixed> $template Template row.
	 * @param array<string,mixed> $variables Variables.
	 * @param string              $channel Channel.
	 * @param string              $sensitivity Sensitivity.
	 * @return array<string,string>
	 */
	public function render( array $template, array $variables, $channel, $sensitivity = 'standard' ) {
		$allowed = json_decode( (string) ( $template['allowed_variables'] ?? '[]' ), true );
		$allowed = is_array( $allowed ) ? array_map( 'sanitize_key', $allowed ) : array();
		$safe    = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $variables ) ) {
				$safe[ $key ] = $this->plain_text( $variables[ $key ], 500 );
			}
		}
		$safe['site_name'] = $safe['site_name'] ?? get_bloginfo( 'name' );
		$title = $this->replace( (string) ( $template['title_template'] ?? '' ), $safe );
		$body  = $this->replace( (string) ( $template['body_template'] ?? '' ), $safe );

		if ( 'in_app' !== $channel && in_array( $sensitivity, array( 'sensitive', 'secret', 'restricted' ), true ) ) {
			$title = __( 'You have a new private update', 'sabri-unified-notifications' );
			$body  = __( 'Sign in to the Sabri Social Homeopathy Platform to review it securely.', 'sabri-unified-notifications' );
		}
		if ( 'sms' === $channel ) {
			$title = '';
			$body  = $this->plain_text( $body, 320 );
		}
		return array(
			'title' => $this->plain_text( $title, 180 ),
			'body'  => $this->plain_text( $body, 'in_app' === $channel ? 1000 : 2000 ),
		);
	}

	/**
	 * Validate a template before activation.
	 *
	 * @param string   $title Title.
	 * @param string   $body Body.
	 * @param string[] $allowed Allowed variables.
	 * @return true|WP_Error
	 */
	public function validate_template( $title, $body, array $allowed ) {
		$combined = (string) $title . "\n" . (string) $body;
		if ( preg_match( '/<\s*(script|style|iframe|object|embed|form|input|meta|link)\b/i', $combined ) || preg_match( '/\bon\w+\s*=|javascript:/i', $combined ) ) {
			return new WP_Error( 'sun_template_unsafe', __( 'Templates must not contain executable HTML.', 'sabri-unified-notifications' ) );
		}
		preg_match_all( '/{{\s*([a-zA-Z0-9_]+)\s*}}/', $combined, $matches );
		foreach ( array_unique( $matches[1] ?? array() ) as $variable ) {
			if ( ! in_array( sanitize_key( $variable ), array_map( 'sanitize_key', $allowed ), true ) ) {
				return new WP_Error( 'sun_template_variable_denied', sprintf( __( 'Template variable “%s” is not allowed.', 'sabri-unified-notifications' ), $variable ) );
			}
		}
		return true;
	}

	/** @param string $template Template. @param array<string,string> $values Values. @return string */
	private function replace( $template, array $values ) {
		return preg_replace_callback(
			'/{{\s*([a-zA-Z0-9_]+)\s*}}/',
			static function ( $match ) use ( $values ) {
				$key = sanitize_key( $match[1] );
				return $values[ $key ] ?? '';
			},
			wp_strip_all_tags( $template )
		);
	}

	/** @param mixed $value Value. @param int $limit Limit. @return string */
	private function plain_text( $value, $limit ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $value ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
	}
}
