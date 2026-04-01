<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PMMMP_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_manual_payments_menu' ), 30 );
		add_action( 'admin_post_pmmmp_manual_action', array( __CLASS__, 'handle_manual_action' ) );
	}

	public static function add_manual_payments_menu() {
		add_submenu_page(
			'pmpro-dashboard',
			esc_html__( 'Manual Payments', 'pmpro-manual-mercadopago' ),
			esc_html__( 'Manual Payments', 'pmpro-manual-mercadopago' ),
			'manage_options',
			'pmmmp-manual-payments',
			array( __CLASS__, 'render_manual_payments_page' )
		);
	}

	public static function render_manual_payments_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'pmpro-manual-mercadopago' ) );
		}

		$rows = self::get_pending_requests();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Manual MercadoPago Requests', 'pmpro-manual-mercadopago' ); ?></h1>
			<table class="widefat striped">
				<thead><tr>
					<th><?php echo esc_html__( 'Name', 'pmpro-manual-mercadopago' ); ?></th>
					<th><?php echo esc_html__( 'Email', 'pmpro-manual-mercadopago' ); ?></th>
					<th><?php echo esc_html__( 'Level', 'pmpro-manual-mercadopago' ); ?></th>
					<th><?php echo esc_html__( 'Date', 'pmpro-manual-mercadopago' ); ?></th>
					<th><?php echo esc_html__( 'Amount', 'pmpro-manual-mercadopago' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'pmpro-manual-mercadopago' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="6"><?php echo esc_html__( 'No pending requests.', 'pmpro-manual-mercadopago' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->display_name ); ?></td>
								<td><?php echo esc_html( $row->user_email ); ?></td>
								<td><?php echo esc_html( $row->level_name ); ?></td>
								<td><?php echo esc_html( $row->timestamp ); ?></td>
								<td><?php echo esc_html( pmpro_formatPrice( $row->InitialPayment ) ); ?></td>
								<td>
									<?php echo wp_kses_post( self::action_links( $row->id ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function action_links( $order_id ) {
		$approve_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=pmmmp_manual_action&do=approve&order_id=' . absint( $order_id ) ),
			'pmmmp_manual_action_' . absint( $order_id )
		);
		$reject_url  = wp_nonce_url(
			admin_url( 'admin-post.php?action=pmmmp_manual_action&do=reject&order_id=' . absint( $order_id ) ),
			'pmmmp_manual_action_' . absint( $order_id )
		);

		return '<a class="button button-primary" href="' . esc_url( $approve_url ) . '">Approve</a> <a class="button" href="' . esc_url( $reject_url ) . '">Reject</a>';
	}

	public static function handle_manual_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'pmpro-manual-mercadopago' ) );
		}

		$order_id = absint( $_GET['order_id'] ?? 0 );
		$do       = sanitize_text_field( $_GET['do'] ?? '' );
		check_admin_referer( 'pmmmp_manual_action_' . $order_id );

		if ( empty( $order_id ) || ! in_array( $do, array( 'approve', 'reject' ), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=pmmmp-manual-payments' ) );
			exit;
		}

		$order = new MemberOrder( $order_id );
		if ( empty( $order->id ) || empty( $order->user_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=pmmmp-manual-payments' ) );
			exit;
		}

		$level = pmpro_getLevel( (int) $order->membership_id );
		$user  = get_userdata( (int) $order->user_id );

		if ( 'approve' === $do ) {
			pmpro_changeMembershipLevel(
				array(
					'user_id'       => (int) $order->user_id,
					'membership_id' => (int) $order->membership_id,
					'startdate'     => current_time( 'mysql' ),
				)
			);
			update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_status', 'approved' );
			self::update_order_status( $order_id, 'success' );
			PMMMP_Emails::send_approval_email( $user, $level );
		} else {
			update_metadata( 'pmpro_membership_order', $order_id, '_pmmmp_status', 'rejected' );
			self::update_order_status( $order_id, 'cancelled' );
			PMMMP_Emails::send_rejection_email( $user, $level );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pmmmp-manual-payments' ) );
		exit;
	}

	private static function get_pending_requests() {
		global $wpdb;

		$sql = "
			SELECT o.id, o.user_id, o.membership_id, o.timestamp, o.InitialPayment, u.display_name, u.user_email, l.name AS level_name
			FROM {$wpdb->pmpro_membership_orders} o
			INNER JOIN {$wpdb->users} u ON u.ID = o.user_id
			LEFT JOIN {$wpdb->pmpro_membership_levels} l ON l.id = o.membership_id
			WHERE o.gateway = %s AND o.status = %s
			ORDER BY o.timestamp DESC
		";

		return $wpdb->get_results( $wpdb->prepare( $sql, 'manual_mp', 'pending' ) );
	}

	private static function update_order_status( $order_id, $status ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->pmpro_membership_orders,
			array( 'status' => $status ),
			array( 'id' => absint( $order_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
