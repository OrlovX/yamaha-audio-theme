<?php
/**
 * SEO heads for the Yamaha shop. The site runs Yoast SEO; Rank Math is supported too, and without
 * either plugin the theme prints the essentials itself.
 *
 * - titles/descriptions generated for the front page, categories, products, promoții and contact
 *   when the plugin has none (or a shortcode-junk one from the old import);
 * - og:image fallback (Customizer "Imagine pentru distribuire", else the logo);
 * - schema: stale Article/Person nodes dropped on static pages, the showroom added as a Store.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Title / description the theme would use for the current request (either may be '').
 */
function yamaha_audio_seo_meta() {
	static $meta = null;
	if ( null !== $meta ) {
		return $meta;
	}
	$meta  = array( 'title' => '', 'desc' => '' );
	$site  = get_bloginfo( 'name' );
	$free  = number_format_i18n( (float) yamaha_audio_opt( 'free_shipping_min' ), 0 );
	$rates = (int) yamaha_audio_opt( 'rates_count' );

	if ( is_front_page() ) {
		$meta['title'] = sprintf( '%s – Receivere, amplificatoare, boxe și MusicCast Yamaha · Showroom București', $site );
		$meta['desc']  = sprintf( 'Dealer autorizat Yamaha: receivere AV, amplificatoare și receivere stereo, streamere MusicCast, boxe, pick‑up‑uri și căști. În stoc în București, livrare în 24h, transport gratuit peste %s lei, până la %d rate fără dobândă. Ascultă‑le în showroom‑ul din Cotroceni.', $free, $rates );
	} elseif ( class_exists( 'WooCommerce' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term ) {
			$meta['title'] = sprintf( '%1$s Yamaha – modele în stoc, prețuri și oferte | %2$s', $term->name, $site );
			if ( empty( $term->description ) ) {
				// What the page actually lists (children included), not the term's own count.
				$count = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
				if ( ! $count ) {
					$count = (int) get_term_meta( $term->term_id, 'product_count_product_cat', true ) ?: (int) $term->count;
				}
				$meta['desc'] = sprintf( '%1$s Yamaha: %2$d modele, în stoc în București, livrare în 24h, garanție 2 ani, până la %3$d rate fără dobândă. Vino să le asculți în showroom‑ul %4$s sau cere o recomandare pe WhatsApp.', $term->name, $count, $rates, $site );
			}
		}
	} elseif ( class_exists( 'WooCommerce' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$short = trim( wp_strip_all_tags( strip_shortcodes( (string) $product->get_short_description() ) ) );
			if ( strlen( $short ) < 60 || yamaha_audio_is_name_like( $short, $product ) ) {
				$lead = function_exists( 'yamaha_audio_product_lead' ) ? yamaha_audio_product_lead( $product, 40 ) : '';
				$meta['desc'] = $lead
					? $lead . ' ' . sprintf( 'În stoc la %s, livrare în 24h, garanție 2 ani.', $site )
					: sprintf( '%1$s – preț, specificații și disponibilitate la %2$s. Livrare în 24h, garanție 2 ani, ascultare în showroom.', $product->get_name(), $site );
			}
		}
	} elseif ( is_page( 'promotii' ) ) {
		$camp          = function_exists( 'yamaha_audio_campaign' ) ? yamaha_audio_campaign() : null;
		$meta['title'] = $camp && ! empty( $camp['name'] )
			? sprintf( '%1$s – promoții Yamaha în stoc | %2$s', $camp['name'], $site )
			: sprintf( 'Promoții Yamaha – oferte în stoc, livrare în 24h | %s', $site );
		$meta['desc'] = sprintf( 'Toate produsele Yamaha la ofertă, în stoc: reduceri reale față de cel mai mic preț din ultimele 30 de zile, livrare în 24h, până la %d rate fără dobândă.', $rates );
	} elseif ( is_page( 'contact' ) ) {
		$meta['title'] = sprintf( 'Contact – showroom Yamaha Cotroceni, București | %s', $site );
		$meta['desc'] = sprintf( 'Showroom %5$s, %1$s. Program: %2$s. Telefon/WhatsApp %3$s, e‑mail %4$s. Programează o audiție sau scrie‑ne pentru o recomandare.', yamaha_audio_opt( 'topbar_address' ), yamaha_audio_opt( 'topbar_hours' ), yamaha_audio_opt( 'phone' ), yamaha_audio_opt( 'topbar_email' ), $site );
	}
	return $meta;
}

