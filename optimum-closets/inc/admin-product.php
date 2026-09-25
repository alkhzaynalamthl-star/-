<?php
/**
 * Admin screens: English content fields (built-in language mode) and the
 * product configuration (sizes, options, price deltas, images per finish).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/*
 * ---------------------------------------------------------------------------
 * English fields for products and pages
 * ---------------------------------------------------------------------------
 */
add_action(
	'add_meta_boxes',
	function () {
		if ( optimum_builtin_i18n() ) {
			foreach ( array( 'product', 'page' ) as $type ) {
				add_meta_box( 'oc_english', __( 'English version', 'optimum' ), 'optimum_english_box', $type, 'normal', 'high' );
			}
		}
		add_meta_box( 'oc_config', __( 'Wardrobe options & pricing', 'optimum' ), 'optimum_config_box', 'product', 'normal', 'high' );
	}
);

/**
 * English fields box.
 *
 * @param WP_Post $post Post.
 */
function optimum_english_box( $post ) {
	wp_nonce_field( 'oc_english', 'oc_english_nonce' );
	$fields = array(
		'title'   => array( __( 'Title (English)', 'optimum' ), 'text' ),
		'excerpt' => array( 'product' === $post->post_type ? __( 'Short description (English)', 'optimum' ) : __( 'Summary (English)', 'optimum' ), 'textarea' ),
		'content' => array( __( 'Full content (English)', 'optimum' ), 'textarea' ),
	);
	echo '<p>' . esc_html__( 'Shown when visitors browse in English (/en/). Leave empty to fall back to Arabic.', 'optimum' ) . '</p>';
	foreach ( $fields as $key => $f ) {
		$val = get_post_meta( $post->ID, '_oc_' . $key . '_en', true );
		echo '<p><label style="display:block;font-weight:600;margin-bottom:4px" for="oc_' . esc_attr( $key ) . '_en">' . esc_html( $f[0] ) . '</label>';
		if ( 'text' === $f[1] ) {
			echo '<input class="widefat" dir="ltr" type="text" id="oc_' . esc_attr( $key ) . '_en" name="oc_en[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '">';
		} else {
			echo '<textarea class="widefat" dir="ltr" rows="' . ( 'content' === $key ? 8 : 3 ) . '" id="oc_' . esc_attr( $key ) . '_en" name="oc_en[' . esc_attr( $key ) . ']">' . esc_textarea( $val ) . '</textarea>';
		}
		echo '</p>';
	}
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['oc_english_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['oc_english_nonce'] ), 'oc_english' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$en = isset( $_POST['oc_en'] ) ? (array) wp_unslash( $_POST['oc_en'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( array( 'title', 'excerpt', 'content' ) as $k ) {
			if ( isset( $en[ $k ] ) ) {
				update_post_meta( $post_id, '_oc_' . $k . '_en', 'content' === $k ? wp_kses_post( $en[ $k ] ) : sanitize_textarea_field( $en[ $k ] ) );
			}
		}
	}
);

/*
 * ---------------------------------------------------------------------------
 * Product configuration (JSON, validated on save)
 * ---------------------------------------------------------------------------
 */

/**
 * Configuration box.
 *
 * @param WP_Post $post Product.
 */
function optimum_config_box( $post ) {
	wp_nonce_field( 'oc_config', 'oc_config_nonce' );
	$cfg  = get_post_meta( $post->ID, '_oc_config', true );
	$json = $cfg ? wp_json_encode( is_string( $cfg ) ? json_decode( $cfg, true ) : $cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
	$err  = get_transient( 'oc_config_error_' . $post->ID );
	if ( $err ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html( $err ) . '</p></div>';
		delete_transient( 'oc_config_error_' . $post->ID );
	}
	echo '<p>' . esc_html__( 'Leave empty for a normal WooCommerce product. With a configuration, customers choose width and options on the product page, and the price is calculated on the server from these values. The product’s regular price is kept in sync with the lowest possible price.', 'optimum' ) . '</p>';
	echo '<textarea class="widefat code" dir="ltr" rows="18" name="oc_config_json" spellcheck="false">' . esc_textarea( (string) $json ) . '</textarea>';
	echo '<details><summary>' . esc_html__( 'Format', 'optimum' ) . '</summary><pre dir="ltr" style="white-space:pre-wrap">' . esc_html(
		'{
  "door": "hinged-wood",
  "height": 255, "depth": 60,
  "pricing": "table",                       // or "per_metre"
  "widths": [[160, 4900], [200, 5900], [240, 6900]],
  "per_metre": {"rate": 2950, "min": 120, "max": 600, "step": 10},
  "default": {"width": 240, "finish": "oak", "handle": "brass", "layout": "classic"},
  "groups": {
    "finish": {"oak": 0, "walnut": 900, "cashmere": 400},
    "handle": {"brass": 0, "black": 0},
    "layout": {"classic": 0, "long": 250, "drawers": 750}
  },
  "extras": {"led": 690},
  "image_group": "finish",
  "images": {"oak": {"closed": 123, "open": 124, "gallery": [125]}},
  "hotspots": {"oak": {"rail_double": [35.9, 57.1]}}
}
Option keys: ' . implode( ', ', array_map( function ( $g, $d ) { return $g . ' = ' . implode( '|', array_keys( $d['options'] ) ); }, array_keys( optimum_option_registry() ), optimum_option_registry() ) )
	) . '</pre></details>';
}

