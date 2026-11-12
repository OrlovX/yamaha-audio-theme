<?php
/**
 * WooCommerce integration: hooks, query tweaks, single-product layout.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Wrappers, sidebar, breadcrumb
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );

add_filter( 'woocommerce_breadcrumb_defaults', function ( $d ) {
	$d['delimiter']   = '<span class="hx-crumbs__sep" aria-hidden="true">/</span>';
	$d['wrap_before'] = '<nav class="hx-crumbs" aria-label="' . esc_attr__( 'Navigare', 'yamaha-audio' ) . '">';
	$d['wrap_after']  = '</nav>';
	$d['home']        = __( 'Acasă', 'yamaha-audio' );
	return $d;
} );

/* -------------------------------------------------------------------------
 * Loop
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_product_loop_start', function () {
	return '<ul class="hx-grid hx-grid--products products">';
} );
add_filter( 'woocommerce_product_loop_end', function () {
	return '</ul>';
} );
add_filter( 'loop_shop_columns', function () {
	return 3;
} );
add_filter( 'loop_shop_per_page', function () {
	return 24;
} );

// Pagination: our pill style.
add_filter( 'woocommerce_pagination_args', function ( $args ) {
	$args['prev_text'] = '‹';
	$args['next_text'] = '›';
	$args['mid_size']  = 1;
	return $args;
} );

// Default ordering: most popular first (the store setting may say price; a snippet may override it).
add_filter( 'woocommerce_default_catalog_orderby', function () {
	return 'popularity';
}, 999 );
add_filter( 'pre_option_woocommerce_default_catalog_orderby', function () {
	return 'popularity';
}, 999 );
add_filter( 'woocommerce_get_catalog_ordering_args', function ( $args ) {
	if ( empty( $_GET['orderby'] ) && ! is_search() ) { // phpcs:ignore WordPress.Security.NonceVerification
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return $args;
}, 999 );

// Catalog order. Default ("Recomandate"): in stock first, priced before "sună pentru preț", then best sellers,
// then newest. Any other ordering keeps its own sort, but in-stock products still come first.
// Runs late (1000) so a site snippet forcing "price ascending" on categories does not win over it.
add_filter( 'posts_clauses', function ( $clauses, $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! ( is_shop() || is_product_taxonomy() || ( is_search() && 'product' === $query->get( 'post_type' ) ) ) ) {
		return $clauses;
	}
	global $wpdb;
	if ( false === strpos( $clauses['join'], 'hx_stock' ) ) {
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} hx_stock ON hx_stock.post_id = {$wpdb->posts}.ID AND hx_stock.meta_key = '_stock_status' ";
	}
	$stock    = "CASE WHEN hx_stock.meta_value = 'instock' THEN 0 WHEN hx_stock.meta_value = 'onbackorder' THEN 1 ELSE 2 END ASC";
	$selected = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $selected || 'popularity' === $selected ) {
		if ( false === strpos( $clauses['join'], 'wc_product_meta_lookup' ) ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} AS wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";
		}
		$clauses['orderby'] = $stock
			. ', CASE WHEN wc_product_meta_lookup.min_price IS NULL OR wc_product_meta_lookup.min_price <= 0 THEN 1 ELSE 0 END ASC'
			. ', COALESCE(wc_product_meta_lookup.total_sales, 0) DESC'
			. ", {$wpdb->posts}.post_date DESC";
		return $clauses;
	}
	$clauses['orderby'] = $clauses['orderby'] ? $stock . ', ' . $clauses['orderby'] : $stock;
	return $clauses;
}, 1000, 2 );

add_filter( 'woocommerce_catalog_orderby', function ( $options ) {
	return array(
		'popularity' => __( 'Recomandate', 'yamaha-audio' ),
		'date'       => __( 'Noutăți', 'yamaha-audio' ),
		'price'      => __( 'Preț crescător', 'yamaha-audio' ),
		'price-desc' => __( 'Preț descrescător', 'yamaha-audio' ),
	);
} );

/* -------------------------------------------------------------------------
 * Header cart count (ajax fragments)
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$fragments['.hx-cart-count'] = '<span class="hx-cart-count' . ( $count ? '' : ' is-empty' ) . '" aria-hidden="true">' . (int) $count . '</span>';
	return $fragments;
} );

/* -------------------------------------------------------------------------
 * Single product
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return __( 'Adaugă în coș', 'yamaha-audio' );
} );
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	if ( $product && $product->is_type( 'variable' ) ) {
		return __( 'Alege opțiunile', 'yamaha-audio' );
	}
	return $text;
}, 10, 2 );

// Summary order: category, title, lead, price, rate, variations/add-to-cart, promises, secondary CTAs, trust.
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

// Short descriptions on this site are "name + [product_info_table]" from the old theme:
// show a real lead sentence from the description instead.
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_lead', 20 );
function yamaha_audio_single_lead() {
	global $product;
	$lead = yamaha_audio_product_lead( $product, 40 );
	if ( $lead ) {
		echo '<p class="woocommerce-product-details__short-description">' . esc_html( $lead ) . '</p>';
	}
}
// Dead shortcode from the previous theme: render nothing rather than the raw tag.
add_action( 'init', function () {
	if ( ! shortcode_exists( 'product_info_table' ) ) {
		add_shortcode( 'product_info_table', '__return_empty_string' );
	}
}, 20 );

add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_kicker', 4 );
add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_price', 10 );
add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_promises', 25 );
add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_secondary', 32 );
add_action( 'woocommerce_single_product_summary', 'yamaha_audio_single_trust', 35 );
add_action( 'woocommerce_after_single_product_summary', 'yamaha_audio_single_sections', 10 );
add_action( 'woocommerce_after_single_product', 'yamaha_audio_single_buybar', 5 );

// Keep sale badge off the gallery (we render our own pill).
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

/**
 * Eyebrow above the product title: the product's main category (the brand is always Yamaha here).
 */
