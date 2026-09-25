<?php
/**
 * Cart (step 1 of 3): each line shows its chosen options, quantity and subtotal.
 *
 * Keeps WooCommerce's form names and classes so its cart script (AJAX update,
 * remove, coupons) keeps working.
 *
 * @package Optimum
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

optimum_checkout_steps( 1 );
do_action( 'woocommerce_before_cart' );
?>
<div class="cart-layout">
	<form class="woocommerce-cart-form cart-items" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>
		<table class="shop_table cart woocommerce-cart-form__contents cart-table">
			<caption class="screen-reader-text"><?php esc_html_e( 'Items in your cart', 'optimum' ); ?></caption>
			<thead class="screen-reader-text">
				<tr>
					<th><?php esc_html_e( 'Image', 'optimum' ); ?></th>
					<th><?php esc_html_e( 'Product and options', 'optimum' ); ?></th>
					<th><?php esc_html_e( 'Quantity', 'optimum' ); ?></th>
					<th><?php esc_html_e( 'Subtotal', 'optimum' ); ?></th>
					<th><?php esc_html_e( 'Remove', 'optimum' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php do_action( 'woocommerce_before_cart_contents' ); ?>
				<?php
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
					$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
					if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						continue;
					}
					$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
					?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
						<td class="product-thumbnail">
							<?php echo $product_permalink ? '<a href="' . esc_url( $product_permalink ) . '" tabindex="-1" aria-hidden="true">' . $thumbnail . '</a>' : $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
						<td class="product-name">
							<h2 class="item-name">
								<?php echo $product_permalink ? '<a href="' . esc_url( $product_permalink ) . '">' . esc_html( $_product->get_name() ) . '</a>' : wp_kses_post( $product_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</h2>
							<?php do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key ); ?>
							<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<p class="item-unit">
								<?php esc_html_e( 'Unit price', 'optimum' ); ?>:
								<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</p>
							<?php if ( $product_permalink && ! empty( $cart_item['oc_selection'] ) ) : ?>
								<a class="item-edit" href="<?php echo esc_url( $product_permalink ); ?>"><?php esc_html_e( 'Change options', 'optimum' ); ?></a>
							<?php endif; ?>
						</td>
						<td class="product-quantity">
							<?php
							$min_quantity = $_product->is_sold_individually() ? 1 : 0;
							$max_quantity = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'woocommerce_cart_item_quantity',
								woocommerce_quantity_input(
									array(
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $max_quantity,
										'min_value'    => $min_quantity,
										'product_name' => $product_name,
									),
									$_product,
									false
								),
								$cart_item_key,
								$cart_item
							);
							?>
						</td>
						<td class="product-subtotal">
							<span class="screen-reader-text"><?php esc_html_e( 'Subtotal', 'optimum' ); ?>:</span>
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
						<td class="product-remove">
							<?php
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%1$s" class="remove" aria-label="%2$s" data-product_id="%3$s" data-product_sku="%4$s">%5$s</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									/* translators: %s: product name */
									esc_attr( sprintf( __( 'Remove %s from cart', 'optimum' ), wp_strip_all_tags( $product_name ) ) ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() ),
									optimum_icon( 'close', 18 )
								),
								$cart_item_key
							);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php do_action( 'woocommerce_cart_contents' ); ?>
				<tr class="cart-actions-row">
					<td colspan="5" class="actions">
						<?php if ( wc_coupons_enabled() ) : ?>
							<div class="coupon">
								<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon code', 'optimum' ); ?></label>
								<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'optimum' ); ?>">
								<button type="submit" class="btn btn-outline" name="apply_coupon" value="1"><?php esc_html_e( 'Apply', 'optimum' ); ?></button>
								<?php do_action( 'woocommerce_cart_coupon' ); ?>
							</div>
						<?php endif; ?>
						<button type="submit" class="btn btn-ghost update-cart" name="update_cart" value="1"><?php esc_html_e( 'Update cart', 'optimum' ); ?></button>
						<?php do_action( 'woocommerce_cart_actions' ); ?>
						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</td>
				</tr>
				<?php do_action( 'woocommerce_after_cart_contents' ); ?>
			</tbody>
		</table>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
		<a class="link-back" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo optimum_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Continue shopping', 'optimum' ); ?></a>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
	<aside class="cart-collaterals">
		<?php do_action( 'woocommerce_cart_collaterals' ); ?>
	</aside>
</div>
<?php
do_action( 'woocommerce_after_cart' );
