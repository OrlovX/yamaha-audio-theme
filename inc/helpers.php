<?php
/**
 * Small helpers used across templates.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme option with default (wraps get_theme_mod so defaults live in one place).
 */
function hifiexpert_opt( $key, $default = '' ) {
	$defaults = hifiexpert_option_defaults();
	$fallback = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : $default;
	return get_theme_mod( $key, $fallback );
}

/**
 * Inline SVG icon (stroke icons, inherit currentColor).
 */
function hifiexpert_icon( $name, $size = 20, $label = '' ) {
	$paths = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
		'heart'    => '<path d="M12 20.5l-7.5-7.6a4.6 4.6 0 0 1 6.5-6.5l1 1 1-1a4.6 4.6 0 0 1 6.5 6.5z"/>',
		'cart'     => '<path d="M6 7h12l1 13H5z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'     => '<path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11z"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'chevron'  => '<path d="M9 6l6 6-6 6"/>',
		'building' => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2M10 21v-3h4v3"/>',
		'truck'    => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
		'card'     => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
		'pin'      => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
		'check'    => '<path d="M5 12l4 4L19 7"/>',
		'expand'   => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
		'filter'   => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'laurel'   => '<path d="M12 4c-3 2-5 5-5 9 0 3 2 6 5 7 3-1 5-4 5-7 0-4-2-7-5-9z"/><path d="M12 6v14M7 10c-2-1-3-3-3-5 2 0 4 1 5 3M17 10c2-1 3-3 3-5-2 0-4 1-5 3M6 15c-2 0-3-1-4-3 2-1 4 0 5 1M18 15c2 0 3-1 4-3-2-1-4 0-5 1"/>',
	);
	if ( empty( $paths[ $name ] ) ) {
		return '';
	}
	$aria = $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"';
	return sprintf(
		'<svg%s class="hx-icon hx-icon--%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
		$aria,
		esc_attr( $name ),
		(int) $size,
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * Brand taxonomy in use on this site.
 */
function hifiexpert_brand_taxonomy() {
	static $tax = null;
	if ( null !== $tax ) {
		return $tax;
	}
	$candidates = array( 'product_brand', 'producator', 'pwb-brand', 'pa_brand', 'pa_producator' );
	// WooCommerce registers an (empty) product_brand taxonomy on its own; the real brands live in pa_producator.
	// Pick the first candidate that actually has terms assigned to products (cached 1h).
	$cached = get_transient( 'hx_brand_tax' );
	if ( is_string( $cached ) && taxonomy_exists( $cached ) ) {
		$tax = $cached;
		return $tax;
	}
	$first = '';
	foreach ( $candidates as $candidate ) {
		if ( ! taxonomy_exists( $candidate ) ) {
			continue;
		}
		$first = $first ?: $candidate;
		$n     = wp_count_terms( array( 'taxonomy' => $candidate, 'hide_empty' => true ) );
		if ( ! is_wp_error( $n ) && (int) $n > 0 ) {
			$tax = $candidate;
			set_transient( 'hx_brand_tax', $tax, HOUR_IN_SECONDS );
			return $tax;
		}
	}
	$tax = $first;
	return $tax;
}

/**
 * First brand term of a product (or null).
 */
function hifiexpert_product_brand( $product ) {
	$tax = hifiexpert_brand_taxonomy();
	if ( ! $tax || ! $product ) {
		return null;
	}
	$id    = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	$terms = get_the_terms( $id, $tax );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Format a lei amount the Romanian way: 17.999 lei.
 */
function hifiexpert_lei( $amount ) {
	$amount = (float) $amount;
	$has_cents = abs( $amount - round( $amount ) ) > 0.004;
	return number_format( $amount, $has_cents ? 2 : 0, ',', '.' ) . ' lei';
}

/**
 * Monthly installment (price / count), or 0 when below the threshold.
 */
function hifiexpert_installment( $price ) {
	if ( ! hifiexpert_opt( 'rates_enabled' ) ) {
		return 0;
	}
	$count = max( 2, (int) hifiexpert_opt( 'rates_count' ) );
	$min   = (float) hifiexpert_opt( 'rates_min_price' );
	$price = (float) $price;
	if ( $price < $min || $price <= 0 ) {
		return 0;
	}
	return (int) ceil( $price / $count );
}

/**
 * Discount percentage for a product on sale (variable: based on min prices).
 */
function hifiexpert_discount_percent( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return 0;
	}
	if ( $product->is_type( 'variable' ) ) {
		$regular = (float) $product->get_variation_regular_price( 'min' );
		$sale    = (float) $product->get_variation_sale_price( 'min' );
	} else {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
	}
	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return 0;
	}
	return (int) round( ( 1 - $sale / $regular ) * 100 );
}

/**
 * Current "active" price used for the installment line.
 */
