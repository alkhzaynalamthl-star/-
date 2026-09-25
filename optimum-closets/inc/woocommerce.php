<?php
/**
 * WooCommerce integration: support, currency display, checkout fields,
 * header cart, cart → checkout → confirmation steps.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme support.
 */
function optimum_wc_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 800,
			'single_image_width'    => 1200,
			'product_grid'          => array(
				'default_columns' => 3,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
}
add_action( 'after_setup_theme', 'optimum_wc_setup' );

// The theme styles every WooCommerce screen itself.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
add_filter( 'woocommerce_show_page_title', '__return_false' );
add_filter(
	'loop_shop_per_page',
	function () {
		return 12;
	}
);

/*
 * ---------------------------------------------------------------------------
 * Currency and numbers
 * ---------------------------------------------------------------------------
 */

/**
 * SAR symbol per language: "ر.س" in Arabic, "SAR" in English.
 */
add_filter(
	'woocommerce_currency_symbol',
	function ( $symbol, $currency ) {
		if ( 'SAR' === $currency ) {
			return optimum_is_en() ? 'SAR' : 'ر.س';
		}
		return $symbol;
	},
	10,
	2
);

/**
 * "6,900 ر.س" in Arabic, "SAR 6,900" in English. Western digits in both.
 */
add_filter(
	'woocommerce_price_format',
	function () {
		return optimum_is_en() ? '%1$s&nbsp;%2$s' : '%2$s&nbsp;%1$s';
	}
);
add_filter(
	'wc_price_args',
	function ( $args ) {
		$args['decimal_separator']  = '.';
		$args['thousand_separator'] = ',';
		$args['decimals']           = 0;
		return $args;
	}
);
add_filter(
	'wc_price',
	function ( $html ) {
		return '<bdi class="money">' . $html . '</bdi>';
	},
	20
);

/*
 * ---------------------------------------------------------------------------
 * Header cart
 * ---------------------------------------------------------------------------
 */

/**
 * Header cart link with count.
 */
function optimum_header_cart() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<a class="icon-btn header-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
		<?php echo optimum_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text">
			<?php
			/* translators: %s: number of items */
			echo esc_html( sprintf( _n( 'Cart, %s item', 'Cart, %s items', $count, 'optimum' ), optimum_num( $count ) ) );
			?>
		</span>
		<span class="header-cart-count<?php echo $count ? '' : ' is-empty'; ?>" aria-hidden="true"><?php echo esc_html( optimum_num( $count ) ); ?></span>
	</a>
	<?php
}

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		ob_start();
		optimum_header_cart();
		$fragments['a.header-cart'] = ob_get_clean();
		return $fragments;
	}
);

/**
 * Cart → Checkout → Confirmation indicator.
 *
 * @param int $current 1, 2 or 3.
 */
function optimum_checkout_steps( $current ) {
	$steps = array(
		1 => __( 'Cart', 'optimum' ),
		2 => __( 'Delivery & payment', 'optimum' ),
		3 => __( 'Confirmation', 'optimum' ),
	);
	echo '<ol class="checkout-steps" aria-label="' . esc_attr__( 'Order progress', 'optimum' ) . '">';
	foreach ( $steps as $n => $label ) {
		$state = $n < $current ? 'is-done' : ( $n === $current ? 'is-current' : '' );
		printf(
			'<li class="%1$s"%2$s><span class="step-dot">%3$s</span><span class="step-label">%4$s</span></li>',
			esc_attr( $state ),
			$n === $current ? ' aria-current="step"' : '',
			$n < $current ? optimum_icon( 'check', 14 ) : esc_html( optimum_num( $n ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label )
		);
	}
	echo '</ol>';
}

/*
 * ---------------------------------------------------------------------------
 * Checkout fields tuned for Saudi addresses (Jeddah & Makkah first)
 * ---------------------------------------------------------------------------
 */

/**
 * Cities served. Keys are stored on the order; labels follow the language.
 *
 * @return array
 */
function optimum_cities() {
	return array(
		'Jeddah' => __( 'Jeddah', 'optimum' ),
		'Makkah' => __( 'Makkah', 'optimum' ),
		'Other'  => __( 'Another city (we will confirm availability)', 'optimum' ),
	);
}

add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		unset( $fields['billing']['billing_company'], $fields['billing']['billing_address_2'], $fields['billing']['billing_postcode'], $fields['billing']['billing_state'] );
		unset( $fields['shipping']['shipping_company'], $fields['shipping']['shipping_address_2'], $fields['shipping']['shipping_postcode'], $fields['shipping']['shipping_state'] );

		$b = &$fields['billing'];
		$b['billing_first_name']['label']        = __( 'First name', 'optimum' );
		$b['billing_last_name']['label']         = __( 'Last name', 'optimum' );
		$b['billing_phone']['label']             = __( 'Mobile number', 'optimum' );
		$b['billing_phone']['placeholder']       = '05XXXXXXXX';
		$b['billing_phone']['priority']          = 25;
		$b['billing_phone']['required']          = true;
		$b['billing_phone']['custom_attributes'] = array(
			'inputmode' => 'tel',
			'dir'       => 'ltr',
		);
		$b['billing_email']['label']             = __( 'E-mail', 'optimum' );
		$b['billing_email']['priority']          = 26;
		$b['billing_email']['custom_attributes'] = array( 'dir' => 'ltr' );
		$b['billing_city']                       = array(
			'type'     => 'select',
			'label'    => __( 'City', 'optimum' ),
			'required' => true,
			'options'  => array( '' => __( 'Choose your city', 'optimum' ) ) + optimum_cities(),
			'class'    => array( 'form-row-wide' ),
			'priority' => 70,
		);
		$b['billing_address_1']['label']       = __( 'District and street', 'optimum' );
		$b['billing_address_1']['placeholder'] = __( 'e.g. Al-Naeem, Prince Sultan Street, building 12', 'optimum' );
		$b['billing_address_1']['priority']    = 80;
		if ( isset( $b['billing_country'] ) ) {
			$b['billing_country']['priority'] = 90;
		}
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( 'Notes for our team', 'optimum' );
			$fields['order']['order_comments']['placeholder'] = __( 'Preferred time for measurement or installation, landmarks, floor…', 'optimum' );
		}
		return $fields;
	},
	20
);

