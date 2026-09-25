<?php
/**
 * تكامل ووكومرس: السلة، واتساب، التوصيل المجاني، شارات الثقة، صفحة الدفع.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * دعم ووكومرس ومعرض صور المنتج.
 */
function optimum_wc_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'optimum_wc_setup' );

// العنوان يظهر في رأس الصفحة الخاص بالقالب.
add_filter( 'woocommerce_show_page_title', '__return_false' );

add_filter(
	'loop_shop_per_page',
	function () {
		return 12;
	}
);

add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

/**
 * أيقونة السلة في الرأس مع العدد.
 */
function optimum_header_cart() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<a class="icon-btn header-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'سلة المشتريات', 'optimum' ); ?>">
		<?php echo optimum_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="header-cart-count<?php echo $count ? '' : ' is-empty'; ?>"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
	</a>
	<?php
}

/**
 * تحديث عدد السلة تلقائياً بعد الإضافة بدون إعادة تحميل الصفحة.
 *
 * @param array $fragments الأجزاء.
 * @return array
 */
function optimum_cart_fragment( $fragments ) {
	ob_start();
	optimum_header_cart();
	$fragments['a.header-cart'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'optimum_cart_fragment' );

/**
 * شريط «باقي X للتوصيل المجاني».
 */
function optimum_free_shipping_bar() {
	$threshold = (float) optimum_mod( 'free_shipping' );
	if ( $threshold <= 0 || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$subtotal  = (float) WC()->cart->get_displayed_subtotal();
	$remaining = max( 0, $threshold - $subtotal );
	$percent   = min( 100, round( $subtotal / $threshold * 100 ) );
	?>
	<div class="free-ship<?php echo $remaining <= 0 ? ' is-done' : ''; ?>">
		<p>
			<?php echo optimum_icon( 'truck', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			if ( $remaining > 0 ) {
				/* translators: %s: المبلغ المتبقي */
				printf( esc_html__( 'أضف %s فقط واحصل على توصيل مجاني!', 'optimum' ), '<strong>' . wp_kses_post( wc_price( $remaining ) ) . '</strong>' );
			} else {
				esc_html_e( 'رائع! طلبك مؤهل للتوصيل المجاني.', 'optimum' );
			}
			?>
		</p>
		<div class="free-ship-track"><span style="width:<?php echo esc_attr( $percent ); ?>%"></span></div>
	</div>
	<?php
}
add_action( 'woocommerce_before_cart_table', 'optimum_free_shipping_bar' );
add_action( 'woocommerce_checkout_before_order_review', 'optimum_free_shipping_bar' );
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'optimum_free_shipping_bar' );

/**
 * شارة الخصم بالنسبة المئوية بدلاً من «تخفيض!».
 *
 * @param string     $html    الشارة.
 * @param WP_Post    $post    المقال.
 * @param WC_Product $product المنتج.
 * @return string
 */
function optimum_sale_flash( $html, $post, $product ) {
	$percent = 0;

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->is_on_sale() && (float) $child->get_regular_price() > 0 ) {
				$percent = max( $percent, 1 - (float) $child->get_sale_price() / (float) $child->get_regular_price() );
			}
		}
	} elseif ( (float) $product->get_regular_price() > 0 && '' !== $product->get_sale_price() ) {
		$percent = 1 - (float) $product->get_sale_price() / (float) $product->get_regular_price();
	}

	$percent = (int) round( $percent * 100 );
	if ( $percent <= 0 ) {
		return '<span class="onsale">' . esc_html__( 'عرض', 'optimum' ) . '</span>';
	}

	/* translators: %s: نسبة الخصم */
	return '<span class="onsale">' . esc_html( sprintf( __( 'خصم %s٪', 'optimum' ), number_format_i18n( $percent ) ) ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'optimum_sale_flash', 10, 3 );

/**
 * زر «اطلب عبر واتساب» في صفحة المنتج.
 */
