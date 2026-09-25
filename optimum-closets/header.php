<?php
/**
 * رأس الصفحة.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'تخطَّ إلى المحتوى', 'optimum' ); ?></a>

<?php
$optimum_announcement = optimum_mod( 'announcement' );
if ( $optimum_announcement ) :
	?>
	<div class="announcement"><div class="container"><?php echo esc_html( $optimum_announcement ); ?></div></div>
<?php endif; ?>

<header class="site-header" id="site-header">
	<div class="container header-inner">
		<button class="icon-btn menu-toggle" type="button" aria-controls="mobile-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'فتح القائمة', 'optimum' ); ?>">
			<?php echo optimum_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>

		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="brand-text" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="brand-mark" aria-hidden="true">
						<svg viewBox="0 0 32 32" width="30" height="30"><rect x="4" y="3" width="24" height="26" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 3v26" stroke="currentColor" stroke-width="2"/><circle cx="13" cy="16" r="1.4" fill="currentColor"/><circle cx="19" cy="16" r="1.4" fill="currentColor"/></svg>
					</span>
					<span class="brand-name"><?php bloginfo( 'name' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'optimum' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => 'optimum_fallback_menu',
				)
			);
			?>
		</nav>

		<div class="header-actions">
			<button class="icon-btn search-toggle" type="button" aria-controls="header-search" aria-expanded="false" aria-label="<?php esc_attr_e( 'بحث', 'optimum' ); ?>">
				<?php echo optimum_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="icon-btn hide-mobile" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'حسابي', 'optimum' ); ?>">
					<?php echo optimum_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<?php optimum_header_cart(); ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="header-search" id="header-search" hidden>
		<div class="container">
			<?php
			if ( class_exists( 'WooCommerce' ) ) {
				get_product_search_form();
			} else {
				get_search_form();
			}
			?>
		</div>
	</div>
</header>

<div class="drawer" id="mobile-drawer" aria-hidden="true">
	<div class="drawer-backdrop" data-close-drawer></div>
	<div class="drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'القائمة', 'optimum' ); ?>">
		<div class="drawer-head">
			<strong><?php bloginfo( 'name' ); ?></strong>
			<button class="icon-btn" type="button" data-close-drawer aria-label="<?php esc_attr_e( 'إغلاق', 'optimum' ); ?>">
				<?php echo optimum_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
		<nav class="drawer-nav">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => 'optimum_fallback_menu',
				)
			);
			?>
		</nav>
		<div class="drawer-foot">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="btn btn-outline btn-block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'حسابي', 'optimum' ); ?></a>
			<?php endif; ?>
			<?php
			$optimum_wa = optimum_whatsapp_url( __( 'مرحباً، أرغب بالاستفسار عن الخزائن', 'optimum' ) );
			if ( $optimum_wa ) :
				?>
				<a class="btn btn-whatsapp btn-block" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
					<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'تواصل عبر واتساب', 'optimum' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<main id="content" class="site-main">