function hifiexpert_active_price( $product ) {
	if ( ! $product ) {
		return 0;
	}
	if ( $product->is_type( 'variable' ) ) {
		return (float) $product->get_variation_price( 'min' );
	}
	return (float) $product->get_price();
}

/**
 * Products in the "music" categories (vinyl, CD…): sourced per order, so the stock story differs.
 */
function hifiexpert_is_music( $product ) {
	static $cache = array();
	if ( ! $product ) {
		return false;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$slugs = array_values( array_filter( array_map( 'sanitize_title', preg_split( '/[,\n]+/', (string) hifiexpert_opt( 'music_cats' ) ) ) ) );
	if ( ! $slugs ) {
		return $cache[ $id ] = false;
	}
	$terms = get_the_terms( $id, 'product_cat' );
	$is    = false;
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		if ( in_array( $t->slug, $slugs, true ) ) {
			$is = true;
			break;
		}
		foreach ( get_ancestors( $t->term_id, 'product_cat' ) as $aid ) {
			$a = get_term( $aid, 'product_cat' );
			if ( $a && ! is_wp_error( $a ) && in_array( $a->slug, $slugs, true ) ) {
				$is = true;
				break 2;
			}
		}
	}
	return $cache[ $id ] = $is;
}

/**
 * Music that is not in stock is asked about, not ordered blindly.
 */
function hifiexpert_is_inquiry_only( $product ) {
	return $product && hifiexpert_is_music( $product ) && 'ask' === hifiexpert_stock_state( $product )[0];
}

/**
 * WhatsApp link asking about a product's availability.
 */
function hifiexpert_inquiry_url( $product ) {
	return hifiexpert_whatsapp_url( sprintf( __( 'Bună! Puteți verifica disponibilitatea pentru „%s”?', 'hifiexpert' ), $product->get_name() ) . ' ' . $product->get_permalink() );
}

/**
 * Stock state → array( class, label ).
 */
function hifiexpert_stock_state( $product ) {
	if ( ! $product ) {
		return array( 'muted', '' );
	}
	if ( hifiexpert_is_music( $product ) ) {
		$in = $product->is_in_stock() && ! $product->is_on_backorder();
		if ( $product->is_type( 'variable' ) ) {
			$in = false;
			foreach ( $product->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child && $child->is_in_stock() && ! $child->is_on_backorder() ) {
					$in = true;
					break;
				}
			}
		}
		return $in ? array( 'ok', hifiexpert_opt( 'music_instock_text' ) ) : array( 'ask', hifiexpert_opt( 'music_inquire_text' ) );
	}
	if ( $product->is_type( 'variable' ) ) {
		$in = false;
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->is_in_stock() ) {
				$in = true;
				break;
			}
		}
		return $in ? array( 'ok', __( 'În stoc', 'hifiexpert' ) ) : array( 'warn', __( 'La comandă', 'hifiexpert' ) );
	}
	if ( $product->is_on_backorder() ) {
		return array( 'warn', __( 'La comandă', 'hifiexpert' ) );
	}
	if ( ! $product->is_in_stock() ) {
		return array( 'muted', __( 'Stoc epuizat', 'hifiexpert' ) );
	}
	$qty = $product->get_stock_quantity();
	if ( $product->managing_stock() && null !== $qty && $qty <= 2 ) {
		return array( 'warn', __( 'Ultimele bucăți', 'hifiexpert' ) );
	}
	return array( 'ok', __( 'În stoc', 'hifiexpert' ) );
}

/**
 * Opening hours as [ dow(1=Mon..7=Sun) => [open, close] ] in minutes since midnight.
 */
function hifiexpert_hours_table() {
	$parse = function ( $s ) {
		if ( ! preg_match( '/(\d{1,2})[:.](\d{2})\s*-\s*(\d{1,2})[:.](\d{2})/', (string) $s, $m ) ) {
			return null;
		}
		return array( (int) $m[1] * 60 + (int) $m[2], (int) $m[3] * 60 + (int) $m[4] );
	};
	$wd = $parse( hifiexpert_opt( 'hours_weekday' ) );
	$sa = $parse( hifiexpert_opt( 'hours_saturday' ) );
	$su = $parse( hifiexpert_opt( 'hours_sunday' ) );
	return array( 1 => $wd, 2 => $wd, 3 => $wd, 4 => $wd, 5 => $wd, 6 => $sa, 7 => $su );
}

/**
 * Open/closed state right now (site timezone): [ is_open, label ].
 */
