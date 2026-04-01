<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMMMP_Emails {

	public static function init() {
		// Intentionally empty for now. Static methods are called by other classes.
	}

	public static function send_initial_user_email( $user, $level, $order ) {
		$settings = PMMMP_Settings::get_settings();
		$subject  = sprintf( 'Tu solicitud de membresía está pendiente: %s', get_bloginfo( 'name' ) );
		$message  = wp_kses_post( $settings['instructions_html'] );
		$message .= '<p><strong>Estado:</strong> Pendiente de validación de pago.</p>';
		$message .= '<p><strong>Nivel:</strong> ' . esc_html( $level->name ?? '' ) . '</p>';
		$message .= '<p><strong>Monto:</strong> ' . esc_html( pmpro_formatPrice( $order->InitialPayment ?? 0 ) ) . '</p>';

		wp_mail( $user->user_email, $subject, $message, self::headers() );
	}

	public static function send_admin_notification( $user, $level, $order, $admin_link ) {
		$settings = PMMMP_Settings::get_settings();
		$to       = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );
		$subject  = sprintf( '[%s] Nueva solicitud manual MercadoPago', get_bloginfo( 'name' ) );
		$phone    = get_user_meta( $user->ID, 'phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $user->ID, 'billing_phone', true );
		}

		$message  = '<p>Se recibió una nueva solicitud manual.</p>';
		$message .= '<ul>';
		$message .= '<li><strong>Nombre:</strong> ' . esc_html( $user->display_name ) . '</li>';
		$message .= '<li><strong>Email:</strong> ' . esc_html( $user->user_email ) . '</li>';
		$message .= '<li><strong>Nivel:</strong> ' . esc_html( $level->name ?? '' ) . '</li>';
		$message .= '<li><strong>Fecha:</strong> ' . esc_html( gmdate( 'Y-m-d H:i:s' ) ) . '</li>';
		$message .= '<li><strong>Monto:</strong> ' . esc_html( pmpro_formatPrice( $order->InitialPayment ?? 0 ) ) . '</li>';
		$message .= '<li><strong>Teléfono:</strong> ' . esc_html( $phone ?: 'N/A' ) . '</li>';
		$message .= '<li><strong>Admin:</strong> <a href="' . esc_url( $admin_link ) . '">Revisar solicitud</a></li>';
		$message .= '</ul>';

		wp_mail( $to, $subject, $message, self::headers() );
	}

	public static function send_approval_email( $user, $level ) {
		$subject = sprintf( 'Membresía aprobada en %s', get_bloginfo( 'name' ) );
		$message = '<p>¡Tu pago fue verificado correctamente!</p>';
		$message .= '<p>Tu membresía <strong>' . esc_html( $level->name ?? '' ) . '</strong> ya está activa.</p>';
		wp_mail( $user->user_email, $subject, $message, self::headers() );
	}

	public static function send_rejection_email( $user, $level ) {
		$subject = sprintf( 'Actualización de tu solicitud en %s', get_bloginfo( 'name' ) );
		$message = '<p>Tu solicitud para la membresía <strong>' . esc_html( $level->name ?? '' ) . '</strong> fue rechazada.</p>';
		$message .= '<p>Por favor, contacta al administrador para más detalles.</p>';
		wp_mail( $user->user_email, $subject, $message, self::headers() );
	}

	private static function headers() {
		return array( 'Content-Type: text/html; charset=UTF-8' );
	}
}
