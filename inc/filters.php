<?php
/**
 * Faceted filters for product archives — no plugin, URL-driven, counts aware of the current result set.
 *
 * URL shape: ?f[pa_producator]=yamaha,triangle&f[pa_clasa]=a&min=1000&max=5000&stock=1&showroom=1
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which facets to offer in which context. Keys are product_cat slugs (any ancestor matches);
 * '_default' is used when nothing matches. Each entry: taxonomy => label.
 * Non-taxonomy facets ('price', 'stock', 'showroom') are handled separately and always shown.
 */
function hifiexpert_facet_map() {
	$brand = array( 'pa_producator' => __( 'Brand', 'hifiexpert' ) );
	return apply_filters(
		'hifiexpert_facet_map',
		array(
			'boxe'          => $brand + array(
				'pa_numar-cai'                     => __( 'Număr căi', 'hifiexpert' ),
				'pa_culoare'                       => __( 'Finisaj', 'hifiexpert' ),
				'pa_impedanta'                     => __( 'Impedanță', 'hifiexpert' ),
				'pa_dimensiune-recomandata-camera' => __( 'Cameră recomandată', 'hifiexpert' ),
				'pa_sensibilitate'                 => __( 'Sensibilitate', 'hifiexpert' ),
			),
			'amplificatoare' => $brand + array(
				'pa_clasa'          => __( 'Clasă', 'hifiexpert' ),
				'pa_numar-canale'   => __( 'Canale', 'hifiexpert' ),
				'pa_dac-inclus'     => __( 'DAC inclus', 'hifiexpert' ),
				'pa_pre-phono'      => __( 'Pre‑phono', 'hifiexpert' ),
				'pa_streamer'       => __( 'Streaming', 'hifiexpert' ),
				'pa_tip-preamplificator' => __( 'Tip', 'hifiexpert' ),
				'pa_destinatie'     => __( 'Destinație', 'hifiexpert' ),
				'pa_culoare'        => __( 'Finisaj', 'hifiexpert' ),
			),
			'surse'         => $brand + array(
				'pa_streamer'   => __( 'Streaming', 'hifiexpert' ),
				'pa_dsd'        => __( 'DSD', 'hifiexpert' ),
				'pa_sacd'       => __( 'SACD', 'hifiexpert' ),
				'pa_tip-curea'  => __( 'Antrenare', 'hifiexpert' ),
				'pa_brat-inclus' => __( 'Braț inclus', 'hifiexpert' ),
				'pa_tip-stylus' => __( 'Stylus', 'hifiexpert' ),
				'pa_culoare'    => __( 'Finisaj', 'hifiexpert' ),
			),
			'cabluri'       => $brand + array(
				'pa_tip-cablu'    => __( 'Tip cablu', 'hifiexpert' ),
				'pa_lungime'      => __( 'Lungime', 'hifiexpert' ),
				'pa_conector-1'   => __( 'Conector', 'hifiexpert' ),
				'pa_standard-cablu' => __( 'Standard', 'hifiexpert' ),
			),
			'muzica'        => array(
				'pa_gen-muzical'  => __( 'Gen muzical', 'hifiexpert' ),
				'pa_format'       => __( 'Format', 'hifiexpert' ),
				'pa_casa-discuri' => __( 'Casă de discuri', 'hifiexpert' ),
			),
			'accesorii-2'   => $brand + array(
				'pa_culoare' => __( 'Culoare', 'hifiexpert' ),
			),
			'_default'      => $brand + array(
				'pa_culoare' => __( 'Finisaj', 'hifiexpert' ),
			),
		)
	);
}

/**
 * Facets for the current archive.
 */
function hifiexpert_active_facet_set() {
	$map = hifiexpert_facet_map();
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		// walk up: category, parent, grandparent
		$t = $term;
		while ( $t && ! is_wp_error( $t ) ) {
			if ( isset( $map[ $t->slug ] ) ) {
				return $map[ $t->slug ];
			}
			$t = $t->parent ? get_term( $t->parent, 'product_cat' ) : null;
		}
	}
	if ( is_tax() && 'pa_producator' === get_queried_object()->taxonomy ) {
		// Brand page: offer category-neutral facets without the brand itself.
		$set = $map['_default'];
		unset( $set['pa_producator'] );
		return array( 'product_cat' => __( 'Categorie', 'hifiexpert' ) ) + $set;
	}
	return $map['_default'];
}

