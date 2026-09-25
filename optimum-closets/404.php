<?php
/**
 * الصفحة غير موجودة.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container section empty-state">
	<span class="big-404">404</span>
	<h1><?php esc_html_e( 'الصفحة التي تبحث عنها غير موجودة', 'optimum' ); ?></h1>
	<p><?php esc_html_e( 'ربما نُقلت أو حُذفت. يمكنك البحث أو العودة إلى المتجر.', 'optimum' ); ?></p>
	<?php
	if ( class_exists( 'WooCommerce' ) ) {
		get_product_search_form();
	} else {
		get_search_form();
	}
	?>
	<p><a class="btn btn-primary" href="<?php echo esc_url( optimum_shop_url() ); ?>"><?php esc_html_e( 'تسوّق الآن', 'optimum' ); ?></a></p>
</div>
<?php
get_footer();