function yamaha_audio_single_kicker() {
	global $product;
	$cat = yamaha_audio_product_category( $product );
	if ( $cat ) {
		printf( '<a class="hx-eyebrow hx-single__kicker" href="%s">%s</a>', esc_url( get_term_link( $cat ) ), esc_html( $cat->name ) );
	}
}

function yamaha_audio_single_price() {
	global $product;
	$percent = yamaha_audio_discount_percent( $product );
	echo '<div class="hx-single__pricing">';
	yamaha_audio_price_block( $product, 'single' );
	$rate = yamaha_audio_installment( yamaha_audio_active_price( $product ) );
	if ( $rate ) {
		printf(
			'<p class="hx-single__rate">%s <strong>%d × %s</strong> %s</p>',
			esc_html__( 'sau', 'yamaha-audio' ),
			(int) yamaha_audio_opt( 'rates_count' ),
			esc_html( yamaha_audio_lei( $rate ) ),
			esc_html( yamaha_audio_opt( 'rates_label' ) )
		);
	}
	echo '</div>';
}

function yamaha_audio_qty_text( $qty ) {
	return sprintf( _n( '%d bucată', '%d bucăți', (int) $qty, 'yamaha-audio' ), (int) $qty );
}

function yamaha_audio_single_promises() {
	global $product;
	$a = yamaha_audio_availability( $product );
	if ( ! $a['label'] ) {
		return;
	}
	$kind = $a['kind'];
	echo '<div class="hx-promise" data-hx-promise data-kind="' . esc_attr( $kind ) . '">';
	echo '<span class="hx-stock hx-stock--' . esc_attr( $a['class'] ) . ' hx-promise__stock"><span class="hx-stock__dot"></span><span data-hx-stock-label>' . esc_html( $a['label'] ) . '</span>';
	echo '<span data-hx-stock-qty>' . ( null !== $a['qty'] && $a['qty'] > 0 && $a['qty'] <= 5 ? ' · ' . esc_html( yamaha_audio_qty_text( $a['qty'] ) ) : '' ) . '</span></span>';
	$in = in_array( $kind, array( 'instock', 'low' ), true );
	echo '<span class="hx-promise__line" data-hx-line="instock"' . ( $in ? '' : ' hidden' ) . '>' . esc_html( yamaha_audio_opt( 'delivery_promise' ) ) . '</span>';
	$pickup = yamaha_audio_opt( 'pickup_promise' );
	if ( $pickup ) {
		echo '<span class="hx-promise__line hx-promise__line--muted" data-hx-line="instock"' . ( $in ? '' : ' hidden' ) . '>' . esc_html( $pickup ) . '</span>';
	}
	echo '<span class="hx-promise__line" data-hx-line="backorder"' . ( 'backorder' === $kind ? '' : ' hidden' ) . '>' . esc_html( yamaha_audio_opt( 'backorder_promise' ) ) . '</span>';
	echo '<span class="hx-promise__line" data-hx-line="outofstock"' . ( 'outofstock' === $kind ? '' : ' hidden' ) . '>' . esc_html( yamaha_audio_opt( 'outofstock_note' ) ) . '</span>';
	echo '</div>';
}