/**
 * Parse the request into a normalized filter state.
 */
function hifiexpert_filter_state() {
	static $state = null;
	if ( null !== $state ) {
		return $state;
	}
	$state = array( 'tax' => array(), 'min' => 0, 'max' => 0, 'stock' => false, 'showroom' => false );
	$f     = isset( $_GET['f'] ) && is_array( $_GET['f'] ) ? wp_unslash( $_GET['f'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	foreach ( $f as $tax => $val ) {
		$tax = sanitize_key( $tax );
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$slugs = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $val ) ) ) );
		if ( $slugs ) {
			$state['tax'][ $tax ] = $slugs;
		}
	}
	$state['min']      = isset( $_GET['min'] ) ? max( 0, (int) $_GET['min'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$state['max']      = isset( $_GET['max'] ) ? max( 0, (int) $_GET['max'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$state['stock']    = ! empty( $_GET['stock'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$state['showroom'] = ! empty( $_GET['showroom'] ); // phpcs:ignore WordPress.Security.NonceVerification
	return $state;
}

function hifiexpert_filters_active() {
	$s = hifiexpert_filter_state();
	return $s['tax'] || $s['min'] || $s['max'] || $s['stock'] || $s['showroom'];
}

/**
 * Apply the state to the main product query.
 */
add_action( 'woocommerce_product_query', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$s   = hifiexpert_filter_state();
	$tax = (array) $q->get( 'tax_query' );
	foreach ( $s['tax'] as $taxonomy => $slugs ) {
		$tax[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slugs, 'operator' => 'IN' );
	}
	if ( $s['showroom'] && taxonomy_exists( 'pa_disponibil-in-showroom' ) ) {
		$tax[] = array( 'taxonomy' => 'pa_disponibil-in-showroom', 'field' => 'slug', 'terms' => array( 'da' ), 'operator' => 'IN' );
	}
	if ( count( $tax ) > 1 && empty( $tax['relation'] ) ) {
		$tax['relation'] = 'AND';
	}
	$q->set( 'tax_query', $tax );

	$meta = (array) $q->get( 'meta_query' );
	if ( $s['stock'] ) {
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
	}
	if ( $s['min'] || $s['max'] ) {
		$range = array( 'key' => '_price', 'type' => 'DECIMAL(10,2)' );
		if ( $s['min'] && $s['max'] ) {
			$range['value']   = array( $s['min'], $s['max'] );
			$range['compare'] = 'BETWEEN';
		} elseif ( $s['min'] ) {
			$range['value']   = $s['min'];
			$range['compare'] = '>=';
		} else {
			$range['value']   = $s['max'];
			$range['compare'] = '<=';
		}
		$meta[] = $range;
	}
	if ( $meta ) {
		$q->set( 'meta_query', $meta );
	}
} );

/**
 * IDs matching the current archive + filters, optionally ignoring one taxonomy
 * (so a facet's own counts show what *adding* each term would give).
 */
function hifiexpert_filtered_ids( $ignore_tax = '' ) {
	static $cache = array();
	if ( isset( $cache[ $ignore_tax ] ) ) {
		return $cache[ $ignore_tax ];
	}
	global $wp_query;
	$s    = hifiexpert_filter_state();
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( 'relation' => 'AND' ),
		'meta_query'     => array(),
	);
	// Context: category / brand / search.
	if ( is_tax() ) {
		$t = get_queried_object();
		$args['tax_query'][] = array( 'taxonomy' => $t->taxonomy, 'field' => 'term_id', 'terms' => $t->term_id );
	}
	if ( is_search() ) {
		$args['s'] = get_search_query();
	}
	$vis = wc_get_product_visibility_term_ids();
	$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => array_filter( array( $vis['exclude-from-catalog'] ?? 0 ) ), 'operator' => 'NOT IN' );
	foreach ( $s['tax'] as $taxonomy => $slugs ) {
		if ( $taxonomy === $ignore_tax ) {
			continue;
		}
		$args['tax_query'][] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slugs );
	}
	if ( $s['showroom'] && 'pa_disponibil-in-showroom' !== $ignore_tax && taxonomy_exists( 'pa_disponibil-in-showroom' ) ) {
		$args['tax_query'][] = array( 'taxonomy' => 'pa_disponibil-in-showroom', 'field' => 'slug', 'terms' => array( 'da' ) );
	}
	if ( $s['stock'] && 'stock' !== $ignore_tax ) {
		$args['meta_query'][] = array( 'key' => '_stock_status', 'value' => 'instock' );
	}
	if ( ( $s['min'] || $s['max'] ) && 'price' !== $ignore_tax ) {
		$args['meta_query'][] = array( 'key' => '_price', 'type' => 'DECIMAL(10,2)', 'value' => array( $s['min'] ?: 0, $s['max'] ?: 99999999 ), 'compare' => 'BETWEEN' );
	}
	$ids = get_posts( $args );
	return $cache[ $ignore_tax ] = array_map( 'intval', $ids );
}

/**
 * Term counts for one taxonomy within a set of product IDs.
 */
function hifiexpert_facet_counts( $taxonomy, array $ids ) {
	global $wpdb;
	if ( ! $ids ) {
		return array();
	}
	$in = implode( ',', array_map( 'intval', $ids ) );
	$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		"SELECT t.term_id, t.name, t.slug, COUNT(DISTINCT tr.object_id) AS n
		 FROM {$wpdb->term_relationships} tr
		 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 WHERE tt.taxonomy = %s AND tr.object_id IN ($in)
		 GROUP BY t.term_id ORDER BY n DESC, t.name ASC",
		$taxonomy
	) );
	return $rows ?: array();
}

