<?php
/**
 * Product listing: filters (door type, wall width, colour, price), search and
 * active-filter chips. Plain GET parameters, so every filtered view has a
 * shareable URL and works without JavaScript.
 *
 *   door[]=hinged-wood   pa_door terms
 *   color[]=natural-oak  pa_color terms
 *   fit=220              wall width in cm → products that can be made that narrow
 *   min_price / max_price  handled natively by WooCommerce
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current filter values from the URL.
 *
 * @return array
 */
function optimum_filter_values() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET filters.
	$clean = function ( $k ) {
		return isset( $_GET[ $k ] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET[ $k ] ) ) ) ) : array();
	};
	$v = array(
		'door'      => $clean( 'door' ),
		'color'     => $clean( 'color' ),
		'fit'       => isset( $_GET['fit'] ) ? absint( $_GET['fit'] ) : 0,
		'min_price' => isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : 0,
		'max_price' => isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : 0,
	);
	// phpcs:enable
	return $v;
}

/**
 * Apply filters to the main product query.
 *
 * @param WP_Query $q Query.
 */
function optimum_apply_filters( $q ) {
	$v   = optimum_filter_values();
	$tax = (array) $q->get( 'tax_query' );
	foreach ( array( 'door' => 'pa_door', 'color' => 'pa_color' ) as $key => $taxonomy ) {
		if ( $v[ $key ] && taxonomy_exists( $taxonomy ) ) {
			$tax[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $v[ $key ],
				'operator' => 'IN',
			);
		}
	}
	$q->set( 'tax_query', $tax );
	if ( $v['fit'] ) {
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = array(
			'key'     => '_oc_wmin',
			'value'   => $v['fit'],
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
		$q->set( 'meta_query', $meta );
	}
}
add_action( 'woocommerce_product_query', 'optimum_apply_filters' );

/**
 * Search English names too when browsing in English.
 *
 * @param string   $search SQL.
 * @param WP_Query $q      Query.
 * @return string
 */
function optimum_search_english( $search, $q ) {
	global $wpdb;
	if ( ! $search || ! $q->is_main_query() || ! $q->is_search() || ! optimum_swap_content() ) {
		return $search;
	}
	$term = '%' . $wpdb->esc_like( $q->get( 's' ) ) . '%';
	$or   = $wpdb->prepare( " OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} ocm WHERE ocm.post_id = {$wpdb->posts}.ID AND ocm.meta_key IN ('_oc_title_en','_oc_excerpt_en') AND ocm.meta_value LIKE %s)", $term );
	return preg_replace( '/\)\)\s*$/', ')' . $or . ')', $search, 1 );
}
add_filter( 'posts_search', 'optimum_search_english', 10, 2 );

/**
 * Filter terms with counts.
 *
 * @param string $taxonomy Taxonomy.
 * @return WP_Term[]
 */
function optimum_filter_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'term_order',
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * The filter form (sidebar on desktop, sheet on mobile).
 */
function optimum_shop_filters() {
	$v      = optimum_filter_values();
	$action = optimum_current_listing_url();
	?>
	<form class="filters" id="shop-filters" method="get" action="<?php echo esc_url( $action ); ?>" data-autosubmit>
		<?php
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( array( 's', 'orderby', 'post_type' ) as $keep ) {
			if ( ! empty( $_GET[ $keep ] ) ) {
				printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $keep ), esc_attr( sanitize_text_field( wp_unslash( $_GET[ $keep ] ) ) ) );
			}
		}
		// phpcs:enable
		$groups = array(
			'door'  => array( 'pa_door', __( 'Door type', 'optimum' ) ),
			'color' => array( 'pa_color', __( 'Colour & finish', 'optimum' ) ),
		);
		foreach ( $groups as $key => $g ) :
			$terms = optimum_filter_terms( $g[0] );
			if ( ! $terms ) {
				continue;
			}
			?>
			<fieldset class="filter-group">
				<legend><?php echo esc_html( $g[1] ); ?></legend>
				<?php foreach ( $terms as $t ) : ?>
					<label class="check">
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( in_array( $t->slug, $v[ $key ], true ) ); ?>>
						<?php if ( 'color' === $key ) : ?>
							<span class="chip-swatch" style="--sw:<?php echo esc_attr( (string) get_term_meta( $t->term_id, 'oc_hex', true ) ); ?>" aria-hidden="true"></span>
						<?php endif; ?>
						<span><?php echo esc_html( $t->name ); ?></span>
						<span class="count"><?php echo esc_html( optimum_num( $t->count ) ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>

		<fieldset class="filter-group">
			<legend><?php esc_html_e( 'Your wall width', 'optimum' ); ?></legend>
			<p class="filter-help"><?php esc_html_e( 'Show wardrobes that can be made to fit.', 'optimum' ); ?></p>
			<div class="field-unit">
				<input type="number" name="fit" min="60" max="1200" step="10" inputmode="numeric" value="<?php echo $v['fit'] ? esc_attr( $v['fit'] ) : ''; ?>" placeholder="240" aria-label="<?php esc_attr_e( 'Wall width in centimetres', 'optimum' ); ?>">
				<span><?php esc_html_e( 'cm', 'optimum' ); ?></span>
			</div>
		</fieldset>

		<fieldset class="filter-group">
			<legend><?php esc_html_e( 'Price', 'optimum' ); ?></legend>
			<div class="price-range">
				<label><span><?php esc_html_e( 'From', 'optimum' ); ?></span><input type="number" name="min_price" min="0" step="100" inputmode="numeric" value="<?php echo $v['min_price'] ? esc_attr( $v['min_price'] ) : ''; ?>" placeholder="0"></label>
				<label><span><?php esc_html_e( 'To', 'optimum' ); ?></span><input type="number" name="max_price" min="0" step="100" inputmode="numeric" value="<?php echo $v['max_price'] ? esc_attr( $v['max_price'] ) : ''; ?>" placeholder="50,000"></label>
			</div>
		</fieldset>

		<div class="filter-actions">
			<button class="btn btn-primary btn-block" type="submit"><?php esc_html_e( 'Show results', 'optimum' ); ?></button>
			<a class="btn btn-ghost btn-block" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear all', 'optimum' ); ?></a>
		</div>
		<?php dynamic_sidebar( 'shop-sidebar' ); ?>
	</form>
	<?php
}

