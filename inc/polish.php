<?php
/**
 * Polish from the November 2026 audit of SEO, speed and the buying flow: small pieces that do not
 * belong to one feature file. Each block says what it fixes; the site-specific texts are in
 * yamaha_audio_polish_cfg().
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

function yamaha_audio_polish_cfg() {
	return array(
		'shop_title' => 'Magazin Yamaha: toate produsele în stoc | Yamaha Audio România',
		'shop_desc'  => 'Tot catalogul Yamaha: receivere, amplificatoare, boxe, soundbaruri, MusicCast și căști, în stoc în București. Livrare în 24h, rate fără dobândă, garanție 2 ani.',
		'blog_desc'  => 'Articole despre tehnologiile și produsele Yamaha: receivere, MusicCast, sunet surround și ghiduri de alegere.',
		'flat_rate'  => 0,        // rate under the free-shipping threshold not published: left out of the markup.
		'brand'      => 'Yamaha', // single-brand shop.
		'defer'      => array(), // HTTP/2 here: nothing to gain, and the consent integration expects order attribution to load first.
	);
}

/* =====================================================================================
 * SEO
 * ================================================================================== */

/**
 * Search results show about 155 characters of a description: cut longer ones at the end of a
 * sentence when one falls late enough, else at a word, so nothing ends mid-word.
 */
function yamaha_audio_seo_clip( $text, $max = 160 ) {
	$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( '' === $text || mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max );
	if ( preg_match( '/^(.{90,}[.!?])\s/u', $cut . ' ', $m ) ) {
		return $m[1];
	}
	$cut = (string) preg_replace( '/\s+\S*$/u', '', mb_substr( $text, 0, $max - 1 ) );
	return rtrim( $cut, " ,;:–-·" ) . '…';
}
add_filter( 'rank_math/frontend/description', 'yamaha_audio_seo_clip', 40 );
add_filter( 'wpseo_metadesc', 'yamaha_audio_seo_clip', 40 );

/** Title and description for the pages the main SEO file does not cover (shop, blog). */
function yamaha_audio_seo_extra() {
	static $meta = null;
	if ( null !== $meta ) {
		return $meta;
	}
	$cfg  = yamaha_audio_polish_cfg();
	$meta = array( 'title' => '', 'desc' => '' );
	if ( function_exists( 'is_shop' ) && is_shop() && ! is_search() ) {
		$meta = array( 'title' => $cfg['shop_title'], 'desc' => $cfg['shop_desc'] );
	} elseif ( is_home() && ! is_front_page() ) {
		$meta['desc'] = $cfg['blog_desc'];
	}
	return $meta;
}
function yamaha_audio_seo_extra_title( $title ) {
	$m = yamaha_audio_seo_extra();
	if ( $m['title'] ) {
		return $m['title'];
	}
	// Products: a stored SEO title without the shop's name is a leftover from the old import
	// ("Cablu Cablu Supra Ply"): use the product's current name instead.
	if ( function_exists( 'is_product' ) && is_product() ) {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		if ( false === mb_stripos( (string) $title, $site ) ) {
			return wp_strip_all_tags( get_the_title( get_queried_object_id() ) ) . ' - ' . $site;
		}
	}
	return $title;
}
function yamaha_audio_seo_extra_desc( $desc ) {
	$m = yamaha_audio_seo_extra();
	return $m['desc'] ? $m['desc'] : $desc;
}
foreach ( array( 'rank_math/frontend/title', 'wpseo_title', 'wpseo_opengraph_title', 'rank_math/opengraph/facebook/og_title' ) as $yamaha_audio_hook ) {
	add_filter( $yamaha_audio_hook, 'yamaha_audio_seo_extra_title', 25 );
}
foreach ( array( 'rank_math/frontend/description', 'wpseo_metadesc', 'wpseo_opengraph_desc', 'rank_math/opengraph/facebook/og_description' ) as $yamaha_audio_hook ) {
	add_filter( $yamaha_audio_hook, 'yamaha_audio_seo_extra_desc', 25 );
}
add_filter( 'document_title_parts', function ( $parts ) {
	$m = yamaha_audio_seo_extra();
	return $m['title'] ? array( 'title' => $m['title'] ) : $parts;
}, 25 );

