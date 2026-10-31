<?php
/**
 * Faceted filters for product archives — no plugin, URL-driven, counts aware of the current result set.
 *
 * URL shape: ?f[pa_culoare]=negru&f[pa_canale]=7-2&min=1000&max=5000&stock=1&showroom=1
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which facets to offer in which context. Keys are product_cat slugs (any ancestor matches);
 * '_default' is used when nothing matches. Each entry: taxonomy => label.
 * Single-brand shop: there is no brand facet. The default set is every product attribute registered in
 * WooCommerce (labelled as in WooCommerce); a facet is only rendered when it actually splits the result
 * set, so this stays tidy on its own. Per-category sets can be added through the filter.
 * Non-taxonomy facets ('price', 'stock', 'showroom') are handled separately and always shown.
 */
function yamaha_audio_facet_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$default = array();
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		// Internal attributes never become filters: the brand (single-brand shop), the showroom flag and
		// whatever the owner lists in the Customizer (price tiers like "PA"/"PA1", import codes…).
		$exclude = array( 'producator', 'brand', 'marca', 'disponibil-in-showroom' );
		foreach ( explode( ',', strtolower( (string) yamaha_audio_opt( 'facets_exclude' ) ) ) as $x ) {
			$x = trim( $x );
			if ( '' !== $x ) {
				$exclude[] = $x;
			}
		}
		foreach ( wc_get_attribute_taxonomies() as $a ) {
			if ( in_array( strtolower( $a->attribute_name ), $exclude, true ) ) {
				continue;
			}
			$tax = wc_attribute_taxonomy_name( $a->attribute_name );
			// Attributes whose values are plain numbers (prices, codes) are data, not something a buyer filters by.
			$sample = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'number' => 6, 'fields' => 'names' ) );
			if ( ! is_wp_error( $sample ) && $sample ) {
				$words = array_filter( $sample, function ( $n ) {
					return ! is_numeric( str_replace( array( ',', '.', ' ' ), '', (string) $n ) );
				} );
				if ( ! $words ) {
					continue;
				}
			}
			$default[ $tax ] = $a->attribute_label;
		}
	}
	$map = apply_filters( 'yamaha_audio_facet_map', array( '_default' => $default ) );
	return $map;
}

/**
 * Facets for the current archive.
 */
function yamaha_audio_active_facet_set() {
	$map = yamaha_audio_facet_map();
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
	return $map['_default'];
}

/**
 * Parse the request into a normalized filter state.
 */