// The variation JSON carries our availability so the box can follow the chosen variation.
add_filter( 'woocommerce_available_variation', function ( $data, $product, $variation ) {
	$a = yamaha_audio_availability( $variation );
	$data['hx_availability'] = array(
		'kind'     => $a['kind'],
		'class'    => $a['class'],
		'label'    => $a['label'],
		'qty_text' => null !== $a['qty'] && $a['qty'] > 0 && $a['qty'] <= 5 ? ' · ' . yamaha_audio_qty_text( $a['qty'] ) : '',
	);
	return $data;
}, 10, 3 );

function yamaha_audio_single_secondary() {
	global $product;
	$wa = yamaha_audio_whatsapp_url( sprintf( __( 'Bună! Am o întrebare despre %s (%s)', 'yamaha-audio' ), $product->get_name(), $product->get_permalink() ) );
	echo '<div class="hx-single__secondary">';
	if ( $wa ) {
		echo '<a class="hx-btn hx-btn--ghost" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . yamaha_audio_icon( 'chat', 18 ) . esc_html__( 'Întreabă un expert', 'yamaha-audio' ) . '</a>';
	}
	echo '<a class="hx-btn hx-btn--ghost" href="' . esc_url( yamaha_audio_opt( 'topbar_address_url' ) ) . '" target="_blank" rel="noopener">' . yamaha_audio_icon( 'pin', 18 ) . esc_html__( 'Ascultă‑l în showroom', 'yamaha-audio' ) . '</a>';
	echo '</div>';
	if ( function_exists( 'YITH_WCWL' ) && shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) {
		echo '<div class="hx-single__wishlist">' . do_shortcode( '[yith_wcwl_add_to_wishlist]' ) . '</div>';
	}
}

// Wishlist button texts (the plugin's defaults are in English / shouty).
add_filter( 'yith_wcwl_button_label', function () { return __( 'Salvează în lista de dorințe', 'yamaha-audio' ); } );
add_filter( 'yith_wcwl_browse_wishlist_label', function () { return __( 'Vezi lista de dorințe', 'yamaha-audio' ); } );
add_filter( 'yith_wcwl_product_added_to_wishlist_message_button', function () { return __( 'Salvat în lista de dorințe', 'yamaha-audio' ); } );
add_filter( 'yith_wcwl_product_already_in_wishlist_message_button', function () { return __( 'Este deja în lista ta', 'yamaha-audio' ); } );

function yamaha_audio_single_trust() {
	$items = array_filter( array( yamaha_audio_opt( 'warranty_text' ), yamaha_audio_opt( 'returns_text' ), yamaha_audio_opt( 'invoice_text' ) ) );
	if ( ! $items ) {
		return;
	}
	echo '<ul class="hx-single__trust">';
	foreach ( $items as $i ) {
		echo '<li>' . esc_html( $i ) . '</li>';
	}
	echo '</ul>';
}

/**
 * Highlights from the short description: if it is a bullet list, tiles; else nothing extra
 * (the short description is already shown as the lead in the summary).
 */
