<?php
/**
 * Search engines: per-language titles and descriptions, hreflang alternates,
 * canonical URLs, Open Graph and structured data (no invented ratings).
 *
 * If an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active, only the
 * hreflang links are added and the plugin keeps control of everything else.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is an SEO plugin handling titles/meta?
 *
 * @return bool
 */
function optimum_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Brand name and tagline follow the language.
 */
add_filter(
	'option_blogname',
	function ( $name ) {
		return ( ! is_admin() && optimum_is_en() ) ? 'Optimum Closets' : $name;
	}
);
add_filter(
	'option_blogdescription',
	function ( $desc ) {
		if ( is_admin() ) {
			return $desc;
		}
		return optimum_is_en() ? __( 'Made-to-measure wardrobes and walk-in closets in Jeddah and Makkah', 'optimum' ) : ( $desc ? $desc : 'خزائن ملابس وغرف ملابس مفصّلة بالمقاس في جدة ومكة' );
	}
);

/**
 * Localised title for views whose title comes from WordPress core strings.
 */
add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( is_404() ) {
			$parts['title'] = __( 'We could not find that page', 'optimum' );
		} elseif ( is_search() ) {
			/* translators: %s: search terms */
			$parts['title'] = sprintf( __( 'Search results for: %s', 'optimum' ), get_search_query() );
		}
		return $parts;
	}
);

/**
 * Meta description for the current view.
 *
 * @return string
 */
function optimum_meta_description() {
	$desc = '';
	if ( is_front_page() ) {
		$desc = __( 'Wardrobes with wooden or glass hinged doors, sliding wardrobes, walk-in closets and made-to-measure designs. Free consultation and measurement in Jeddah and Makkah.', 'optimum' );
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$p    = wc_get_product( get_queried_object_id() );
		$desc = $p ? optimum_product_short( $p ) : '';
	} elseif ( is_tax() || is_category() ) {
		$t    = get_queried_object();
		$desc = $t && ! empty( $t->description ) ? $t->description : '';
		if ( ! $desc && $t ) {
			/* translators: %s: category name */
			$desc = sprintf( __( '%s from Optimum Closets: compare sizes, finishes and prices, then order online or request a made-to-measure design.', 'optimum' ), $t->name );
		}
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$desc = __( 'Shop wardrobes by door type, wall width, colour and price. Prices in Saudi riyals, installation in Jeddah and Makkah.', 'optimum' );
	} elseif ( is_singular() ) {
		$tpl  = get_page_template_slug();
		$map  = array(
			'templates/made-to-measure.php' => __( 'Send your room measurements and photos and get a made-to-measure wardrobe design and quote from Optimum Closets.', 'optimum' ),
			'templates/showrooms.php'       => __( 'Visit the Optimum Closets showrooms in Jeddah (Prince Sultan Street, Al-Naeem) and Makkah (Third Ring Road).', 'optimum' ),
			'templates/contact.php'         => __( 'Contact Optimum Closets by phone, e-mail or message. Showrooms in Jeddah and Makkah.', 'optimum' ),
			'templates/our-work.php'        => __( 'Wardrobes and walk-in closets designed and installed by Optimum Closets.', 'optimum' ),
		);
		$desc = isset( $map[ $tpl ] ) ? $map[ $tpl ] : wp_strip_all_tags( get_the_excerpt() );
	}
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	return mb_strlen( $desc ) > 160 ? mb_substr( $desc, 0, 157 ) . '…' : $desc;
}

/**
 * Short description of a product in the current language.
 *
 * @param WC_Product $p Product.
 * @return string
 */
function optimum_product_short( $p ) {
	$en = optimum_swap_content() ? optimum_post_en( $p->get_id(), 'excerpt' ) : '';
	return '' !== $en ? $en : wp_strip_all_tags( $p->get_short_description() );
}

/**
 * Canonical URL of the current view (without filters/sorting).
 *
 * @return string
 */
function optimum_canonical() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$l = get_term_link( get_queried_object() );
		return is_wp_error( $l ) ? '' : $l;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return wc_get_page_permalink( 'shop' );
	}
	return '';
}

/**
 * <head> tags.
 */
