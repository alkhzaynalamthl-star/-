<?php
/**
 * مصمم الخزانة التفاعلي: الألوان، الكود المختصر، تحميل الملفات.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * التشطيبات المتاحة في المصمم. يمكن تعديلها بالفلتر optimum_config_finishes.
 *
 * @return array
 */
function optimum_config_finishes() {
	return apply_filters(
		'optimum_config_finishes',
		array(
			'oak'    => array( 'name' => __( 'بلوط طبيعي', 'optimum' ), 'color' => '#b98a55', 'grain' => true ),
			'walnut' => array( 'name' => __( 'جوز داكن', 'optimum' ), 'color' => '#5e3d26', 'grain' => true ),
			'white'  => array( 'name' => __( 'أبيض لامع', 'optimum' ), 'color' => '#f2efe9', 'grain' => false ),
			'cream'  => array( 'name' => __( 'بيج كشميري', 'optimum' ), 'color' => '#d9c8ae', 'grain' => false ),
			'stone'  => array( 'name' => __( 'رمادي حجري', 'optimum' ), 'color' => '#8e8a83', 'grain' => false ),
			'olive'  => array( 'name' => __( 'أخضر زيتوني', 'optimum' ), 'color' => '#5d6a57', 'grain' => false ),
			'black'  => array( 'name' => __( 'أسود مطفي', 'optimum' ), 'color' => '#262322', 'grain' => false ),
		)
	);
}

/**
 * هل تحتاج الصفحة الحالية ملفات المصمم؟
 *
 * @return bool
 */
function optimum_needs_configurator() {
	if ( is_front_page() || is_page_template( 'template-configurator.php' ) ) {
		return true;
	}
	$post = get_post();
	return is_singular() && $post && has_shortcode( $post->post_content, 'optimum_configurator' );
}

/**
 * تحميل ملفات المصمم عند الحاجة فقط.
 */
function optimum_configurator_assets() {
	if ( ! optimum_needs_configurator() ) {
		return;
	}
	wp_enqueue_style( 'optimum-configurator', OPTIMUM_URI . '/assets/css/configurator.css', array( 'optimum-main' ), OPTIMUM_VERSION );
	wp_enqueue_script( 'optimum-configurator', OPTIMUM_URI . '/assets/js/configurator.js', array(), OPTIMUM_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'optimum_configurator_assets', 20 );

/**
 * الكود المختصر [optimum_configurator].
 *
 * @return string
 */
function optimum_configurator_shortcode() {
	ob_start();
	get_template_part( 'template-parts/configurator' );
	return ob_get_clean();
}
add_shortcode( 'optimum_configurator', 'optimum_configurator_shortcode' );
