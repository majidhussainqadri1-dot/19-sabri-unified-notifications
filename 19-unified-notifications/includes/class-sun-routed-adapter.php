<?php
/** Delivery-adapter wrapper that adds provider routing, cost awareness and failover. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Routed_Adapter implements SUN_Delivery_Adapter {
    /** @var string */ private $channel;
    /** @var SUN_Delivery_Adapter|null */ private $base;
    /** @var SUN_Routing_Service */ private $routing;
    /** @param string $channel Channel. @param SUN_Delivery_Adapter|null $base Base. @param SUN_Routing_Service $routing Routing. */
    public function __construct( $channel, $base, SUN_Routing_Service $routing ) { $this->channel = sanitize_key( $channel ); $this->base = $base; $this->routing = $routing; }
    /** @return string */ public function channel() { return $this->channel; }
    /** @param array<string,mixed> $delivery Delivery. @param array<string,mixed> $notification Notification. @return array<string,mixed>|WP_Error */ public function send( array $delivery, array $notification ) { return $this->routing->send( $this->channel, $this->base, $delivery, $notification ); }
    /** @return array<string,mixed> */ public function health() { $base = $this->base instanceof SUN_Delivery_Adapter ? $this->base->health() : array(); return array( 'channel' => $this->channel, 'routing' => true, 'base' => $base, 'candidates' => $this->routing->candidates( $this->channel ) ); }
}
