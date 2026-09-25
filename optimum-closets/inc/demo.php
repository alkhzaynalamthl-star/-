<?php
/**
 * Demo data importer (preview mode).
 *
 *   wp optimum demo import [--store-settings]
 *   wp optimum demo remove
 *
 * or Tools → Optimum demo data. Everything created is flagged `_oc_demo` so
 * it stays separate from real products and can be removed completely.
 * `--store-settings` also applies Saudi store settings (SAR, VAT 15 % included,
 * Saudi-only addresses, delivery & installation method, classic cart/checkout
 * pages): use it on a fresh development site, not on the live store.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import everything.
 *
 * @param bool $store_settings Also apply store settings.
 * @return array Log lines.
 */
function optimum_demo_import( $store_settings = false ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$data = include OPTIMUM_DIR . '/inc/demo-data.php';
	$log  = array();

	if ( $store_settings ) {
		$log = array_merge( $log, optimum_demo_store_settings() );
	}

	// Attributes.
	foreach ( $data['attributes'] as $slug => $attr ) {
		$tax = 'pa_' . $slug;
		if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
			wc_create_attribute(
				array(
					'name'         => $attr['label'][0],
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
			$log[] = "attribute $tax";
		}
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'product', array( 'hierarchical' => false ) );
		}
		$order = 0;
		foreach ( $attr['terms'] as $tslug => $t ) {
			$term = term_exists( $tslug, $tax );
			if ( ! $term ) {
				$term = wp_insert_term( $t[0], $tax, array( 'slug' => $tslug ) );
			}
			if ( ! is_wp_error( $term ) ) {
				$tid = (int) $term['term_id'];
				update_term_meta( $tid, 'oc_name_en', $t[1] );
				update_term_meta( $tid, 'order', $order++ );
				update_term_meta( $tid, '_oc_demo', 1 );
				if ( ! empty( $t[2] ) ) {
					update_term_meta( $tid, 'oc_hex', $t[2] );
				}
			}
		}
	}
	update_option( 'oc_attr_labels_en', array_map( function ( $a ) { return $a['label'][1]; }, $data['attributes'] ) );

	// Images.
	$media = array();
	$get   = function ( $name ) use ( &$media, &$log ) {
		if ( ! $name ) {
			return 0;
		}
		if ( ! isset( $media[ $name ] ) ) {
			$media[ $name ] = optimum_demo_image( $name );
			if ( $media[ $name ] ) {
				$log[] = "image $name";
			}
		}
		return $media[ $name ];
	};

	// Categories.
	$cats  = array();
	$order = 0;
	foreach ( $data['categories'] as $slug => $c ) {
		$term = term_exists( $slug, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $c[0], 'product_cat', array( 'slug' => $slug, 'description' => $c[3] ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$tid           = (int) $term['term_id'];
		$cats[ $slug ] = $tid;
		update_term_meta( $tid, 'oc_name_en', $c[1] );
		update_term_meta( $tid, 'oc_desc_en', $c[4] );
		update_term_meta( $tid, 'order', $order++ );
		update_term_meta( $tid, '_oc_demo', 1 );
		$img = $get( $c[2] );
		if ( $img ) {
			update_term_meta( $tid, 'thumbnail_id', $img );
		}
		$log[] = "category $slug";
	}

	// Products.
	$hotspots_all = optimum_demo_hotspots();
	foreach ( $data['products'] as $p ) {
		$existing = wc_get_product_id_by_sku( $p['sku'] );
		$product  = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();
		$product->set_name( $p['name'][0] );
		$product->set_slug( sanitize_title( $p['name'][1] ) );
		$product->set_sku( $p['sku'] );
		$product->set_status( 'publish' );
		$product->set_short_description( $p['short'][0] );
		$product->set_description( $p['desc'][0] );
		$product->set_featured( ! empty( $p['featured'] ) );
		$product->set_menu_order( isset( $p['order'] ) ? $p['order'] : 0 );
		$product->set_category_ids( isset( $cats[ $p['cat'] ] ) ? array( $cats[ $p['cat'] ] ) : array() );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_tax_status( 'taxable' );
		if ( isset( $p['price'] ) ) {
			$product->set_regular_price( (string) $p['price'] );
		}

		// Attributes (visible on the product page, used by the filters).
		$attrs = array();
		foreach ( array( 'door' => 'pa_door', 'color' => 'pa_color' ) as $key => $tax ) {
			$slugs = 'door' === $key ? $p['door'] : $p['colors'];
			if ( ! $slugs ) {
				continue;
			}
			$ids = array();
			foreach ( $slugs as $s ) {
				$t = get_term_by( 'slug', $s, $tax );
				if ( $t ) {
					$ids[] = $t->term_id;
				}
			}
			$a = new WC_Product_Attribute();
			$a->set_id( wc_attribute_taxonomy_id_by_name( $key ) );
			$a->set_name( $tax );
			$a->set_options( $ids );
			$a->set_visible( true );
			$attrs[] = $a;
		}
		$product->set_attributes( $attrs );

		// Images and configuration.
		if ( isset( $p['config'] ) ) {
			$cfg      = $p['config'];
			$gallery  = array();
			$hotspots = array();
			foreach ( $cfg['images'] as $key => $set ) {
				$resolved = array(
					'closed'  => $get( isset( $set['closed'] ) ? $set['closed'] : '' ),
					'open'    => $get( isset( $set['open'] ) ? $set['open'] : '' ),
					'open_by' => array(),
					'gallery' => array_values( array_filter( array_map( $get, isset( $set['gallery'] ) ? $set['gallery'] : array() ) ) ),
				);
				foreach ( ( isset( $set['open_by'] ) ? $set['open_by'] : array() ) as $layout => $render ) {
					$resolved['open_by'][ $layout ] = $get( $render );
				}
				foreach ( array_merge( array( isset( $set['open'] ) ? $set['open'] : '' ), array_values( isset( $set['open_by'] ) ? $set['open_by'] : array() ) ) as $render ) {
					if ( $render && ! empty( $hotspots_all[ $render ] ) && $get( $render ) ) {
						$hotspots[ $get( $render ) ] = $hotspots_all[ $render ];
					}
				}
				$cfg['images'][ $key ] = $resolved;
				$gallery               = array_merge( $gallery, array_filter( array( $resolved['closed'], $resolved['open'] ) ), $resolved['gallery'] );
			}
			$cfg['hotspots'] = $hotspots;
			$first           = reset( $cfg['images'] );
			$product->set_image_id( $first['closed'] );
			$product->set_gallery_image_ids( array_values( array_unique( array_diff( $gallery, array( $first['closed'] ) ) ) ) );
			$product->update_meta_data( '_oc_config', $cfg );
		} elseif ( ! empty( $p['image'] ) ) {
			$product->set_image_id( $get( $p['image'] ) );
		}

		$product->update_meta_data( '_oc_demo', 1 );
		$product->update_meta_data( '_oc_title_en', $p['name'][1] );
		$product->update_meta_data( '_oc_excerpt_en', $p['short'][1] );
		$product->update_meta_data( '_oc_content_en', $p['desc'][1] );
		$id = $product->save();
		if ( isset( $p['config'] ) ) {
			optimum_sync_base_price( $id );
		}
		$log[] = "product {$p['sku']} #$id";
	}

	// Site pages (structure, not demo content: kept on removal).
	foreach ( $data['pages'] as $key => $pg ) {
		$tpl  = 'templates/' . $pg[2] . '.php';
		$have = get_posts(
			array(
				'post_type'      => 'page',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $tpl, // phpcs:ignore WordPress.DB.SlowDBQuery
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			)
		);
		if ( $have ) {
			continue;
		}
		$pid = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $pg[0],
				'post_name'   => $key,
			)
		);
		update_post_meta( $pid, '_wp_page_template', $tpl );
		update_post_meta( $pid, '_oc_title_en', $pg[1] );
		$log[] = "page $key";
	}

	// Shop page English title.
	$shop = wc_get_page_id( 'shop' );
	if ( $shop > 0 ) {
		update_post_meta( $shop, '_oc_title_en', 'Wardrobes' );
		wp_update_post( array( 'ID' => $shop, 'post_title' => 'الخزائن' ) );
	}
	foreach ( array( 'cart' => array( 'السلة', 'Cart' ), 'checkout' => array( 'إتمام الطلب', 'Checkout' ), 'myaccount' => array( 'حسابي', 'My account' ) ) as $page => $t ) {
		$id = wc_get_page_id( $page );
		if ( $id > 0 ) {
			if ( $store_settings ) {
				wp_update_post( array( 'ID' => $id, 'post_title' => $t[0] ) );
			}
			update_post_meta( $id, '_oc_title_en', $t[1] );
		}
	}

	update_option( 'oc_demo_imported', time() );
	return $log;
}

