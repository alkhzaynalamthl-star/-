<?php
/**
 * Not found.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container section empty-state">
	<span class="big-num" aria-hidden="true">404</span>
	<h1><?php esc_html_e( 'We could not find that page', 'optimum' ); ?></h1>
	<p><?php esc_html_e( 'It may have moved. Try a search, or browse our wardrobes.', 'optimum' ); ?></p>
	<?php get_search_form(); ?>
	<p><a class="btn btn-primary" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Shop Wardrobes', 'optimum' ); ?></a></p>
</div>
<?php
get_footer();
