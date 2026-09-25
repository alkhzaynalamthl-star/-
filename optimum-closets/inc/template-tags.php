<?php
/**
 * دوال مساعدة للقوالب: الأيقونات، روابط واتساب، التواصل الاجتماعي.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * أيقونة SVG مضمنة (بدون ملفات خارجية لسرعة التحميل).
 *
 * @param string $name  اسم الأيقونة.
 * @param int    $size  الحجم.
 * @return string
 */
function optimum_icon( $name, $size = 22 ) {
	$paths = array(
		'cart'     => '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'ruler'    => '<path d="M3 17 17 3l4 4L7 21z"/><path d="m7 13 2 2M10 10l2 2M13 7l2 2"/>',
		'truck'    => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
		'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'lock'     => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'gem'      => '<path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18M12 21 8 9l4-6 4 6z"/>',
		'chat'     => '<path d="M21 12a8 8 0 0 1-12 7l-5 1 1-4.5A8 8 0 1 1 21 12z"/>',
		'phone'    => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'check'    => '<path d="m5 12 5 5L20 7"/>',
		'whatsapp' => '<path d="M20.5 3.5A11 11 0 0 0 3.4 17.1L2 22l5-1.3a11 11 0 0 0 5.2 1.3A11 11 0 0 0 20.5 3.5zm-8.3 16.8a9 9 0 0 1-4.6-1.3l-.3-.2-3 .8.8-2.9-.2-.3a9.1 9.1 0 1 1 7.3 3.9zm5-6.8c-.3-.1-1.6-.8-1.9-.9s-.4-.1-.6.1-.7.9-.9 1.1-.3.2-.6.1a7.4 7.4 0 0 1-3.7-3.2c-.3-.5.3-.5.8-1.6a.5.5 0 0 0 0-.5l-.9-2c-.2-.6-.5-.5-.6-.5h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-1 2.3 5.3 5.3 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4.1c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.6-.3z" fill="currentColor" stroke="none"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="icon icon-%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ] // Static markup defined above.
	);
}

/**
 * رابط واتساب مع رسالة جاهزة.
 *
 * @param string $message الرسالة.
 * @return string رابط فارغ إذا لم يُضبط الرقم.
 */
function optimum_whatsapp_url( $message = '' ) {
	$number = preg_replace( '/\D/', '', (string) optimum_mod( 'whatsapp' ) );
	if ( '' === $number ) {
		return '';
	}
	$url = 'https://wa.me/' . $number;
	if ( '' !== $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/**
 * رابط صفحة المتجر.
 *
 * @return string
 */
function optimum_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}
	return home_url( '/' );
}

/**
 * روابط التواصل الاجتماعي.
 */
function optimum_social_links() {
	$networks = array(
		'instagram' => 'انستقرام',
		'snapchat'  => 'سناب شات',
		'tiktok'    => 'تيك توك',
		'x'         => 'X',
	);

	$items = '';
	foreach ( $networks as $key => $label ) {
		$url = optimum_mod( $key );
		if ( $url ) {
			$items .= sprintf( '<li><a href="%s" target="_blank" rel="noopener">%s</a></li>', esc_url( $url ), esc_html( $label ) );
		}
	}

	if ( $items ) {
		echo '<ul class="social-links">' . $items . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
}

/**
 * القائمة الاحتياطية إذا لم تُنشأ قائمة رئيسية بعد: تصنيفات المنتجات.
 */
function optimum_fallback_menu() {
	echo '<ul class="menu">';
	printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'الرئيسية', 'optimum' ) );
	printf( '<li><a href="%s">%s</a></li>', esc_url( optimum_shop_url() ), esc_html__( 'المتجر', 'optimum' ) );

	if ( taxonomy_exists( 'product_cat' ) ) {
		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => 4,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		if ( ! is_wp_error( $cats ) ) {
			foreach ( $cats as $cat ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $cat ) ), esc_html( $cat->name ) );
			}
		}
	}
	echo '</ul>';
}

/**
 * عنوان قسم موحد.
 *
 * @param string $eyebrow نص صغير.
 * @param string $title   العنوان.
 * @param string $link    رابط «عرض الكل» (اختياري).
 */
function optimum_section_head( $eyebrow, $title, $link = '' ) {
	echo '<div class="section-head">';
	echo '<div>';
	if ( $eyebrow ) {
		echo '<span class="eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}
	echo '<h2 class="section-title">' . esc_html( $title ) . '</h2>';
	echo '</div>';
	if ( $link ) {
		printf( '<a class="link-more" href="%s">%s %s</a>', esc_url( $link ), esc_html__( 'عرض الكل', 'optimum' ), optimum_icon( 'arrow', 18 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';
}
