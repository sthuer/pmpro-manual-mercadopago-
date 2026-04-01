<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMMMP_Checkout {

	private static $signup_context = array();

	public static function init() {
		add_filter( 'pmpro_registration_checks', array( __CLASS__, 'registration_checks' ) );
		add_filter( 'pmpro_checkout_level', array( __CLASS__, 'mark_manual_level_checkout' ) );
		add_action( 'pmpro_after_checkout', array( __CLASS__, 'handle_manual_checkout_side_effects' ), 10, 2 );
		add_filter( 'pmpro_confirmation_message', array( __CLASS__, 'confirmation_message' ), 20, 2 );
	}

	/**
	 * Validate gateway eligibility before checkout processing.
	 */
	public static function registration_checks( $okay ) {
		global $pmpro_level, $pmpro_gateway;

		if ( ! $okay || empty( $pmpro_level->id ) || 'manual_mp' !== $pmpro_gateway ) {
			return $okay;
		}

		if ( ! PMMMP_Settings::is_enabled_for_level( (int) $pmpro_level->id ) ) {
			pmpro_setMessage( __( 'Manual MercadoPago is not enabled for this membership level.', 'pmpro-manual-mercadopago' ), 'pmpro_error' );
			return false;
		}

		if ( ! self::is_new_signup_request() ) {
			pmpro_setMessage( __( 'Manual MercadoPago is only available for new signups.', 'pmpro-manual-mercadopago' ), 'pmpro_error' );
			return false;
		}

		return $okay;
	}

	/**
	 * Ensure order is created as manual pending when this flow is selected.
	 */
	public static function mark_manual_level_checkout( $checkout_level ) {
		global $pmpro_gateway;

		if ( empty( $checkout_level ) || 'manual_mp' !== $pmpro_gateway ) {
			return $checkout_level;
		}

		if ( ! PMMMP_Settings::is_enabled_for_level( (int) $checkout_level->id ) ) {
			return $checkout_level;
		}

		// Force no recurring billing/card workflow for manual payments.
		$checkout_level->billing_amount = 0;
		$checkout_level->trial_amount   = 0;

		return $checkout_level;
	}

	/**
	 * Post-checkout side effects only: maintain pending status + notifications + disable active access.
	 */
	public static function handle_manual_checkout_side_effects( $user_id, $morder ) {
		if ( empty( $user_id ) || empty( $morder ) || empty( $morder->membership_id ) ) {
			return;
		}

		if ( 'manual_mp' !== (string) $morder->gateway ) {
			return;
		}

		if ( ! PMMMP_Settings::is_enabled_for_level( (int) $morder->membership_id ) ) {
			return;
		}

		$order_id = absint( $morder->id );
		$user     = get_userdata( $user_id );
		$level    = pmpro_getLevel( (int) $morder->membership_id );

		global $wpdb;
		$wpdb->update(
			$wpdb->pmpro_membership_orders,
			array(
				'status'              => 'pending',
				'gateway'             => 'manual_mp',
				'gateway_environment' => 'manual',
			),
			array( 'id' => $order_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_status', 'pending' );
		update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_payment_method', 'manual_mp' );
		update_user_meta( $user_id, '_pmmmp_latest_order', $order_id );

		// Keep membership inactive until admin approval.
		pmpro_changeMembershipLevel( 0, $user_id );

		PMMMP_Emails::send_initial_user_email( $user, $level, $morder );
		PMMMP_Emails::send_admin_notification(
			$user,
			$level,
			$morder,
			admin_url( 'admin.php?page=pmmmp-manual-payments' )
		);
	}

	public static function confirmation_message( $message, $invoice ) {
		if ( empty( $invoice ) || empty( $invoice->id ) || 'manual_mp' !== (string) $invoice->gateway ) {
			return $message;
		}

		$status = get_metadata( 'pmpro_membership_order', (int) $invoice->id, '_pmmmp_status', true );
		if ( 'pending' !== $status ) {
			return $message;
		}

		$settings = PMMMP_Settings::get_settings();
		$user     = wp_get_current_user();
		$level    = ! empty( $invoice->membership_id ) ? pmpro_getLevel( (int) $invoice->membership_id ) : null;
		$template = PMMMP_PLUGIN_DIR . 'templates/confirmation-instructions.php';

		$whatsapp_message = strtr(
			$settings['whatsapp_template'],
			array(
				'{name}'  => $user->display_name ?? '',
				'{email}' => $user->user_email ?? '',
				'{level}' => $level->name ?? '',
			)
		);

		ob_start();
		require $template;
		$extra = ob_get_clean();

		return $message . $extra;
	}

	/**
	 * Reliable "new signup" detection before checkout side effects run.
	 */
	private static function is_new_signup_request() {
		if ( ! is_user_logged_in() ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( isset( self::$signup_context[ $user_id ] ) ) {
			return self::$signup_context[ $user_id ];
		}

		global $wpdb;
		$has_history = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->pmpro_memberships_users} WHERE user_id = %d LIMIT 1",
				$user_id
			)
		);

		self::$signup_context[ $user_id ] = ! $has_history;
		return self::$signup_context[ $user_id ];
	}
}