function hifiexpert_open_state() {
	$table = hifiexpert_hours_table();
	$now   = current_datetime(); // site timezone
	$dow   = (int) $now->format( 'N' );
	$mins  = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	$fmt   = function ( $m ) { return sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 ); };
	$days  = array( 1 => 'luni', 2 => 'marți', 3 => 'miercuri', 4 => 'joi', 5 => 'vineri', 6 => 'sâmbătă', 7 => 'duminică' );

	$today = $table[ $dow ];
	if ( $today && $mins >= $today[0] && $mins < $today[1] ) {
		return array( true, sprintf( __( 'Deschis acum · până la %s', 'hifiexpert' ), $fmt( $today[1] ) ) );
	}
	if ( $today && $mins < $today[0] ) {
		return array( false, sprintf( __( 'Deschidem azi la %s', 'hifiexpert' ), $fmt( $today[0] ) ) );
	}
	for ( $i = 1; $i <= 7; $i++ ) {
		$d = ( $dow + $i - 1 ) % 7 + 1;
		if ( $table[ $d ] ) {
			$when = 1 === $i ? __( 'mâine', 'hifiexpert' ) : $days[ $d ];
			return array( false, sprintf( __( 'Ne vedem %1$s de la %2$s', 'hifiexpert' ), $when, $fmt( $table[ $d ][0] ) ) );
		}
	}
	return array( false, __( 'Închis', 'hifiexpert' ) );
}

/**
 * WhatsApp link from the configured number.
 */
function hifiexpert_whatsapp_url( $text = '' ) {
	$number = preg_replace( '/[^0-9]/', '', (string) hifiexpert_opt( 'whatsapp' ) );
	if ( ! $number ) {
		return '';
	}
	$url = 'https://wa.me/' . $number;
	if ( $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}
	return $url;
}

/**
 * Base query args shared by all homepage product pickers:
 * published, visible in catalog, in stock, not in excluded categories.
 */
function hifiexpert_home_base_args( $limit ) {
	$vis = wc_get_product_visibility_term_ids();
	$tax = array(
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array_values( array_filter( array( $vis['exclude-from-catalog'] ?? 0, $vis['outofstock'] ?? 0 ) ) ),
			'operator' => 'NOT IN',
		),
	);
	$exclude = array_filter( array_map( 'trim', explode( ',', (string) hifiexpert_opt( 'home_exclude_cats' ) ) ) );
	if ( $exclude ) {
		$tax[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $exclude,
			'operator' => 'NOT IN',
		);
	}
	return array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $limit,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
	);
}

/**
 * Products for the homepage grid, by source: sale | bestsellers | featured | newest.
 * "sale" and "bestsellers" both order by popularity, so the first card is the one people buy.
 */
function hifiexpert_home_products( $limit = 8, $source = null ) {
	static $cache = array();
	$source = $source ?: hifiexpert_opt( 'home_products_source' );
	$key    = $source . ':' . $limit;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$args = hifiexpert_home_base_args( $limit );

	switch ( $source ) {
		case 'sale':
			$ids = wc_get_product_ids_on_sale();
			if ( empty( $ids ) ) {
				return $cache[ $key ] = array();
			}
			$args['post__in'] = $ids;
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num date';
			$args['order']    = 'DESC';
			break;
		case 'featured':
			$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured' );
			$args['orderby']     = 'menu_order title';
			$args['order']       = 'ASC';
			break;
		case 'newest':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
		case 'bestsellers':
		default:
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
	}

	$ids = get_posts( $args );
	return $cache[ $key ] = array_values( array_filter( array_map( 'wc_get_product', $ids ) ) );
}

/**
 * Products of one brand (by brand-taxonomy slug), most popular first.
 */
function hifiexpert_brand_products( $slug, $limit = 4 ) {
	$tax = hifiexpert_brand_taxonomy();
	if ( ! $tax ) {
		return array();
	}
	$args = hifiexpert_home_base_args( $limit );
	$args['tax_query'][] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => $slug );
	$args['meta_key']    = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
	$args['orderby']     = 'meta_value_num date';
	$args['order']       = 'DESC';
	$ids = get_posts( $args );
	return array_values( array_filter( array_map( 'wc_get_product', $ids ) ) );
}

/**
 * Brand rows configured in the Customizer: [ [slug, title, term], … ].
 */
function hifiexpert_home_rows() {
	$tax  = hifiexpert_brand_taxonomy();
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) hifiexpert_opt( 'home_rows' ) ) as $line ) {
		$line = trim( $line );
		if ( ! $line ) {
			continue;
		}
		list( $slug, $title ) = array_pad( array_map( 'trim', explode( '|', $line, 2 ) ), 2, '' );
		$term = $tax ? get_term_by( 'slug', sanitize_title( $slug ), $tax ) : null;
		if ( ! $term ) {
			continue;
		}
		$rows[] = array( 'slug' => $term->slug, 'title' => $title ?: $term->name, 'term' => $term );
	}
	return $rows;
}

/**
 * Product for the homepage hero: configured ID, else the most popular on-sale product.
 */
function hifiexpert_hero_product() {
	static $product = false;
	if ( false !== $product ) {
		return $product;
	}
	$product = null;
	$id      = (int) hifiexpert_opt( 'hero_product' );
	if ( $id ) {
		$p = wc_get_product( $id );
		if ( $p && 'publish' === $p->get_status() ) {
			return $product = $p;
		}
	}
	foreach ( array( 'sale', 'bestsellers' ) as $source ) {
		$found = hifiexpert_home_products( 1, $source );
		if ( $found ) {
			return $product = $found[0];
		}
	}
	return $product;
}

