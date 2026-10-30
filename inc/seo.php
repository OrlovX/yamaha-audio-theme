<?php
/**
 * SEO touches the theme guarantees whatever the Rank Math per-page settings say:
 * - front page title/description (the page is called "HiFi Expert Home Default" in WP);
 * - brand archives: "Yamaha – …" instead of "Yamaha Archives";
 * - categories / brands / promoții / branduri: generated descriptions when none is set;
 * - products: the lead (cleaned of shortcodes) as description when the short description is junk;
 * - schema: no stale Article/VideoObject/Person on static pages, plus a Store (LocalBusiness)
 *   node with the showroom address and opening hours.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

/**
 * Title / description the theme wants for the current request, or nulls.
 */
function hifiexpert_seo_meta() {
	static $meta = null;
	if ( null !== $meta ) {
		return $meta;
	}
	$meta = array( 'title' => null, 'desc' => null );
	$site = get_bloginfo( 'name' );
	$free = number_format_i18n( (float) hifiexpert_opt( 'free_shipping_min' ), 0 );
	$rates = (int) hifiexpert_opt( 'rates_count' );

	if ( is_front_page() ) {
		$meta['title'] = sprintf( '%s – Receivere, amplificatoare, boxe și MusicCast Yamaha · Showroom București', $site );
		$meta['desc']  = sprintf( 'Dealer autorizat Yamaha: receivere AV, amplificatoare și receivere stereo, streamere MusicCast, boxe, pick‑up‑uri și căști. În stoc în București, livrare în 24h, transport gratuit peste %s lei, până la %d rate fără dobândă. Ascultă‑le în showroom‑ul din Cotroceni.', $free, $rates );
	} elseif ( class_exists( 'WooCommerce' ) && is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term && hifiexpert_brand_taxonomy() === $term->taxonomy ) {
			$meta['title'] = sprintf( '%1$s – produse în stoc, prețuri și oferte | %2$s', $term->name, $site );
			$meta['desc']  = $term->description ? wp_strip_all_tags( $term->description ) : sprintf( '%1$s la %3$s: %2$d produse, în stoc în București, livrare în 24h, garanție 2 ani și service autorizat. Ascultă %1$s în showroom‑ul din Cotroceni sau cere o recomandare pe WhatsApp.', $term->name, (int) $term->count, $site );
		} elseif ( $term && 'product_cat' === $term->taxonomy && empty( $term->description ) ) {
			$brands = hifiexpert_category_top_brands( $term, 4 );
			$meta['desc'] = $brands
				? sprintf( '%1$s de la %2$s: %3$d produse, în stoc în București, livrare în 24h, până la %4$d rate fără dobândă. Vino să le asculți în showroom‑ul %5$s.', $term->name, implode( ', ', $brands ), (int) $term->count, $rates, $site )
				: sprintf( '%1$s Yamaha: %2$d produse, în stoc în București, livrare în 24h, până la %3$d rate fără dobândă. Vino să le asculți în showroom‑ul %4$s.', $term->name, (int) $term->count, $rates, $site );
		}
	} elseif ( class_exists( 'WooCommerce' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$short = trim( wp_strip_all_tags( strip_shortcodes( (string) $product->get_short_description() ) ) );
			if ( strlen( $short ) < 60 || hifiexpert_is_name_like( $short, $product ) ) {
				$lead = function_exists( 'hifiexpert_product_lead' ) ? hifiexpert_product_lead( $product, 40 ) : '';
				$meta['desc'] = $lead ? $lead . ' ' . sprintf( 'În stoc la %s, livrare în 24h, garanție 2 ani.', $site ) : sprintf( '%1$s – preț, specificații și disponibilitate la %2$s. Livrare în 24h, garanție 2 ani, ascultare în showroom.', $product->get_name(), $site );
			}
		}
	} elseif ( is_page( 'promotii' ) ) {
		$meta['desc'] = sprintf( 'Toate produsele Yamaha la ofertă, în stoc: reduceri reale față de cel mai mic preț din ultimele 30 de zile, livrare în 24h, până la %d rate fără dobândă.', $rates );
	} elseif ( is_page( 'contact' ) ) {
		$meta['desc'] = sprintf( 'Showroom %5$s, %1$s. Program: %2$s. Telefon/WhatsApp %3$s, e‑mail %4$s. Programează o audiție sau scrie‑ne pentru o recomandare.', hifiexpert_opt( 'topbar_address' ), hifiexpert_opt( 'topbar_hours' ), hifiexpert_opt( 'phone' ), hifiexpert_opt( 'topbar_email' ), $site );
	} elseif ( is_page( 'brands' ) || is_page( 'branduri' ) ) {
		$meta['desc'] = sprintf( 'Brandurile %s – disponibile pentru audiție în showroom‑ul din București.', $site );
	}
	return $meta;
}

