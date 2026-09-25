<?php
/**
 * Optimum Closets theme bootstrap.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

define( 'OPTIMUM_VERSION', '2.0.0' );
define( 'OPTIMUM_DIR', get_template_directory() );
define( 'OPTIMUM_URI', get_template_directory_uri() );

// Language first: it decides the request locale and strips the /en/ prefix before WordPress parses the URL.
require OPTIMUM_DIR . '/inc/i18n.php';
require OPTIMUM_DIR . '/inc/setup.php';
require OPTIMUM_DIR . '/inc/customizer.php';
require OPTIMUM_DIR . '/inc/concepts.php';
require OPTIMUM_DIR . '/inc/template-tags.php';
require OPTIMUM_DIR . '/inc/images.php';
require OPTIMUM_DIR . '/inc/seo.php';
require OPTIMUM_DIR . '/inc/requests.php';

if ( class_exists( 'WooCommerce' ) ) {
	require OPTIMUM_DIR . '/inc/catalog.php';
	require OPTIMUM_DIR . '/inc/configurator.php';
	require OPTIMUM_DIR . '/inc/woocommerce.php';
	require OPTIMUM_DIR . '/inc/shop-filters.php';
	require OPTIMUM_DIR . '/inc/preview-gateway.php';
	require OPTIMUM_DIR . '/inc/admin-product.php';
	require OPTIMUM_DIR . '/inc/demo.php';
}
