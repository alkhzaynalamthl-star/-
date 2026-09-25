<?php
/**
 * Catalogue model: option registry, per-product configuration and the
 * server-side price calculation that every price shown or charged goes through.
 *
 * A product's configuration lives in post meta `_oc_config` (edited in the
 * product screen, see inc/admin-product.php):
 *
 *   door        hinged-wood | hinged-glass | sliding | walk-in | bespoke
 *   height,depth  cm
 *   pricing     table | per_metre
 *   widths      [ [cm, price], ... ]                (pricing = table)
 *   per_metre   [ rate, min, max, step ]            (pricing = per_metre, width in cm)
 *   default     [ width => 240, finish => 'oak', ... ]
 *   groups      [ group => [ option_key => price_delta_SAR ] ]
 *   extras      [ extra_key => price_delta_SAR ]
 *   image_group group whose choice selects the gallery (e.g. finish, frame)
 *   images      [ option_key => [ closed => att_id, open => att_id,
 *                 open_by => [ layout_key => att_id ], gallery => [ids] ] ]
 *   hotspots    [ att_id => [ key => [x%, y%] ] ]  (projected from the 3D scene)
 *   shown       [ width => 240 ]   what the images depict, so the page can say so
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option registry with bilingual labels. Swatches reference bundled renders.
 *
 * @return array
 */
function optimum_option_registry() {
	return array(
		'finish' => array(
			'label'   => array( 'الخامة واللون', 'Finish' ),
			'options' => array(
				'oak'      => array( 'بلوط طبيعي', 'Natural oak', 'swatch-oak' ),
				'walnut'   => array( 'جوز مدخّن', 'Smoked walnut', 'swatch-walnut' ),
				'cashmere' => array( 'كشميري مطفي', 'Matt cashmere', 'swatch-cashmere' ),
				'graphite' => array( 'جرافيت مطفي', 'Matt graphite', 'swatch-graphite' ),
			),
		),
		'frame'  => array(
			'label'   => array( 'الإطار والزجاج', 'Frame & glass' ),
			'options' => array(
				'bronze'    => array( 'إطار برونزي · زجاج برونزي', 'Bronze frame · bronze glass', 'swatch-frame-bronze' ),
				'black'     => array( 'إطار أسود · زجاج مدخّن', 'Black frame · smoked glass', 'swatch-frame-black' ),
				'champagne' => array( 'إطار شمبانيا · زجاج شفاف', 'Champagne frame · clear glass', 'swatch-frame-champagne' ),
			),
		),
		'glass'  => array(
			'label'   => array( 'الزجاج', 'Glass' ),
			'options' => array(
				'bronze' => array( 'زجاج برونزي', 'Bronze glass', 'swatch-glass-bronze' ),
				'smoked' => array( 'زجاج مدخّن', 'Smoked glass', 'swatch-glass-smoked' ),
				'clear'  => array( 'زجاج شفاف', 'Clear glass', 'swatch-glass-clear' ),
				'fluted' => array( 'زجاج ساتان', 'Satin glass', 'swatch-glass-fluted' ),
			),
		),
		'handle' => array(
			'label'   => array( 'المقابض', 'Handles' ),
			'options' => array(
				'brass'  => array( 'نحاسي مصقول', 'Brushed brass', '' ),
				'black'  => array( 'أسود مطفي', 'Matt black', '' ),
				'bronze' => array( 'برونزي', 'Bronze', '' ),
			),
		),
		'layout' => array(
			'label'   => array( 'التقسيم الداخلي', 'Interior layout' ),
			'options' => array(
				'classic' => array( 'كلاسيكي: علّاقتان + أرفف', 'Classic: double hanging + shelves', '' ),
				'long'    => array( 'علّاقة طويلة للثياب والعبايات + أرفف', 'Long hanging for thobes & abayas + shelves', '' ),
				'drawers' => array( 'أدراج داخلية + أرفف', 'Interior drawers + shelves', '' ),
			),
		),
		'extras' => array(
			'label'   => array( 'إضافات', 'Extras' ),
			'options' => array(
				'led'       => array( 'إضاءة LED داخلية', 'Interior LED lighting', '' ),
				'organiser' => array( 'منظّم أدراج مخملي', 'Velvet drawer organiser', '' ),
				'install'   => array( 'فك وتركيب خزانة قديمة', 'Removal of an old wardrobe', '' ),
			),
		),
	);
}

/**
 * Label for a group or option in the current language.
 *
 * @param string $group  Group.
 * @param string $option Option (empty for the group label).
 * @return string
 */
function optimum_option_label( $group, $option = '' ) {
	$r = optimum_option_registry();
	if ( ! isset( $r[ $group ] ) ) {
		return $option ? $option : $group;
	}
	if ( '' === $option ) {
		return optimum_pick( $r[ $group ]['label'][0], $r[ $group ]['label'][1] );
	}
	if ( ! isset( $r[ $group ]['options'][ $option ] ) ) {
		return $option;
	}
	$o = $r[ $group ]['options'][ $option ];
	return optimum_pick( $o[0], $o[1] );
}

