<?php
/**
 * Single product wrapper.
 *
 * @package Optimum
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<div class="container product-crumbs"><?php woocommerce_breadcrumb(); ?></div>
<?php
while ( have_posts() ) {
	the_post();
	wc_get_template_part( 'content', 'single-product' );
}
get_footer( 'shop' );
