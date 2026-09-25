<?php
/**
 * Product page: gallery with zoom, "see inside" view with hotspots, finish
 * switching with matching images, and the configurator whose price is
 * computed on the server (inc/configurator.php).
 *
 * Products without a configuration fall back to WooCommerce's standard
 * add-to-cart form.
 *
 * @package Optimum
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );
if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$optimum_cfg  = optimum_product_config( $product );
$optimum_sel  = $optimum_cfg ? optimum_initial_selection( $optimum_cfg ) : array();
$optimum_imgs = $optimum_cfg ? optimum_selection_images( $optimum_cfg, $optimum_sel ) : array(
	'closed'  => $product->get_image_id(),
	'open'    => 0,
	'gallery' => $product->get_gallery_image_ids(),
);
$optimum_main = $optimum_imgs['closed'] ? $optimum_imgs['closed'] : $product->get_image_id();
$optimum_cats = wc_get_product_terms( $product->get_id(), 'product_cat' );
$optimum_reg  = optimum_option_registry();
$optimum_ph   = optimum_phone();
$optimum_thumbs = array_values( array_filter( array_merge( array( $optimum_main, $optimum_imgs['open'] ), (array) $optimum_imgs['gallery'] ) ) );
if ( ! $optimum_cfg ) {
	$optimum_thumbs = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp container', $product ); ?>>
	<div class="pdp-grid">
		<section class="pdp-gallery" aria-label="<?php esc_attr_e( 'Product images', 'optimum' ); ?>" data-gallery>
			<div class="stage" data-stage>
				<div class="stage-frame" data-frame>
					<?php
					echo wp_get_attachment_image(
						$optimum_main,
						'large',
						false,
						array(
							'class'         => 'stage-img',
							'sizes'         => '(min-width: 1000px) 55vw, 100vw',
							'fetchpriority' => 'high',
							'loading'       => 'eager',
							'data-stage-img' => '1',
						)
					);
					?>
					<div class="hotspots" data-hotspots aria-hidden="true"></div>
				</div>
				<?php if ( $optimum_main && optimum_is_render( $optimum_main ) ) : ?>
					<span class="render-tag" data-render-tag><?php esc_html_e( 'Illustrative render', 'optimum' ); ?></span>
				<?php endif; ?>
				<button class="icon-btn stage-zoom" type="button" data-zoom>
					<?php echo optimum_icon( 'zoom', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Zoom image', 'optimum' ); ?></span>
				</button>
				<?php if ( $optimum_cfg && $optimum_imgs['open'] ) : ?>
					<div class="inside-toggle" role="radiogroup" aria-label="<?php esc_attr_e( 'See inside', 'optimum' ); ?>">
						<button type="button" role="radio" aria-checked="true" data-view="closed"><?php echo optimum_icon( 'door', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Doors closed', 'optimum' ); ?></button>
						<button type="button" role="radio" aria-checked="false" data-view="open"><?php esc_html_e( 'See inside', 'optimum' ); ?></button>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( count( $optimum_thumbs ) > 1 ) : ?>
				<ul class="thumbs" data-thumbs aria-label="<?php esc_attr_e( 'More images', 'optimum' ); ?>">
					<?php foreach ( $optimum_thumbs as $optimum_i => $optimum_t ) : ?>
						<li>
							<button type="button" class="thumb" data-thumb="<?php echo (int) $optimum_i; ?>" aria-pressed="<?php echo 0 === $optimum_i ? 'true' : 'false'; ?>">
								<?php echo wp_get_attachment_image( $optimum_t, 'thumbnail', false, array( 'loading' => 'lazy' ) ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p class="gallery-note" data-shown-note hidden></p>
			<p class="gallery-note" data-hotspot-note hidden><?php esc_html_e( 'Tap the points to learn about each part of the interior.', 'optimum' ); ?></p>
		</section>

		<section class="pdp-summary">
			<?php if ( $optimum_cats ) : ?>
				<p class="eyebrow"><a href="<?php echo esc_url( get_term_link( $optimum_cats[0] ) ); ?>"><?php echo esc_html( $optimum_cats[0]->name ); ?></a></p>
			<?php endif; ?>
			<h1 class="product_title pdp-title"><?php echo esc_html( $product->get_name() ); ?></h1>
			<?php if ( $product->get_review_count() > 0 ) : ?>
				<?php woocommerce_template_single_rating(); ?>
			<?php endif; ?>
			<div class="pdp-short"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>

			<?php if ( $optimum_cfg ) : ?>
				<form class="cart configurator" method="post" enctype="multipart/form-data" action="<?php echo esc_url( $product->get_permalink() ); ?>" data-configurator>
					<div class="pdp-price" aria-live="polite">
						<span class="pdp-price-label"><?php esc_html_e( 'Your price', 'optimum' ); ?></span>
						<span class="pdp-price-value" data-price><?php echo wp_kses_post( wc_price( optimum_price_for( $optimum_cfg, $optimum_sel )['total'] ) ); ?></span>
						<span class="pdp-price-state" data-price-state></span>
						<?php if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) : ?>
							<span class="pdp-price-note"><?php esc_html_e( 'Includes VAT', 'optimum' ); ?></span>
						<?php endif; ?>
					</div>

					<fieldset class="opt-group">
						<legend><?php esc_html_e( 'Width', 'optimum' ); ?> <span class="opt-current" data-current="width"></span></legend>
						<?php if ( 'per_metre' === $optimum_cfg['pricing'] ) : ?>
							<?php $optimum_pm = wp_parse_args( $optimum_cfg['per_metre'], array( 'min' => 100, 'max' => 600, 'step' => 10 ) ); ?>
							<div class="field-unit field-unit-lg">
								<input type="number" name="oc[width]" value="<?php echo esc_attr( $optimum_sel['width'] ); ?>" min="<?php echo esc_attr( $optimum_pm['min'] ); ?>" max="<?php echo esc_attr( $optimum_pm['max'] ); ?>" step="<?php echo esc_attr( $optimum_pm['step'] ); ?>" inputmode="numeric" required aria-describedby="width-help">
								<span><?php esc_html_e( 'cm', 'optimum' ); ?></span>
							</div>
							<p class="opt-help" id="width-help">
								<?php
								/* translators: 1: min width, 2: max width */
								echo esc_html( sprintf( __( 'Enter your wall width between %1$s and %2$s cm. Our team confirms exact measurements at the free visit.', 'optimum' ), optimum_num( $optimum_pm['min'] ), optimum_num( $optimum_pm['max'] ) ) );
								?>
							</p>
						<?php else : ?>
							<div class="chips-choice">
								<?php foreach ( $optimum_cfg['widths'] as $optimum_w ) : ?>
									<label class="choice">
										<input type="radio" name="oc[width]" value="<?php echo esc_attr( $optimum_w[0] ); ?>" <?php checked( (int) $optimum_sel['width'], (int) $optimum_w[0] ); ?>>
										<span><bdi dir="ltr"><?php echo esc_html( optimum_num( $optimum_w[0] ) ); ?></bdi> <?php esc_html_e( 'cm', 'optimum' ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $optimum_cfg['height'] ) ) : ?>
							<p class="opt-help">
								<?php
								/* translators: 1: height, 2: depth */
								echo esc_html( sprintf( __( 'Height %1$s cm · depth %2$s cm', 'optimum' ), optimum_num( $optimum_cfg['height'] ), optimum_num( $optimum_cfg['depth'] ) ) );
								?>
							</p>
						<?php endif; ?>
					</fieldset>

					<?php
					foreach ( $optimum_cfg['groups'] as $optimum_group => $optimum_options ) :
						$optimum_visual = in_array( $optimum_group, array( 'finish', 'frame', 'glass' ), true );
						?>
						<fieldset class="opt-group opt-<?php echo esc_attr( $optimum_group ); ?>">
							<legend><?php echo esc_html( optimum_option_label( $optimum_group ) ); ?>: <span class="opt-current" data-current="<?php echo esc_attr( $optimum_group ); ?>"><?php echo esc_html( optimum_option_label( $optimum_group, $optimum_sel[ $optimum_group ] ) ); ?></span></legend>
							<div class="<?php echo $optimum_visual ? 'swatch-choice' : ( 'layout' === $optimum_group ? 'cards-choice' : 'chips-choice' ); ?>">
								<?php
								foreach ( $optimum_options as $optimum_key => $optimum_delta ) :
									$optimum_sw = isset( $optimum_reg[ $optimum_group ]['options'][ $optimum_key ][2] ) ? $optimum_reg[ $optimum_group ]['options'][ $optimum_key ][2] : '';
									?>
									<label class="choice<?php echo $optimum_visual ? ' choice-swatch' : ''; ?>">
										<input type="radio" name="oc[<?php echo esc_attr( $optimum_group ); ?>]" value="<?php echo esc_attr( $optimum_key ); ?>" data-label="<?php echo esc_attr( optimum_option_label( $optimum_group, $optimum_key ) ); ?>" <?php checked( $optimum_sel[ $optimum_group ], $optimum_key ); ?>>
										<?php if ( $optimum_visual && $optimum_sw ) : ?>
											<span class="swatch-img"><?php echo optimum_render_picture( $optimum_sw, '56px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
											<span class="<?php echo $optimum_visual ? 'screen-reader-text' : ''; ?>"><?php echo esc_html( optimum_option_label( $optimum_group, $optimum_key ) ); ?></span>
										<?php else : ?>
											<span>
												<?php echo esc_html( optimum_option_label( $optimum_group, $optimum_key ) ); ?>
												<?php if ( (float) $optimum_delta ) : ?>
													<small class="delta">+<?php echo wp_kses_post( wc_price( $optimum_delta ) ); ?></small>
												<?php endif; ?>
											</span>
										<?php endif; ?>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endforeach; ?>

					<?php if ( $optimum_cfg['extras'] ) : ?>
						<fieldset class="opt-group">
							<legend><?php echo esc_html( optimum_option_label( 'extras' ) ); ?></legend>
							<div class="extras">
								<?php foreach ( $optimum_cfg['extras'] as $optimum_key => $optimum_delta ) : ?>
									<label class="check">
										<input type="checkbox" name="oc[extras][]" value="<?php echo esc_attr( $optimum_key ); ?>" <?php checked( in_array( $optimum_key, $optimum_sel['extras'], true ) ); ?>>
										<span><?php echo esc_html( optimum_option_label( 'extras', $optimum_key ) ); ?></span>
										<small class="delta">+<?php echo wp_kses_post( wc_price( $optimum_delta ) ); ?></small>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endif; ?>

					<details class="breakdown" data-breakdown>
						<summary><?php esc_html_e( 'Price breakdown', 'optimum' ); ?></summary>
						<dl data-lines>
							<?php foreach ( optimum_price_for( $optimum_cfg, $optimum_sel )['lines'] as $optimum_l ) : ?>
								<div><dt><?php echo esc_html( $optimum_l[0] ); ?></dt><dd><?php echo wp_kses_post( wc_price( $optimum_l[1] ) ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					</details>

					<div class="buy-row">
						<?php
						woocommerce_quantity_input(
							array(
								'min_value'   => 1,
								'max_value'   => 20,
								'input_value' => 1,
							)
						);
						?>
						<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="btn btn-primary btn-lg single_add_to_cart_button" data-add>
							<?php echo optimum_icon( 'bag', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php esc_html_e( 'Add to cart', 'optimum' ); ?>
						</button>
					</div>
					<p class="form-error" data-form-error role="alert" hidden></p>
				</form>
			<?php else : ?>
				<p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
				<?php woocommerce_template_single_add_to_cart(); ?>
			<?php endif; ?>

			<ul class="assurances">
				<li><?php echo optimum_icon( 'ruler', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Free consultation & measurement before manufacturing', 'optimum' ); ?></span></li>
				<li><?php echo optimum_icon( 'clock', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( optimum_mod_i18n( 'lead_time' ) ); ?></span></li>
				<li><?php echo optimum_icon( 'pin', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'See the finishes in our Jeddah and Makkah showrooms', 'optimum' ); ?></span></li>
			</ul>
			<p class="pdp-help">
				<?php esc_html_e( 'Need a different size or layout?', 'optimum' ); ?>
				<a href="<?php echo esc_url( optimum_page_url( 'made-to-measure' ) ); ?>"><?php esc_html_e( 'Request a made-to-measure design', 'optimum' ); ?></a>
				· <a href="<?php echo esc_url( $optimum_ph['href'] ); ?>"><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></a>
			</p>
		</section>
	</div>

	<section class="pdp-details" aria-label="<?php esc_attr_e( 'Product details', 'optimum' ); ?>">
		<div class="detail-block">
			<h2><?php esc_html_e( 'Dimensions & materials', 'optimum' ); ?></h2>
			<table class="spec">
				<tbody>
					<?php if ( $optimum_cfg ) : ?>
						<tr>
							<th><?php esc_html_e( 'Width', 'optimum' ); ?></th>
							<td>
								<?php
								if ( 'per_metre' === $optimum_cfg['pricing'] ) {
									/* translators: 1: min, 2: max */
									echo esc_html( sprintf( __( 'Any width from %1$s to %2$s cm', 'optimum' ), optimum_num( $optimum_cfg['per_metre']['min'] ), optimum_num( $optimum_cfg['per_metre']['max'] ) ) );
								} else {
									echo esc_html( implode( ' · ', array_map( 'optimum_num', wp_list_pluck( $optimum_cfg['widths'], 0 ) ) ) . ' ' . __( 'cm', 'optimum' ) );
								}
								?>
							</td>
						</tr>
						<?php if ( ! empty( $optimum_cfg['height'] ) ) : ?>
							<tr><th><?php esc_html_e( 'Height', 'optimum' ); ?></th><td><?php echo esc_html( optimum_num( $optimum_cfg['height'] ) . ' ' . __( 'cm', 'optimum' ) ); ?></td></tr>
							<tr><th><?php esc_html_e( 'Depth', 'optimum' ); ?></th><td><?php echo esc_html( optimum_num( $optimum_cfg['depth'] ) . ' ' . __( 'cm', 'optimum' ) ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $optimum_cfg['groups'] as $optimum_group => $optimum_options ) : ?>
							<tr>
								<th><?php echo esc_html( optimum_option_label( $optimum_group ) ); ?></th>
								<td><?php echo esc_html( implode( optimum_is_en() ? ', ' : '، ', array_map( function ( $k ) use ( $optimum_group ) { return optimum_option_label( $optimum_group, $k ); }, array_keys( $optimum_options ) ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php
					foreach ( $product->get_attributes() as $optimum_attr ) :
						if ( ! $optimum_attr->get_visible() ) {
							continue;
						}
						?>
						<tr>
							<th><?php echo esc_html( wc_attribute_label( $optimum_attr->get_name() ) ); ?></th>
							<td><?php echo esc_html( implode( optimum_is_en() ? ', ' : '، ', $optimum_attr->is_taxonomy() ? wp_list_pluck( wc_get_product_terms( $product->get_id(), $optimum_attr->get_name() ), 'name' ) : $optimum_attr->get_options() ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( $product->get_description() ) : ?>
			<div class="detail-block entry-content">
				<h2><?php esc_html_e( 'About this wardrobe', 'optimum' ); ?></h2>
				<?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?>
			</div>
		<?php endif; ?>
		<div class="detail-block">
			<h2><?php esc_html_e( 'Ordering, delivery & installation', 'optimum' ); ?></h2>
			<ol class="mini-steps">
				<?php foreach ( optimum_process_steps() as $optimum_s ) : ?>
					<li><strong><?php echo esc_html( $optimum_s[0] ); ?></strong> <?php echo esc_html( $optimum_s[1] ); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php if ( comments_open() && $product->get_review_count() > 0 ) : ?>
			<div class="detail-block"><?php comments_template(); ?></div>
		<?php endif; ?>
	</section>

	<?php woocommerce_output_related_products(); ?>
</div>

<?php if ( $optimum_cfg ) : ?>
	<div class="sticky-buy" data-sticky-buy hidden>
		<div>
			<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			<span data-price-sticky><?php echo wp_kses_post( wc_price( optimum_price_for( $optimum_cfg, $optimum_sel )['total'] ) ); ?></span>
		</div>
		<button class="btn btn-primary" type="button" data-sticky-add><?php esc_html_e( 'Add to cart', 'optimum' ); ?></button>
	</div>
	<script type="application/json" id="configurator-data"><?php echo wp_json_encode( optimum_configurator_payload( $product, $optimum_cfg, $optimum_sel ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
<?php else : ?>
	<script type="application/json" id="configurator-data"><?php echo wp_json_encode( array( 'gallery' => array_values( array_filter( array_map( 'optimum_image_data', $optimum_thumbs ) ) ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
<?php endif; ?>
<?php
do_action( 'woocommerce_after_single_product' );