function yamaha_audio_highlight_items( $product ) {
	$html = $product->get_short_description();
	if ( ! $html || false === stripos( $html, '<li' ) ) {
		return array();
	}
	preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $html, $m );
	$items = array_values( array_filter( array_map( function ( $li ) {
		return trim( wp_strip_all_tags( $li ) );
	}, $m[1] ) ) );
	return array_slice( $items, 0, 6 );
}

function yamaha_audio_single_sections() {
	global $product;
	$highlights = yamaha_audio_highlight_items( $product );
	$desc       = apply_filters( 'yamaha_audio_product_description_html', apply_filters( 'the_content', yamaha_audio_clean_description( $product ) ) );

	echo '<div class="hx-single__sections">';

	if ( $highlights ) {
		echo '<section class="hx-section hx-section--tight"><h2 class="hx-section__title">' . sprintf( esc_html__( 'De ce %s', 'yamaha-audio' ), esc_html( $product->get_name() ) ) . '</h2><div class="hx-why">';
		foreach ( $highlights as $h ) {
			echo '<div class="hx-why__item">' . esc_html( $h ) . '</div>';
		}
		echo '</div></section>';
	}

	$specs = yamaha_audio_spec_rows( $product );

	if ( $desc ) {
		echo '<section class="hx-section hx-section--tight hx-single__desc"><h2 class="hx-section__title">' . esc_html__( 'Descriere', 'yamaha-audio' ) . '</h2><div class="hx-prose">' . $desc . '</div></section>'; // phpcs:ignore
	}
	if ( function_exists( 'yamaha_audio_single_tech_strip' ) ) {
		yamaha_audio_single_tech_strip();
	}
	if ( count( $specs ) >= 3 ) {
		echo '<section class="hx-section hx-section--tight hx-single__specs"><h2 class="hx-section__title">' . esc_html__( 'Specificații', 'yamaha-audio' ) . '</h2><dl class="hx-specs hx-specs--grid">';
		foreach ( $specs as $row ) {
			$value = function_exists( 'yamaha_audio_link_tech_terms' ) ? yamaha_audio_link_tech_terms( esc_html( $row[1] ) ) : esc_html( $row[1] );
			echo '<div class="hx-specs__row"><dt>' . esc_html( $row[0] ) . '</dt><dd>' . wp_kses_post( $value ) . '</dd></div>';
		}
		$sku = $product->get_sku();
		if ( $sku ) {
			echo '<div class="hx-specs__row"><dt>' . esc_html__( 'Cod produs', 'yamaha-audio' ) . '</dt><dd>' . esc_html( $sku ) . '</dd></div>';
		}
		echo '</dl></section>';
	}

	// Reviews stay available but only when there are any.
	if ( comments_open() && $product->get_review_count() > 0 ) {
		echo '<section class="hx-section hx-section--tight hx-reviews">';
		comments_template();
		echo '</section>';
	}

	echo '</div>';
}

/**
 * Sticky buy bar shown when the summary scrolls out of view.
 */
function yamaha_audio_single_buybar() {
	global $product;
	if ( ! $product || ! $product->is_purchasable() ) {
		return;
	}
	$cat  = yamaha_audio_product_category( $product );
	$rate = yamaha_audio_rate_line( $product );
	?>
	<div class="hx-buybar" id="hx-buybar" hidden>
		<div class="hx-buybar__name">
			<?php if ( $cat ) : ?><span class="hx-buybar__kicker"><?php echo esc_html( $cat->name ); ?></span><?php endif; ?>
			<span class="hx-buybar__title"><?php echo esc_html( $product->get_name() ); ?></span>
		</div>
		<div class="hx-buybar__right">
			<span class="hx-buybar__price">
				<span><?php echo esc_html( yamaha_audio_lei( yamaha_audio_active_price( $product ) ) ); ?></span>
				<?php if ( $rate ) : ?><small><?php echo esc_html( $rate ); ?></small><?php endif; ?>
			</span>
			<button type="button" class="hx-btn hx-btn--primary hx-btn--sm" data-hx-buy><?php echo $product->is_type( 'variable' ) ? esc_html__( 'Alege opțiunile', 'yamaha-audio' ) : esc_html__( 'Adaugă în coș', 'yamaha-audio' ); ?></button>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Related / upsells: 3 per row, our headings
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 3;
	$args['columns']        = 3;
	return $args;
} );
add_filter( 'woocommerce_upsells_columns', function () {
	return 3;
} );
add_filter( 'woocommerce_product_related_products_heading', function () {
	return __( 'Produse similare', 'yamaha-audio' );
} );
add_filter( 'woocommerce_product_upsells_products_heading', function () {
	return __( 'Se potrivește cu', 'yamaha-audio' );
} );

