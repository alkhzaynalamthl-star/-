<?php
/**
 * Compact product card.
 *
 * Image (hover shows the open, interior view), type, name, size, price and
 * the finishes on offer. Finish swatches swap the card image to the matching
 * render/photo.
 *
 * @package Optimum
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$optimum_cfg    = optimum_product_config( $product );
$optimum_link   = $product->get_permalink();
$optimum_cats   = wc_get_product_terms( $product->get_id(), 'product_cat' );
$optimum_cat    = $optimum_cats ? $optimum_cats[0]->name : '';
$optimum_sizes  = '(min-width: 1100px) 24vw, (min-width: 700px) 45vw, 50vw';
$optimum_sel    = $optimum_cfg ? (array) $optimum_cfg['default'] : array();
$optimum_images = $optimum_cfg ? optimum_selection_images( $optimum_cfg, $optimum_sel ) : array( 'closed' => $product->get_image_id(), 'open' => 0 );
$optimum_closed = $optimum_images['closed'] ? $optimum_images['closed'] : $product->get_image_id();
$optimum_group  = $optimum_cfg ? $optimum_cfg['image_group'] : '';
$optimum_opts   = ( $optimum_cfg && $optimum_group && isset( $optimum_cfg['groups'][ $optimum_group ] ) ) ? array_keys( $optimum_cfg['groups'][ $optimum_group ] ) : array();
$optimum_reg    = optimum_option_registry();
?>
<li <?php wc_product_class( 'card', $product ); ?>>
	<a class="card-media" href="<?php echo esc_url( $optimum_link ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		echo wp_get_attachment_image( $optimum_closed, 'optimum-card', false, array( 'class' => 'card-img', 'sizes' => $optimum_sizes, 'loading' => 'lazy' ) );
		if ( ! empty( $optimum_images['open'] ) ) {
			echo wp_get_attachment_image( $optimum_images['open'], 'optimum-card', false, array( 'class' => 'card-img card-img-open', 'sizes' => $optimum_sizes, 'loading' => 'lazy', 'alt' => '' ) );
		}
		if ( optimum_is_render( $optimum_closed ) ) {
			echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( $product->is_on_sale() ) {
			echo '<span class="card-badge">' . esc_html__( 'Offer', 'optimum' ) . '</span>';
		}
		?>
	</a>
	<div class="card-body">
		<?php if ( $optimum_cat ) : ?>
			<p class="card-cat"><?php echo esc_html( $optimum_cat ); ?></p>
		<?php endif; ?>
		<h3 class="card-name"><a href="<?php echo esc_url( $optimum_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<?php if ( $optimum_cfg && ! empty( $optimum_cfg['height'] ) ) : ?>
			<?php
			$optimum_w = 'per_metre' === $optimum_cfg['pricing'] ? (int) $optimum_cfg['default']['width'] : (int) $optimum_sel['width'];
			?>
			<p class="card-dims"><?php echo optimum_dims( array( $optimum_w, $optimum_cfg['height'], $optimum_cfg['depth'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php wc_get_template( 'loop/rating.php' ); ?>
		<div class="card-foot">
			<p class="card-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			<?php if ( count( $optimum_opts ) > 1 ) : ?>
				<div class="swatches" role="radiogroup" aria-label="<?php echo esc_attr( optimum_option_label( $optimum_group ) ); ?>">
					<?php
					foreach ( $optimum_opts as $optimum_k ) :
						$optimum_set = isset( $optimum_cfg['images'][ $optimum_k ] ) ? $optimum_cfg['images'][ $optimum_k ] : array();
						$optimum_src = ! empty( $optimum_set['closed'] ) ? wp_get_attachment_image_src( $optimum_set['closed'], 'optimum-card' ) : null;
						$optimum_ss  = ! empty( $optimum_set['closed'] ) ? wp_get_attachment_image_srcset( $optimum_set['closed'], 'optimum-card' ) : '';
						$optimum_os  = ! empty( $optimum_set['open'] ) ? wp_get_attachment_image_srcset( $optimum_set['open'], 'optimum-card' ) : '';
						$optimum_sw  = isset( $optimum_reg[ $optimum_group ]['options'][ $optimum_k ][2] ) ? $optimum_reg[ $optimum_group ]['options'][ $optimum_k ][2] : '';
						$optimum_on  = isset( $optimum_sel[ $optimum_group ] ) && $optimum_sel[ $optimum_group ] === $optimum_k;
						?>
						<button type="button" class="swatch" role="radio" aria-checked="<?php echo $optimum_on ? 'true' : 'false'; ?>"
							title="<?php echo esc_attr( optimum_option_label( $optimum_group, $optimum_k ) ); ?>"
							data-src="<?php echo $optimum_src ? esc_url( $optimum_src[0] ) : ''; ?>"
							data-srcset="<?php echo esc_attr( (string) $optimum_ss ); ?>"
							data-open-srcset="<?php echo esc_attr( (string) $optimum_os ); ?>"
							data-href="<?php echo esc_url( add_query_arg( 'oc[' . $optimum_group . ']', $optimum_k, $optimum_link ) ); ?>">
							<?php echo $optimum_sw ? optimum_render_picture( $optimum_sw, '28px' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="screen-reader-text"><?php echo esc_html( optimum_option_label( $optimum_group, $optimum_k ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! $optimum_cfg ) : ?>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		<?php endif; ?>
	</div>
</li>
