<?php
/**
 * Configurator ↔ WooCommerce: price endpoint, cart, order.
 *
 * The browser only ever sends *choices* (width, finish key, …). Prices are
 * computed here from product data on every request: in the live price
 * endpoint, when adding to the cart, and again on every cart total
 * calculation, so a tampered request can never change what is charged.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/*
 * ---------------------------------------------------------------------------
 * Live price endpoint (POST /wp-json/optimum/v1/price)
 * ---------------------------------------------------------------------------
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'optimum/v1',
			'/price',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // Public, read-only: returns a price for public product data.
				'args'                => array(
					'product_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'selection'  => array(
						'type'    => 'object',
						'default' => array(),
					),
					'lang'       => array(
						'type'    => 'string',
						'default' => 'ar',
					),
				),
				'callback'            => 'optimum_rest_price',
			)
		);
	}
);

/**
 * Price endpoint callback.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function optimum_rest_price( $req ) {
	$product = wc_get_product( (int) $req['product_id'] );
	if ( ! $product || 'publish' !== $product->get_status() ) {
		return new WP_Error( 'not_found', __( 'Product not found.', 'optimum' ), array( 'status' => 404 ) );
	}
	$cfg = optimum_product_config( $product );
	if ( ! $cfg ) {
		return new WP_Error( 'no_config', __( 'This product has no options.', 'optimum' ), array( 'status' => 400 ) );
	}
	$sel = optimum_validate_selection( $cfg, (array) $req['selection'] );
	if ( is_wp_error( $sel ) ) {
		return new WP_Error( $sel->get_error_code(), $sel->get_error_message(), array( 'status' => 422 ) );
	}
	$price = optimum_price_for( $cfg, $sel );
	$lines = array();
	foreach ( $price['lines'] as $l ) {
		$lines[] = array(
			'label'  => $l[0],
			'amount' => wc_price( $l[1] ),
		);
	}
	return rest_ensure_response(
		array(
			'total'      => $price['total'],
			'total_html' => wc_price( $price['total'] ),
			'lines'      => $lines,
			'selection'  => $sel,
		)
	);
}

/*
 * ---------------------------------------------------------------------------
 * Cart
 * ---------------------------------------------------------------------------
 */

/**
 * Raw selection posted with an add-to-cart request.
 *
 * @return array
 */