/**
 * Description HTML with the imported junk removed: repeated name-only headings/paragraphs,
 * dead shortcodes, "/h4&gt;" fragments.
 */
/**
 * Shared pre-clean for imported product HTML (short + long descriptions):
 * hidden blocks, empty images, brand-logo/award templates, dead shortcodes, broken heading fragments.
 */
function hifiexpert_preclean_html( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return '';
	}
	$html = preg_replace( '/<(h[1-6]|p|div|span)[^>]*style="[^"]*display\s*:\s*none[^"]*"[^>]*>.*?<\/\1>/is', '', $html );
	$html = preg_replace( '/<div class="(brand-logo|short-description-awards)">.*?<\/div>/is', '', $html );
	$html = preg_replace( '/<img[^>]*src=""[^>]*>/i', '', $html );
	$html = preg_replace( '/\[[^\]]+\]/', '', $html );
	$html = preg_replace( '/\/h[1-6]&gt;|\/h[1-6]>/i', '', $html );
	$html = preg_replace( '/(&lt;|<)(?=\s*(<\/|\r|\n|$))/', '', $html ); // "Title<" fragments
	$html = preg_replace( '/<(p|div)>\s*(&nbsp;|\s)*<\/\1>/i', '', $html ); // empty paragraphs
	return hifiexpert_fix_heading_blocks( $html );
}

/**
 * Pasted descriptions sometimes leave a heading open around whole lists/paragraphs
 * ("<h2>Title<ul>…</ul></h2>"), which renders everything in heading size and weight.
 * Move block children out of headings, after the heading, in order.
 */
function hifiexpert_fix_heading_blocks( $html ) {
	if ( ! class_exists( 'DOMDocument' ) || ! preg_match( '/<h[1-6][\s>]/i', $html ) ) {
		return $html;
	}
	$doc = new DOMDocument();
	libxml_use_internal_errors( true );
	$ok = $doc->loadHTML( '<?xml encoding="utf-8" ?><div id="hxroot">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	if ( ! $ok ) {
		return $html;
	}
	$blocks = array( 'ul', 'ol', 'p', 'div', 'table', 'blockquote', 'figure', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) as $tag ) {
		$headings = iterator_to_array( $doc->getElementsByTagName( $tag ) );
		foreach ( $headings as $heading ) {
			if ( ! $heading->parentNode ) {
				continue;
			}
			$after = $heading;
			foreach ( iterator_to_array( $heading->childNodes ) as $child ) {
				if ( XML_ELEMENT_NODE === $child->nodeType && in_array( strtolower( $child->nodeName ), $blocks, true ) ) {
					$heading->removeChild( $child );
					if ( $after->nextSibling ) {
						$heading->parentNode->insertBefore( $child, $after->nextSibling );
					} else {
						$heading->parentNode->appendChild( $child );
					}
					$after = $child;
				}
			}
			if ( '' === trim( $heading->textContent ) && ! $heading->getElementsByTagName( 'img' )->length ) {
				$heading->parentNode->removeChild( $heading );
			}
		}
	}
	$root = $doc->getElementById( 'hxroot' );
	if ( ! $root ) {
		return $html;
	}
	$out = '';
	foreach ( $root->childNodes as $node ) {
		$out .= $doc->saveHTML( $node );
	}
	return preg_replace( '/<(p|div)>\s*(&nbsp;|\s)*<\/\1>/i', '', $out );
}

/**
 * Is this text just the product name (or a trivial variant of it)?
 */
