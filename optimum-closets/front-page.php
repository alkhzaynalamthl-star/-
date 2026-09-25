<?php
/**
 * Homepage.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

$optimum_shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$optimum_custom = optimum_page_url( 'made-to-measure' );
$optimum_title  = optimum_mod_i18n( 'hero_title', __( 'Wardrobes designed around your life', 'optimum' ) );
$optimum_text   = optimum_mod_i18n( 'hero_text', __( 'Selected woods, slim-framed glass and light that reveals every detail. Designed, made and installed in Jeddah and Makkah.', 'optimum' ) );
$optimum_hero   = (int) optimum_mod( 'hero_image' );
$optimum_hero_m = (int) optimum_mod( 'hero_image_m' );
?>

<section class="hero" aria-labelledby="hero-title">
	<div class="hero-media">
		<?php
		if ( $optimum_hero ) {
			echo '<picture>';
			if ( $optimum_hero_m ) {
				echo '<source media="(max-width: 699px)" srcset="' . esc_attr( (string) wp_get_attachment_image_srcset( $optimum_hero_m, 'full' ) ) . '" sizes="100vw">';
			}
			echo wp_get_attachment_image( $optimum_hero, 'full', false, array( 'sizes' => '100vw', 'fetchpriority' => 'high', 'loading' => 'eager' ) );
			echo '</picture>';
		} else {
			echo optimum_render_picture( 'hero-glass-wide', '100vw', array( 'eager' => true, 'mobile' => 'hero-glass-mobile' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<div class="container hero-copy">
		<p class="eyebrow"><?php esc_html_e( 'Optimum Closets · Jeddah & Makkah', 'optimum' ); ?></p>
		<h1 id="hero-title"><?php echo esc_html( $optimum_title ); ?></h1>
		<p class="hero-lede"><?php echo esc_html( $optimum_text ); ?></p>
		<div class="hero-ctas">
			<a class="btn btn-hero" href="<?php echo esc_url( $optimum_shop ); ?>"><?php esc_html_e( 'Shop Wardrobes', 'optimum' ); ?></a>
			<a class="btn btn-hero-line" href="<?php echo esc_url( $optimum_custom ); ?>"><?php esc_html_e( 'Request a Made-to-Measure Design', 'optimum' ); ?></a>
		</div>
	</div>
	<ul class="container hero-facts">
		<li><?php echo optimum_icon( 'ruler', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Free consultation & measurement', 'optimum' ); ?></li>
		<li><?php echo optimum_icon( 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( optimum_mod_i18n( 'lead_time' ) ); ?></li>
		<li><?php echo optimum_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Showrooms in Jeddah & Makkah', 'optimum' ); ?></li>
	</ul>
</section>

<?php
$optimum_cats = optimum_product_categories();
if ( $optimum_cats ) :
	?>
	<section class="section container home-cats" aria-labelledby="cats-title">
		<header class="sec-head reveal">
			<span class="sec-idx">01</span>
			<h2 class="sec-title" id="cats-title"><?php esc_html_e( 'Shop by type', 'optimum' ); ?></h2>
			<a class="link-more" href="<?php echo esc_url( $optimum_shop ); ?>"><?php esc_html_e( 'View all', 'optimum' ); ?> <?php echo optimum_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</header>
		<div class="cat-grid">
			<?php foreach ( array_values( $optimum_cats ) as $optimum_i => $optimum_cat ) : ?>
				<a class="cat-card reveal cat-<?php echo (int) $optimum_i; ?>" href="<?php echo esc_url( get_term_link( $optimum_cat ) ); ?>">
					<span class="cat-media"><?php echo optimum_category_image( $optimum_cat, 3 === $optimum_i ? '(min-width: 1100px) 50vw, 90vw' : '(min-width: 1100px) 25vw, 70vw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="cat-cap">
						<span class="cat-n"><?php echo esc_html( sprintf( '%02d', $optimum_i + 1 ) ); ?></span>
						<span class="cat-name"><?php echo esc_html( $optimum_cat->name ); ?></span>
						<?php echo optimum_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php
if ( class_exists( 'WooCommerce' ) ) :
	$optimum_featured = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 4,
			'featured' => true,
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
		)
	);
	if ( ! $optimum_featured ) {
		$optimum_featured = wc_get_products( array( 'status' => 'publish', 'limit' => 4, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	}
	if ( $optimum_featured ) :
		?>
		<section class="section container home-products" aria-labelledby="feat-title">
			<header class="sec-head reveal">
				<span class="sec-idx">02</span>
				<h2 class="sec-title" id="feat-title"><?php esc_html_e( 'Featured wardrobes', 'optimum' ); ?></h2>
				<a class="link-more" href="<?php echo esc_url( $optimum_shop ); ?>"><?php esc_html_e( 'View all', 'optimum' ); ?> <?php echo optimum_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</header>
			<ul class="products product-grid cols-4">
				<?php
				foreach ( $optimum_featured as $optimum_p ) {
					$GLOBALS['product'] = $optimum_p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					$GLOBALS['post']    = get_post( $optimum_p->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $GLOBALS['post'] );
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
				?>
			</ul>
		</section>
		<?php
	endif;
endif;
?>

<section class="section materials" aria-labelledby="mat-title">
	<div class="container mat-in">
		<div class="mat-media reveal" data-tabs-panels>
			<?php
			$optimum_materials = array(
				array( 'detail-handle', __( 'Natural oak', 'optimum' ), __( 'Real straight-grain veneer with brushed brass handles.', 'optimum' ) ),
				array( 'detail-glass-frame', __( 'Glass & slim frame', 'optimum' ), __( 'A 22 mm bronze aluminium frame around tinted glass.', 'optimum' ) ),
				array( 'detail-led-shelf', __( 'Integrated light', 'optimum' ), __( 'Warm LED strips under each shelf show every garment clearly.', 'optimum' ) ),
				array( 'detail-drawer', __( 'Walnut drawers', 'optimum' ), __( 'Soft-close interior drawers with bronze pulls.', 'optimum' ) ),
			);
			foreach ( $optimum_materials as $optimum_i => $optimum_m ) {
				printf(
					'<div class="mat-frame%1$s" id="mat-panel-%2$d" role="tabpanel" aria-labelledby="mat-tab-%2$d"%3$s>%4$s</div>',
					0 === $optimum_i ? ' is-active' : '',
					(int) $optimum_i,
					0 === $optimum_i ? '' : ' aria-hidden="true"',
					optimum_render_picture( $optimum_m[0], '(min-width: 900px) 50vw, 100vw' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
			echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<div class="mat-copy">
			<header class="sec-head reveal">
				<span class="sec-idx">03</span>
				<h2 class="sec-title" id="mat-title"><?php esc_html_e( 'Materials you feel before you see', 'optimum' ); ?></h2>
			</header>
			<div class="mat-list" role="tablist" aria-orientation="vertical" data-tabs>
				<?php foreach ( $optimum_materials as $optimum_i => $optimum_m ) : ?>
					<button class="mat-tab" type="button" role="tab" id="mat-tab-<?php echo (int) $optimum_i; ?>" aria-controls="mat-panel-<?php echo (int) $optimum_i; ?>" aria-selected="<?php echo 0 === $optimum_i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $optimum_i ? '0' : '-1'; ?>">
						<span class="mat-n"><?php echo esc_html( sprintf( '%02d', $optimum_i + 1 ) ); ?></span>
						<span><strong><?php echo esc_html( $optimum_m[1] ); ?></strong><span><?php echo esc_html( $optimum_m[2] ); ?></span></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<section class="section container process" aria-labelledby="proc-title">
	<header class="sec-head reveal">
		<span class="sec-idx">04</span>
		<h2 class="sec-title" id="proc-title"><?php esc_html_e( 'How ordering works', 'optimum' ); ?></h2>
	</header>
	<ol class="steps">
		<?php foreach ( optimum_process_steps() as $optimum_i => $optimum_s ) : ?>
			<li class="step reveal">
				<span class="step-n"><?php echo esc_html( sprintf( '%02d', $optimum_i + 1 ) ); ?></span>
				<h3><?php echo esc_html( $optimum_s[0] ); ?></h3>
				<p><?php echo esc_html( $optimum_s[1] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
</section>

<section class="bespoke-band" aria-labelledby="bespoke-title">
	<div class="bespoke-media">
		<?php echo optimum_render_picture( 'bespoke-wall-wide', '100vw', array( 'mobile' => 'bespoke-wall' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="container bespoke-copy reveal">
		<p class="eyebrow"><?php esc_html_e( 'Made to measure', 'optimum' ); ?></p>
		<h2 id="bespoke-title"><?php esc_html_e( 'A whole wall, drawn to the size of your room', 'optimum' ); ?></h2>
		<p><?php esc_html_e( 'Send your room’s measurements and photos; our designer will come back with a proposal and an initial quote.', 'optimum' ); ?></p>
		<a class="btn btn-hero" href="<?php echo esc_url( $optimum_custom ); ?>"><?php esc_html_e( 'Request a Made-to-Measure Design', 'optimum' ); ?></a>
	</div>
</section>

<section class="section container showrooms" aria-labelledby="show-title">
	<header class="sec-head reveal">
		<span class="sec-idx">05</span>
		<h2 class="sec-title" id="show-title"><?php esc_html_e( 'Visit our showrooms', 'optimum' ); ?></h2>
		<a class="link-more" href="<?php echo esc_url( optimum_page_url( 'showrooms' ) ); ?>"><?php esc_html_e( 'Details', 'optimum' ); ?> <?php echo optimum_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</header>
	<div class="show-grid">
		<?php get_template_part( 'template-parts/showroom-cards' ); ?>
	</div>
</section>

<?php
get_footer();