/* -------------------------------------------------------------------------
 * Misc
 * ---------------------------------------------------------------------- */

// Quantity: no "Cantitate" label clutter.
add_filter( 'woocommerce_quantity_input_args', function ( $args ) {
	$args['input_name'] = $args['input_name'] ?? 'quantity';
	return $args;
} );

// Notices wrapper class.
add_filter( 'woocommerce_demo_store', function ( $notice ) {
	return $notice; // leave as is
} );

/* ---- Page cache (WP Super Cache): keep product, home and category pages honest ---- */
function yamaha_audio_purge_product_cache( $product ) {
	if ( ! function_exists( 'wp_cache_post_change' ) ) {
		return; // no page cache active
	}
	$id = is_object( $product ) ? (int) ( $product->get_parent_id() ?: $product->get_id() ) : (int) $product;
	if ( ! $id ) {
		return;
	}
	static $done = array();
	if ( isset( $done[ $id ] ) ) {
		return;
	}
	$done[ $id ] = true;
	wp_cache_post_change( $id );
	if ( function_exists( 'wpsc_delete_url_cache' ) ) {
		$urls = array( home_url( '/' ), home_url( '/promotii/' ), wc_get_page_permalink( 'shop' ) );
		foreach ( (array) wp_get_post_terms( $id, 'product_cat' ) as $term ) {
			if ( $term instanceof WP_Term ) {
				$urls[] = get_term_link( $term );
				foreach ( get_ancestors( $term->term_id, 'product_cat' ) as $anc ) {
					$urls[] = get_term_link( (int) $anc, 'product_cat' );
				}
			}
		}
		foreach ( array_unique( array_filter( $urls, 'is_string' ) ) as $url ) {
			wpsc_delete_url_cache( $url );
		}
	}
}
add_action( 'woocommerce_product_set_stock', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_variation_set_stock', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_product_set_stock_status', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_variation_set_stock_status', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_update_product', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_new_product', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_update_product_variation', 'yamaha_audio_purge_product_cache' );
add_action( 'woocommerce_product_object_updated_props', 'yamaha_audio_purge_product_cache' );
// Campaign / Customizer changes affect every page.
add_action( 'customize_save_after', function () {
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
} );

/**
 * Checkout without a postcode: shipping zones go by county, so the field is only friction.
 * Hidden + optional for every country, in both the block and the classic checkout.
 */
function yamaha_hide_postcode_field( $field ) {
	return array_merge( is_array( $field ) ? $field : array(), array( 'required' => false, 'hidden' => true ) );
}
add_filter( 'woocommerce_default_address_fields', function ( $fields ) {
	$fields['postcode'] = yamaha_hide_postcode_field( $fields['postcode'] ?? array() );
	return $fields;
}, 20 );
add_filter( 'woocommerce_get_country_locale_default', function ( $locale ) {
	$locale['postcode'] = yamaha_hide_postcode_field( $locale['postcode'] ?? array() );
	return $locale;
}, 20 );
add_filter( 'woocommerce_get_country_locale', function ( $locales ) {
	foreach ( $locales as $cc => $fields ) {
		$locales[ $cc ]['postcode'] = yamaha_hide_postcode_field( $fields['postcode'] ?? array() );
	}
	$locales['RO']['postcode'] = yamaha_hide_postcode_field( $locales['RO']['postcode'] ?? array() );
	return $locales;
}, 20 );
// Classic checkout and My account → Addresses: drop the field outright.
foreach ( array( 'billing', 'shipping' ) as $yamaha_addr_type ) {
	add_filter( 'woocommerce_' . $yamaha_addr_type . '_fields', function ( $fields ) use ( $yamaha_addr_type ) {
		unset( $fields[ $yamaha_addr_type . '_postcode' ] );
		return $fields;
	}, 20 );
}

