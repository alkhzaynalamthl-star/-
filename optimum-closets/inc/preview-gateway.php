<?php
/**
 * "Preview — no payment" checkout method.
 *
 * Available only while preview mode is on. It records the order as a
 * *preview* (status on-hold, flagged `_oc_preview_order`), takes no payment
 * and never reports a payment as successful. The confirmation page and
 * e-mails say so explicitly. Real payments (e.g. mada, Apple Pay, Tabby)
 * need a gateway plugin with server-side verification: see docs/HANDOVER.md.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

// Themes load after plugins_loaded, so the class is declared right away.
add_action( 'after_setup_theme', 'optimum_register_preview_gateway', 0 );

/**
 * Declare the class once WooCommerce is available.
 */
function optimum_register_preview_gateway() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'Optimum_Preview_Gateway' ) ) {
		return;
	}

	/**
	 * Preview gateway.
	 */
	class Optimum_Preview_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id                 = 'oc_preview';
			$this->has_fields         = false;
			$this->method_title       = __( 'Preview — no payment', 'optimum' );
			$this->method_description = __( 'For testing the order journey while the store is in preview mode. Takes no payment and marks orders as preview orders.', 'optimum' );
			$this->title              = __( 'Preview order — no payment is taken', 'optimum' );
			$this->description        = __( 'This store is in preview mode. Your order will be recorded for testing only: no payment is taken and it is not a confirmed purchase.', 'optimum' );
			$this->enabled            = 'yes';
			$this->supports           = array( 'products' );
		}

		/**
		 * Only while preview mode is on.
		 *
		 * @return bool
		 */
		public function is_available() {
			return optimum_preview_mode() && parent::is_available();
		}

		/**
		 * Record the preview order.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_oc_preview_order', 1 );
			$order->update_meta_data( '_oc_lang', optimum_lang() );
			$order->update_status( 'on-hold', __( 'Preview order: no payment was taken. Not a confirmed purchase.', 'optimum' ) );
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}

add_filter(
	'woocommerce_payment_gateways',
	function ( $gateways ) {
		if ( class_exists( 'Optimum_Preview_Gateway' ) ) {
			$gateways[] = 'Optimum_Preview_Gateway';
		}
		return $gateways;
	}
);

/**
 * Is this a preview order?
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function optimum_is_preview_order( $order ) {
	return $order instanceof WC_Order && (bool) $order->get_meta( '_oc_preview_order' );
}

/**
 * Admin: a clear badge on preview orders.
 */
add_action(
	'woocommerce_admin_order_data_after_order_details',
	function ( $order ) {
		if ( optimum_is_preview_order( $order ) ) {
			echo '<p style="clear:both;padding:8px 12px;background:#fff4e5;border-inline-start:4px solid #f0a020"><strong>' . esc_html__( 'Preview order', 'optimum' ) . '</strong> — ' . esc_html__( 'no payment was taken; created while the store was in preview mode.', 'optimum' ) . '</p>';
		}
	}
);

/**
 * E-mails: say it plainly at the top of every customer e-mail for a preview order.
 */
add_action(
	'woocommerce_email_before_order_table',
	function ( $order ) {
		if ( optimum_is_preview_order( $order ) ) {
			echo '<p style="padding:10px;background:#fff4e5"><strong>' . esc_html__( 'Preview order — no payment was taken and this is not a confirmed purchase.', 'optimum' ) . '</strong></p>';
		}
	},
	1
);