/**
 * Sideload a bundled render into the media library (largest WebP), with
 * Arabic alt (standard field) and English alt, flagged as an illustrative render.
 *
 * @param string $name Render name.
 * @return int Attachment ID or 0.
 */
function optimum_demo_image( $name ) {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'meta_key'       => '_oc_render_name', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $name, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $found ) {
		return (int) $found[0];
	}
	$m = optimum_render_manifest();
	if ( empty( $m[ $name ] ) ) {
		return 0;
	}
	$w   = max( $m[ $name ]['sizes'] );
	$src = OPTIMUM_DIR . '/assets/img/renders/' . $name . '-' . $w . '.webp';
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( $name . '.webp' );
	copy( $src, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => $name . '.webp',
			'tmp_name' => $tmp,
		),
		0,
		$m[ $name ]['alt']['ar']
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $m[ $name ]['alt']['ar'] );
	update_post_meta( $id, '_oc_alt_en', $m[ $name ]['alt']['en'] );
	update_post_meta( $id, '_oc_render', 1 );
	update_post_meta( $id, '_oc_render_name', $name );
	update_post_meta( $id, '_oc_demo', 1 );
	return (int) $id;
}

/**
 * Hotspots projected by the render script.
 *
 * @return array
 */
function optimum_demo_hotspots() {
	$f = OPTIMUM_DIR . '/assets/img/renders/hotspots.json';
	return file_exists( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Saudi store settings for a development site.
 *
 * @return array Log.
 */
function optimum_demo_store_settings() {
	$opts = array(
		'woocommerce_currency'                  => 'SAR',
		'woocommerce_default_country'           => 'SA',
		'woocommerce_allowed_countries'         => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'SA' ),
		'woocommerce_ship_to_countries'         => 'specific',
		'woocommerce_specific_ship_to_countries' => array( 'SA' ),
		'woocommerce_price_num_decimals'        => 0,
		'woocommerce_price_thousand_sep'        => ',',
		'woocommerce_price_decimal_sep'         => '.',
		'woocommerce_currency_pos'              => 'right_space',
		'woocommerce_calc_taxes'                => 'yes',
		'woocommerce_prices_include_tax'        => 'yes',
		'woocommerce_tax_display_shop'          => 'incl',
		'woocommerce_tax_display_cart'          => 'incl',
		'woocommerce_tax_total_display'         => 'single',
		'woocommerce_enable_guest_checkout'     => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_enable_myaccount_registration' => 'yes',
		'woocommerce_registration_generate_password' => 'yes',
		'woocommerce_coming_soon'               => 'no',
		'woocommerce_task_list_hidden'          => 'yes',
		'woocommerce_enable_reviews'            => 'yes',
		'woocommerce_review_rating_verification_required' => 'yes',
		'woocommerce_cart_redirect_after_add'   => 'no',
		'woocommerce_enable_ajax_add_to_cart'   => 'yes',
	);
	foreach ( $opts as $k => $v ) {
		update_option( $k, $v );
	}
	$log = array( 'store settings' );

	// VAT 15 % (Saudi Arabia), prices entered and shown including VAT.
	global $wpdb;
	$has = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s LIMIT 1", 'SA' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( ! $has ) {
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'SA',
				'tax_rate'          => '15.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
		$log[] = 'VAT 15%';
	}

	// Delivery & installation (cost confirmed by the team; 0 in the demo).
	$zones = WC_Shipping_Zones::get_zones();
	if ( ! $zones ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Saudi Arabia' );
		$zone->add_location( 'SA', 'country' );
		$zone->save();
		$iid    = $zone->add_shipping_method( 'flat_rate' );
		$method = WC_Shipping_Zones::get_shipping_method( $iid );
		if ( $method ) {
			update_option(
				$method->get_instance_option_key(),
				array(
					'title'      => 'Delivery & installation',
					'tax_status' => 'none',
					'cost'       => '0',
				)
			);
		}
		$log[] = 'shipping zone';
	}

	// Preview payment method on; classic cart/checkout/account shortcodes.
	update_option( 'woocommerce_oc_preview_settings', array( 'enabled' => 'yes' ) );
	foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]', 'myaccount' => '[woocommerce_my_account]' ) as $page => $sc ) {
		$id = wc_get_page_id( $page );
		if ( $id > 0 ) {
			wp_update_post( array( 'ID' => $id, 'post_content' => '<!-- wp:shortcode -->' . $sc . '<!-- /wp:shortcode -->' ) );
		}
	}
	update_option( 'WPLANG', 'ar' );
	update_option( 'blogname', 'الخزائن الأمثل' );
	update_option( 'blogdescription', 'خزائن ملابس وغرف ملابس مفصّلة بالمقاس في جدة ومكة' );
	update_option( 'timezone_string', 'Asia/Riyadh' );
	update_option( 'date_format', 'j F Y' );
	return $log;
}

