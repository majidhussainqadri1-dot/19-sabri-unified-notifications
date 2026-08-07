<?php
/**
 * Strict domain-event envelope validation and minimization.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Event_Validator {
	/** @var SUN_Producer_Registry */ private $registry;
	/** @var int */ private $data_nodes = 0;
	/** @param SUN_Producer_Registry $registry Registry. */
	public function __construct( SUN_Producer_Registry $registry ) { $this->registry = $registry; }

	/** @param array<string,mixed> $event Event. @return array<string,mixed>|WP_Error */
	public function validate( array $event ) {
		$required = array( 'producer', 'owner', 'event_id', 'event_type', 'schema_version', 'occurred_at', 'recipients' );
		foreach ( $required as $field ) {
			if ( ! isset( $event[ $field ] ) || '' === $event[ $field ] || array() === $event[ $field ] ) {
				return new WP_Error( 'sun_event_missing_' . $field, sprintf( __( 'The event field “%s” is required.', 'sabri-unified-notifications' ), $field ), array( 'status' => 400 ) );
			}
		}

		$producer      = sanitize_key( (string) $event['producer'] );
		$event_type    = sanitize_text_field( (string) $event['event_type'] );
		$authorization = $this->registry->authorize_type( $producer, $event_type );
		if ( is_wp_error( $authorization ) ) { return $authorization; }
		$config = $this->registry->get( $producer );
		if ( ! is_array( $config ) ) {
			return new WP_Error( 'sun_unknown_producer', __( 'The notification producer is not registered.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		$declared_owner  = sanitize_text_field( (string) $event['owner'] );
		$canonical_owner = sanitize_text_field( (string) ( $config['owner'] ?? '' ) );
		if ( '' === $canonical_owner || ! hash_equals( $canonical_owner, $declared_owner ) ) {
			return new WP_Error( 'sun_event_owner_mismatch', __( 'The producer event owner does not match the registered canonical owner.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		if ( ! preg_match( '/^[A-Z][A-Za-z0-9]*(?:\.[A-Z][A-Za-z0-9]*)+$/', $event_type ) ) {
			return new WP_Error( 'sun_event_type_invalid', __( 'The event type must use a versioned domain fact name.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		$event_id = sanitize_text_field( (string) $event['event_id'] );
		if ( strlen( $event_id ) > 191 || ! preg_match( '/^[A-Za-z0-9._:\-]+$/', $event_id ) ) {
			return new WP_Error( 'sun_event_id_invalid', __( 'The event identifier is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		$schema = sanitize_text_field( (string) $event['schema_version'] );
		if ( ! preg_match( '/^v?[0-9]+(?:\.[0-9]+){0,2}$/', $schema ) ) {
			return new WP_Error( 'sun_schema_version_invalid', __( 'The event schema version is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		if ( ! empty( $config['schema_versions'] ) && is_array( $config['schema_versions'] ) ) {
			$allowed_schemas = array_map( 'strval', $config['schema_versions'] );
			if ( ! in_array( $schema, $allowed_schemas, true ) ) {
				return new WP_Error( 'sun_schema_version_unsupported', __( 'This producer schema version is not supported.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
			}
		}
		$occurred = strtotime( (string) $event['occurred_at'] );
		if ( false === $occurred || $occurred > time() + 300 || $occurred < time() - YEAR_IN_SECONDS ) {
			return new WP_Error( 'sun_event_time_invalid', __( 'The event time is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}

		$recipients = $this->normalize_recipients( $event['recipients'] );
		if ( is_wp_error( $recipients ) ) { return $recipients; }
		$scope = $this->normalize_subscription_scope( $event['subscription_scope'] ?? null );
		if ( is_wp_error( $scope ) ) { return $scope; }
		$catalog = SUN_Four_Plan_Compliance::event_catalog();
		if ( ! empty( $catalog[ $event_type ]['subscription_required'] ) && ( empty( $scope ) || empty( $scope['required'] ) ) ) {
			return new WP_Error( 'sun_subscription_scope_required', __( 'This notification event requires an explicit opt-in subscription scope.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}

		$this->data_nodes = 0;
		$data = isset( $event['data'] ) && is_array( $event['data'] ) ? $this->sanitize_data( $event['data'], 0 ) : array();
		if ( is_wp_error( $data ) ) { return $data; }
		if ( strlen( wp_json_encode( $data ) ) > (int) apply_filters( 'sun_event_data_max_bytes', 65536, $producer ) ) {
			return new WP_Error( 'sun_event_data_too_large', __( 'The event data exceeds the allowed size.', 'sabri-unified-notifications' ), array( 'status' => 413 ) );
		}
		$expires_at = $this->normalize_optional_datetime( $event['expires_at'] ?? null );
		if ( is_wp_error( $expires_at ) ) { return $expires_at; }

		$normalized = array(
			'producer'=>$producer,
			'owner'=>$canonical_owner,
			'event_id'=>$event_id,
			'event_type'=>$event_type,
			'schema_version'=>$schema,
			'occurred_at'=>gmdate( 'Y-m-d H:i:s', $occurred ),
			'recipients'=>$recipients,
			'subscription_scope'=>$scope,
			'actor'=>isset($event['actor'])&&is_array($event['actor'])?$this->sanitize_identity_ref($event['actor']):array(),
			'subject'=>isset($event['subject'])&&is_array($event['subject'])?$this->sanitize_identity_ref($event['subject']):array(),
			'trace_id'=>sanitize_text_field((string)($event['trace_id']??SUN_Database::uuid())),
			'category'=>sanitize_key((string)($event['category']??'')),
			'priority'=>sanitize_key((string)($event['priority']??'')),
			'sensitivity'=>sanitize_key((string)($event['sensitivity']??'standard')),
			'template_key'=>sanitize_key((string)($event['template_key']??'')),
			'deep_link'=>esc_url_raw((string)($event['deep_link']??'')),
			'deep_context'=>sanitize_text_field((string)($event['deep_context']??'')),
			'expires_at'=>$expires_at,
			'data'=>$data,
			'meta'=>array(
				'idempotency_key'=>sanitize_text_field((string)($event['idempotency_key']??'')),
				'source_version'=>sanitize_text_field((string)($event['source_version']??'')),
			),
		);

		/*
		 * Security boundary: once validated, the canonical envelope is immutable.
		 * A mutating filter here would allow downstream code to reintroduce an
		 * unauthorized owner, recipient, sensitivity, deep link, or payload after
		 * all checks above. Observers should hook into the post-ingestion actions
		 * exposed by the notification service instead of rewriting this envelope.
		 */
		return $normalized;
	}

	/** @param mixed $recipients Recipients. @return array<int,array<string,mixed>>|WP_Error */
	private function normalize_recipients( $recipients ) {
		if ( ! is_array( $recipients ) || count( $recipients ) > (int) apply_filters( 'sun_event_max_recipients', 1000 ) ) {
			return new WP_Error( 'sun_recipients_invalid', __( 'The event recipients are invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		$out=array();$seen=array();
		foreach($recipients as $recipient){
			if(is_numeric($recipient)){$recipient=array('user_id'=>absint($recipient));}
			if(!is_array($recipient)||empty($recipient['user_id'])){return new WP_Error('sun_recipient_invalid',__('Every recipient must contain a canonical user identifier.','sabri-unified-notifications'),array('status'=>400));}
			$user_id=absint($recipient['user_id']);if($user_id<1||isset($seen[$user_id])){continue;}$seen[$user_id]=true;
			$out[]=array('user_id'=>$user_id,'locale'=>sanitize_text_field((string)($recipient['locale']??'')),'channels'=>isset($recipient['channels'])&&is_array($recipient['channels'])?array_values(array_unique(array_map('sanitize_key',$recipient['channels']))):array());
		}
		return empty($out)?new WP_Error('sun_recipients_empty',__('The event has no valid recipients.','sabri-unified-notifications'),array('status'=>400)):$out;
	}

	/** @param mixed $scope Scope. @return array<string,mixed>|WP_Error */
	private function normalize_subscription_scope( $scope ) {
		if ( null === $scope || array() === $scope || '' === $scope ) { return array(); }
		if ( ! is_array( $scope ) ) { return new WP_Error('sun_subscription_scope_invalid',__('The notification subscription scope is invalid.','sabri-unified-notifications'),array('status'=>400)); }
		$type=sanitize_key((string)($scope['type']??''));$id=sanitize_text_field((string)($scope['id']??''));
		$allowed=array('person','topic','community','course','event','doctor','channel');
		if(!in_array($type,$allowed,true)||''===$id||strlen($id)>191){return new WP_Error('sun_subscription_scope_invalid',__('The notification subscription scope is invalid.','sabri-unified-notifications'),array('status'=>400));}
		return array('type'=>$type,'id'=>$id,'required'=>!empty($scope['required']));
	}

	/** @param array<string,mixed> $reference Reference. @return array<string,mixed> */
	private function sanitize_identity_ref( array $reference ) {
		return array('type'=>sanitize_key((string)($reference['type']??'object')),'id'=>sanitize_text_field((string)($reference['id']??'')),'public_id'=>sanitize_text_field((string)($reference['public_id']??'')));
	}

	/** @param mixed $value Value. @param int $depth Depth. @return mixed|WP_Error */
	private function sanitize_data( $value, $depth = 0 ) {
		++$this->data_nodes;
		$max_depth = max( 1, (int) apply_filters( 'sun_event_data_max_depth', 8 ) );
		$max_nodes = max( 1, (int) apply_filters( 'sun_event_data_max_nodes', 5000 ) );
		if ( $depth > $max_depth || $this->data_nodes > $max_nodes ) {
			return new WP_Error( 'sun_event_data_complexity_exceeded', __( 'The event data is too deeply nested or complex.', 'sabri-unified-notifications' ), array( 'status' => 413 ) );
		}
		if ( is_array( $value ) ) {
			$out=array();
			foreach(array_slice($value,0,100,true) as $key=>$item){
				$clean=$this->sanitize_data($item,$depth+1);
				if(is_wp_error($clean)){return $clean;}
				$out[sanitize_key((string)$key)]=$clean;
			}
			return $out;
		}
		if(is_bool($value)||is_int($value)||is_float($value)||null===$value){return $value;}
		return sanitize_textarea_field((string)$value);
	}

	/** @param mixed $value Value. @return string|null|WP_Error */
	private function normalize_optional_datetime( $value ) {
		if ( null === $value || '' === $value ) { return null; }
		$timestamp = strtotime( (string) $value );
		if ( false === $timestamp ) {
			return new WP_Error( 'sun_event_expiry_invalid', __( 'The event expiry time is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