function yamaha_audio_filter_state() {
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

function yamaha_audio_filters_active() {
	$s = yamaha_audio_filter_state();
	return $s['tax'] || $s['min'] || $s['max'] || $s['stock'] || $s['showroom'];
}

/**
 * Apply the state to the main product query.
 */
add_action( 'woocommerce_product_query', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$s   = yamaha_audio_filter_state();
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
function yamaha_audio_filtered_ids( $ignore_tax = '' ) {
	static $cache = array();
	if ( isset( $cache[ $ignore_tax ] ) ) {
		return $cache[ $ignore_tax ];
	}
	global $wp_query;
	$s    = yamaha_audio_filter_state();
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( 'relation' => 'AND' ),
		'meta_query'     => array(),
	);
	// Context: category / search.
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
function yamaha_audio_facet_counts( $taxonomy, array $ids ) {
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
function yamaha_audio_price_bounds( array $ids ) {
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
function yamaha_audio_filter_url( array $changes = array() ) {
	$s = yamaha_audio_filter_state();
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
function yamaha_audio_facet_toggle_url( $tax, $slug ) {
	$s       = yamaha_audio_filter_state();
	$current = $s['tax'][ $tax ] ?? array();
	if ( in_array( $slug, $current, true ) ) {
		$current = array_values( array_diff( $current, array( $slug ) ) );
	} else {
		$current[] = $slug;
	}
	return yamaha_audio_filter_url( array( 'tax' => array( $tax => $current ) ) );
}

/**
 * Render the filter rail. Returns the number of facets printed.
 */
function yamaha_audio_render_filters() {
	$facets  = yamaha_audio_active_facet_set();
	$state   = yamaha_audio_filter_state();
	$all_ids = yamaha_audio_filtered_ids();
	$printed = 0;

	// Price (bounds from the context without the price filter applied).
	list( $lo, $hi ) = yamaha_audio_price_bounds( yamaha_audio_filtered_ids( 'price' ) );
	if ( $hi > $lo ) {
		$printed++;
		?>
		<form class="hx-filter hx-filter--price" method="get" action="<?php echo esc_url( yamaha_audio_filter_url( array( 'min' => 0, 'max' => 0 ) ) ); ?>">
			<h3 class="hx-filter__title"><?php esc_html_e( 'Preț', 'yamaha-audio' ); ?></h3>
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
				<label><span class="screen-reader-text"><?php esc_html_e( 'Preț minim', 'yamaha-audio' ); ?></span><input type="number" name="min" inputmode="numeric" min="0" placeholder="<?php echo esc_attr( $lo ); ?>" value="<?php echo $state['min'] ? (int) $state['min'] : ''; ?>"></label>
				<span aria-hidden="true">–</span>
				<label><span class="screen-reader-text"><?php esc_html_e( 'Preț maxim', 'yamaha-audio' ); ?></span><input type="number" name="max" inputmode="numeric" min="0" placeholder="<?php echo esc_attr( $hi ); ?>" value="<?php echo $state['max'] ? (int) $state['max'] : ''; ?>"></label>
				<button class="hx-filter__go" type="submit" aria-label="<?php esc_attr_e( 'Aplică prețul', 'yamaha-audio' ); ?>"><?php echo yamaha_audio_icon( 'arrow', 16 ); ?></button>
			</div>
			<span class="hx-filter__hint"><?php printf( esc_html__( '%1$s – %2$s', 'yamaha-audio' ), esc_html( yamaha_audio_lei( $lo ) ), esc_html( yamaha_audio_lei( $hi ) ) ); ?></span>
		</form>
		<?php
	}

	// Availability.
	$printed++;
	?>
	<div class="hx-filter">
		<h3 class="hx-filter__title"><?php esc_html_e( 'Disponibilitate', 'yamaha-audio' ); ?></h3>
		<ul class="hx-filter__list">
			<li><a class="hx-filter__opt<?php echo $state['stock'] ? ' is-on' : ''; ?>" href="<?php echo yamaha_audio_filter_url( array( 'stock' => ! $state['stock'] ) ); ?>" rel="nofollow"><span class="hx-filter__box"></span><?php esc_html_e( 'În stoc, livrare 24h', 'yamaha-audio' ); ?></a></li>
			<?php if ( taxonomy_exists( 'pa_disponibil-in-showroom' ) ) : ?>
				<li><a class="hx-filter__opt<?php echo $state['showroom'] ? ' is-on' : ''; ?>" href="<?php echo yamaha_audio_filter_url( array( 'showroom' => ! $state['showroom'] ) ); ?>" rel="nofollow"><span class="hx-filter__box"></span><?php esc_html_e( 'Poate fi ascultat în showroom', 'yamaha-audio' ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
	<?php

	$shown = 0;
	foreach ( $facets as $tax => $label ) {
		if ( ! taxonomy_exists( $tax ) || $shown >= 8 ) {
			continue;
		}
		$ids   = yamaha_audio_filtered_ids( $tax );
		$terms = yamaha_audio_facet_counts( $tax, $ids );
		// Facet is only useful if it splits the set: at least 2 options, or 1 option that is already selected.
		$selected = $state['tax'][ $tax ] ?? array();
		if ( count( $terms ) < 2 && ! $selected ) {
			continue;
		}
		// A 'product_cat' facet (search results) shows only top-level categories.
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
		$shown++;
		$limit = 8;
		$many  = count( $terms ) > $limit;
		?>
		<div class="hx-filter<?php echo $many ? ' hx-filter--collapsible' : ''; ?>" data-facet="<?php echo esc_attr( $tax ); ?>">
			<h3 class="hx-filter__title"><?php echo esc_html( $label ); ?></h3>
			<?php if ( count( $terms ) > 12 ) : ?>
				<input class="hx-filter__search" type="search" placeholder="<?php esc_attr_e( 'Caută…', 'yamaha-audio' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Caută în %s', 'yamaha-audio' ), $label ) ); ?>" data-hx-facet-search>
			<?php endif; ?>
			<ul class="hx-filter__list">
				<?php foreach ( $terms as $i => $t ) : ?>
					<?php $on = in_array( $t->slug, $selected, true ); ?>
					<li<?php echo ( $many && $i >= $limit && ! $on ) ? ' class="hx-filter__extra" hidden' : ''; ?>>
						<a class="hx-filter__opt<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo yamaha_audio_facet_toggle_url( $tax, $t->slug ); ?>" rel="nofollow">
							<span class="hx-filter__box"></span><span class="hx-filter__name"><?php echo esc_html( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ); ?></span> <small><?php echo (int) $t->n; ?></small>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $many ) : ?>
				<button class="hx-filter__more" type="button" data-hx-facet-more aria-expanded="false"><?php printf( esc_html__( 'Toate (%d) +', 'yamaha-audio' ), count( $terms ) ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	if ( yamaha_audio_filters_active() ) {
		echo '<a class="hx-btn hx-btn--ghost hx-btn--sm hx-filter__reset" href="' . yamaha_audio_filter_url( array( 'tax' => array_fill_keys( array_keys( $state['tax'] ), array() ), 'min' => 0, 'max' => 0, 'stock' => false, 'showroom' => false ) ) . '">' . esc_html__( 'Resetează filtrele', 'yamaha-audio' ) . '</a>';
	}
	return $printed;
}

/**
 * Applied-filter chips for the toolbar.
 */
function yamaha_audio_render_applied_chips() {
	$state = yamaha_audio_filter_state();
	$out   = array();
	foreach ( $state['tax'] as $tax => $slugs ) {
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $tax );
			if ( ! $term ) {
				continue;
			}
			$out[] = '<a class="hx-tag" href="' . yamaha_audio_facet_toggle_url( $tax, $slug ) . '" rel="nofollow">' . esc_html( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) . ' <span aria-hidden="true">×</span></a>';
		}
	}
	if ( $state['min'] || $state['max'] ) {
		$label = $state['min'] && $state['max'] ? yamaha_audio_lei( $state['min'] ) . ' – ' . yamaha_audio_lei( $state['max'] ) : ( $state['min'] ? 'peste ' . yamaha_audio_lei( $state['min'] ) : 'sub ' . yamaha_audio_lei( $state['max'] ) );
		$out[] = '<a class="hx-tag" href="' . yamaha_audio_filter_url( array( 'min' => 0, 'max' => 0 ) ) . '" rel="nofollow">' . esc_html( $label ) . ' <span aria-hidden="true">×</span></a>';
	}
	if ( $state['stock'] ) {
		$out[] = '<a class="hx-tag" href="' . yamaha_audio_filter_url( array( 'stock' => false ) ) . '" rel="nofollow">' . esc_html__( 'În stoc', 'yamaha-audio' ) . ' <span aria-hidden="true">×</span></a>';
	}
	if ( $state['showroom'] ) {
		$out[] = '<a class="hx-tag" href="' . yamaha_audio_filter_url( array( 'showroom' => false ) ) . '" rel="nofollow">' . esc_html__( 'În showroom', 'yamaha-audio' ) . ' <span aria-hidden="true">×</span></a>';
	}
	echo implode( '', $out ); // phpcs:ignore
	return count( $out );
}

/**
 * Filtered pages shouldn't be indexed as separate URLs.
 */
add_action( 'wp_head', function () {
	if ( ( is_shop() || is_product_taxonomy() ) && yamaha_audio_filters_active() ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
}, 1 );