/**
 * Robots: one answer from the SEO plugin instead of a second, contradicting meta tag.
 * Filtered lists and the wishlist page stay out of the index; the shop page is indexable.
 * Returns '' (no opinion), 'index', or the follow value to pair with noindex.
 */
function yamaha_audio_robots_wish() {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && function_exists( 'yamaha_audio_filters_active' ) && yamaha_audio_filters_active() ) {
		return 'nofollow';
	}
	if ( is_page() && function_exists( 'YITH_WCWL' ) && (int) get_option( 'yith_wcwl_wishlist_page_id' ) === (int) get_queried_object_id() ) {
		return 'follow';
	}
	if ( is_singular( 'blocks' ) ) {
		return 'follow'; // layout fragments of the old theme, not pages.
	}
	if ( function_exists( 'is_shop' ) && is_shop() && ! is_search() ) {
		return 'index';
	}
	return '';
}
function yamaha_audio_robots_array( $robots ) {
	$wish = yamaha_audio_robots_wish();
	if ( ! is_array( $robots ) || '' === $wish ) {
		return $robots;
	}
	if ( 'index' === $wish ) {
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';
	} else {
		$robots['index']  = 'noindex';
		$robots['follow'] = $wish;
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'yamaha_audio_robots_array', 40 );
add_filter( 'wpseo_robots_array', 'yamaha_audio_robots_array', 40 );
add_filter( 'rank_math/sitemap/exclude_post_type', function ( $exclude, $type ) {
	return 'blocks' === $type ? true : $exclude;
}, 10, 2 );

/* ---- Product structured data: who ships it, what it costs to send, how returns work ---- */

/** Extra Offer fields. Shipping only when it is certain: in stock, and a known rate. */
function yamaha_audio_offer_extras( $price, $in_stock ) {
	$cfg  = yamaha_audio_polish_cfg();
	$free = (float) yamaha_audio_opt( 'free_shipping_min' );
	$out  = array(
		'hasMerchantReturnPolicy' => array(
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'RO',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays'   => 14,
			'returnMethod'         => 'https://schema.org/ReturnByMail',
			'returnFees'           => 'https://schema.org/ReturnFeesCustomerResponsibility',
		),
	);
	$rate = null;
	if ( $free > 0 && $price >= $free ) {
		$rate = 0;
	} elseif ( $cfg['flat_rate'] > 0 ) {
		$rate = $cfg['flat_rate'];
	}
	if ( $in_stock && null !== $rate ) {
		$out['shippingDetails'] = array(
			'@type'               => 'OfferShippingDetails',
			'shippingRate'        => array( '@type' => 'MonetaryAmount', 'value' => $rate, 'currency' => get_woocommerce_currency() ),
			'shippingDestination' => array( '@type' => 'DefinedRegion', 'addressCountry' => 'RO' ),
			'deliveryTime'        => array(
				'@type'        => 'ShippingDeliveryTime',
				'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ),
				'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 2, 'unitCode' => 'DAY' ),
			),
		);
	}
	return $out;
}
function yamaha_audio_offer_fix( $offer ) {
	if ( ! is_array( $offer ) ) {
		return $offer;
	}
	if ( ! isset( $offer['@type'] ) && isset( $offer[0] ) ) {
		return array_map( 'yamaha_audio_offer_fix', $offer ); // a list of offers.
	}
	$price = $offer['price'] ?? ( isset( $offer['priceSpecification'][0]['price'] ) ? $offer['priceSpecification'][0]['price'] : ( $offer['priceSpecification']['price'] ?? null ) );
	if ( null === $price || '' === $price ) {
		return $offer;
	}
	$extras = yamaha_audio_offer_extras( (float) $price, false !== strpos( (string) ( $offer['availability'] ?? '' ), 'InStock' ) );
	foreach ( $extras as $key => $value ) {
		if ( empty( $offer[ $key ] ) ) {
			$offer[ $key ] = $value;
		}
	}
	return $offer;
}
function yamaha_audio_schema_description( $product ) {
	$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $product->get_short_description() ) ) ) );
	if ( mb_strlen( $text ) < 50 && function_exists( 'yamaha_audio_product_lead' ) ) {
		$text = (string) yamaha_audio_product_lead( $product, 60 );
	}
	return mb_strlen( $text ) >= 50 ? mb_substr( $text, 0, 480 ) : '';
}
function yamaha_audio_product_brand_name( $product ) {
	$cfg = yamaha_audio_polish_cfg();
	if ( $cfg['brand'] ) {
		return $cfg['brand'];
	}
	$term = function_exists( 'yamaha_audio_product_brand' ) ? yamaha_audio_product_brand( $product ) : null;
	return $term && ! empty( $term->name ) ? html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) : '';
}
/** Acasă > category path > product, from the product's deepest category. */
function yamaha_audio_product_breadcrumb_schema( $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	$best  = null;
	$depth = -1;
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		$d = count( get_ancestors( $term->term_id, 'product_cat' ) );
		if ( $d > $depth ) {
			$depth = $d;
			$best  = $term;
		}
	}
	$trail = array( array( __( 'Acasă', 'yamaha-audio' ), home_url( '/' ) ) );
	if ( $best ) {
		foreach ( array_reverse( get_ancestors( $best->term_id, 'product_cat' ) ) as $id ) {
			$t = get_term( $id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				$trail[] = array( $t->name, get_term_link( $t ) );
			}
		}
		$trail[] = array( $best->name, get_term_link( $best ) );
	}
	$trail[] = array( $product->get_name(), $product->get_permalink() );
	$items   = array();
	foreach ( $trail as $i => $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => wp_strip_all_tags( html_entity_decode( (string) $crumb[0], ENT_QUOTES, 'UTF-8' ) ),
			'item'     => is_string( $crumb[1] ) ? $crumb[1] : home_url( '/' ),
		);
	}
	return array( '@type' => 'BreadcrumbList', '@id' => $product->get_permalink() . '#breadcrumb', 'itemListElement' => $items );
}