/**
 * Remove all demo items.
 *
 * @return array Log.
 */
function optimum_demo_remove() {
	$log = array();
	foreach ( array( 'product', 'attachment' ) as $type ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'meta_key'       => '_oc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			if ( 'attachment' === $type ) {
				wp_delete_attachment( $id, true );
			} else {
				$p = wc_get_product( $id );
				if ( $p ) {
					$p->delete( true );
				}
			}
			$log[] = "$type #$id";
		}
	}
	foreach ( array( 'product_cat', 'pa_door', 'pa_color' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'meta_key'   => '_oc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'     => 'ids',
			)
		);
		foreach ( (array) $terms as $tid ) {
			wp_delete_term( $tid, $tax );
			$log[] = "$tax #$tid";
		}
	}
	delete_option( 'oc_demo_imported' );
	return $log;
}

/**
 * Attribute labels in English (attribute names are single-language in WooCommerce).
 */
add_filter(
	'woocommerce_attribute_label',
	function ( $label, $name ) {
		if ( optimum_is_en() && ( ! is_admin() || wp_doing_ajax() ) ) {
			$en  = (array) get_option( 'oc_attr_labels_en', array() );
			$key = preg_replace( '/^pa_/', '', (string) $name );
			if ( ! empty( $en[ $key ] ) ) {
				return $en[ $key ];
			}
		}
		return $label;
	},
	10,
	2
);

