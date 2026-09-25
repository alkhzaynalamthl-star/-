<?php
/**
 * Theme supports, menus, assets.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme supports and menus.
 */
function optimum_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Main menu', 'optimum' ),
			'footer'  => __( 'Footer links', 'optimum' ),
		)
	);

	add_image_size( 'optimum-card', 800, 1000, true );
	add_image_size( 'optimum-wide', 1600, 800, true );
}
add_action( 'after_setup_theme', 'optimum_setup' );

/**
 * Content width.
 */
function optimum_content_width() {
	$GLOBALS['content_width'] = 1320;
}
add_action( 'after_setup_theme', 'optimum_content_width', 0 );

/**
 * Asset URL with cache-busting by file time.
 *
 * @param string $rel Relative path.
 * @return string
 */
function optimum_asset( $rel ) {
	$file = OPTIMUM_DIR . '/assets/' . $rel;
	$ver  = file_exists( $file ) ? filemtime( $file ) : OPTIMUM_VERSION;
	return add_query_arg( 'v', $ver, OPTIMUM_URI . '/assets/' . $rel );
}

/**
 * Styles and scripts.
 */
function optimum_assets() {
	wp_enqueue_style( 'optimum-fonts', optimum_asset( 'fonts/fonts.css' ), array(), null );
	wp_enqueue_style( 'optimum-store', optimum_asset( 'css/store.css' ), array( 'optimum-fonts' ), null );

	wp_enqueue_script( 'optimum-store', optimum_asset( 'js/store.js' ), array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'optimum-store',
		'OptimumStore',
		array(
			'lang'    => optimum_lang(),
			'reduced' => false,
			'i18n'    => array(
				'added'   => __( 'Added to your cart', 'optimum' ),
				'viewCart' => __( 'View cart', 'optimum' ),
				'error'   => __( 'Something went wrong. Please try again.', 'optimum' ),
				'loading' => __( 'Loading…', 'optimum' ),
			),
		)
	);

	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'optimum-product', optimum_asset( 'js/product.js' ), array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'optimum_assets' );

/**
 * Preload the two fonts every page of the active concept uses.
 */
function optimum_preload_fonts() {
	$files = optimum_concept_fonts();
	$sub   = optimum_is_en() ? 'latin' : 'arabic';
	foreach ( $files as $f ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( OPTIMUM_URI . '/assets/fonts/' . sprintf( $f, $sub ) ) );
	}
}
add_action( 'wp_head', 'optimum_preload_fonts', 2 );

/**
 * Browser theme colour.
 */
function optimum_meta_theme_color() {
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( optimum_concept_meta( 'theme_color' ) ) );
}
add_action( 'wp_head', 'optimum_meta_theme_color', 1 );

/**
 * Widgets: optional extra area under the shop filters.
 */
function optimum_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Shop sidebar (below filters)', 'optimum' ),
			'id'            => 'shop-sidebar',
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'optimum_widgets_init' );

/**
 * Body classes: concept, language, preview mode.
 *
 * @param array $classes Classes.
 * @return array
 */
function optimum_body_classes( $classes ) {
	$classes[] = 'concept-' . optimum_concept();
	$classes[] = 'lang-' . optimum_lang();
	if ( optimum_preview_mode() ) {
		$classes[] = 'is-preview-mode';
	}
	return $classes;
}
add_filter( 'body_class', 'optimum_body_classes' );

/**
 * Preview mode: demo data and no live payments/integrations.
 * On by default until an administrator switches it off in the Customizer
 * (or defines OPTIMUM_PREVIEW_MODE false in wp-config.php).
 *
 * @return bool
 */
function optimum_preview_mode() {
	if ( defined( 'OPTIMUM_PREVIEW_MODE' ) ) {
		return (bool) OPTIMUM_PREVIEW_MODE;
	}
	return (bool) get_theme_mod( 'preview_mode', true );
}
