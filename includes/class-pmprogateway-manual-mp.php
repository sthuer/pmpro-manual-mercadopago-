<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PMPro gateway implementation for Manual MercadoPago.
 */
if ( class_exists( 'PMProGateway' ) && ! class_exists( 'PMProGateway_manual_mp' ) ) :
class PMProGateway_manual_mp extends PMProGateway {

	/**
	 * Register PMPro hooks for this gateway.
	 */
	public static function init() {
		add_filter( 'pmpro_gateways', array( __CLASS__, 'register_gateway_label' ) );
		add_filter( 'pmpro_valid_gateways', array( __CLASS__, 'register_valid_gateway' ) );
		add_filter( 'pmpro_payment_options', array( __CLASS__, 'register_payment_options' ) );
		add_filter( 'pmpro_required_billing_fields', array( __CLASS__, 'required_billing_fields' ) );
		add_filter( 'pmpro_include_billing_address_fields', array( __CLASS__, 'include_billing_fields' ) );
		add_filter( 'pmpro_include_payment_information_fields', array( __CLASS__, 'include_payment_fields' ) );
	}

	public static function register_gateway_label( $gateways ) {
		$gateways['manual_mp'] = __( 'Manual MercadoPago', 'pmpro-manual-mercadopago' );
		return $gateways;
	}

	public static function register_valid_gateway( $gateways ) {
		if ( ! in_array( 'manual_mp', $gateways, true ) ) {
			$gateways[] = 'manual_mp';
		}
		return $gateways;
	}

	public static function register_payment_options( $options ) {
		// Keep PMPro happy when this gateway is selected in Memberships > Settings > Payments.
		if ( ! in_array( 'gateway_environment', $options, true ) ) {
			$options[] = 'gateway_environment';
		}
		return $options;
	}

	public static function required_billing_fields( $fields ) {
		global $pmpro_gateway;
		if ( 'manual_mp' === $pmpro_gateway ) {
			return array();
		}
		return $fields;
	}

	public static function include_billing_fields( $include ) {
		global $pmpro_gateway;
		if ( 'manual_mp' === $pmpro_gateway ) {
			return false;
		}
		return $include;
	}

	public static function include_payment_fields( $include ) {
		global $pmpro_gateway;
		if ( 'manual_mp' === $pmpro_gateway ) {
			return false;
		}
		return $include;
	}

	public function process( &$order ) {
		$order->gateway              = 'manual_mp';
		$order->gateway_environment  = 'manual';
		$order->payment_type         = 'Manual MercadoPago';
		$order->CardType             = '';
		$order->accountnumber        = '';
		$order->expirationmonth      = '';
		$order->expirationyear       = '';
		$order->status               = 'pending';
		$order->payment_transaction_id = 'manualmp-' . time() . '-' . wp_rand( 1000, 9999 );

		// PMPro expects process() true for successful checkout completion.
		return true;
	}

	public function subscribe( &$order, &$user, $level ) {
		// Same behavior as one-time checkout: create pending manual order and complete checkout.
		return $this->process( $order );
	}

	public function cancel( &$order ) {
		return true;
	}

	public function refund( &$order ) {
		return false;
	}

	public function void( &$order ) {
		return false;
	}
}

endif;