function optimum_head_meta() {
	if ( is_404() || is_search() ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}

	// hreflang: both languages point at the same page.
	if ( optimum_builtin_i18n() && ! is_404() ) {
		$ar = optimum_lang_url( 'ar' );
		$en = optimum_lang_url( 'en' );
		$ar = remove_query_arg( array( 'concept', 'oc_result', 'add-to-cart' ), $ar );
		$en = remove_query_arg( array( 'concept', 'oc_result', 'add-to-cart' ), $en );
		printf( '<link rel="alternate" hreflang="ar" href="%s">' . "\n", esc_url( $ar ) );
		printf( '<link rel="alternate" hreflang="en" href="%s">' . "\n", esc_url( $en ) );
		printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( $ar ) );
	}

	if ( optimum_seo_plugin_active() ) {
		return;
	}

	$desc = optimum_meta_description();
	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	$canon = optimum_canonical();
	if ( $canon && ! is_singular() ) {
		// WordPress prints the canonical for singular views itself.
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canon ) );
	}

	$title = wp_get_document_title();
	$image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'large' );
	} else {
		$m     = optimum_render_manifest();
		$image = isset( $m['hero-glass-wide'] ) ? OPTIMUM_URI . '/assets/img/renders/hero-glass-wide-1600.jpg' : '';
	}
	$og = array(
		'og:type'             => function_exists( 'is_product' ) && is_product() ? 'product' : 'website',
		'og:site_name'        => get_bloginfo( 'name' ),
		'og:title'            => $title,
		'og:description'      => $desc,
		'og:url'              => $canon,
		'og:image'            => $image,
		'og:locale'           => optimum_is_en() ? 'en_US' : 'ar_SA',
		'og:locale:alternate' => optimum_is_en() ? 'ar_SA' : 'en_US',
	);
	foreach ( $og as $k => $v ) {
		if ( $v ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $k ), esc_attr( $v ) );
		}
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

	optimum_structured_data();
}
add_action( 'wp_head', 'optimum_head_meta', 3 );

/**
 * JSON-LD. Ratings only from real, approved reviews.
 */
function optimum_structured_data() {
	$graph = array();
	if ( is_front_page() ) {
		$stores = array();
		foreach ( array( 'jeddah' => __( 'Jeddah', 'optimum' ), 'makkah' => __( 'Makkah', 'optimum' ) ) as $key => $city ) {
			$stores[] = array(
				'@type'     => 'FurnitureStore',
				/* translators: %s: city */
				'name'      => sprintf( __( 'Optimum Closets — %s showroom', 'optimum' ), $city ),
				'telephone' => optimum_mod( 'phone' ),
				'address'   => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => optimum_mod_i18n( $key ),
					'addressLocality' => $city,
					'addressCountry'  => 'SA',
				),
			);
		}
		$graph[] = array(
			'@type'      => 'Organization',
			'name'       => get_bloginfo( 'name' ),
			'url'        => home_url( '/' ),
			'email'      => optimum_mod( 'email' ),
			'telephone'  => optimum_mod( 'phone' ),
			'department' => $stores,
			'sameAs'     => array_values( array_filter( array( optimum_mod( 'instagram' ) ) ) ),
		);
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p ) {
			$item = array(
				'@type'       => 'Product',
				'name'        => $p->get_name(),
				'description' => optimum_product_short( $p ),
				'sku'         => $p->get_sku(),
				'brand'       => array(
					'@type' => 'Brand',
					'name'  => get_bloginfo( 'name' ),
				),
				'image'       => array_values( array_filter( array( wp_get_attachment_image_url( $p->get_image_id(), 'large' ) ) ) ),
				'offers'      => array(
					'@type'         => 'Offer',
					'priceCurrency' => get_woocommerce_currency(),
					'price'         => wc_format_decimal( $p->get_price(), 2 ),
					'availability'  => $p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
					'url'           => get_permalink( $p->get_id() ),
				),
			);
			if ( $p->get_review_count() > 0 ) {
				$item['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => $p->get_average_rating(),
					'reviewCount' => $p->get_review_count(),
				);
			}
			$graph[] = $item;
		}
	}
	if ( $graph ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}
}

// WooCommerce prints its own product JSON-LD; ours replaces it (same data, language-aware).
add_action(
	'init',
	function () {
		if ( ! optimum_seo_plugin_active() && function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
			remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
		}
	}
);