function hifiexpert_is_name_like( $text, $product ) {
	$t = trim( preg_replace( '/\s+/', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
	$n = trim( preg_replace( '/\s+/', ' ', html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ) ) );
	if ( '' === $t ) {
		return true;
	}
	$norm = function ( $s ) { return mb_strtolower( preg_replace( '/[^\p{L}\p{N}]+/u', '', $s ) ); };
	$tn   = $norm( $t );
	$nn   = $norm( $n );
	if ( $tn === $nn || ( mb_strlen( $tn ) < mb_strlen( $nn ) + 20 && ( false !== mb_strpos( $nn, $tn ) || false !== mb_strpos( $tn, $nn ) ) ) ) {
		return true;
	}
	// Name repeated several times in one block ("X X X")
	if ( mb_strlen( $nn ) > 8 && substr_count( $tn, $nn ) >= 2 && mb_strlen( $tn ) < mb_strlen( $nn ) * 4 ) {
		return true;
	}
	// Mostly the same words as the name (e.g. the name without the brand): a label, not a sentence.
	$tw = array_filter( preg_split( '/[^\p{L}\p{N}]+/u', mb_strtolower( $t ) ) );
	$nw = array_filter( preg_split( '/[^\p{L}\p{N}]+/u', mb_strtolower( $n ) ) );
	if ( $tw && count( $tw ) <= 12 ) {
		$hits = count( array_intersect( $tw, $nw ) );
		if ( $hits / count( $tw ) >= 0.7 ) {
			return true;
		}
	}
	if ( false !== mb_stripos( $t, 'Hi-Fi Expert' ) && mb_strlen( $t ) < 40 ) {
		return true;
	}
	return false;
}

function hifiexpert_clean_description( $product ) {
	$html = hifiexpert_preclean_html( $product->get_description() );
	if ( ! $html ) {
		return '';
	}
	// Blocks whose text is only the product name (or a short variant of it).
	$html = preg_replace_callback(
		'/<(h[1-6]|p|div|strong)[^>]*>(.*?)<\/\1>/is',
		function ( $m ) use ( $product ) {
			return hifiexpert_is_name_like( wp_strip_all_tags( $m[2] ), $product ) && ! preg_match( '/<img/i', $m[2] ) ? '' : $m[0];
		},
		$html
	);
	// Demote any h1 to h2 (the page already has its h1) and drop leading <br>s.
	$html = preg_replace( '/<(\/?)h1\b/i', '<$1h2', $html );
	$html = preg_replace( '/^(\s*<br\s*\/?>)+/i', '', trim( $html ) );
	return trim( wp_strip_all_tags( $html ) ) === '' ? '' : $html;
}

/**
 * Spec rows from product attributes: visible, non-brand, short values. [ [label, value], … ]
 */
function hifiexpert_spec_rows( $product ) {
	$skip = array( 'pa_producator', 'pa_disponibil-in-showroom', 'pa_artist', 'pa_gen-muzical', 'pa_casa-discuri' );
	$rows = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible() ) {
			continue;
		}
		$name = $attribute->get_name();
		if ( in_array( $name, $skip, true ) ) {
			continue;
		}
		if ( $attribute->is_taxonomy() ) {
			$values = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) );
		} else {
			$values = array_map( 'trim', $attribute->get_options() );
		}
		$values = array_values( array_filter( array_map( function ( $v ) { return trim( html_entity_decode( wp_strip_all_tags( $v ), ENT_QUOTES, 'UTF-8' ) ); }, $values ) ) );
		if ( ! $values ) {
			continue;
		}
		$value = implode( ' · ', $values );
		if ( mb_strlen( $value ) > 160 ) {
			$value = wp_trim_words( $value, 24, '…' );
		}
		$label = wc_attribute_label( $name, $product );
		$label = ucfirst( mb_strtolower( $label ) ) === $label ? $label : $label;
		$rows[] = array( $label, $value );
	}
	return $rows;
}

/**
 * One-sentence lead for a product: first real sentence of the (short) description.
 */
function hifiexpert_product_lead( $product, $max_words = 32 ) {
	$name   = trim( $product->get_name() );
	$pieces = array();
	foreach ( array( $product->get_short_description(), $product->get_description() ) as $html ) {
		if ( ! $html ) {
			continue;
		}
		$html = hifiexpert_preclean_html( strip_shortcodes( $html ) );
		$html = preg_replace( '/\/?h[1-6]&gt;|\/h[1-6]>/i', '</p><p>', $html ); // broken heading fragments act as block breaks
		// Split on block boundaries so headings that just repeat the product name can be dropped.
		$blocks = preg_split( '/<\/?(p|h[1-6]|li|div|br)[^>]*>/i', $html );
		foreach ( $blocks as $b ) {
			$t = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $b ), ENT_QUOTES, 'UTF-8' ) ) );
			$t = trim( $t, " \t\n\r\0\x0B-–·:" );
			if ( mb_strlen( $t ) < 30 ) {
				continue; // too short to be a sentence (names, labels, "h4>" junk)
			}
			if ( hifiexpert_is_name_like( $t, $product ) ) {
				continue; // the product name again
			}
			// Model number + a few words (e.g. "Amplificator integrat A-S701 Hi-Fi Expert Yamaha Audio"): a label, not a sentence.
			if ( ! preg_match( '/[.!?,;:]/u', $t ) && str_word_count( $t ) <= 9 ) {
				continue;
			}
			if ( preg_match( '/^[A-ZĂÂÎȘȚ0-9][^.!?]{0,60}$/u', $t ) && mb_strlen( $t ) < 60 ) {
				continue; // a bare heading
			}
			$pieces[] = $t;
			if ( count( $pieces ) >= 2 ) {
				break 2;
			}
		}
	}
	if ( ! $pieces ) {
		return '';
	}
	$text = implode( ' ', $pieces );
	if ( preg_match( '/^(.{40,220}?[.!?])(\s|$)/u', $text, $m ) ) {
		return $m[1];
	}
	return wp_trim_words( $text, $max_words, '…' );
}

/**
 * Top-level product categories for the homepage tiles.
 */