// Rank Math (hifiexpert.ro): Product / ProductGroup nodes.
add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_array( $data ) || ! function_exists( 'is_product' ) || ! is_product() ) {
		return $data;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return $data;
	}
	$has_crumbs = false;
	foreach ( $data as $key => $node ) {
		$types = is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( in_array( 'BreadcrumbList', $types, true ) ) {
			$has_crumbs = true;
		}
		if ( ! array_intersect( $types, array( 'Product', 'ProductGroup' ) ) ) {
			continue;
		}
		$node['name'] = wp_strip_all_tags( $product->get_name() );
		$desc         = yamaha_audio_schema_description( $product );
		if ( $desc ) {
			$node['description'] = $desc;
		}
		$brand = yamaha_audio_product_brand_name( $product );
		if ( $brand && empty( $node['brand'] ) ) {
			$node['brand'] = array( '@type' => 'Brand', 'name' => $brand );
		}
		if ( isset( $node['offers'] ) ) {
			$node['offers'] = yamaha_audio_offer_fix( $node['offers'] );
		}
		if ( ! empty( $node['hasVariant'] ) && is_array( $node['hasVariant'] ) ) {
			foreach ( $node['hasVariant'] as $i => $variant ) {
				if ( is_array( $variant ) && isset( $variant['offers'] ) ) {
					$node['hasVariant'][ $i ]['offers'] = yamaha_audio_offer_fix( $variant['offers'] );
				}
				if ( is_array( $variant ) && $desc ) {
					$node['hasVariant'][ $i ]['description'] = $desc;
				}
			}
		}
		$data[ $key ] = $node;
	}
	if ( ! $has_crumbs ) {
		$data['hxBreadcrumb'] = yamaha_audio_product_breadcrumb_schema( $product );
	}
	return $data;
}, 100 );

// WooCommerce's own Product node (yamaha-audio.ro).
add_filter( 'woocommerce_structured_data_product', function ( $markup ) {
	if ( is_array( $markup ) && isset( $markup['offers'] ) ) {
		$markup['offers'] = yamaha_audio_offer_fix( $markup['offers'] );
	}
	return $markup;
}, 30 );

// Yoast prints "Prima pagină > Shop > product" next to WooCommerce's real category trail: keep one.
add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_array( $graph ) || ! function_exists( 'is_product' ) || ! ( is_product() || is_product_taxonomy() || is_shop() ) ) {
		return $graph;
	}
	$out = array();
	foreach ( $graph as $node ) {
		$types = is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( in_array( 'BreadcrumbList', $types, true ) ) {
			continue;
		}
		if ( is_array( $node ) ) {
			unset( $node['breadcrumb'] );
		}
		$out[] = $node;
	}
	return $out;
}, 40 );