function optimum_posted_selection() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce add-to-cart requests are nonce-less by design; values are validated against product data.
	return isset( $_POST['oc'] ) && is_array( $_POST['oc'] ) ? wp_unslash( $_POST['oc'] ) : ( isset( $_GET['oc'] ) && is_array( $_GET['oc'] ) ? wp_unslash( $_GET['oc'] ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

add_filter(
	'woocommerce_add_to_cart_validation',
	function ( $passed, $product_id ) {
		$cfg = optimum_product_config( $product_id );
		if ( ! $cfg ) {
			return $passed;
		}
		$sel = optimum_validate_selection( $cfg, optimum_posted_selection() );
		if ( is_wp_error( $sel ) ) {
			wc_add_notice( $sel->get_error_message(), 'error' );
			return false;
		}
		return $passed;
	},
	10,
	2
);

add_filter(
	'woocommerce_add_cart_item_data',
	function ( $data, $product_id ) {
		$cfg = optimum_product_config( $product_id );
		if ( $cfg ) {
			$sel = optimum_validate_selection( $cfg, optimum_posted_selection() );
			if ( ! is_wp_error( $sel ) ) {
				$data['oc_selection'] = $sel;
			}
		}
		return $data;
	},
	10,
	2
);

add_filter(
	'woocommerce_get_cart_item_from_session',
	function ( $item, $values ) {
		if ( isset( $values['oc_selection'] ) ) {
			$item['oc_selection'] = $values['oc_selection'];
		}
		return $item;
	},
	10,
	2
);

/**
 * Re-price every configured line from product data.
 *
 * @param WC_Cart $cart Cart.
 */
function optimum_reprice_cart( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $key => $item ) {
		if ( empty( $item['oc_selection'] ) ) {
			continue;
		}
		$cfg = optimum_product_config( $item['product_id'] );
		$sel = $cfg ? optimum_validate_selection( $cfg, $item['oc_selection'] ) : new WP_Error( 'gone', '' );
		if ( is_wp_error( $sel ) ) {
			$cart->remove_cart_item( $key );
			wc_add_notice( __( 'An item in your cart was removed because its options are no longer available.', 'optimum' ), 'notice' );
			continue;
		}
		$price = optimum_price_for( $cfg, $sel );
		$item['data']->set_price( $price['total'] );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'optimum_reprice_cart', 20 );

/**
 * Show the chosen options under each cart / checkout line, in the current language.
 */
add_filter(
	'woocommerce_get_item_data',
	function ( $data, $item ) {
		if ( empty( $item['oc_selection'] ) ) {
			return $data;
		}
		$cfg = optimum_product_config( $item['product_id'] );
		if ( ! $cfg ) {
			return $data;
		}
		foreach ( optimum_selection_summary( $cfg, $item['oc_selection'] ) as $label => $value ) {
			$data[] = array(
				'key'   => $label,
				'value' => $value,
			);
		}
		return $data;
	},
	10,
	2
);

/**
 * Cart line thumbnail follows the chosen finish.
 */
add_filter(
	'woocommerce_cart_item_thumbnail',
	function ( $thumb, $item ) {
		if ( empty( $item['oc_selection'] ) ) {
			return $thumb;
		}
		$cfg = optimum_product_config( $item['product_id'] );
		if ( ! $cfg ) {
			return $thumb;
		}
		$imgs = optimum_selection_images( $cfg, $item['oc_selection'] );
		return $imgs['closed'] ? wp_get_attachment_image( $imgs['closed'], 'woocommerce_thumbnail' ) : $thumb;
	},
	10,
	2
);

/**
 * Link from the cart back to the product with the same options pre-selected.
 */
add_filter(
	'woocommerce_cart_item_permalink',
	function ( $url, $item ) {
		if ( $url && ! empty( $item['oc_selection'] ) ) {
			$args = array();
			foreach ( $item['oc_selection'] as $k => $v ) {
				if ( 'extras' === $k ) {
					foreach ( $v as $i => $x ) {
						$args[ "oc[extras][$i]" ] = $x;
					}
				} else {
					$args[ "oc[$k]" ] = $v;
				}
			}
			$url = add_query_arg( $args, $url );
		}
		return $url;
	},
	10,
	2
);

/*
 * ---------------------------------------------------------------------------
 * Order
 * ---------------------------------------------------------------------------
 */
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $line, $cart_key, $values ) {
		if ( empty( $values['oc_selection'] ) ) {
			return;
		}
		$cfg = optimum_product_config( $values['product_id'] );
		if ( ! $cfg ) {
			return;
		}
		foreach ( optimum_selection_summary( $cfg, $values['oc_selection'] ) as $label => $value ) {
			$line->add_meta_data( $label, $value );
		}
		$line->add_meta_data( '_oc_selection', $values['oc_selection'] );
	},
	10,
	3
);

/*
 * ---------------------------------------------------------------------------
 * Loop behaviour for configurable products
 * ---------------------------------------------------------------------------
 */
add_filter(
	'woocommerce_product_add_to_cart_url',
	function ( $url, $product ) {
		return optimum_product_config( $product ) ? $product->get_permalink() : $url;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_add_to_cart_text',
	function ( $text, $product ) {
		return optimum_product_config( $product ) ? __( 'Choose options', 'optimum' ) : $text;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_supports',
	function ( $supports, $feature, $product ) {
		if ( 'ajax_add_to_cart' === $feature && optimum_product_config( $product ) ) {
			return false;
		}
		return $supports;
	},
	10,
	3
);

/**
 * "From" prefix on listing prices of configurable products.
 */
add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		$cfg = optimum_product_config( $product );
		if ( ! $cfg || '' === $html ) {
			return $html;
		}
		$suffix = 'per_metre' === $cfg['pricing'] ? ' <small class="price-unit">' . esc_html__( 'per linear metre', 'optimum' ) . '</small>' : '';
		$price  = 'per_metre' === $cfg['pricing'] ? wc_price( $cfg['per_metre']['rate'] ) : $html;
		return '<span class="price-from">' . esc_html__( 'From', 'optimum' ) . '</span> ' . $price . $suffix;
	},
	10,
	2
);

/*
 * ---------------------------------------------------------------------------
 * Product page data for the configurator script
 * ---------------------------------------------------------------------------
 */

/**
 * Image data for the browser (URLs, srcset, alt, provenance).
 *
 * @param int    $id   Attachment ID.
 * @param string $size Size.
 * @return array|null
 */
