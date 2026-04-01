<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMMMP_Settings {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_submenu' ), 20 );
	}

	public static function get_settings() {
		$defaults = array(
			'enabled'               => 0,
			'instructions_html'     => '',
			'payment_url'           => '',
			'payment_button_label'  => 'Pagar con MercadoPago',
			'whatsapp_number'       => '',
			'whatsapp_template'     => 'Hola, realicé mi solicitud de membresía. Mi correo es {email} y mi nivel es {level}.',
			'admin_email'           => get_option( 'admin_email' ),
			'enabled_levels'        => array(),
		);

		return wp_parse_args( get_option( PMMMP_OPTION_KEY, array() ), $defaults );
	}

	public static function is_enabled_for_level( $level_id ) {
		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		$enabled_levels = array_map( 'absint', (array) $settings['enabled_levels'] );
		return in_array( absint( $level_id ), $enabled_levels, true );
	}

	public static function register_settings() {
		register_setting( 'pmmmp_settings_group', PMMMP_OPTION_KEY, array( __CLASS__, 'sanitize_settings' ) );
	}

	public static function sanitize_settings( $input ) {
		$sanitized = array();
		$sanitized['enabled']              = ! empty( $input['enabled'] ) ? 1 : 0;
		$sanitized['instructions_html']    = wp_kses_post( $input['instructions_html'] ?? '' );
		$sanitized['payment_url']          = esc_url_raw( $input['payment_url'] ?? '' );
		$sanitized['payment_button_label'] = sanitize_text_field( $input['payment_button_label'] ?? '' );
		$sanitized['whatsapp_number']      = preg_replace( '/[^0-9]/', '', (string) ( $input['whatsapp_number'] ?? '' ) );
		$sanitized['whatsapp_template']    = sanitize_textarea_field( $input['whatsapp_template'] ?? '' );
		$sanitized['admin_email']          = sanitize_email( $input['admin_email'] ?? '' );
		$sanitized['enabled_levels']       = array_map( 'absint', (array) ( $input['enabled_levels'] ?? array() ) );

		return $sanitized;
	}

	public static function add_settings_submenu() {
		add_submenu_page(
			'pmpro-dashboard',
			esc_html__( 'Manual MercadoPago Settings', 'pmpro-manual-mercadopago' ),
			esc_html__( 'Manual MercadoPago', 'pmpro-manual-mercadopago' ),
			'manage_options',
			'pmmmp-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'pmpro-manual-mercadopago' ) );
		}

		$settings = self::get_settings();
		$levels   = pmpro_getAllLevels( true, true );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'PMPro Manual MercadoPago Settings', 'pmpro-manual-mercadopago' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'pmmmp_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable method', 'pmpro-manual-mercadopago' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable manual MercadoPago flow', 'pmpro-manual-mercadopago' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'HTML instructions', 'pmpro-manual-mercadopago' ); ?></th>
						<td><textarea class="large-text" rows="6" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[instructions_html]"><?php echo esc_textarea( $settings['instructions_html'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Payment link', 'pmpro-manual-mercadopago' ); ?></th>
						<td><input class="regular-text" type="url" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[payment_url]" value="<?php echo esc_attr( $settings['payment_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Payment button label', 'pmpro-manual-mercadopago' ); ?></th>
						<td><input class="regular-text" type="text" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[payment_button_label]" value="<?php echo esc_attr( $settings['payment_button_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'WhatsApp number', 'pmpro-manual-mercadopago' ); ?></th>
						<td><input class="regular-text" type="text" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[whatsapp_number]" value="<?php echo esc_attr( $settings['whatsapp_number'] ); ?>" /><p class="description">5491112345678</p></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'WhatsApp message template', 'pmpro-manual-mercadopago' ); ?></th>
						<td><textarea class="large-text" rows="3" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[whatsapp_template]"><?php echo esc_textarea( $settings['whatsapp_template'] ); ?></textarea><p class="description">{name}, {email}, {level}</p></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Admin email', 'pmpro-manual-mercadopago' ); ?></th>
						<td><input class="regular-text" type="email" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[admin_email]" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Membership levels enabled', 'pmpro-manual-mercadopago' ); ?></th>
						<td>
							<?php foreach ( $levels as $level ) : ?>
								<label style="display:block; margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( PMMMP_OPTION_KEY ); ?>[enabled_levels][]" value="<?php echo esc_attr( (string) $level->id ); ?>" <?php checked( in_array( (int) $level->id, array_map( 'intval', (array) $settings['enabled_levels'] ), true ) ); ?> />
									<?php echo esc_html( $level->name ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