// Yoast free marks products as og:type "article": say product, with price and availability.
add_filter( 'wpseo_opengraph_type', function ( $type ) {
	return function_exists( 'is_product' ) && is_product() ? 'product' : $type;
} );
add_action( 'wp_head', function () {
	if ( ! defined( 'WPSEO_VERSION' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || '' === (string) $product->get_price() ) {
		return;
	}
	printf( "<meta property=\"product:price:amount\" content=\"%s\">\n", esc_attr( wc_format_decimal( wc_get_price_to_display( $product ), 2 ) ) );
	printf( "<meta property=\"product:price:currency\" content=\"%s\">\n", esc_attr( get_woocommerce_currency() ) );
	printf( "<meta property=\"product:availability\" content=\"%s\">\n", $product->is_in_stock() ? 'instock' : 'oos' );
}, 6 );

// Blog posts imported with their own <h1> in the text: the page already has one, the title.
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || false === stripos( $content, '<h1' ) ) {
		return $content;
	}
	$title   = trim( wp_strip_all_tags( get_the_title() ) );
	$content = preg_replace_callback( '#<h1\b([^>]*)>(.*?)</h1>#is', function ( $m ) use ( $title ) {
		return trim( wp_strip_all_tags( $m[2] ) ) === $title ? '' : '<h2' . $m[1] . '>' . $m[2] . '</h2>';
	}, $content );
	return $content;
}, 9 );

/* =====================================================================================
 * Speed: do not load what the page does not use
 * ================================================================================== */

/**
 * Leave assets out of the page by a part of their URL (plugin handles change, paths do not).
 * $strip_deps: also remove them from other assets' dependency lists, or WordPress prints them anyway.
 * Only safe for styles: a script that lists a dependency usually calls it.
 */
function yamaha_audio_drop_assets( $deps, array $needles, $strip_deps ) {
	if ( ! $deps || ! $needles ) {
		return;
	}
	$gone = array();
	foreach ( $deps->registered as $handle => $item ) {
		$src = is_string( $item->src ) ? $item->src : '';
		foreach ( $needles as $needle ) {
			if ( '' !== $src && false !== stripos( $src, $needle ) ) {
				$gone[] = $handle;
				break;
			}
		}
	}
	foreach ( $gone as $handle ) {
		$deps->dequeue( $handle );
	}
	if ( $strip_deps && $gone ) {
		foreach ( $deps->registered as $item ) {
			if ( ! empty( $item->deps ) ) {
				$item->deps = array_values( array_diff( $item->deps, $gone ) );
			}
		}
	}
}

/**
 * Tiny local stylesheets are written into the page instead of being fetched: one request less before
 * the first paint. WordPress does the inlining itself (wp_maybe_inline_styles) once it knows the file.
 */
function yamaha_audio_inline_small_styles( array $needles, $max_bytes = 3000 ) {
	$base = wp_parse_url( content_url(), PHP_URL_PATH );
	if ( ! $base ) {
		return;
	}
	foreach ( wp_styles()->registered as $handle => $item ) {
		$src = is_string( $item->src ) ? $item->src : '';
		if ( '' === $src || ! empty( $item->extra['path'] ) ) {
			continue;
		}
		foreach ( $needles as $needle ) {
			if ( false === stripos( $src, $needle ) ) {
				continue;
			}
			$rel = (string) wp_parse_url( $src, PHP_URL_PATH );
			if ( 0 === strpos( $rel, $base . '/' ) && false === strpos( $rel, '..' ) ) {
				$path = WP_CONTENT_DIR . substr( $rel, strlen( $base ) );
				if ( is_file( $path ) && filesize( $path ) <= $max_bytes ) {
					wp_style_add_data( $handle, 'path', $path );
				}
			}
			break;
		}
	}
}

/** Font Awesome icons only appear where an editor put them in the page content. */
function yamaha_audio_page_uses_fa() {
	$post    = is_singular() ? get_queried_object() : null;
	$content = $post instanceof WP_Post ? (string) $post->post_content : '';
	return false !== strpos( $content, 'fa-' ) || false !== strpos( $content, '[icon' );
}
// ...and without its stylesheet there is no reason to open a connection to its CDN.
add_filter( 'wp_resource_hints', function ( $urls ) {
	if ( is_admin() || ! is_array( $urls ) || yamaha_audio_page_uses_fa() ) {
		return $urls;
	}
	return array_values( array_filter( $urls, function ( $url ) {
		$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : (string) $url;
		return false === stripos( $href, 'fontawesome.com' );
	} ) );
}, 99 );