/**
 * Min/max price in a set of IDs.
 */
function hifiexpert_price_bounds( array $ids ) {
	global $wpdb;
	if ( ! $ids ) {
		return array( 0, 0 );
	}
	$in  = implode( ',', array_map( 'intval', $ids ) );
	$row = $wpdb->get_row( "SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS mn, MAX(CAST(meta_value AS DECIMAL(10,2))) AS mx FROM {$wpdb->postmeta} WHERE meta_key='_price' AND meta_value <> '' AND post_id IN ($in)" ); // phpcs:ignore
	return array( (int) floor( (float) ( $row->mn ?? 0 ) ), (int) ceil( (float) ( $row->mx ?? 0 ) ) );
}

/**
 * Build a URL for the current archive with a modified filter state.
 */
function hifiexpert_filter_url( array $changes = array() ) {
	$s = hifiexpert_filter_state();
	foreach ( $changes as $k => $v ) {
		if ( 'tax' === $k ) {
			foreach ( $v as $tax => $slugs ) {
				if ( empty( $slugs ) ) {
					unset( $s['tax'][ $tax ] );
				} else {
					$s['tax'][ $tax ] = array_values( array_unique( $slugs ) );
				}
			}
		} else {
			$s[ $k ] = $v;
		}
	}
	$query = array();
	foreach ( $s['tax'] as $tax => $slugs ) {
		$query['f'][ $tax ] = implode( ',', $slugs );
	}
	if ( $s['min'] ) { $query['min'] = (int) $s['min']; }
	if ( $s['max'] ) { $query['max'] = (int) $s['max']; }
	if ( $s['stock'] ) { $query['stock'] = 1; }
	if ( $s['showroom'] ) { $query['showroom'] = 1; }
	if ( isset( $_GET['orderby'] ) ) { $query['orderby'] = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); } // phpcs:ignore WordPress.Security.NonceVerification
	if ( is_search() ) {
		$query['s'] = get_search_query();
		$query['post_type'] = 'product';
	}
	$base = is_search() ? home_url( '/' ) : ( is_tax() ? get_term_link( get_queried_object() ) : wc_get_page_permalink( 'shop' ) );
	$url  = $query ? add_query_arg( $query, $base ) : $base;
	return esc_url( $url );
}

/**
 * Toggle URL for a term in a facet.
 */
function hifiexpert_facet_toggle_url( $tax, $slug ) {
	$s       = hifiexpert_filter_state();
	$current = $s['tax'][ $tax ] ?? array();
	if ( in_array( $slug, $current, true ) ) {
		$current = array_values( array_diff( $current, array( $slug ) ) );
	} else {
		$current[] = $slug;
	}
	return hifiexpert_filter_url( array( 'tax' => array( $tax => $current ) ) );
}

/**
 * Render the filter rail. Returns the number of facets printed.
 */