function optimum_whatsapp_order_button() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$message = sprintf(
		/* translators: 1: اسم المنتج 2: رابط المنتج */
		__( "مرحباً، أرغب بطلب أو الاستفسار عن:\n%1\$s\n%2\$s", 'optimum' ),
		$product->get_name(),
		get_permalink( $product->get_id() )
	);
	$url = optimum_whatsapp_url( $message );
	if ( ! $url ) {
		return;
	}
	?>
	<a class="btn btn-whatsapp btn-block wa-order" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
		<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php esc_html_e( 'اطلب أو استفسر عبر واتساب', 'optimum' ); ?>
	</a>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'optimum_whatsapp_order_button', 35 );

/**
 * شارات الثقة تحت زر الشراء.
 */
function optimum_trust_badges() {
	$icons  = array( 1 => 'truck', 2 => 'shield', 3 => 'lock', 4 => 'chat' );
	$badges = '';
	foreach ( $icons as $i => $icon ) {
		$text = optimum_mod( "trust_{$i}" );
		if ( $text ) {
			$badges .= '<li>' . optimum_icon( $icon, 20 ) . '<span>' . esc_html( $text ) . '</span></li>';
		}
	}
	if ( $badges ) {
		echo '<ul class="trust-badges">' . $badges . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
}
add_action( 'woocommerce_single_product_summary', 'optimum_trust_badges', 36 );

/**
 * شريط الشراء الثابت أسفل الشاشة في الجوال (صفحة المنتج).
 */
function optimum_sticky_add_to_cart() {
	if ( ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	?>
	<div class="sticky-atc" id="sticky-atc" aria-hidden="true">
		<div class="sticky-atc-info">
			<strong><?php echo esc_html( $product->get_name() ); ?></strong>
			<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		</div>
		<a class="btn btn-primary" href="#product-<?php echo esc_attr( $product->get_id() ); ?>" data-scroll-to-cart tabindex="-1">
			<?php echo esc_html( $product->is_type( 'simple' ) ? __( 'أضف للسلة', 'optimum' ) : __( 'اختر المواصفات', 'optimum' ) ); ?>
		</a>
	</div>
	<?php
}
add_action( 'wp_footer', 'optimum_sticky_add_to_cart' );

/**
 * صفحة دفع أبسط: إزالة الحقول غير الضرورية.
 *
 * @param array $fields الحقول.
 * @return array
 */
function optimum_checkout_fields( $fields ) {
	unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );
	unset( $fields['billing']['billing_address_2'], $fields['shipping']['shipping_address_2'] );

	if ( isset( $fields['billing']['billing_postcode'] ) ) {
		$fields['billing']['billing_postcode']['required'] = false;
	}
	if ( isset( $fields['shipping']['shipping_postcode'] ) ) {
		$fields['shipping']['shipping_postcode']['required'] = false;
	}
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['priority'] = 25;
		$fields['billing']['billing_phone']['placeholder'] = '05XXXXXXXX';
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['placeholder'] = __( 'مقاسات خاصة، موعد التركيب المفضل، أو أي ملاحظة...', 'optimum' );
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'optimum_checkout_fields' );

/**
 * الرمز البريدي اختياري في العناوين أيضاً (غير مستخدم عادة في المملكة).
 *
 * @param array $fields الحقول.
 * @return array
 */
function optimum_default_address_fields( $fields ) {
	if ( isset( $fields['postcode'] ) ) {
		$fields['postcode']['required'] = false;
	}
	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'optimum_default_address_fields' );

/**
 * رسالة طمأنة تحت زر إتمام الطلب.
 */
function optimum_checkout_secure_note() {
	echo '<p class="secure-note">' . optimum_icon( 'lock', 18 ) . esc_html__( 'بياناتك ومدفوعاتك محمية ومشفّرة بالكامل.', 'optimum' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_review_order_after_submit', 'optimum_checkout_secure_note' );

/**
 * زر فلترة المنتجات للجوال (يفتح الشريط الجانبي).
 */
function optimum_filters_toggle() {
	if ( is_active_sidebar( 'shop-sidebar' ) ) {
		echo '<button class="btn btn-outline filters-toggle" type="button" aria-controls="shop-sidebar">' . esc_html__( 'فلترة', 'optimum' ) . '</button>';
	}
}
add_action( 'woocommerce_before_shop_loop', 'optimum_filters_toggle', 25 );
