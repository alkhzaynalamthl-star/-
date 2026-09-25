<?php
/**
 * Product listing: shop, categories, search.
 *
 * @package Optimum
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$optimum_term  = is_product_taxonomy() ? get_queried_object() : null;
$optimum_count = optimum_active_filter_count();
?>
<div class="page-hero shop-hero">
	<div class="container">
		<?php woocommerce_breadcrumb(); ?>
		<h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
		<?php if ( $optimum_term && $optimum_term->description ) : ?>
			<p class="page-desc"><?php echo esc_html( wp_strip_all_tags( $optimum_term->description ) ); ?></p>
		<?php endif; ?>
		<?php
		// Quick category chips.
		$optimum_cats = optimum_product_categories();
		if ( $optimum_cats ) :
			?>
			<nav class="cat-chips" aria-label="<?php esc_attr_e( 'Categories', 'optimum' ); ?>">
				<a class="chip" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" <?php echo is_shop() ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'All', 'optimum' ); ?></a>
				<?php foreach ( $optimum_cats as $optimum_c ) : ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $optimum_c ) ); ?>" <?php echo ( $optimum_term && $optimum_term->term_id === $optimum_c->term_id ) ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $optimum_c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</div>

<div class="container shop-layout">
	<aside class="shop-filters" id="shop-filters-panel" aria-label="<?php esc_attr_e( 'Filters', 'optimum' ); ?>">
		<div class="sheet-head">
			<strong><?php esc_html_e( 'Filters', 'optimum' ); ?></strong>
			<button class="icon-btn" type="button" data-close-filters>
				<?php echo optimum_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close filters', 'optimum' ); ?></span>
			</button>
		</div>
		<?php optimum_shop_filters(); ?>
	</aside>

	<div class="shop-main">
		<div class="shop-toolbar">
			<button class="btn btn-outline filters-toggle" type="button" aria-controls="shop-filters-panel" aria-expanded="false">
				<?php echo optimum_icon( 'filter', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Filters', 'optimum' ); ?>
				<?php if ( $optimum_count ) : ?>
					<span class="badge-count"><?php echo esc_html( optimum_num( $optimum_count ) ); ?></span>
				<?php endif; ?>
			</button>
			<?php
			if ( woocommerce_product_loop() ) {
				woocommerce_result_count();
				woocommerce_catalog_ordering();
			}
			?>
		</div>
		<?php optimum_active_filter_chips(); ?>
		<?php woocommerce_output_all_notices(); ?>

		<?php if ( woocommerce_product_loop() ) : ?>
			<?php woocommerce_product_loop_start(); ?>
			<?php
			while ( have_posts() ) {
				the_post();
				wc_get_template_part( 'content', 'product' );
			}
			?>
			<?php woocommerce_product_loop_end(); ?>
			<?php woocommerce_pagination(); ?>
		<?php else : ?>
			<div class="empty-state">
				<?php echo optimum_icon( 'door', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<h2><?php esc_html_e( 'No wardrobes match these filters', 'optimum' ); ?></h2>
				<p><?php esc_html_e( 'Try removing a filter, or tell us your measurements and we will design one that fits.', 'optimum' ); ?></p>
				<p class="empty-actions">
					<a class="btn btn-outline" href="<?php echo esc_url( optimum_current_listing_url() ); ?>"><?php esc_html_e( 'Clear filters', 'optimum' ); ?></a>
					<a class="btn btn-primary" href="<?php echo esc_url( optimum_page_url( 'made-to-measure' ) ); ?>"><?php esc_html_e( 'Request a Made-to-Measure Design', 'optimum' ); ?></a>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
<div class="sheet-backdrop" data-close-filters hidden></div>
<?php
get_footer( 'shop' );