function hifiexpert_render_filters() {
	$facets  = hifiexpert_active_facet_set();
	$state   = hifiexpert_filter_state();
	$all_ids = hifiexpert_filtered_ids();
	$printed = 0;

	// Price (bounds from the context without the price filter applied).
	list( $lo, $hi ) = hifiexpert_price_bounds( hifiexpert_filtered_ids( 'price' ) );
	if ( $hi > $lo ) {
		$printed++;
		?>
		<form class="hx-filter hx-filter--price" method="get" action="<?php echo esc_url( hifiexpert_filter_url( array( 'min' => 0, 'max' => 0 ) ) ); ?>">
			<h3 class="hx-filter__title"><?php esc_html_e( 'Preț', 'hifiexpert' ); ?></h3>
			<?php
			// carry everything else as hidden inputs
			foreach ( $state['tax'] as $tax => $slugs ) {
				printf( '<input type="hidden" name="f[%s]" value="%s">', esc_attr( $tax ), esc_attr( implode( ',', $slugs ) ) );
			}
			if ( $state['stock'] ) { echo '<input type="hidden" name="stock" value="1">'; }
			if ( $state['showroom'] ) { echo '<input type="hidden" name="showroom" value="1">'; }
			if ( is_search() ) { printf( '<input type="hidden" name="s" value="%s"><input type="hidden" name="post_type" value="product">', esc_attr( get_search_query() ) ); }
			?>
			<div class="hx-filter__range">
				<label><span class="screen-reader-text"><?php esc_html_e( 'Preț minim', 'hifiexpert' ); ?></span><input type="number" name="min" inputmode="numeric" min="0" placeholder="<?php echo esc_attr( $lo ); ?>" value="<?php echo $state['min'] ? (int) $state['min'] : ''; ?>"></label>
				<span aria-hidden="true">–</span>
				<label><span class="screen-reader-text"><?php esc_html_e( 'Preț maxim', 'hifiexpert' ); ?></span><input type="number" name="max" inputmode="numeric" min="0" placeholder="<?php echo esc_attr( $hi ); ?>" value="<?php echo $state['max'] ? (int) $state['max'] : ''; ?>"></label>
				<button class="hx-filter__go" type="submit" aria-label="<?php esc_attr_e( 'Aplică prețul', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'arrow', 16 ); ?></button>
			</div>
			<span class="hx-filter__hint"><?php printf( esc_html__( '%1$s – %2$s', 'hifiexpert' ), esc_html( hifiexpert_lei( $lo ) ), esc_html( hifiexpert_lei( $hi ) ) ); ?></span>
		</form>
		<?php
	}

	// Availability.
	$printed++;
	?>
	<div class="hx-filter">
		<h3 class="hx-filter__title"><?php esc_html_e( 'Disponibilitate', 'hifiexpert' ); ?></h3>
		<ul class="hx-filter__list">
			<li><a class="hx-filter__opt<?php echo $state['stock'] ? ' is-on' : ''; ?>" href="<?php echo hifiexpert_filter_url( array( 'stock' => ! $state['stock'] ) ); ?>" rel="nofollow"><span class="hx-filter__box"></span><?php esc_html_e( 'În stoc, livrare 24h', 'hifiexpert' ); ?></a></li>
			<?php if ( taxonomy_exists( 'pa_disponibil-in-showroom' ) ) : ?>
				<li><a class="hx-filter__opt<?php echo $state['showroom'] ? ' is-on' : ''; ?>" href="<?php echo hifiexpert_filter_url( array( 'showroom' => ! $state['showroom'] ) ); ?>" rel="nofollow"><span class="hx-filter__box"></span><?php esc_html_e( 'Poate fi ascultat în showroom', 'hifiexpert' ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
	<?php

	foreach ( $facets as $tax => $label ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$ids   = hifiexpert_filtered_ids( $tax );
		$terms = hifiexpert_facet_counts( $tax, $ids );
		// Facet is only useful if it splits the set: at least 2 options, or 1 option that is already selected.
		$selected = $state['tax'][ $tax ] ?? array();
		if ( count( $terms ) < 2 && ! $selected ) {
			continue;
		}
		// For brand pages, 'product_cat' facet shows only top-level categories.
		if ( 'product_cat' === $tax ) {
			$terms = array_values( array_filter( $terms, function ( $t ) {
				$term = get_term( $t->term_id, 'product_cat' );
				return $term && 0 === (int) $term->parent && 'uncategorized' !== $term->slug;
			} ) );
			if ( count( $terms ) < 2 ) {
				continue;
			}
		}
		$printed++;
		$limit = 8;
		$many  = count( $terms ) > $limit;
		?>
		<div class="hx-filter<?php echo $many ? ' hx-filter--collapsible' : ''; ?>" data-facet="<?php echo esc_attr( $tax ); ?>">
			<h3 class="hx-filter__title"><?php echo esc_html( $label ); ?></h3>
			<?php if ( count( $terms ) > 12 ) : ?>
				<input class="hx-filter__search" type="search" placeholder="<?php esc_attr_e( 'Caută…', 'hifiexpert' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Caută în %s', 'hifiexpert' ), $label ) ); ?>" data-hx-facet-search>
			<?php endif; ?>
			<ul class="hx-filter__list">
				<?php foreach ( $terms as $i => $t ) : ?>
					<?php $on = in_array( $t->slug, $selected, true ); ?>
					<li<?php echo ( $many && $i >= $limit && ! $on ) ? ' class="hx-filter__extra" hidden' : ''; ?>>
						<a class="hx-filter__opt<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo hifiexpert_facet_toggle_url( $tax, $t->slug ); ?>" rel="nofollow">
							<span class="hx-filter__box"></span><span class="hx-filter__name"><?php echo esc_html( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ); ?></span> <small><?php echo (int) $t->n; ?></small>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $many ) : ?>
				<button class="hx-filter__more" type="button" data-hx-facet-more aria-expanded="false"><?php printf( esc_html__( 'Toate (%d) +', 'hifiexpert' ), count( $terms ) ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	if ( hifiexpert_filters_active() ) {
		echo '<a class="hx-btn hx-btn--ghost hx-btn--sm hx-filter__reset" href="' . hifiexpert_filter_url( array( 'tax' => array_fill_keys( array_keys( $state['tax'] ), array() ), 'min' => 0, 'max' => 0, 'stock' => false, 'showroom' => false ) ) . '">' . esc_html__( 'Resetează filtrele', 'hifiexpert' ) . '</a>';
	}
	return $printed;
}

/**
 * Applied-filter chips for the toolbar.
 */
function hifiexpert_render_applied_chips() {
	$state = hifiexpert_filter_state();
	$out   = array();
	foreach ( $state['tax'] as $tax => $slugs ) {
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $tax );
			if ( ! $term ) {
				continue;
			}
			$out[] = '<a class="hx-tag" href="' . hifiexpert_facet_toggle_url( $tax, $slug ) . '" rel="nofollow">' . esc_html( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) . ' <span aria-hidden="true">×</span></a>';
		}
	}
	if ( $state['min'] || $state['max'] ) {
		$label = $state['min'] && $state['max'] ? hifiexpert_lei( $state['min'] ) . ' – ' . hifiexpert_lei( $state['max'] ) : ( $state['min'] ? 'peste ' . hifiexpert_lei( $state['min'] ) : 'sub ' . hifiexpert_lei( $state['max'] ) );
		$out[] = '<a class="hx-tag" href="' . hifiexpert_filter_url( array( 'min' => 0, 'max' => 0 ) ) . '" rel="nofollow">' . esc_html( $label ) . ' <span aria-hidden="true">×</span></a>';
	}
	if ( $state['stock'] ) {
		$out[] = '<a class="hx-tag" href="' . hifiexpert_filter_url( array( 'stock' => false ) ) . '" rel="nofollow">' . esc_html__( 'În stoc', 'hifiexpert' ) . ' <span aria-hidden="true">×</span></a>';
	}
	if ( $state['showroom'] ) {
		$out[] = '<a class="hx-tag" href="' . hifiexpert_filter_url( array( 'showroom' => false ) ) . '" rel="nofollow">' . esc_html__( 'În showroom', 'hifiexpert' ) . ' <span aria-hidden="true">×</span></a>';
	}
	echo implode( '', $out ); // phpcs:ignore
	return count( $out );
}

/**
 * Filtered pages shouldn't be indexed as separate URLs.
 */
add_action( 'wp_head', function () {
	if ( ( is_shop() || is_product_taxonomy() ) && hifiexpert_filters_active() ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
}, 1 );
