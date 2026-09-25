<?php
/**
 * Design concepts ("directions") as switchable skins.
 *
 * Atelier is the recommended default. Showroom and Bayt stay available: an
 * administrator can switch the default in Customizer → Optimum Closets → Design,
 * and anyone can preview a direction with ?concept=showroom (remembered for the
 * browsing session; ?concept=default clears it). The static concept homepages
 * live in /concepts/ inside the theme.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registered concepts.
 *
 * @return array
 */
function optimum_concepts() {
	return array(
		'atelier'  => array(
			'label'       => __( 'Atelier — quiet architectural luxury', 'optimum' ),
			'fonts'       => array( 'markazi-text-%s-500-normal.woff2', 'ibm-plex-sans-arabic-%s-400-normal.woff2' ),
			'theme_color' => '#1f1a16',
		),
		'showroom' => array(
			'label'       => __( 'Showroom — interactive, after hours', 'optimum' ),
			'fonts'       => array( 'alexandria-%s-500-normal.woff2', 'readex-pro-%s-300-normal.woff2' ),
			'theme_color' => '#12100e',
		),
		'bayt'     => array(
			'label'       => __( 'Bayt — warm and fast', 'optimum' ),
			'fonts'       => array( 'almarai-%s-700-normal.woff2', 'tajawal-%s-400-normal.woff2' ),
			'theme_color' => '#2f4a3c',
		),
	);
}

/**
 * Active concept for this request.
 *
 * @return string
 */
function optimum_concept() {
	static $current = null;
	if ( null !== $current ) {
		return $current;
	}
	$all     = optimum_concepts();
	$current = get_theme_mod( 'concept', 'atelier' );
	if ( ! isset( $all[ $current ] ) ) {
		$current = 'atelier';
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $current;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only preview switch.
	if ( isset( $_GET['concept'] ) ) {
		$req = sanitize_key( wp_unslash( $_GET['concept'] ) );
		if ( isset( $all[ $req ] ) ) {
			$current = $req;
			if ( ! headers_sent() ) {
				setcookie( 'oc_concept', $req, 0, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			}
		} elseif ( 'default' === $req && ! headers_sent() ) {
			setcookie( 'oc_concept', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	} elseif ( isset( $_COOKIE['oc_concept'] ) && isset( $all[ $_COOKIE['oc_concept'] ] ) ) {
		$current = sanitize_key( $_COOKIE['oc_concept'] );
	}
	// phpcs:enable
	return $current;
}
add_action( 'init', 'optimum_concept', 1 );

/**
 * Concept metadata.
 *
 * @param string $key Key.
 * @return mixed
 */
function optimum_concept_meta( $key ) {
	$all = optimum_concepts();
	$c   = $all[ optimum_concept() ];
	return isset( $c[ $key ] ) ? $c[ $key ] : '';
}

/**
 * Font files to preload.
 *
 * @return array
 */
function optimum_concept_fonts() {
	return (array) optimum_concept_meta( 'fonts' );
}

/**
 * Is the concept being previewed (not the site default)?
 *
 * @return bool
 */
function optimum_concept_is_preview() {
	return optimum_concept() !== get_theme_mod( 'concept', 'atelier' );
}
