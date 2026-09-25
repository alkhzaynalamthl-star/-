<?php
/**
 * Order confirmation (step 3 of 3).
 *
 * Says exactly what happened. A payment is only described as received when
 * the payment gateway has verified it on the server (WC_Order::is_paid()).
 * Preview orders are labelled as such.
 *
 * @package Optimum
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order order-confirm">
	<?php optimum_checkout_steps( 3 ); ?>
	<?php
	if ( $order ) :
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		$optimum_preview = optimum_is_preview_order( $order );
		if ( $order->has_status( 'failed' ) ) :
			?>
			<div class="confirm-card is-error">
				<h2><?php esc_html_e( 'Payment was not completed', 'optimum' ); ?></h2>
				<p><?php esc_html_e( 'Your bank or the payment provider declined the transaction, so no order was placed. You can try again.', 'optimum' ); ?></p>
				<p><a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn btn-primary"><?php esc_html_e( 'Try paying again', 'optimum' ); ?></a></p>
			</div>
		<?php else : ?>
			<div class="confirm-card<?php echo $optimum_preview ? ' is-preview' : ''; ?>">
				<?php if ( $optimum_preview ) : ?>
					<p class="eyebrow"><?php esc_html_e( 'Preview mode', 'optimum' ); ?></p>
					<h2><?php esc_html_e( 'Preview order recorded', 'optimum' ); ?></h2>
					<p><?php esc_html_e( 'This store is in preview mode. No payment was taken and this is not a confirmed purchase. The order was saved so the team can test the journey.', 'optimum' ); ?></p>
				<?php elseif ( $order->is_paid() ) : ?>
					<?php echo optimum_icon( 'check', 36 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h2><?php esc_html_e( 'Thank you — your payment was received', 'optimum' ); ?></h2>
					<p><?php esc_html_e( 'We will call you to confirm the measurements and book the installation date.', 'optimum' ); ?></p>
				<?php else : ?>
					<h2><?php esc_html_e( 'Order received — awaiting payment confirmation', 'optimum' ); ?></h2>
					<p><?php esc_html_e( 'We have your order. It will be confirmed once payment is verified; our team will contact you.', 'optimum' ); ?></p>
				<?php endif; ?>
				<ul class="order-overview">
					<li><span><?php esc_html_e( 'Order number', 'optimum' ); ?></span><strong><bdi><?php echo esc_html( $order->get_order_number() ); ?></bdi></strong></li>
					<li><span><?php esc_html_e( 'Date', 'optimum' ); ?></span><strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong></li>
					<li><span><?php esc_html_e( 'Total', 'optimum' ); ?></span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></li>
					<?php if ( $order->get_payment_method_title() ) : ?>
						<li><span><?php esc_html_e( 'Payment method', 'optimum' ); ?></span><strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong></li>
					<?php endif; ?>
				</ul>
				<h3 class="next-title"><?php esc_html_e( 'What happens next', 'optimum' ); ?></h3>
				<ol class="mini-steps">
					<?php foreach ( optimum_process_steps() as $optimum_s ) : ?>
						<li><strong><?php echo esc_html( $optimum_s[0] ); ?></strong> <?php echo esc_html( $optimum_s[1] ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
	<?php else : ?>
		<div class="confirm-card">
			<h2><?php esc_html_e( 'Thank you. Your order has been received.', 'optimum' ); ?></h2>
		</div>
	<?php endif; ?>
</div>
