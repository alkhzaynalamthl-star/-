<?php
/**
 * Arabic / English.
 *
 * Arabic is the default (RTL); English lives under /en/ (LTR), matching the
 * URL scheme the live site already uses. If Polylang or WPML is active the
 * theme defers to it completely and only reads the current language.
 *
 * Built-in mode, in short:
 *  - /en/... requests are detected before WordPress parses the URL; the prefix
 *    is removed for parsing and restored straight after, so canonical
 *    redirects and add_query_arg() still see the real URL.
 *  - home_url() gets the /en/ prefix back on the front end, so every link,
 *    permalink, WooCommerce endpoint and wc-ajax call stays in English.
 *  - The request locale is switched (ar / en_US), which also selects the
 *    theme's .mo file and WooCommerce's language pack.
 *  - Product, page and term content has optional English fields (meta),
 *    edited in the product/term screens (see inc/admin-product.php).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

const OPTIMUM_LANGS = array( 'ar', 'en' );

/**
 * Active multilingual plugin, if any.
 *
 * @return string 'polylang' | 'wpml' | ''
 */
function optimum_ml_plugin() {
	if ( function_exists( 'pll_current_language' ) ) {
		return 'polylang';
	}
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return 'wpml';
	}
	return '';
}

/**
 * Whether the theme handles language itself.
 *
 * @return bool
 */
function optimum_builtin_i18n() {
	return '' === optimum_ml_plugin() && ! ( defined( 'OPTIMUM_DISABLE_I18N' ) && OPTIMUM_DISABLE_I18N );
}

/**
 * Path of the site home ("/" or "/sub/").
 *
 * @return string
 */
function optimum_home_path() {
	$path = wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
	return trailingslashit( $path ? $path : '/' );
}

/**
 * Detect the language of the current request (built-in mode) and strip /en/.
 */