function yamaha_audio_trim_assets() {
	if ( is_admin() || apply_filters( 'yamaha_audio_keep_all_assets', false ) ) {
		return;
	}
	$post    = is_singular() ? get_queried_object() : null;
	$content = $post instanceof WP_Post ? (string) $post->post_content : '';
	$styles  = array();
	$scripts = array();

	// Font Awesome (official plugin, from its CDN): the theme draws its own icons; only content that uses it needs it.
	if ( ! yamaha_audio_page_uses_fa() ) {
		$styles[]  = 'fontawesome.com/';
		$styles[]  = '/uploads/font-awesome/';
		$scripts[] = 'fontawesome.com/';
	}
	// YITH Wishlist's lightbox and select styles: only its own page uses them.
	if ( ! ( function_exists( 'yith_wcwl_is_wishlist_page' ) && yith_wcwl_is_wishlist_page() ) ) {
		$styles[] = 'prettyPhoto';
		$styles[] = 'selectBox';
	}
	// Contact Form 7: its stylesheet where a form is expected, its script only where one was built.
	if ( defined( 'WPCF7_VERSION' ) ) {
		$expects_form = has_shortcode( $content, 'contact-form-7' ) || is_page_template( 'page-contact.php' ) || is_page( 'contact' )
			|| false !== stripos( (string) yamaha_audio_opt( 'newsletter_shortcode' ), 'contact-form-7' ); // the footer newsletter can be a CF7 form.
		if ( ! $expects_form && ! did_action( 'wpcf7_contact_form' ) ) {
			$styles[] = '/plugins/contact-form-7/';
		}
		if ( doing_action( 'wp_footer' ) && ! did_action( 'wpcf7_contact_form' ) ) {
			$scripts[] = '/plugins/contact-form-7/';
			$scripts[] = 'google.com/recaptcha';
		}
	}
	// Extra Product Options and YITH Wishlist put their controls on product pages (the wishlist also on its
	// own page): a product list, the front page or the blog has no use for their stylesheets.
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || is_front_page() || is_home() || is_search() || is_404() ) ) {
		$styles[]  = '/woocommerce-tm-extra-product-options/';
		$scripts[] = '/woocommerce-tm-extra-product-options/';
		$styles[]  = '/yith-woocommerce-wishlist/';
	}
	yamaha_audio_drop_assets( wp_styles(), $styles, true );
	yamaha_audio_drop_assets( wp_scripts(), $scripts, false );
	if ( doing_action( 'wp_enqueue_scripts' ) ) {
		yamaha_audio_inline_small_styles( array( '/plugins/honeypot/' ) ); // 1 KB that hides the anti-spam field.
	}
}
add_action( 'wp_enqueue_scripts', 'yamaha_audio_trim_assets', 9999 );
add_action( 'wp_print_styles', 'yamaha_audio_trim_assets', 1000 ); // some plugins enqueue at the very last moment; after the theme lists plugin styles as its dependencies (999).
add_action( 'wp_footer', 'yamaha_audio_trim_assets', 1 );
add_action( 'wp_print_footer_scripts', 'yamaha_audio_trim_assets', 1 );

/**
 * Plugin scripts at the end of the page that only attach handlers once the page is ready are loaded with
 * "defer", in their original order. Printed without it the browser fetches them as page-blocking, and
 * PageSpeed counts them as such whenever they arrive before the first paint: on a phone the score swung
 * between about 65 and 90 from one run to the next. Cart, checkout and account pages are left untouched.
 */