function hifiexpert_home_categories( $limit = 6 ) {
	$exclude = array_filter( array_map( 'trim', explode( ',', (string) hifiexpert_opt( 'home_categories_exclude' ) ) ) );
	$terms   = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$terms = array_values( array_filter( $terms, function ( $t ) use ( $exclude ) {
		return ! in_array( $t->slug, $exclude, true );
	} ) );
	return array_slice( $terms, 0, (int) $limit );
}

/**
 * Image for a category tile: the category's own thumbnail, else the best-selling in-stock
 * product's image in that category.
 */
function hifiexpert_category_image_id( $term ) {
	$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumb ) {
		return $thumb;
	}
	$cache_key = 'hx_cat_img_' . $term->term_id;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$img = 0;
	foreach ( $ids as $pid ) {
		$t = (int) get_post_thumbnail_id( $pid );
		if ( $t ) {
			$img = $t;
			break;
		}
	}
	set_transient( $cache_key, $img, 6 * HOUR_IN_SECONDS );
	return $img;
}

/**
 * Child category names, comma separated, for a tile's subtitle.
 */
function hifiexpert_category_children_label( $term, $max = 3 ) {
	$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true, 'number' => $max, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( is_wp_error( $children ) || empty( $children ) ) {
		return $term->description ? wp_strip_all_tags( $term->description ) : '';
	}
	return implode( ' · ', wp_list_pluck( $children, 'name' ) );
}

/**
 * Brand slugs hidden from the Branduri page / strips (Customizer).
 */
function hifiexpert_hidden_brand_slugs() {
	return array_values( array_filter( array_map( 'sanitize_title', preg_split( '/[,\n]+/', (string) hifiexpert_opt( 'brands_hidden' ) ) ) ) );
}

/**
 * Banner images the old (Flatsome) brands page used, by brand slug. Cached a day.
 * An image used for two brands is kept only for the first one (the old page had a copy/paste slip).
 */
function hifiexpert_legacy_brand_banners() {
	$legacy = get_transient( 'hx_brand_banners_v4' );
	if ( false !== $legacy && is_array( $legacy ) ) {
		return $legacy;
	}
	$legacy = array();
	$page   = get_page_by_path( 'brands' ) ?: get_page_by_path( 'branduri' );
	if ( $page ) {
		$c = str_replace( array( '&#8221;', '&#8243;', '&#8220;', '”', '″', '“' ), '"', (string) $page->post_content );
		if ( preg_match_all( '/\[ux_banner[^\]]*?bg="(\d+)"[^\]]*\]([\s\S]*?)\[\/ux_banner\]/', $c, $m, PREG_SET_ORDER ) ) {
			$used = array();
			foreach ( $m as $row ) {
				$id = (int) $row[1];
				if ( isset( $used[ $id ] ) || ! preg_match( '#/producator/([a-z0-9\-]+)#i', $row[2], $lm ) ) {
					continue;
				}
				$slug = strtolower( $lm[1] );
				if ( ! isset( $legacy[ $slug ] ) ) {
					$legacy[ $slug ] = $id;
					$used[ $id ]     = true;
				}
			}
		}
		// A banner whose file name carries ANOTHER brand's name (the old page had a WiiM tile with a ZenSati photo) is dropped.
		$tax   = hifiexpert_brand_taxonomy();
		$slugs = $tax ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'slugs' ) ) : array();
		$slugs = is_wp_error( $slugs ) ? array_keys( $legacy ) : array_unique( array_merge( (array) $slugs, array_keys( $legacy ) ) );
		foreach ( $legacy as $slug => $id ) {
			$file = strtolower( wp_basename( (string) get_attached_file( $id ) ) );
			$file = str_replace( array( '-', '_', ' ' ), '', $file );
			$own  = str_replace( '-', '', $slug );
			if ( ! $file || false !== strpos( $file, $own ) ) {
				continue;
			}
			foreach ( $slugs as $other ) {
				$o = str_replace( '-', '', $other );
				if ( $other !== $slug && strlen( $o ) >= 4 && false !== strpos( $file, $o ) ) {
					unset( $legacy[ $slug ] );
					break;
				}
			}
		}
	}
	set_transient( 'hx_brand_banners_v4', $legacy, DAY_IN_SECONDS );
	return $legacy;
}

/**
 * Image for a brand tile: term meta `hx_brand_image` (set on the brand edit screen),
 * else the old page's banner, else the best-selling product of that brand.
 * $kind receives 'photo' (lifestyle banner) or 'product' (cut-out product shot on paper).
 */