function optimum_detect_request_language() {
	$GLOBALS['optimum_lang']         = 'ar';
	$GLOBALS['optimum_original_uri'] = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	if ( ! optimum_builtin_i18n() ) {
		return;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	$uri  = $GLOBALS['optimum_original_uri'];
	$home = optimum_home_path();
	if ( preg_match( '#^' . preg_quote( $home, '#' ) . 'en(?=/|\?|$)#', $uri ) ) {
		$GLOBALS['optimum_lang'] = 'en';
		$stripped                = preg_replace( '#^' . preg_quote( $home, '#' ) . 'en/?#', $home, $uri, 1 );
		$_SERVER['REQUEST_URI']  = $stripped;
		return;
	}

	// admin-ajax.php has no language in its URL: fall back to the visitor's last page language.
	if ( wp_doing_ajax() && isset( $_COOKIE['oc_lang'] ) && 'en' === $_COOKIE['oc_lang'] ) {
		$GLOBALS['optimum_lang'] = 'en';
	}
}
optimum_detect_request_language();

/**
 * Put the original URI back once WordPress has parsed the request.
 */
function optimum_restore_request_uri() {
	if ( isset( $GLOBALS['optimum_original_uri'] ) ) {
		$_SERVER['REQUEST_URI'] = $GLOBALS['optimum_original_uri'];
	}
}
add_action( 'parse_request', 'optimum_restore_request_uri', 999 );

/**
 * Current language code.
 *
 * @return string 'ar' | 'en'
 */
function optimum_lang() {
	$plugin = optimum_ml_plugin();
	if ( 'polylang' === $plugin ) {
		$l = pll_current_language( 'slug' );
		return 'en' === $l ? 'en' : 'ar';
	}
	if ( 'wpml' === $plugin ) {
		$l = apply_filters( 'wpml_current_language', null );
		return 'en' === $l ? 'en' : 'ar';
	}
	return isset( $GLOBALS['optimum_lang'] ) && 'en' === $GLOBALS['optimum_lang'] ? 'en' : 'ar';
}

/**
 * Is the current request English?
 *
 * @return bool
 */
function optimum_is_en() {
	return 'en' === optimum_lang();
}

/**
 * Pick a value for the current language.
 *
 * @param mixed $ar Arabic value.
 * @param mixed $en English value (falls back to Arabic when empty).
 * @return mixed
 */
function optimum_pick( $ar, $en ) {
	return optimum_is_en() && '' !== $en && null !== $en ? $en : $ar;
}

/**
 * Add /en/ to front-end URLs while browsing in English.
 *
 * @param string $url  URL.
 * @param string $path Path argument.
 * @return string
 */
function optimum_home_url_prefix( $url, $path = '' ) {
	if ( ! optimum_builtin_i18n() || 'en' !== optimum_lang() || ( is_admin() && ! wp_doing_ajax() ) || ! empty( $GLOBALS['optimum_no_prefix'] ) ) {
		return $url;
	}
	return optimum_add_lang_prefix( $url, 'en' );
}
add_filter( 'home_url', 'optimum_home_url_prefix', 10, 2 );

/**
 * Insert or remove the language prefix in a front-end URL.
 *
 * @param string $url  Absolute URL on this site.
 * @param string $lang Target language.
 * @return string
 */
function optimum_add_lang_prefix( $url, $lang ) {
	$home  = untrailingslashit( get_option( 'home' ) );
	$home2 = preg_replace( '#^https?:#', '', $home );
	$check = preg_replace( '#^https?:#', '', $url );
	if ( 0 !== strpos( $check, $home2 ) ) {
		return $url;
	}
	$scheme = substr( $url, 0, strlen( $url ) - strlen( $check ) );
	$rest   = substr( $check, strlen( $home2 ) );
	$rest   = preg_replace( '#^/en(?=/|\?|\#|$)#', '', $rest );
	if ( 'en' === $lang ) {
		$rest = '/en' . ( '' === $rest || '/' === $rest[0] ? $rest : '/' . $rest );
		if ( '/en' === $rest ) {
			$rest = '/en/';
		}
	}
	return $scheme . $home2 . $rest;
}

/**
 * URL of the current page in another language (keeps query string, so the
 * current product, filters and cart session all carry over).
 *
 * @param string $lang Target language.
 * @return string
 */
function optimum_lang_url( $lang ) {
	$plugin = optimum_ml_plugin();
	if ( 'polylang' === $plugin && function_exists( 'pll_the_languages' ) ) {
		$langs = pll_the_languages( array( 'raw' => 1 ) );
		foreach ( (array) $langs as $l ) {
			if ( $lang === $l['slug'] ) {
				return $l['url'];
			}
		}
	}
	if ( 'wpml' === $plugin ) {
		$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		if ( isset( $langs[ $lang ]['url'] ) ) {
			return $langs[ $lang ]['url'];
		}
	}
	$GLOBALS['optimum_no_prefix'] = true;
	$base                         = untrailingslashit( get_option( 'home' ) );
	$GLOBALS['optimum_no_prefix'] = false;
	$uri                          = isset( $GLOBALS['optimum_original_uri'] ) ? $GLOBALS['optimum_original_uri'] : '/';
	$host_root                    = preg_replace( '#^(https?://[^/]+).*$#', '$1', $base );
	return optimum_add_lang_prefix( $host_root . $uri, $lang );
}

/**
 * Locale for the request.
 */
function optimum_switch_locale() {
	if ( ! optimum_builtin_i18n() || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}
	$target = optimum_is_en() ? 'en_US' : 'ar';
	add_filter(
		'locale',
		function () use ( $target ) {
			return $target;
		},
		99
	);
	add_filter(
		'determine_locale',
		function () use ( $target ) {
			return $target;
		},
		99
	);
	if ( get_locale() !== $target || determine_locale() !== $target ) {
		unload_textdomain( 'default' );
		load_default_textdomain( $target );
	}
	// Remember for admin-ajax requests (no URL language there).
	if ( ! headers_sent() && ! wp_doing_ajax() && ( ! isset( $_COOKIE['oc_lang'] ) || $_COOKIE['oc_lang'] !== optimum_lang() ) ) {
		setcookie( 'oc_lang', optimum_lang(), time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
}
add_action( 'after_setup_theme', 'optimum_switch_locale', 0 );

/**
 * Text direction: make sure Arabic is RTL even when the core Arabic language
 * pack is not installed (development environments).
 *
 * @param string $translation Translation.
 * @param string $text        Text.
 * @param string $context     Context.
 * @return string
 */
function optimum_text_direction( $translation, $text, $context ) {
	if ( 'text direction' === $context && 'ltr' === $text && ( ! is_admin() || wp_doing_ajax() ) && optimum_builtin_i18n() ) {
		return optimum_is_en() ? 'ltr' : 'rtl';
	}
	return $translation;
}
add_filter( 'gettext_with_context', 'optimum_text_direction', 10, 3 );

/**
 * <html lang dir>.
 *
 * @param string $output Attributes.
 * @return string
 */
function optimum_language_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}
	return optimum_is_en() ? 'lang="en" dir="ltr"' : 'lang="ar" dir="rtl"';
}
add_filter( 'language_attributes', 'optimum_language_attributes' );

/**
 * Theme translations: English source strings, Arabic in languages/ar.mo.
 */
function optimum_load_textdomain() {
	load_theme_textdomain( 'optimum', OPTIMUM_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'optimum_load_textdomain', 1 );

/*
 * --------------------------------------------------------------------------
 * English content fields (built-in mode only)
 * --------------------------------------------------------------------------
 */

/**
 * Should content be swapped for English fields?
 *
 * @return bool
 */
function optimum_swap_content() {
	return optimum_builtin_i18n() && optimum_is_en() && ( ! is_admin() || wp_doing_ajax() );
}

/**
 * English value of a post field.
 *
 * @param int    $post_id Post ID.
 * @param string $field   title | excerpt | content.
 * @return string
 */
function optimum_post_en( $post_id, $field ) {
	return (string) get_post_meta( $post_id, '_oc_' . $field . '_en', true );
}

add_filter(
	'the_title',
	function ( $title, $post_id = 0 ) {
		if ( $post_id && optimum_swap_content() ) {
			$en = optimum_post_en( $post_id, 'title' );
			if ( '' !== $en ) {
				return $en;
			}
		}
		return $title;
	},
	10,
	2
);

add_filter(
	'single_post_title',
	function ( $title, $post ) {
		if ( $post && optimum_swap_content() ) {
			$en = optimum_post_en( $post->ID, 'title' );
			return '' !== $en ? $en : $title;
		}
		return $title;
	},
	10,
	2
);

add_filter(
	'the_content',
	function ( $content ) {
		$id = get_the_ID();
		if ( $id && optimum_swap_content() ) {
			$en = optimum_post_en( $id, 'content' );
			if ( '' !== $en ) {
				return wpautop( wp_kses_post( $en ) );
			}
		}
		return $content;
	},
	5
);

/*
 * WooCommerce product fields read through getters, not the_title().
 */
foreach ( array( 'name' => 'title', 'short_description' => 'excerpt', 'description' => 'content' ) as $optimum_prop => $optimum_field ) {
	add_filter(
		'woocommerce_product_get_' . $optimum_prop,
		function ( $value, $product ) use ( $optimum_field ) {
			if ( optimum_swap_content() ) {
				$en = optimum_post_en( $product->get_id(), $optimum_field );
				if ( '' !== $en ) {
					return $en;
				}
			}
			return $value;
		},
		10,
		2
	);
}

/**
 * Translated term name/description.
 *
 * @param WP_Term $term Term.
 * @return WP_Term
 */
function optimum_translate_term( $term ) {
	if ( $term instanceof WP_Term && optimum_swap_content() ) {
		$name = get_term_meta( $term->term_id, 'oc_name_en', true );
		if ( $name ) {
			$term->name = $name;
		}
		$desc = get_term_meta( $term->term_id, 'oc_desc_en', true );
		if ( $desc ) {
			$term->description = $desc;
		}
	}
	return $term;
}
add_filter( 'get_term', 'optimum_translate_term' );
add_filter(
	'get_terms',
	function ( $terms ) {
		if ( optimum_swap_content() && is_array( $terms ) ) {
			foreach ( $terms as $i => $t ) {
				if ( $t instanceof WP_Term ) {
					$terms[ $i ] = optimum_translate_term( $t );
				}
			}
		}
		return $terms;
	}
);
add_filter(
	'get_object_terms',
	function ( $terms ) {
		if ( optimum_swap_content() && is_array( $terms ) ) {
			foreach ( $terms as $i => $t ) {
				if ( $t instanceof WP_Term ) {
					$terms[ $i ] = optimum_translate_term( $t );
				}
			}
		}
		return $terms;
	}
);

/**
 * Menu items follow the translated title of the object they link to.
 *
 * @param WP_Post $item Menu item.
 * @return WP_Post
 */
function optimum_translate_menu_item( $item ) {
	if ( ! optimum_swap_content() || ! isset( $item->object ) ) {
		return $item;
	}
	$custom = get_post_meta( $item->ID, '_oc_title_en', true );
	if ( $custom ) {
		$item->title = $custom;
		return $item;
	}
	if ( 'taxonomy' === $item->type ) {
		$en = get_term_meta( (int) $item->object_id, 'oc_name_en', true );
		if ( $en ) {
			$item->title = $en;
		}
	} elseif ( 'post_type' === $item->type ) {
		$en = optimum_post_en( (int) $item->object_id, 'title' );
		if ( $en ) {
			$item->title = $en;
		}
	}
	return $item;
}
add_filter( 'wp_setup_nav_menu_item', 'optimum_translate_menu_item' );

/**
 * WooCommerce strings in Arabic when WooCommerce's own Arabic language pack is
 * not installed (development). On production WordPress installs the official
 * pack automatically for an Arabic site and this fallback steps aside.
 *
 * @param string $translation Translation.
 * @param string $text        Source.
 * @param string $domain      Text domain.
 * @return string
 */
function optimum_wc_arabic_fallback( $translation, $text, $domain ) {
	static $map = null;
	if ( 'woocommerce' !== $domain || $translation !== $text || optimum_is_en() || ( is_admin() && ! wp_doing_ajax() ) ) {
		return $translation;
	}
	if ( null === $map ) {
		$map = include OPTIMUM_DIR . '/languages/woocommerce-ar-fallback.php';
	}
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}
add_filter( 'gettext', 'optimum_wc_arabic_fallback', 20, 3 );
add_filter(
	'ngettext',
	function ( $translation, $single, $plural, $number, $domain ) {
		return optimum_wc_arabic_fallback( $translation, 1 === (int) $number ? $single : $plural, $domain );
	},
	20,
	5
);
add_filter(
	'gettext_with_context',
	function ( $translation, $text, $context, $domain ) {
		return optimum_wc_arabic_fallback( $translation, $text, $domain );
	},
	20,
	4
);
add_filter(
	'ngettext_with_context',
	function ( $translation, $single, $plural, $number, $context, $domain ) {
		return optimum_wc_arabic_fallback( $translation, 1 === (int) $number ? $single : $plural, $domain );
	},
	20,
	6
);

/*
 * --------------------------------------------------------------------------
 * Numbers, currency and dimensions
 * --------------------------------------------------------------------------
 */

/**
 * Western digits with locale separators (the norm in Saudi e-commerce).
 *
 * @param float $n        Number.
 * @param int   $decimals Decimals.
 * @return string
 */
function optimum_num( $n, $decimals = 0 ) {
	return number_format( (float) $n, $decimals, '.', ',' );
}

/**
 * Dimensions "240 × 255 × 60 cm", isolated so they never reorder in RTL.
 *
 * @param array $dims W, H, D in cm.
 * @return string HTML.
 */
function optimum_dims( $dims ) {
	$dims = array_map( 'optimum_num', array_filter( (array) $dims ) );
	if ( ! $dims ) {
		return '';
	}
	return '<bdi class="dims" dir="ltr">' . esc_html( implode( ' × ', $dims ) ) . '</bdi>&nbsp;' . esc_html__( 'cm', 'optimum' );
}