/**
 * The brands with most products inside a category (names, by count), cached 12h.
 */
function hifiexpert_category_top_brands( $term, $limit = 4 ) {
	$tax = hifiexpert_brand_taxonomy();
	if ( ! $tax ) {
		return array();
	}
	$key    = 'hx_cat_brands_' . $term->term_id;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	$ids = get_term_children( $term->term_id, 'product_cat' );
	$ids = array_map( 'intval', array_merge( array( $term->term_id ), is_wp_error( $ids ) ? array() : $ids ) );
	$in  = implode( ',', $ids );
	$rows = $wpdb->get_col( $wpdb->prepare(
		"SELECT t.name FROM {$wpdb->term_relationships} tr
		 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
		 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 WHERE tr.object_id IN (
			SELECT tr2.object_id FROM {$wpdb->term_relationships} tr2
			INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id AND tt2.taxonomy = 'product_cat' AND tt2.term_id IN ({$in})
		 )
		 GROUP BY t.term_id ORDER BY COUNT(*) DESC LIMIT %d",
		$tax,
		(int) $limit
	) );
	$hidden = hifiexpert_hidden_brand_slugs();
	$rows   = array_values( array_filter( (array) $rows, function ( $n ) use ( $hidden ) { return ! in_array( sanitize_title( $n ), $hidden, true ); } ) );
	set_transient( $key, $rows, 12 * HOUR_IN_SECONDS );
	return $rows;
}

add_filter( 'document_title_parts', function ( $parts ) {
	$m = hifiexpert_seo_meta();
	if ( $m['title'] ) {
		$parts = array( 'title' => $m['title'] );
	}
	return $parts;
}, 20 );
add_filter( 'rank_math/frontend/title', function ( $title ) {
	$m = hifiexpert_seo_meta();
	return $m['title'] ? $m['title'] : $title;
}, 20 );
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	$m = hifiexpert_seo_meta();
	if ( ! $m['desc'] ) {
		return $desc;
	}
	// ours wins when there is none, on the front page, or when the existing one carries shortcode junk
	$junk = $desc && ( false !== strpos( $desc, '[' ) || false !== strpos( $desc, ']' ) );
	return ( ! $desc || is_front_page() || $junk ) ? $m['desc'] : $desc;
}, 20 );
// Share image fallback: Customizer "Imagine pentru distribuire", else the logo.
function hifiexpert_og_fallback_image() {
	$id = (int) hifiexpert_opt( 'og_image' ) ?: (int) get_theme_mod( 'custom_logo' );
	return $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
}
add_filter( 'rank_math/opengraph/facebook/image', function ( $img ) { return $img ? $img : hifiexpert_og_fallback_image(); }, 20 );
add_filter( 'rank_math/opengraph/twitter/image', function ( $img ) { return $img ? $img : hifiexpert_og_fallback_image(); }, 20 );
add_filter( 'rank_math/opengraph/facebook/og_title', function ( $t ) { $m = hifiexpert_seo_meta(); return $m['title'] ?: $t; }, 20 );
add_filter( 'rank_math/opengraph/facebook/og_description', function ( $d ) { $m = hifiexpert_seo_meta(); return $m['desc'] ?: $d; }, 20 );

