<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pmmmp-confirmation-box" style="margin-top:20px; padding:16px; border:1px solid #ddd;">
	<h3><?php echo esc_html__( 'Pago manual pendiente', 'pmpro-manual-mercadopago' ); ?></h3>
	<div class="pmmmp-instructions"><?php echo wp_kses_post( $settings['instructions_html'] ); ?></div>
	<?php if ( ! empty( $settings['payment_url'] ) ) : ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $settings['payment_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $settings['payment_button_label'] ?: 'Pagar con MercadoPago' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php if ( ! empty( $settings['whatsapp_number'] ) ) : ?>
		<?php $wa_url = 'https://wa.me/' . rawurlencode( $settings['whatsapp_number'] ) . '?text=' . rawurlencode( $whatsapp_message ); ?>
		<p>
			<a class="button" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'Enviar comprobante por WhatsApp', 'pmpro-manual-mercadopago' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
