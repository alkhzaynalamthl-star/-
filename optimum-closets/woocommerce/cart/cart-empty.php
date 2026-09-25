<?php
/**
 * Empty cart.
 *
 * @package Optimum
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<div class="empty-state">
	<?php echo optimum_icon( 'bag', 44 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h2><?php esc_html_e( 'Your cart is empty', 'optimum' ); ?></h2>
	<p><?php esc_html_e( 'Browse our wardrobes, or send us your measurements for a made-to-measure design.', 'optimum' ); ?></p>
	<p class="empty-actions">
		<a class="btn btn-primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Shop Wardrobes', 'optimum' ); ?></a>
		<a class="btn btn-outline" href="<?php echo esc_url( optimum_page_url( 'made-to-measure' ) ); ?>"><?php esc_html_e( 'Request a Made-to-Measure Design', 'optimum' ); ?></a>
	</p>
</div>