function hifiexpert_brand_image_id( $term, &$kind = null ) {
	$kind = 'photo';
	$own  = (int) get_term_meta( $term->term_id, 'hx_brand_image', true );
	if ( $own ) {
		return $own;
	}
	$legacy = hifiexpert_legacy_brand_banners();
	if ( ! empty( $legacy[ $term->slug ] ) && wp_attachment_is_image( $legacy[ $term->slug ] ) ) {
		return (int) $legacy[ $term->slug ];
	}
	$kind      = 'product';
	$cache_key = 'hx_brand_img_' . $term->term_id;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$img = 0;
	foreach ( hifiexpert_brand_products( $term->slug, 6 ) as $p ) {
		$t = (int) $p->get_image_id();
		if ( $t ) {
			$img = $t;
			break;
		}
	}
	set_transient( $cache_key, $img, 6 * HOUR_IN_SECONDS );
	return $img;
}

/**
 * Brand terms for the audiophile band / brand strip.
 */
function hifiexpert_brand_terms( $slugs = '' ) {
	$tax = hifiexpert_brand_taxonomy();
	if ( ! $tax ) {
		return array();
	}
	$args = array( 'taxonomy' => $tax, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 12 );
	$slugs = array_filter( array_map( 'trim', explode( ',', (string) $slugs ) ) );
	if ( $slugs ) {
		$args['slug']    = $slugs;
		$args['orderby'] = 'include';
		unset( $args['number'] );
	}
	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$hidden = hifiexpert_hidden_brand_slugs();
	if ( $hidden && ! $slugs ) {
		$terms = array_values( array_filter( $terms, function ( $t ) use ( $hidden ) {
			return ! in_array( $t->slug, $hidden, true );
		} ) );
	}
	if ( $slugs ) {
		// keep the configured order
		usort( $terms, function ( $a, $b ) use ( $slugs ) {
			return array_search( $a->slug, $slugs, true ) <=> array_search( $b->slug, $slugs, true );
		} );
	}
	return $terms;
}

/**
 * Logo of a brand (attachment ID): term meta `hx_brand_logo` (brand edit screen), else the best
 * "logo" file in the media library whose name carries the brand (cached a day). 0 when none.
 */
function hifiexpert_brand_logo_id( $term ) {
	$own = (int) get_term_meta( $term->term_id, 'hx_brand_logo', true );
	if ( $own ) {
		return $own;
	}
	$key    = 'hx_brand_logo2_' . $term->term_id;
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	global $wpdb;
	$hints = array( 'vitus' => 'VA_Master-Logo', 'luxman' => 'Luxman.png', 'sonus-faber' => 'Sonus-faber-Logo', 'mcintosh' => 'mcIntosh-logo-White' );
	$name  = preg_replace( '/\s+(audio|acoustics?|design|hifi)$/i', '', $term->name );
	$needle = isset( $hints[ $term->slug ] ) ? $hints[ $term->slug ] : $name;
	$like  = '%' . $wpdb->esc_like( $needle ) . '%';
	$rows  = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.ID, p.post_title, p.post_mime_type, m.meta_value AS file
		 FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attached_file'
		 WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%%' AND ( p.post_title LIKE %s OR m.meta_value LIKE %s )
		 LIMIT 60",
		$like,
		$like
	) );
	$best  = 0;
	$score = -99;
	foreach ( (array) $rows as $r ) {
		$f = strtolower( wp_basename( (string) $r->file ) . ' ' . $r->post_title );
		$s = 0;
		if ( false !== strpos( $f, 'logo' ) ) {
			$s += 4;
		} elseif ( ! isset( $hints[ $term->slug ] ) && 'image/svg+xml' !== $r->post_mime_type ) {
			continue; // without a hint, only files called "…logo…" (or SVGs) qualify
		}
		if ( 'image/svg+xml' === $r->post_mime_type ) {
			$s += 3;
		} elseif ( 'image/png' === $r->post_mime_type || 'image/webp' === $r->post_mime_type ) {
			$s += 2;
		}
		if ( false !== strpos( $f, 'white' ) || false !== strpos( $f, 'weiss' ) ) {
			$s += 1;
		}
		if ( preg_match( '/-\d+x\d+\./', $f ) ) {
			$s -= 2; // a generated size, not the original
		}
		if ( $s > $score ) {
			$score = $s;
			$best  = (int) $r->ID;
		}
	}
	set_transient( $key, $best, DAY_IN_SECONDS );
	return $best;
}

/**
 * True when an image has no transparency (JPEG, or PNG/WebP without any transparent pixel), so it
 * must be blended rather than recoloured. Cached in attachment meta.
 */