/**
 * URL of the current listing without filter parameters.
 *
 * @return string
 */
function optimum_current_listing_url() {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link;
	}
	return wc_get_page_permalink( 'shop' );
}

/**
 * Active filters as removable chips.
 */
function optimum_active_filter_chips() {
	$v     = optimum_filter_values();
	$chips = array();
	foreach ( array( 'door' => 'pa_door', 'color' => 'pa_color' ) as $key => $tax ) {
		foreach ( $v[ $key ] as $slug ) {
			$t = get_term_by( 'slug', $slug, $tax );
			if ( $t ) {
				$t       = optimum_translate_term( $t );
				$rest    = array_values( array_diff( $v[ $key ], array( $slug ) ) );
				$chips[] = array( $t->name, add_query_arg( $key, $rest ? $rest : false ) );
			}
		}
	}
	if ( $v['fit'] ) {
		/* translators: %s: width in cm */
		$chips[] = array( sprintf( __( 'Fits %s cm', 'optimum' ), optimum_num( $v['fit'] ) ), remove_query_arg( 'fit' ) );
	}
	if ( $v['min_price'] || $v['max_price'] ) {
		$chips[] = array(
			wp_strip_all_tags( ( $v['min_price'] ? wc_price( $v['min_price'] ) : '0' ) . ' – ' . ( $v['max_price'] ? wc_price( $v['max_price'] ) : '∞' ) ),
			remove_query_arg( array( 'min_price', 'max_price' ) ),
		);
	}
	if ( ! $chips ) {
		return;
	}
	echo '<ul class="active-filters" aria-label="' . esc_attr__( 'Active filters', 'optimum' ) . '">';
	foreach ( $chips as $c ) {
		printf(
			'<li><a href="%1$s"><span>%2$s</span>%3$s<span class="screen-reader-text">%4$s</span></a></li>',
			esc_url( remove_query_arg( 'paged', $c[1] ) ),
			esc_html( $c[0] ),
			optimum_icon( 'close', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Remove filter', 'optimum' )
		);
	}
	echo '</ul>';
}

/**
 * Number of active filters (for the mobile button badge).
 *
 * @return int
 */
function optimum_active_filter_count() {
	$v = optimum_filter_values();
	return count( $v['door'] ) + count( $v['color'] ) + ( $v['fit'] ? 1 : 0 ) + ( $v['min_price'] || $v['max_price'] ? 1 : 0 );
}