/**
 * Ours wins when the plugin has no description, on the front page, or when the existing one carries
 * shortcode junk from the old import.
 */
function yamaha_audio_seo_pick_desc( $plugin_desc ) {
	$m = yamaha_audio_seo_meta();
	if ( ! $m['desc'] ) {
		return $plugin_desc;
	}
	$junk  = $plugin_desc && ( false !== strpos( $plugin_desc, '[' ) || false !== strpos( $plugin_desc, ']' ) );
	$short = $plugin_desc && mb_strlen( wp_strip_all_tags( $plugin_desc ) ) < 70; // too short to be a useful snippet
	return ( ! $plugin_desc || is_front_page() || $junk || $short ) ? $m['desc'] : $plugin_desc;
}

/**
 * Share image fallback: Customizer "Imagine pentru distribuire", else the logo.
 */
function yamaha_audio_og_fallback_image() {
	$id = (int) yamaha_audio_opt( 'og_image' ) ?: (int) get_theme_mod( 'custom_logo' );
	return $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
}

/* ---- WordPress core title ---- */
add_filter( 'document_title_parts', function ( $parts ) {
	$m = yamaha_audio_seo_meta();
	if ( $m['title'] ) {
		$parts = array( 'title' => $m['title'] );
	}
	return $parts;
}, 20 );

/* ---- Yoast SEO ---- */
add_filter( 'wpseo_title', function ( $title ) {
	$m = yamaha_audio_seo_meta();
	return $m['title'] ? $m['title'] : $title;
}, 20 );
add_filter( 'wpseo_metadesc', 'yamaha_audio_seo_pick_desc', 20 );
add_filter( 'wpseo_opengraph_title', function ( $t ) { $m = yamaha_audio_seo_meta(); return $m['title'] ?: $t; }, 20 );
add_filter( 'wpseo_opengraph_desc', 'yamaha_audio_seo_pick_desc', 20 );
add_filter( 'wpseo_twitter_description', 'yamaha_audio_seo_pick_desc', 20 );
add_filter( 'wpseo_opengraph_image', function ( $img ) { return $img ? $img : yamaha_audio_og_fallback_image(); }, 20 );
add_filter( 'wpseo_twitter_image', function ( $img ) { return $img ? $img : yamaha_audio_og_fallback_image(); }, 20 );
// Yoast ≥14 only runs the og:image filter when it already has an image; add the fallback to its image container
// (home, categories, pages without a featured image) so every URL carries an og:image.
add_action( 'wpseo_add_opengraph_additional_images', function ( $images ) {
	if ( is_object( $images ) && method_exists( $images, 'has_images' ) && method_exists( $images, 'add_image_by_id' ) && ! $images->has_images() ) {
		$id = (int) yamaha_audio_opt( 'og_image' ) ?: (int) get_theme_mod( 'custom_logo' );
		if ( $id ) {
			$images->add_image_by_id( $id );
		}
	}
} );
add_filter( 'wpseo_frontend_presentation', function ( $presentation ) {
	if ( $presentation instanceof \Yoast\WP\SEO\Presentations\Indexable_Presentation ) {
		$imgs = $presentation->open_graph_images; // lazily generated by Yoast
		if ( empty( $imgs ) ) {
			$url = yamaha_audio_og_fallback_image();
			if ( $url ) {
				$presentation->open_graph_images = array( array( 'url' => $url ) );
			}
		}
	}
	return $presentation;
}, 20 );
// Yoast schema graph: drop stale nodes on static pages, add the showroom.
add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	if ( is_page() || is_front_page() ) {
		$graph = array_values( array_filter( $graph, function ( $node ) {
			$type = is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
			return ! array_intersect( $type, array( 'Article', 'NewsArticle', 'BlogPosting', 'VideoObject', 'Person' ) );
		} ) );
	}
	if ( is_front_page() || is_page( 'contact' ) ) {
		$graph[] = yamaha_audio_store_schema();
	}
	return $graph;
}, 99 );