function hifiexpert_image_is_flat( $id ) {
	$cached = get_post_meta( $id, '_hx_flat', true );
	if ( '' !== $cached ) {
		return '1' === $cached;
	}
	$mime = get_post_mime_type( $id );
	$flat = false;
	if ( 'image/jpeg' === $mime ) {
		$flat = true;
	} elseif ( in_array( $mime, array( 'image/png', 'image/webp', 'image/gif' ), true ) && function_exists( 'imagecreatefromstring' ) ) {
		$src  = image_get_intermediate_size( $id, 'thumbnail' );
		$path = $src && ! empty( $src['path'] ) ? path_join( wp_upload_dir()['basedir'], $src['path'] ) : get_attached_file( $id );
		$data = $path && file_exists( $path ) ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$im   = $data ? @imagecreatefromstring( $data ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $im ) {
			$w = imagesx( $im );
			$h = imagesy( $im );
			$transparent = false;
			for ( $y = 0; $y < 24 && ! $transparent; $y++ ) {
				for ( $x = 0; $x < 24; $x++ ) {
					$px = imagecolorat( $im, (int) ( $x * ( $w - 1 ) / 23 ), (int) ( $y * ( $h - 1 ) / 23 ) );
					if ( ( ( $px >> 24 ) & 0x7F ) > 8 ) {
						$transparent = true;
						break;
					}
				}
			}
			imagedestroy( $im );
			$flat = ! $transparent;
		}
	}
	update_post_meta( $id, '_hx_flat', $flat ? '1' : '0' );
	return $flat;
}

/**
 * <img> for a brand logo rendered in white on dark (any source colour), or '' when there is none.
 */
function hifiexpert_brand_logo_img( $term, $height = 36 ) {
	$id = hifiexpert_brand_logo_id( $term );
	if ( ! $id ) {
		return '';
	}
	$flat = hifiexpert_image_is_flat( $id );
	if ( 'image/svg+xml' === get_post_mime_type( $id ) ) {
		$src = array( wp_get_attachment_url( $id ), 0, 0 );
	} else {
		$src = wp_get_attachment_image_src( $id, 'large' );
	}
	if ( ! $src || empty( $src[0] ) ) {
		return '';
	}
	// Square-ish marks (Marten, Vitus) would be tiny at wordmark height: give them more.
	$tall = ! empty( $src[1] ) && ! empty( $src[2] ) && ( $src[1] / $src[2] ) < 1.7;
	if ( $tall ) {
		$height = (int) round( $height * 1.7 );
	}
	$w = ! empty( $src[1] ) && ! empty( $src[2] ) ? round( $height * $src[1] / $src[2] ) : '';
	return sprintf(
		'<img class="hx-logomono%s%s" src="%s" alt="%s" loading="lazy" decoding="async" height="%d"%s style="--h:%dpx">',
		$flat ? ' is-flat' : '',
		$tall ? ' is-tall' : '',
		esc_url( $src[0] ),
		esc_attr( $term->name ),
		(int) $height,
		$w ? ' width="' . (int) $w . '"' : '',
		(int) $height
	);
}

/**
 * The active sale campaign (Black Edition, Winter Sales…) or null.
 * Enabled in the Customizer, optionally fenced by start/end dates (site timezone).
 */
function hifiexpert_campaign() {
	static $camp = false;
	if ( false !== $camp ) {
		return $camp;
	}
	$camp = null;
	if ( ! hifiexpert_opt( 'camp_enabled' ) ) {
		return $camp;
	}
	$tz    = wp_timezone();
	$now   = current_datetime()->getTimestamp();
	$start = trim( (string) hifiexpert_opt( 'camp_start' ) );
	$end   = trim( (string) hifiexpert_opt( 'camp_end' ) );
	$start_ts = $start ? ( date_create( $start, $tz ) ? date_create( $start, $tz )->getTimestamp() : 0 ) : 0;
	$end_ts   = $end ? ( date_create( $end, $tz ) ? date_create( $end, $tz )->getTimestamp() : 0 ) : 0;
	if ( $end_ts && ! preg_match( '/\d{1,2}:\d{2}/', $end ) ) {
		$end_ts += DAY_IN_SECONDS - 1; // a bare date means "until the end of that day"
	}
	if ( ( $start_ts && $now < $start_ts ) || ( $end_ts && $now > $end_ts ) ) {
		return $camp;
	}
	$style = (string) hifiexpert_opt( 'camp_style' );
	$camp  = array(
		'name'    => (string) hifiexpert_opt( 'camp_name' ) ?: 'Sale',
		'tagline' => (string) hifiexpert_opt( 'camp_tagline' ),
		'style'   => in_array( $style, array( 'black', 'winter', 'red' ), true ) ? $style : 'black',
		'badge'   => (string) hifiexpert_opt( 'camp_badge' ) ?: (string) hifiexpert_opt( 'camp_name' ),
		'end_ts'  => $end_ts,
		'end_txt' => $end_ts ? wp_date( 'j F', $end_ts ) : '',
		'url'     => (string) hifiexpert_opt( 'camp_url' ) ?: ( get_page_by_path( 'promotii' ) ? get_permalink( get_page_by_path( 'promotii' ) ) : ( class_exists( 'WooCommerce' ) ? add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ) : home_url( '/' ) ) ),
	);
	return $camp;
}

/**
 * Is this product part of the campaign? (any product on sale while a campaign is active)
 */
function hifiexpert_in_campaign( $product ) {
	return $product && hifiexpert_campaign() && $product->is_on_sale();
}