/*
 * WP-CLI and Tools screen.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'optimum demo',
		function ( $args, $assoc ) {
			$sub = isset( $args[0] ) ? $args[0] : '';
			if ( 'import' === $sub ) {
				foreach ( optimum_demo_import( ! empty( $assoc['store-settings'] ) ) as $l ) {
					WP_CLI::log( $l );
				}
				WP_CLI::success( 'Demo data imported.' );
			} elseif ( 'remove' === $sub ) {
				foreach ( optimum_demo_remove() as $l ) {
					WP_CLI::log( $l );
				}
				WP_CLI::success( 'Demo data removed.' );
			} else {
				WP_CLI::error( 'Usage: wp optimum demo import [--store-settings] | remove' );
			}
		}
	);
}

add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Optimum demo data', 'optimum' ),
			__( 'Optimum demo data', 'optimum' ),
			'manage_woocommerce',
			'optimum-demo',
			function () {
				$log = array();
				if ( isset( $_POST['oc_demo'] ) && check_admin_referer( 'oc_demo' ) ) {
					$log = 'remove' === $_POST['oc_demo'] ? optimum_demo_remove() : optimum_demo_import( false );
				}
				echo '<div class="wrap"><h1>' . esc_html__( 'Optimum demo data', 'optimum' ) . '</h1>';
				echo '<p>' . esc_html__( 'Adds clearly flagged demo wardrobes (placeholder names and prices) with illustrative renders, for preview mode. Removing deletes only items created here.', 'optimum' ) . '</p>';
				echo '<form method="post">';
				wp_nonce_field( 'oc_demo' );
				echo '<p><button class="button button-primary" name="oc_demo" value="import">' . esc_html__( 'Import demo data', 'optimum' ) . '</button> ';
				echo '<button class="button" name="oc_demo" value="remove" onclick="return confirm(\'' . esc_js( __( 'Remove all demo products, images and terms?', 'optimum' ) ) . '\')">' . esc_html__( 'Remove demo data', 'optimum' ) . '</button></p></form>';
				if ( $log ) {
					echo '<pre>' . esc_html( implode( "\n", $log ) ) . '</pre>';
				}
				echo '</div>';
			}
		);
	}
);