/**
 * Checkout locality must be one FAN Courier knows: its nomenclature is in assets/data/fan/<county>.json
 * (one file per county, courier spelling, no diacritics). The checkout suggests those names (main.js);
 * this is the server side – rewrite what was typed to the courier's spelling ("cluj napoca" →
 * "Cluj-Napoca"), fill "Bucuresti" for the county București, and refuse a locality that is not in the list.
 */
function yamaha_fan_key( $text ) {
	return preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( (string) $text ) ) );
}
/** Courier spelling of $city in county $state, '' when unknown, null when there is no list to check against. */
function yamaha_fan_locality( $state, $city ) {
	static $cache = array();
	if ( 'B' === $state ) {
		return 'Bucuresti';
	}
	if ( ! preg_match( '/^[A-Z]{1,2}$/', (string) $state ) ) {
		return null;
	}
	if ( ! isset( $cache[ $state ] ) ) {
		$file  = get_theme_file_path( 'assets/data/fan/' . $state . '.json' );
		$names = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$index = array();
		foreach ( is_array( $names ) ? $names : array() as $name ) {
			$index[ yamaha_fan_key( $name ) ] = $name;
		}
		$cache[ $state ] = $index;
	}
	if ( ! $cache[ $state ] ) {
		return null;
	}
	return $cache[ $state ][ yamaha_fan_key( $city ) ] ?? '';
}
function yamaha_fan_message( $city ) {
	return sprintf( 'Localitatea „%s” nu este în lista curierului pentru județul ales. Alege localitatea din sugestiile câmpului.', $city );
}
// Classic checkout.
add_filter( 'woocommerce_checkout_posted_data', function ( $data ) {
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		if ( 'RO' !== ( $data[ $type . '_country' ] ?? 'RO' ) || empty( $data[ $type . '_state' ] ) ) {
			continue;
		}
		$name = yamaha_fan_locality( $data[ $type . '_state' ], $data[ $type . '_city' ] ?? '' );
		if ( $name ) {
			$data[ $type . '_city' ] = $name;
		}
	}
	return $data;
} );
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	$types = array( 'billing' );
	if ( ! empty( $data['ship_to_different_address'] ) ) {
		$types[] = 'shipping';
	}
	foreach ( $types as $type ) {
		$city = trim( (string) ( $data[ $type . '_city' ] ?? '' ) );
		if ( '' === $city || 'RO' !== ( $data[ $type . '_country' ] ?? 'RO' ) || empty( $data[ $type . '_state' ] ) ) {
			continue;
		}
		if ( '' === yamaha_fan_locality( $data[ $type . '_state' ], $city ) ) {
			$errors->add( $type . '_city_fan', yamaha_fan_message( $city ) );
			return;
		}
	}
}, 10, 2 );
// Block checkout (Store API).
add_action( 'woocommerce_store_api_checkout_update_order_from_request', function ( $order ) {
	$changed = false;
	$pickup  = false;
	foreach ( $order->get_shipping_methods() as $method ) {
		if ( in_array( $method->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) {
			$pickup = true; // the "shipping address" is then the showroom's own.
		}
	}
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		$city = trim( (string) $order->{"get_{$type}_city"}() );
		if ( ( 'shipping' === $type && $pickup ) || '' === $city || 'RO' !== $order->{"get_{$type}_country"}() || ! $order->{"get_{$type}_state"}() ) {
			continue;
		}
		$name = yamaha_fan_locality( $order->{"get_{$type}_state"}(), $city );
		if ( '' === $name ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'yamaha_locality_unknown', yamaha_fan_message( $city ), 400 );
		}
		if ( $name && $name !== $city ) {
			$order->{"set_{$type}_city"}( $name );
			$changed = true;
		}
	}
	if ( $changed ) {
		$order->save();
	}
}, 10, 1 );