add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['oc_config_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['oc_config_nonce'] ), 'oc_config' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = isset( $_POST['oc_config_json'] ) ? trim( (string) wp_unslash( $_POST['oc_config_json'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $raw ) {
			delete_post_meta( $post_id, '_oc_config' );
			return;
		}
		$cfg   = json_decode( $raw, true );
		$error = optimum_check_config( $cfg );
		if ( $error ) {
			set_transient( 'oc_config_error_' . $post_id, $error, 60 );
			return;
		}
		update_post_meta( $post_id, '_oc_config', $cfg );
		add_action(
			'woocommerce_after_product_object_save',
			function ( $p ) use ( $post_id ) {
				static $done = false;
				if ( ! $done && $p->get_id() === $post_id ) {
					$done = true;
					optimum_sync_base_price( $post_id );
				}
			}
		);
	}
);

/**
 * Validate a configuration; returns an error message or ''.
 *
 * @param mixed $cfg Decoded JSON.
 * @return string
 */
function optimum_check_config( $cfg ) {
	if ( ! is_array( $cfg ) ) {
		return __( 'The configuration is not valid JSON.', 'optimum' );
	}
	if ( empty( $cfg['pricing'] ) || ! in_array( $cfg['pricing'], array( 'table', 'per_metre' ), true ) ) {
		return __( '"pricing" must be "table" or "per_metre".', 'optimum' );
	}
	if ( 'table' === $cfg['pricing'] && ( empty( $cfg['widths'] ) || ! is_array( $cfg['widths'] ) ) ) {
		return __( '"widths" must list [width_cm, price] pairs.', 'optimum' );
	}
	if ( 'per_metre' === $cfg['pricing'] && empty( $cfg['per_metre']['rate'] ) ) {
		return __( '"per_metre" needs a "rate".', 'optimum' );
	}
	$reg = optimum_option_registry();
	foreach ( (array) ( isset( $cfg['groups'] ) ? $cfg['groups'] : array() ) as $group => $opts ) {
		if ( ! isset( $reg[ $group ] ) ) {
			/* translators: %s: group key */
			return sprintf( __( 'Unknown option group "%s".', 'optimum' ), $group );
		}
		foreach ( (array) $opts as $key => $delta ) {
			if ( ! isset( $reg[ $group ]['options'][ $key ] ) || ! is_numeric( $delta ) ) {
				/* translators: 1: group, 2: option */
				return sprintf( __( 'Option "%1$s: %2$s" is unknown or its price is not a number.', 'optimum' ), $group, $key );
			}
		}
	}
	$test = optimum_validate_selection( wp_parse_args( $cfg, array( 'groups' => array(), 'extras' => array(), 'widths' => array(), 'per_metre' => array() ) ), isset( $cfg['default'] ) ? $cfg['default'] : array() );
	if ( is_wp_error( $test ) ) {
		/* translators: %s: error */
		return sprintf( __( 'The default selection is not valid: %s', 'optimum' ), $test->get_error_message() );
	}
	return '';
}

/*
 * ---------------------------------------------------------------------------
 * Term fields: English name/description, order, colour swatch
 * ---------------------------------------------------------------------------
 */
foreach ( array( 'product_cat', 'pa_door', 'pa_color' ) as $optimum_tax ) {
	add_action(
		$optimum_tax . '_edit_form_fields',
		function ( $term ) use ( $optimum_tax ) {
			wp_nonce_field( 'oc_term', 'oc_term_nonce' );
			$rows = array(
				'oc_name_en' => __( 'Name (English)', 'optimum' ),
				'oc_desc_en' => __( 'Description (English)', 'optimum' ),
			);
			if ( 'product_cat' === $optimum_tax ) {
				$rows['order'] = __( 'Menu order', 'optimum' );
			}
			if ( 'pa_color' === $optimum_tax ) {
				$rows['oc_hex'] = __( 'Swatch colour (hex)', 'optimum' );
			}
			foreach ( $rows as $key => $label ) {
				printf(
					'<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="text" dir="ltr" id="%1$s" name="oc_term[%1$s]" value="%3$s"></td></tr>',
					esc_attr( $key ),
					esc_html( $label ),
					esc_attr( (string) get_term_meta( $term->term_id, $key, true ) )
				);
			}
		}
	);
	add_action(
		'edited_' . $optimum_tax,
		function ( $term_id ) {
			if ( ! isset( $_POST['oc_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['oc_term_nonce'] ), 'oc_term' ) || ! current_user_can( 'manage_product_terms' ) ) {
				return;
			}
			foreach ( (array) wp_unslash( $_POST['oc_term'] ?? array() ) as $k => $v ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				if ( in_array( $k, array( 'oc_name_en', 'oc_desc_en', 'order', 'oc_hex' ), true ) ) {
					update_term_meta( $term_id, $k, sanitize_text_field( $v ) );
				}
			}
		}
	);
}