function optimum_image_data( $id, $size = 'large' ) {
	if ( ! $id ) {
		return null;
	}
	$src = wp_get_attachment_image_src( $id, $size );
	$full = wp_get_attachment_image_src( $id, 'full' );
	if ( ! $src ) {
		return null;
	}
	$attr = optimum_attachment_alt( array( 'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ) ), get_post( $id ) );
	return array(
		'id'     => (int) $id,
		'src'    => $src[0],
		'w'      => $src[1],
		'h'      => $src[2],
		'full'   => $full ? $full[0] : $src[0],
		'srcset' => (string) wp_get_attachment_image_srcset( $id, $size ),
		'alt'    => (string) $attr['alt'],
		'render' => optimum_is_render( $id ),
	);
}

/**
 * Everything the product page script needs.
 *
 * @param WC_Product $product Product.
 * @param array      $cfg     Config.
 * @param array      $sel     Initial selection.
 * @return array
 */
function optimum_configurator_payload( $product, $cfg, $sel ) {
	$images = array();
	foreach ( $cfg['images'] as $key => $set ) {
		$set            = wp_parse_args( $set, array( 'closed' => 0, 'open' => 0, 'open_by' => array(), 'gallery' => array() ) );
		$images[ $key ] = array(
			'closed'  => optimum_image_data( $set['closed'] ),
			'open'    => optimum_image_data( $set['open'] ),
			'openBy'  => (object) array_filter( array_map( 'optimum_image_data', (array) $set['open_by'] ) ),
			'gallery' => array_values( array_filter( array_map( 'optimum_image_data', (array) $set['gallery'] ) ) ),
		);
	}
	$hotspots = array();
	foreach ( (array) $cfg['hotspots'] as $att_id => $spots ) {
		foreach ( (array) $spots as $name => $xy ) {
			$hotspots[ (int) $att_id ][] = array(
				'key'   => $name,
				'x'     => (float) $xy[0],
				'y'     => (float) $xy[1],
				'label' => optimum_hotspot_label( $name ),
			);
		}
	}
	$pm = 'per_metre' === $cfg['pricing'] ? wp_parse_args( $cfg['per_metre'], array( 'rate' => 0, 'min' => 100, 'max' => 600, 'step' => 10 ) ) : null;
	return array(
		'productId'  => $product->get_id(),
		'endpoint'   => esc_url_raw( rest_url( 'optimum/v1/price' ) ),
		'ajaxAdd'    => esc_url_raw( WC_AJAX::get_endpoint( 'add_to_cart' ) ),
		'cartUrl'    => wc_get_cart_url(),
		'lang'       => optimum_lang(),
		'imageGroup' => $cfg['image_group'],
		'images'     => $images,
		'hotspots'   => $hotspots,
		'selection'  => $sel,
		'perMetre'   => $pm,
		'shown'      => (object) $cfg['shown'],
		'i18n'       => array(
			'closed'     => __( 'Doors closed', 'optimum' ),
			'open'       => __( 'Doors open', 'optimum' ),
			'updating'   => __( 'Updating price…', 'optimum' ),
			'added'      => __( 'Added to your cart', 'optimum' ),
			'viewCart'   => __( 'View cart', 'optimum' ),
			'error'      => __( 'We could not update the price. Please try again.', 'optimum' ),
			'zoom'       => __( 'Zoom image', 'optimum' ),
			'close'      => __( 'Close', 'optimum' ),
			'render'     => __( 'Illustrative render', 'optimum' ),
			/* translators: %s: width in cm */
			'shownWidth' => __( 'Images show the %s cm size; the proportions of your size may differ.', 'optimum' ),
		),
	);
}

/**
 * Hotspot labels.
 *
 * @param string $key Key.
 * @return string
 */
function optimum_hotspot_label( $key ) {
	$labels = array(
		'rail_double' => __( 'Double hanging: shirts above, trousers below', 'optimum' ),
		'rail_long'   => __( 'Long hanging for thobes and abayas', 'optimum' ),
		'shelf_led'   => __( 'Adjustable shelves with a warm LED strip', 'optimum' ),
		'drawers'     => __( 'Soft-close interior drawers', 'optimum' ),
		'shoes'       => __( 'Shoe shelves', 'optimum' ),
		'island'      => __( 'Drawer island with a glass top', 'optimum' ),
	);
	return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
}

/**
 * Initial selection: product defaults, overridden by ?oc[...] (links from the cart).
 *
 * @param array $cfg Config.
 * @return array
 */
function optimum_initial_selection( $cfg ) {
	$raw = array_merge( (array) $cfg['default'], optimum_posted_selection() );
	$sel = optimum_validate_selection( $cfg, $raw );
	if ( is_wp_error( $sel ) ) {
		$sel = optimum_validate_selection( $cfg, (array) $cfg['default'] );
	}
	return is_wp_error( $sel ) ? array( 'width' => 0, 'extras' => array() ) : $sel;
}