/**
 * Normalised configuration for a product, or null.
 *
 * @param int|WC_Product $product Product.
 * @return array|null
 */
function optimum_product_config( $product ) {
	$id  = $product instanceof WC_Product ? $product->get_id() : (int) $product;
	$cfg = get_post_meta( $id, '_oc_config', true );
	if ( is_string( $cfg ) && '' !== $cfg ) {
		$cfg = json_decode( $cfg, true );
	}
	if ( ! is_array( $cfg ) || empty( $cfg['pricing'] ) ) {
		return null;
	}
	return wp_parse_args(
		$cfg,
		array(
			'door'        => '',
			'height'      => 0,
			'depth'       => 0,
			'widths'      => array(),
			'per_metre'   => array(),
			'default'     => array(),
			'groups'      => array(),
			'extras'      => array(),
			'image_group' => '',
			'images'      => array(),
			'hotspots'    => array(),
			'shown'       => array(),
		)
	);
}

/**
 * Validate a customer's selection against the product's configuration.
 * Unknown keys are dropped; any invalid value is an error. Nothing from the
 * client is trusted beyond picking among the product's own options.
 *
 * @param array $cfg Config.
 * @param array $raw Raw selection.
 * @return array|WP_Error Clean selection.
 */
function optimum_validate_selection( $cfg, $raw ) {
	$raw = is_array( $raw ) ? $raw : array();
	$sel = array();

	$width = isset( $raw['width'] ) ? (int) $raw['width'] : (int) ( isset( $cfg['default']['width'] ) ? $cfg['default']['width'] : 0 );
	if ( 'per_metre' === $cfg['pricing'] ) {
		$pm = wp_parse_args( $cfg['per_metre'], array( 'rate' => 0, 'min' => 100, 'max' => 600, 'step' => 10 ) );
		if ( $width < $pm['min'] || $width > $pm['max'] || 0 !== ( $width - $pm['min'] ) % max( 1, (int) $pm['step'] ) ) {
			/* translators: 1: minimum width, 2: maximum width, 3: step */
			return new WP_Error( 'width', sprintf( __( 'Please enter a width between %1$s and %2$s cm, in steps of %3$s cm.', 'optimum' ), optimum_num( $pm['min'] ), optimum_num( $pm['max'] ), optimum_num( $pm['step'] ) ) );
		}
	} else {
		$ok = false;
		foreach ( $cfg['widths'] as $w ) {
			if ( (int) $w[0] === $width ) {
				$ok = true;
			}
		}
		if ( ! $ok ) {
			return new WP_Error( 'width', __( 'Please choose one of the available widths.', 'optimum' ) );
		}
	}
	$sel['width'] = $width;

	foreach ( $cfg['groups'] as $group => $options ) {
		$val = isset( $raw[ $group ] ) ? sanitize_key( $raw[ $group ] ) : ( isset( $cfg['default'][ $group ] ) ? $cfg['default'][ $group ] : '' );
		if ( ! array_key_exists( $val, $options ) ) {
			/* translators: %s: option group, e.g. Finish */
			return new WP_Error( $group, sprintf( __( 'Please choose a valid option for: %s', 'optimum' ), optimum_option_label( $group ) ) );
		}
		$sel[ $group ] = $val;
	}

	$extras = array();
	if ( ! empty( $raw['extras'] ) && is_array( $raw['extras'] ) ) {
		foreach ( $raw['extras'] as $x ) {
			$x = sanitize_key( $x );
			if ( array_key_exists( $x, $cfg['extras'] ) ) {
				$extras[] = $x;
			}
		}
	}
	sort( $extras );
	$sel['extras'] = array_values( array_unique( $extras ) );
	return $sel;
}

/**
 * Price for a validated selection. Server-side, from product data only.
 *
 * @param array $cfg Config.
 * @param array $sel Clean selection.
 * @return array total (float) and lines (label, amount)
 */
function optimum_price_for( $cfg, $sel ) {
	$lines = array();
	if ( 'per_metre' === $cfg['pricing'] ) {
		$rate  = (float) $cfg['per_metre']['rate'];
		$base  = round( $rate * $sel['width'] / 100, 2 );
		/* translators: 1: width in cm, 2: rate per metre */
		$lines[] = array( sprintf( __( 'Width %1$s cm × %2$s per linear metre', 'optimum' ), optimum_num( $sel['width'] ), wp_strip_all_tags( wc_price( $rate ) ) ), $base );
	} else {
		$base = 0;
		foreach ( $cfg['widths'] as $w ) {
			if ( (int) $w[0] === (int) $sel['width'] ) {
				$base = (float) $w[1];
			}
		}
		/* translators: %s: width in cm */
		$lines[] = array( sprintf( __( 'Width %s cm', 'optimum' ), optimum_num( $sel['width'] ) ), $base );
	}
	$total = $base;
	foreach ( $cfg['groups'] as $group => $options ) {
		$d = isset( $options[ $sel[ $group ] ] ) ? (float) $options[ $sel[ $group ] ] : 0;
		if ( $d ) {
			$lines[] = array( optimum_option_label( $group, $sel[ $group ] ), $d );
		}
		$total += $d;
	}
	foreach ( $sel['extras'] as $x ) {
		$d       = (float) $cfg['extras'][ $x ];
		$lines[] = array( optimum_option_label( 'extras', $x ), $d );
		$total  += $d;
	}
	return array(
		'total' => round( $total, 2 ),
		'lines' => $lines,
	);
}

