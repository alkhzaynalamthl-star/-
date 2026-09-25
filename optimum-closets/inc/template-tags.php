<?php
/**
 * Template helpers: icons, contact links, menus, section headings.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon.
 *
 * @param string $name Icon.
 * @param int    $size Size.
 * @return string
 */
function optimum_icon( $name, $size = 22 ) {
	$paths = array(
		'bag'      => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'menu'     => '<path d="M3 8h18M3 16h18"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'chev'     => '<path d="m6 9 6 6 6-6"/>',
		'ruler'    => '<path d="M3 17 17 3l4 4L7 21z"/><path d="m7 13 2 2M10 10l2 2M13 7l2 2"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'pin'      => '<path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
		'phone'    => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'check'    => '<path d="m5 12 5 5L20 7"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'minus'    => '<path d="M5 12h14"/>',
		'filter'   => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'zoom'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>',
		'door'     => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M12 3v18M10 12h.01M14 12h.01"/>',
		'camera'   => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
		'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
		'whatsapp' => '<path d="M20.5 3.5A11 11 0 0 0 3.4 17.1L2 22l5-1.3a11 11 0 0 0 5.2 1.3A11 11 0 0 0 20.5 3.5zm-8.3 16.8a9 9 0 0 1-4.6-1.3l-.3-.2-3 .8.8-2.9-.2-.3a9.1 9.1 0 1 1 7.3 3.9z" fill="currentColor" stroke="none"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icon icon-%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * WhatsApp link (empty until a number is configured).
 *
 * @param string $message Prefilled message.
 * @return string
 */
function optimum_whatsapp_url( $message = '' ) {
	$n = preg_replace( '/\D/', '', (string) optimum_mod( 'whatsapp' ) );
	if ( '' === $n ) {
		return '';
	}
	return 'https://wa.me/' . $n . ( $message ? '?text=' . rawurlencode( $message ) : '' );
}

/**
 * Phone link parts.
 *
 * @return array href, label
 */
function optimum_phone() {
	$raw = (string) optimum_mod( 'phone' );
	$d   = preg_replace( '/\D/', '', $raw );
	$lbl = $raw;
	if ( preg_match( '/^966(\d{2})(\d{3})(\d{4})$/', $d, $m ) ) {
		$lbl = '+966 ' . $m[1] . ' ' . $m[2] . ' ' . $m[3];
	}
	return array(
		'href'  => 'tel:+' . $d,
		'label' => $lbl,
	);
}

/**
 * Showrooms from settings.
 *
 * @return array
 */
function optimum_showrooms() {
	return array(
		array(
			'id'      => 'jeddah',
			'city'    => __( 'Jeddah', 'optimum' ),
			'address' => optimum_mod_i18n( 'jeddah' ),
			'map'     => optimum_mod( 'jeddah_map' ),
		),
		array(
			'id'      => 'makkah',
			'city'    => __( 'Makkah', 'optimum' ),
			'address' => optimum_mod_i18n( 'makkah' ),
			'map'     => optimum_mod( 'makkah_map' ),
		),
	);
}

/**
 * Page permalink by template (e.g. made-to-measure), falling back to home.
 *
 * @param string $template Template file slug without folder/extension.
 * @return string
 */
function optimum_page_url( $template ) {
	static $cache = array();
	if ( ! isset( $cache[ $template ] ) ) {
		$pages              = get_posts(
			array(
				'post_type'      => 'page',
				'posts_per_page' => 1,
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'templates/' . $template . '.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
			)
		);
		$cache[ $template ] = $pages ? get_permalink( $pages[0] ) : '';
	}
	return $cache[ $template ] ? $cache[ $template ] : home_url( '/' );
}

/**
 * Wardrobe categories for "Our Products", in a fixed order.
 *
 * @return WP_Term[]
 */
function optimum_product_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$exclude = array( (int) get_option( 'default_product_cat' ) );
	$acc     = get_term_by( 'slug', 'accessories', 'product_cat' );
	if ( $acc ) {
		$exclude[] = (int) $acc->term_id; // "Our Products" is about wardrobes; accessories stay in the shop.
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'exclude'    => $exclude,
			'orderby'    => 'meta_value_num',
			'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Category image (term thumbnail) as <img>, or a bundled render fallback.
 *
 * @param WP_Term $term  Term.
 * @param string  $sizes sizes attribute.
 * @return string
 */
function optimum_category_image( $term, $sizes = '25vw' ) {
	$id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $id ) {
		return wp_get_attachment_image( $id, 'optimum-card', false, array( 'sizes' => $sizes, 'loading' => 'lazy' ) );
	}
	return '';
}

/**
 * Fallback primary menu: Our Products (categories) + key pages.
 */
function optimum_fallback_menu() {
	optimum_primary_nav();
}

/**
 * Primary navigation. "Our Products" groups the wardrobe categories.
 *
 * @param string $context header | drawer.
 */
function optimum_primary_nav( $context = 'header' ) {
	$cats = optimum_product_categories();
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$id   = 'mega-' . $context;
	echo '<ul class="menu">';
	echo '<li class="menu-item has-mega">';
	printf(
		'<button type="button" class="nav-link mega-toggle" aria-expanded="false" aria-controls="%1$s">%2$s%3$s</button>',
		esc_attr( $id ),
		esc_html__( 'Our Products', 'optimum' ),
		optimum_icon( 'chev', 14 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	printf( '<div class="mega" id="%s" hidden><div class="container mega-in">', esc_attr( $id ) );
	echo '<ul class="mega-list">';
	foreach ( $cats as $cat ) {
		$link = get_term_link( $cat );
		printf(
			'<li><a class="mega-card" href="%1$s"><span class="mega-media">%2$s</span><span class="mega-name">%3$s</span></a></li>',
			esc_url( is_wp_error( $link ) ? $shop : $link ),
			'drawer' === $context ? '' : optimum_category_image( $cat, '200px' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $cat->name )
		);
	}
	printf( '<li class="mega-all"><a href="%1$s">%2$s %3$s</a></li>', esc_url( $shop ), esc_html__( 'All wardrobes', 'optimum' ), optimum_icon( 'arrow', 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</ul></div></div></li>';
	$links = array(
		optimum_page_url( 'made-to-measure' ) => __( 'Made to measure', 'optimum' ),
		optimum_page_url( 'our-work' )        => __( 'Our Work', 'optimum' ),
		optimum_page_url( 'showrooms' )       => __( 'Showrooms', 'optimum' ),
		optimum_page_url( 'contact' )         => __( 'Contact', 'optimum' ),
	);
	foreach ( $links as $url => $label ) {
		$current = trailingslashit( strtok( optimum_lang_url( optimum_lang() ), '?' ) ) === trailingslashit( $url );
		printf( '<li class="menu-item"><a class="nav-link" href="%1$s"%3$s>%2$s</a></li>', esc_url( $url ), esc_html( $label ), $current ? ' aria-current="page"' : '' );
	}
	echo '</ul>';
}

/**
 * Language switcher (keeps the current page, product, filters and cart).
 *
 * @param string $class Extra class.
 */
function optimum_language_switcher( $class = '' ) {
	$other = optimum_is_en() ? 'ar' : 'en';
	printf(
		'<a class="lang-switch %1$s" href="%2$s" hreflang="%3$s" lang="%3$s">%4$s<span>%5$s</span></a>',
		esc_attr( $class ),
		esc_url( optimum_lang_url( $other ) ),
		esc_attr( $other ),
		optimum_icon( 'globe', 16 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'en' === $other ? 'English' : 'العربية'
	);
}

/**
 * Section heading.
 *
 * @param string $index   Small index ("01").
 * @param string $title   Title.
 * @param string $link    Optional "view all" link.
 * @param string $tag     Heading tag.
 */
function optimum_section_head( $index, $title, $link = '', $tag = 'h2' ) {
	echo '<header class="sec-head reveal">';
	if ( $index ) {
		echo '<span class="sec-idx">' . esc_html( $index ) . '</span>';
	}
	printf( '<%1$s class="sec-title">%2$s</%1$s>', tag_escape( $tag ), esc_html( $title ) );
	if ( $link ) {
		printf( '<a class="link-more" href="%s">%s %s</a>', esc_url( $link ), esc_html__( 'View all', 'optimum' ), optimum_icon( 'arrow', 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</header>';
}

/**
 * Steps of the ordering process (confirmed facts only).
 *
 * @return array
 */
function optimum_process_steps() {
	return array(
		array( __( 'Consult & measure', 'optimum' ), __( 'A free visit to measure your space and understand how you live.', 'optimum' ) ),
		array( __( 'Design & approve', 'optimum' ), __( 'We propose the interior and materials; you approve the design and final price.', 'optimum' ) ),
		array( __( 'Crafting', 'optimum' ), optimum_mod_i18n( 'lead_time' ) . '.' ),
		array( __( 'Installation', 'optimum' ), __( 'Our team installs with care and hands over a ready-to-use wardrobe.', 'optimum' ) ),
	);
}

/**
 * Preview-mode banner.
 */
function optimum_preview_banner() {
	if ( ! optimum_preview_mode() ) {
		return;
	}
	echo '<div class="preview-banner" role="note">';
	echo '<strong>' . esc_html__( 'Preview mode', 'optimum' ) . '</strong> ';
	esc_html_e( 'Demo products and prices, illustrative 3D renders, and no real payments.', 'optimum' );
	if ( optimum_concept_is_preview() ) {
		printf( ' <a href="%s">%s</a>', esc_url( add_query_arg( 'concept', 'default' ) ), esc_html__( 'Back to the default design', 'optimum' ) );
	}
	echo '</div>';
}
