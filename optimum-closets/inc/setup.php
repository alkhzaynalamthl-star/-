<?php
/**
 * إعداد القالب: الدعم، القوائم، الأنماط والسكربتات.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * تفعيل مزايا ووردبريس.
 */
function optimum_setup() {
	load_theme_textdomain( 'optimum', OPTIMUM_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'القائمة الرئيسية', 'optimum' ),
			'footer'  => __( 'روابط التذييل', 'optimum' ),
			'footer2' => __( 'روابط خدمة العملاء', 'optimum' ),
		)
	);

	add_image_size( 'optimum-category', 600, 750, true );
	add_image_size( 'optimum-hero', 1600, 1000, true );
}
add_action( 'after_setup_theme', 'optimum_setup' );

/**
 * عرض المحتوى الافتراضي.
 */
function optimum_content_width() {
	$GLOBALS['content_width'] = 1240;
}
add_action( 'after_setup_theme', 'optimum_content_width', 0 );

/**
 * الأنماط والسكربتات.
 */
function optimum_assets() {
	wp_enqueue_style(
		'optimum-fonts',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Noto+Kufi+Arabic:wght@600;700;800&family=Reem+Kufi:wght@500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'optimum-main', OPTIMUM_URI . '/assets/css/main.css', array(), OPTIMUM_VERSION );

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'optimum-woocommerce', OPTIMUM_URI . '/assets/css/woocommerce.css', array( 'optimum-main' ), OPTIMUM_VERSION );
	}

	if ( is_front_page() ) {
		wp_enqueue_style( 'optimum-home', OPTIMUM_URI . '/assets/css/home.css', array( 'optimum-main' ), OPTIMUM_VERSION );
	}

	wp_add_inline_style( 'optimum-main', optimum_customizer_css() );

	wp_enqueue_script( 'optimum-main', OPTIMUM_URI . '/assets/js/main.js', array(), OPTIMUM_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'optimum_assets' );

/**
 * الاتصال المسبق بخوادم الخطوط لتسريع التحميل.
 *
 * @param array  $urls          الروابط.
 * @param string $relation_type نوع العلاقة.
 * @return array
 */
function optimum_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'optimum_resource_hints', 10, 2 );

/**
 * منطقة الودجات في التذييل وفي الشريط الجانبي للمتجر.
 */
function optimum_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'فلاتر المتجر', 'optimum' ),
			'id'            => 'shop-sidebar',
			'description'   => __( 'تظهر بجانب قائمة المنتجات (مثل فلترة السعر واللون).', 'optimum' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'optimum_widgets_init' );

/**
 * رأس داكن فوق الواجهة السينمائية في الصفحة الرئيسية.
 *
 * @param array $classes الأصناف.
 * @return array
 */
function optimum_body_classes( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'header-dark';
	}
	return $classes;
}
add_filter( 'body_class', 'optimum_body_classes' );

/**
 * لون شريط المتصفح على الجوال.
 */
function optimum_meta_theme_color() {
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( optimum_mod( 'color_dark' ) ) );
}
add_action( 'wp_head', 'optimum_meta_theme_color', 1 );
