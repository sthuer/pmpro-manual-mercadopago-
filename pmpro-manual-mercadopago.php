<?php
/**
 * Plugin Name: PMPro Manual MercadoPago
 * Description: Manual MercadoPago payment flow for Paid Memberships Pro new signups.
 * Version: 1.0.0
 * Author: PMPro Manual MercadoPago
 * Requires Plugins: paid-memberships-pro
 * Text Domain: pmpro-manual-mercadopago
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PMMMP_VERSION', '1.0.0' );
define( 'PMMMP_PLUGIN_FILE', __FILE__ );
define( 'PMMMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMMMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PMMMP_OPTION_KEY', 'pmmmp_settings' );

require_once PMMMP_PLUGIN_DIR . 'includes/class-pmmmp-settings.php';
require_once PMMMP_PLUGIN_DIR . 'includes/class-pmmmp-emails.php';
require_once PMMMP_PLUGIN_DIR . 'includes/class-pmmmp-checkout.php';
require_once PMMMP_PLUGIN_DIR . 'includes/class-pmmmp-admin.php';

register_activation_hook(
	PMMMP_PLUGIN_FILE,
	static function() {
		$defaults = array(
			'enabled'             => 0,
			'instructions_html'   => '<p>Realiza el pago en MercadoPago y luego envía tu comprobante por WhatsApp.</p>',
			'payment_url'         => '',
			'payment_button_label'=> 'Pagar con MercadoPago',
			'whatsapp_number'     => '',
			'whatsapp_template'   => 'Hola, realicé mi solicitud de membresía. Mi correo es {email} y mi nivel es {level}.',
			'admin_email'         => get_option( 'admin_email' ),
			'enabled_levels'      => array(),
		);
		$current  = get_option( PMMMP_OPTION_KEY, array() );
		update_option( PMMMP_OPTION_KEY, wp_parse_args( $current, $defaults ) );
	}
);

add_action(
	'plugins_loaded',
	static function() {
		if ( ! function_exists( 'pmpro_getLevel' ) ) {
			return;
		}

		PMMMP_Settings::init();
		PMMMP_Emails::init();
		PMMMP_Checkout::init();
		PMMMP_Admin::init();
	}
);