function yamaha_audio_defer_scripts( $tag, $handle, $src ) {
	$paths = (array) ( yamaha_audio_polish_cfg()['defer'] ?? array() );
	if ( ! $paths || is_admin() || ! is_string( $tag ) || preg_match( '/<script\b[^>]*\s(?:defer|async|type=["\']module)/i', $tag ) ) {
		return $tag;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return $tag;
	}
	foreach ( $paths as $needle ) {
		if ( false !== stripos( (string) $src, $needle ) ) {
			return preg_replace( '/<script\b(?=[^>]*\ssrc=)/i', '<script defer', $tag, 1 );
		}
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'yamaha_audio_defer_scripts', 20, 3 );

/* =====================================================================================
 * Buying flow
 * ================================================================================== */

// County before locality: the locality suggestions depend on the county.
function yamaha_audio_state_before_city( $fields ) {
	if ( is_array( $fields ) ) {
		if ( isset( $fields['state'] ) && is_array( $fields['state'] ) ) {
			$fields['state']['priority'] = 65;
		}
		if ( isset( $fields['city'] ) && is_array( $fields['city'] ) ) {
			$fields['city']['priority'] = 70;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'yamaha_audio_state_before_city', 30 );
add_filter( 'woocommerce_get_country_locale_default', 'yamaha_audio_state_before_city', 30 );
add_filter( 'woocommerce_get_country_locale', function ( $locales ) {
	foreach ( (array) $locales as $country => $fields ) {
		$locales[ $country ]['state'] = array_merge( isset( $fields['state'] ) && is_array( $fields['state'] ) ? $fields['state'] : array(), array( 'priority' => 65 ) );
		$locales[ $country ]['city']  = array_merge( isset( $fields['city'] ) && is_array( $fields['city'] ) ? $fields['city'] : array(), array( 'priority' => 70 ) );
	}
	return $locales;
}, 30 );

// Classic cart: the shipping estimate only needs the county (zones go by county; no postcode anywhere).
add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_false' );
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_false' );

// "3.699 lei", not "3.699,00 lei": the same price looks the same on every page.
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

// The card gateway under a name customers recognise (only while it still has the plugin's default title).
add_filter( 'woocommerce_gateway_title', function ( $title, $id = '' ) {
	return 'NETOPIA Payments' === trim( (string) $title ) ? __( 'Card bancar online (NETOPIA)', 'yamaha-audio' ) : $title;
}, 20, 2 );

// Classic checkout: "Am citit și sunt de acord cu termeni și condiții site-ului web" → plain Romanian.
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', function ( $text ) {
	$page = function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0;
	if ( ! $page ) {
		return $text;
	}
	return sprintf(
		/* translators: %s: link to the terms page */
		__( 'Am citit și accept %s.', 'yamaha-audio' ),
		'<a href="' . esc_url( get_permalink( $page ) ) . '" class="woocommerce-terms-and-conditions-link" target="_blank">' . esc_html__( 'termenii și condițiile', 'yamaha-audio' ) . '</a>'
	);
}, 20 );

// Wishlist button texts left in English by the plugin's settings.
function yamaha_audio_wishlist_text( $value ) {
	$map = array(
		'add to wishlist'                         => __( 'Adaugă la favorite', 'yamaha-audio' ),
		'wishlist'                                => __( 'Adaugă la favorite', 'yamaha-audio' ),
		'browse wishlist'                         => __( 'Vezi favoritele', 'yamaha-audio' ),
		'product added!'                          => __( 'Adăugat la favorite', 'yamaha-audio' ),
		'the product is already in your wishlist!' => __( 'Este deja în lista ta de favorite', 'yamaha-audio' ),
		'remove from list'                        => __( 'Scoate din favorite', 'yamaha-audio' ),
	);
	$key = strtolower( trim( (string) $value ) );
	return isset( $map[ $key ] ) ? $map[ $key ] : $value;
}
foreach ( array( 'add_to_wishlist_text', 'browse_wishlist_text', 'product_added_text', 'already_in_wishlist_text', 'remove_from_wishlist_text' ) as $yamaha_audio_opt ) {
	add_filter( 'option_yith_wcwl_' . $yamaha_audio_opt, 'yamaha_audio_wishlist_text' );
}
unset( $yamaha_audio_hook, $yamaha_audio_opt );

// Plugin strings with a typo or a wrong word, fixed where they are printed.
add_filter( 'gettext', function ( $translation ) {
	if ( 'Reseteză opțiunile' === $translation ) {
		return 'Resetează opțiunile';
	}
	return $translation;
} );
// Block cart / checkout print "Save 7 lei" as "Salvează 7 lei" (save a file); it means "you save".
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) && wp_script_is( 'wp-i18n', 'registered' ) ) {
		wp_add_inline_script( 'wp-i18n', "window.wp&&wp.hooks&&wp.hooks.addFilter('i18n.gettext_woocommerce','hx/save',function(t,s){return s==='Save %s'?'Economisești %s':t;});" );
	}
}, 20 );