/**
 * Lowest possible price (used as the product's listing price).
 *
 * @param array $cfg Config.
 * @return float
 */
function optimum_min_price( $cfg ) {
	if ( 'per_metre' === $cfg['pricing'] ) {
		$base = (float) $cfg['per_metre']['rate'] * (int) $cfg['per_metre']['min'] / 100;
	} else {
		$prices = wp_list_pluck( $cfg['widths'], 1 );
		$base   = $prices ? min( array_map( 'floatval', $prices ) ) : 0;
	}
	foreach ( $cfg['groups'] as $options ) {
		$base += $options ? min( array_map( 'floatval', $options ) ) : 0;
	}
	return round( $base, 2 );
}

/**
 * Human-readable summary lines for a selection ("Finish: Natural oak").
 *
 * @param array $cfg Config.
 * @param array $sel Selection.
 * @return array label => value
 */
function optimum_selection_summary( $cfg, $sel ) {
	$out = array();
	$w   = optimum_num( $sel['width'] ) . ' ' . __( 'cm', 'optimum' );
	if ( ! empty( $cfg['height'] ) ) {
		$w = optimum_num( $sel['width'] ) . ' × ' . optimum_num( $cfg['height'] ) . ' × ' . optimum_num( $cfg['depth'] ) . ' ' . __( 'cm', 'optimum' );
	}
	$out[ __( 'Size (W × H × D)', 'optimum' ) ] = $w;
	foreach ( $cfg['groups'] as $group => $options ) {
		$out[ optimum_option_label( $group ) ] = optimum_option_label( $group, $sel[ $group ] );
	}
	if ( $sel['extras'] ) {
		$out[ optimum_option_label( 'extras' ) ] = implode( '، ', array_map( function ( $x ) { return optimum_option_label( 'extras', $x ); }, $sel['extras'] ) );
		if ( optimum_is_en() ) {
			$out[ optimum_option_label( 'extras' ) ] = implode( ', ', array_map( function ( $x ) { return optimum_option_label( 'extras', $x ); }, $sel['extras'] ) );
		}
	}
	return $out;
}

/**
 * Images for the selected option of the image group.
 *
 * @param array $cfg Config.
 * @param array $sel Selection (or defaults).
 * @return array closed, open, gallery
 */
function optimum_selection_images( $cfg, $sel ) {
	$g   = $cfg['image_group'];
	$key = $g && isset( $sel[ $g ] ) ? $sel[ $g ] : '';
	$set = $key && isset( $cfg['images'][ $key ] ) ? $cfg['images'][ $key ] : reset( $cfg['images'] );
	$set = wp_parse_args( $set ? $set : array(), array( 'closed' => 0, 'open' => 0, 'open_by' => array(), 'gallery' => array() ) );
	// The interior view follows the chosen layout when a matching image exists.
	if ( ! empty( $sel['layout'] ) && ! empty( $set['open_by'][ $sel['layout'] ] ) ) {
		$set['open'] = $set['open_by'][ $sel['layout'] ];
	}
	return $set;
}

/**
 * Keep WooCommerce's stored price in sync with the configuration's lowest
 * price, so sorting, price filters and listings are right.
 *
 * @param int $product_id Product ID.
 */
function optimum_sync_base_price( $product_id ) {
	$cfg = optimum_product_config( $product_id );
	$p   = wc_get_product( $product_id );
	if ( ! $cfg || ! $p || ! $p->is_type( 'simple' ) ) {
		return;
	}
	$min = optimum_min_price( $cfg );
	if ( (float) $p->get_regular_price() !== (float) $min ) {
		$p->set_regular_price( (string) $min );
		$p->save();
	}
	// Width range for the dimensions filter.
	if ( 'per_metre' === $cfg['pricing'] ) {
		$wmin = (int) $cfg['per_metre']['min'];
		$wmax = (int) $cfg['per_metre']['max'];
	} else {
		$ws   = array_map( 'intval', wp_list_pluck( $cfg['widths'], 0 ) );
		$wmin = $ws ? min( $ws ) : 0;
		$wmax = $ws ? max( $ws ) : 0;
	}
	update_post_meta( $product_id, '_oc_wmin', $wmin );
	update_post_meta( $product_id, '_oc_wmax', $wmax );
}
