<?php
/**
 * Header.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
$optimum_transparent = is_front_page() && 'bayt' !== optimum_concept();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'optimum' ); ?></a>
<?php optimum_preview_banner(); ?>

<header class="site-header<?php echo $optimum_transparent ? ' is-transparent' : ''; ?>" id="site-header">
	<div class="container header-inner">
		<button class="icon-btn menu-toggle" type="button" aria-controls="drawer" aria-expanded="false">
			<?php echo optimum_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'optimum' ); ?></span>
		</button>

		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php echo wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'medium', false, array( 'class' => 'brand-logo', 'alt' => get_bloginfo( 'name' ) ) ); ?>
			<?php else : ?>
				<span class="brand-ar" lang="ar">الخزائن الأمثل</span>
				<span class="brand-en" lang="en">Optimum Closets</span>
			<?php endif; ?>
		</a>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Main', 'optimum' ); ?>">
			<?php optimum_primary_nav( 'header' ); ?>
		</nav>

		<div class="header-actions">
			<?php optimum_language_switcher( 'hide-sm' ); ?>
			<button class="icon-btn search-toggle" type="button" aria-controls="header-search" aria-expanded="false">
				<?php echo optimum_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'optimum' ); ?></span>
			</button>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="icon-btn hide-sm" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
					<?php echo optimum_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php esc_html_e( 'My account', 'optimum' ); ?></span>
				</a>
				<?php optimum_header_cart(); ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="header-search" id="header-search" hidden>
		<div class="container">
			<?php get_search_form(); ?>
		</div>
	</div>
</header>

<div class="drawer" id="drawer" hidden>
	<div class="drawer-backdrop" data-close-drawer></div>
	<div class="drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'optimum' ); ?>">
		<div class="drawer-head">
			<span class="brand-ar"><?php bloginfo( 'name' ); ?></span>
			<button class="icon-btn" type="button" data-close-drawer>
				<?php echo optimum_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'optimum' ); ?></span>
			</button>
		</div>
		<nav class="drawer-nav" aria-label="<?php esc_attr_e( 'Main', 'optimum' ); ?>">
			<?php optimum_primary_nav( 'drawer' ); ?>
		</nav>
		<div class="drawer-foot">
			<?php optimum_language_switcher( 'btn btn-outline btn-block' ); ?>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="btn btn-outline btn-block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'My account', 'optimum' ); ?></a>
			<?php endif; ?>
			<?php $optimum_ph = optimum_phone(); ?>
			<a class="btn btn-ghost btn-block" href="<?php echo esc_url( $optimum_ph['href'] ); ?>"><?php echo optimum_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></a>
		</div>
	</div>
</div>

<main id="content" class="site-main" tabindex="-1">
