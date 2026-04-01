<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMMMP_Checkout {

	public static function init() {
		add_action( 'pmpro_after_checkout', array( __CLASS__, 'handle_manual_checkout' ), 10, 2 );
		add_filter( 'pmpro_confirmation_message', array( __CLASS__, 'confirmation_message' ), 20, 2 );
	}

	public static function handle_manual_checkout( $user_id, $morder ) {
		if ( empty( $user_id ) || empty( $morder ) || empty( $morder->membership_id ) ) {
			return;
		}

		if ( ! PMMMP_Settings::is_enabled_for_level( (int) $morder->membership_id ) ) {
			return;
		}

		if ( ! self::is_new_signup( $user_id ) ) {
			return;
		}

		$order_id = absint( $morder->id );
		$user     = get_userdata( $user_id );
		$level    = pmpro_getLevel( (int) $morder->membership_id );

		global $wpdb;
		$wpdb->update(
			$wpdb->pmpro_membership_orders,
			array(
				'status'      => 'pending',
				'gateway'     => 'manual_mp',
				'gateway_environment' => 'manual',
			),
			array( 'id' => $order_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_status', 'pending' );
		update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_payment_method', 'manual_mp' );
		update_user_meta( $user_id, '_pmmmp_latest_order', $order_id );

		// Keep signup pending by removing active level until approved.
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
		if ( empty( $invoice ) || empty( $invoice->id ) ) {
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

	private static function is_new_signup( $user_id ) {
		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );
		return empty( $levels );
	}
}