/* ---- Rank Math (kept so the theme also works on a Rank Math site) ---- */
add_filter( 'rank_math/frontend/title', function ( $title ) {
	$m = yamaha_audio_seo_meta();
	return $m['title'] ? $m['title'] : $title;
}, 20 );
add_filter( 'rank_math/frontend/description', 'yamaha_audio_seo_pick_desc', 20 );
add_filter( 'rank_math/opengraph/facebook/image', function ( $img ) { return $img ? $img : yamaha_audio_og_fallback_image(); }, 20 );
add_filter( 'rank_math/opengraph/twitter/image', function ( $img ) { return $img ? $img : yamaha_audio_og_fallback_image(); }, 20 );
add_filter( 'rank_math/opengraph/facebook/og_title', function ( $t ) { $m = yamaha_audio_seo_meta(); return $m['title'] ?: $t; }, 20 );
add_filter( 'rank_math/opengraph/facebook/og_description', 'yamaha_audio_seo_pick_desc', 20 );
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
		$data['hxStore'] = yamaha_audio_store_schema();
	}
	return $data;
}, 99, 2 );

/* ---- No SEO plugin: description + Store schema printed by the theme ---- */
add_action( 'wp_head', function () {
	if ( class_exists( 'RankMath' ) || defined( 'WPSEO_VERSION' ) ) {
		return;
	}
	$m = yamaha_audio_seo_meta();
	if ( $m['desc'] ) {
		echo '<meta name="description" content="' . esc_attr( $m['desc'] ) . '">' . "\n";
	}
	if ( is_front_page() || is_page( 'contact' ) ) {
		echo '<script type="application/ld+json">' . wp_json_encode( yamaha_audio_store_schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}, 1 );

/**
 * The showroom as a Store node (address from the Customizer: "Bd. Eroii Sanitari 89, Cotroceni, București").
 */
function yamaha_audio_store_schema() {
	$hours = array();
	$map   = array( 'hours_weekday' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), 'hours_saturday' => array( 'Saturday' ), 'hours_sunday' => array( 'Sunday' ) );
	foreach ( $map as $opt => $days ) {
		if ( preg_match( '/(\d{1,2}[:.]\d{2})\s*-\s*(\d{1,2}[:.]\d{2})/', (string) yamaha_audio_opt( $opt ), $m ) ) {
			$hours[] = array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => str_replace( '.', ':', $m[1] ), 'closes' => str_replace( '.', ':', $m[2] ) );
		}
	}
	$parts  = array_map( 'trim', explode( ',', (string) yamaha_audio_opt( 'topbar_address' ) ) );
	$street = $parts[0] ?? '';
	$city   = end( $parts ) ?: 'București';
	$postal = preg_match( '/\b(\d{6})\b/', (string) yamaha_audio_opt( 'company_address' ), $pm ) ? $pm[1] : '';
	$logo   = (int) get_theme_mod( 'custom_logo' );
	$node   = array(
		'@type'              => array( 'Store', 'ElectronicsStore' ),
		'@id'                => home_url( '/#showroom' ),
		'name'               => get_bloginfo( 'name' ),
		'legalName'          => yamaha_audio_opt( 'company_name' ),
		'url'                => home_url( '/' ),
		'telephone'          => preg_replace( '/\s+/', '', (string) ( yamaha_audio_opt( 'whatsapp' ) ?: yamaha_audio_opt( 'phone' ) ) ),
		'email'              => yamaha_audio_opt( 'topbar_email' ),
		'brand'              => array( '@type' => 'Brand', 'name' => 'Yamaha' ),
		'priceRange'         => '$$$',
		'currenciesAccepted' => 'RON',
		'paymentAccepted'    => 'Cash, Credit Card, Bank Transfer',
		'address'            => array_filter( array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
			'addressRegion'   => $city,
			'postalCode'      => $postal,
			'addressCountry'  => 'RO',
		) ),
		'hasMap'             => yamaha_audio_opt( 'topbar_address_url' ),
		'openingHoursSpecification' => $hours,
	);
	if ( $logo ) {
		$node['image'] = wp_get_attachment_image_url( $logo, 'full' );
		$node['logo']  = $node['image'];
	}
	if ( yamaha_audio_opt( 'company_cui' ) ) {
		$node['vatID'] = yamaha_audio_opt( 'company_cui' );
		$node['taxID'] = yamaha_audio_opt( 'company_cui' );
	}
	return $node;
}

/**
 * Archive pages: the term name alone, not "Receivere Archives".
 */
add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_tax() ) {
		return single_term_title( '', false );
	}
	return $title;
} );