/**
 * Saudi mobile numbers: 05XXXXXXXX or +9665XXXXXXXX.
 */
add_action(
	'woocommerce_after_checkout_validation',
	function ( $data, $errors ) {
		$phone = isset( $data['billing_phone'] ) ? preg_replace( '/[\s\-]/', '', $data['billing_phone'] ) : '';
		$phone = strtr( $phone, array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( 0, 9 ) ) );
		if ( $phone && ! preg_match( '/^(?:\+?966|0)5\d{8}$/', $phone ) ) {
			$errors->add( 'billing_phone', __( 'Please enter a Saudi mobile number, for example 05XXXXXXXX.', 'optimum' ) );
		}
		if ( isset( $data['billing_city'] ) && '' !== $data['billing_city'] && ! array_key_exists( $data['billing_city'], optimum_cities() ) ) {
			$errors->add( 'billing_city', __( 'Please choose your city.', 'optimum' ) );
		}
	},
	10,
	2
);

/**
 * Show the translated city name on order screens.
 */
add_filter(
	'woocommerce_order_formatted_billing_address',
	function ( $address ) {
		$cities = optimum_cities();
		if ( isset( $address['city'], $cities[ $address['city'] ] ) && 'Other' !== $address['city'] ) {
			$address['city'] = $cities[ $address['city'] ];
		}
		return $address;
	}
);

// Deliver to the billing address (installation address) only.
add_filter(
	'woocommerce_ship_to_different_address_checked',
	'__return_false'
);
add_filter(
	'option_woocommerce_ship_to_destination',
	function () {
		return 'billing_only';
	}
);

/**
 * Plain, reassuring note under "Place order". No claims about payment
 * security are made here: those depend on the gateway chosen later.
 */
add_action(
	'woocommerce_review_order_after_submit',
	function () {
		echo '<p class="secure-note">' . esc_html__( 'Your order is reviewed by our team before manufacturing. We will call you to confirm measurements and the installation date.', 'optimum' ) . '</p>';
	}
);

/**
 * My Account menu: keep what a furniture customer needs.
 */
add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		unset( $items['downloads'] );
		return $items;
	}
);

/**
 * Related products: 3 (fits the card grid).
 */
add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = 3;
		$args['columns']        = 3;
		return $args;
	}
);

/**
 * Structured breadcrumbs markup.
 */
add_filter(
	'woocommerce_breadcrumb_defaults',
	function ( $d ) {
		$d['delimiter']   = '<span class="sep" aria-hidden="true">/</span>';
		$d['wrap_before'] = '<nav class="breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'optimum' ) . '">';
		$d['wrap_after']  = '</nav>';
		$d['home']        = __( 'Home', 'optimum' );
		return $d;
	}
);

/**
 * Quantity input: no spinner clutter, bigger target.
 */
add_filter(
	'woocommerce_quantity_input_args',
	function ( $args ) {
		$args['classes'][] = 'qty-input';
		return $args;
	}
);

/**
 * Only real, approved reviews produce ratings: turn off star output when a
 * product has none (WooCommerce already hides zero ratings; this keeps the
 * card layout from reserving space).
 */
add_filter(
	'woocommerce_product_get_rating_html',
	function ( $html, $rating, $count ) {
		return $count > 0 ? $html : '';
	},
	10,
	3
);

/**
 * −/+ buttons around quantity inputs (hidden automatically for sold-individually items).
 */
add_action(
	'woocommerce_before_quantity_input_field',
	function () {
		echo '<button type="button" class="qty-step" data-step="-1"><span aria-hidden="true">−</span><span class="screen-reader-text">' . esc_html__( 'Decrease quantity', 'optimum' ) . '</span></button>';
	}
);
add_action(
	'woocommerce_after_quantity_input_field',
	function () {
		echo '<button type="button" class="qty-step" data-step="1"><span aria-hidden="true">+</span><span class="screen-reader-text">' . esc_html__( 'Increase quantity', 'optimum' ) . '</span></button>';
	}
);

/**
 * Shipping method labels created by the theme's store setup follow the language.
 */
add_filter(
	'woocommerce_shipping_rate_label',
	function ( $label ) {
		$map = array(
			'Delivery & installation' => __( 'Delivery & installation', 'optimum' ),
		);
		return isset( $map[ $label ] ) ? $map[ $label ] : $label;
	}
);

/**
 * Privacy notices in the visitor's language (WooCommerce stores them as options).
 */
add_filter(
	'woocommerce_get_privacy_policy_text',
	function ( $text, $type ) {
		if ( 'checkout' === $type ) {
			return __( 'We use your details to process your order and arrange measurement, delivery and installation, as described in our [privacy_policy].', 'optimum' );
		}
		if ( 'registration' === $type ) {
			return __( 'We use your details to manage your account and orders, as described in our [privacy_policy].', 'optimum' );
		}
		return $text;
	},
	10,
	2
);
