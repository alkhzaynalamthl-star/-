<?php
/**
 * غلاف صفحات ووكومرس (المتجر، التصنيفات، المنتج).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

$optimum_is_listing = is_shop() || is_product_taxonomy();
?>
<?php if ( $optimum_is_listing ) : ?>
	<div class="page-hero shop-hero">
		<div class="container">
			<?php woocommerce_breadcrumb(); ?>
			<h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
		</div>
	</div>
<?php else : ?>
	<div class="container product-breadcrumb"><?php woocommerce_breadcrumb(); ?></div>
<?php endif; ?>

<div class="container shop-container<?php echo ( $optimum_is_listing && is_active_sidebar( 'shop-sidebar' ) ) ? ' has-sidebar' : ''; ?>">
	<?php if ( $optimum_is_listing && is_active_sidebar( 'shop-sidebar' ) ) : ?>
		<aside class="shop-sidebar" id="shop-sidebar" aria-label="<?php esc_attr_e( 'فلترة المنتجات', 'optimum' ); ?>">
			<div class="shop-sidebar-head">
				<strong><?php esc_html_e( 'فلترة', 'optimum' ); ?></strong>
				<button class="icon-btn" type="button" data-close-filters aria-label="<?php esc_attr_e( 'إغلاق', 'optimum' ); ?>"><?php echo optimum_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<?php dynamic_sidebar( 'shop-sidebar' ); ?>
		</aside>
	<?php endif; ?>

	<div class="shop-main">
		<?php woocommerce_content(); ?>
	</div>
</div>
<?php
get_footer();
