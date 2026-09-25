<?php
/**
 * Search form (products first when WooCommerce is active).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
$optimum_sid = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $optimum_sid ); ?>"><?php esc_html_e( 'Search wardrobes', 'optimum' ); ?></label>
	<?php echo optimum_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<input type="search" id="<?php echo esc_attr( $optimum_sid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search: glass wardrobe, sliding doors, walnut…', 'optimum' ); ?>">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Search', 'optimum' ); ?></button>
</form>