// Without an SEO plugin, print a description ourselves.
add_action( 'wp_head', function () {
	if ( class_exists( 'RankMath' ) || defined( 'WPSEO_VERSION' ) ) {
		return;
	}
	$m = hifiexpert_seo_meta();
	if ( $m['desc'] ) {
		echo '<meta name="description" content="' . esc_attr( $m['desc'] ) . '">' . "\n";
	}
}, 1 );

/**
 * Schema: drop stale Article / VideoObject / Person nodes on static pages; add the showroom as a Store.
 */
add_filter( 'rank_math/json_ld', function ( $data, $jsonld ) {
	if ( is_page() || is_front_page() ) {
		foreach ( $data as $key => $node ) {
			$type = is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
			if ( array_intersect( $type, array( 'Article', 'NewsArticle', 'BlogPosting', 'VideoObject', 'Person' ) ) ) {
				unset( $data[ $key ] );
			}
		}
	}
	if ( is_front_page() || is_page( 'contact' ) ) {
		$data['hxStore'] = hifiexpert_store_schema();
	}
	return $data;
}, 99, 2 );

add_action( 'wp_head', function () {
	if ( class_exists( 'RankMath' ) || ! ( is_front_page() || is_page( 'contact' ) ) ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( hifiexpert_store_schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 5 );

function hifiexpert_store_schema() {
	$hours = array();
	$map   = array( 'hours_weekday' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), 'hours_saturday' => array( 'Saturday' ), 'hours_sunday' => array( 'Sunday' ) );
	foreach ( $map as $opt => $days ) {
		if ( preg_match( '/(\d{1,2}[:.]\d{2})\s*-\s*(\d{1,2}[:.]\d{2})/', (string) hifiexpert_opt( $opt ), $m ) ) {
			$hours[] = array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => str_replace( '.', ':', $m[1] ), 'closes' => str_replace( '.', ':', $m[2] ) );
		}
	}
	$logo = (int) get_theme_mod( 'custom_logo' );
	$node = array(
		'@type'            => array( 'Store', 'ElectronicsStore' ),
		'@id'              => home_url( '/#showroom' ),
		'name'             => get_bloginfo( 'name' ),
		'legalName'        => hifiexpert_opt( 'company_name' ),
		'url'              => home_url( '/' ),
		'telephone'        => preg_replace( '/\s+/', '', (string) ( hifiexpert_opt( 'whatsapp' ) ?: hifiexpert_opt( 'phone' ) ) ),
		'email'            => hifiexpert_opt( 'topbar_email' ),
		'priceRange'       => '$$$',
		'currenciesAccepted' => 'RON',
		'paymentAccepted'  => 'Cash, Credit Card, Bank Transfer',
		'address'          => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Bd. Eroii Sanitari 89',
			'addressLocality' => 'București',
			'addressRegion'   => 'București',
			'postalCode'      => '050472',
			'addressCountry'  => 'RO',
		),
		'hasMap'           => hifiexpert_opt( 'topbar_address_url' ),
		'openingHoursSpecification' => $hours,
	);
	if ( $logo ) {
		$node['image'] = wp_get_attachment_image_url( $logo, 'full' );
		$node['logo']  = $node['image'];
	}
	if ( hifiexpert_opt( 'company_cui' ) ) {
		$node['vatID']  = hifiexpert_opt( 'company_cui' );
		$node['taxID']  = hifiexpert_opt( 'company_cui' );
	}
	return $node;
}

/**
 * Archive / brand pages: the WooCommerce title says "Yamaha" already; Rank Math's "%term% Archives" does not.
 */
add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_tax() ) {
		return single_term_title( '', false );
	}
	return $title;
} );
