<?php
/**
 * Checkout (step 2 of 3).
 *
 * @package Optimum
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

optimum_checkout_steps( 2 );
do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout checkout-layout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Checkout', 'optimum' ); ?>">
	<div class="checkout-main">
		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
			<section class="checkout-card" id="customer_details">
				<h2 class="checkout-card-title"><span class="n">1</span><?php esc_html_e( 'Contact & installation address', 'optimum' ); ?></h2>
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</section>
			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		<?php endif; ?>
	</div>

	<aside class="checkout-side">
		<section class="checkout-card checkout-review">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h2 class="checkout-card-title" id="order_review_heading"><span class="n">2</span><?php esc_html_e( 'Your order & payment', 'optimum' ); ?></h2>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</section>
	</aside>
</form>
<?php
do_action( 'woocommerce_after_checkout_form', $checkout );
