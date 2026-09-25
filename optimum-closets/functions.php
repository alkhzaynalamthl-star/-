<?php
/**
 * قالب الخزائن الأمثل - نقطة الدخول.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

define( 'OPTIMUM_VERSION', '2.0.0' );
define( 'OPTIMUM_DIR', get_template_directory() );
define( 'OPTIMUM_URI', get_template_directory_uri() );

require OPTIMUM_DIR . '/inc/setup.php';
require OPTIMUM_DIR . '/inc/customizer.php';
require OPTIMUM_DIR . '/inc/template-tags.php';
require OPTIMUM_DIR . '/inc/configurator.php';

if ( class_exists( 'WooCommerce' ) ) {
	require OPTIMUM_DIR . '/inc/woocommerce.php';
}
