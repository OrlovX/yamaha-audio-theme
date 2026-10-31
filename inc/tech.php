<?php
/**
 * Technology pages ("Tehnologii Yamaha"): posts in the `tehnologii` category that explain one Yamaha technology each.
 *
 * Each post carries:
 *   _hx_aliases  – one alias per line; every form the name takes in product texts (first line = canonical name)
 *   _hx_related  – slugs of related technology posts, one per line
 *   _hx_cats     – product_cat slugs where the technology is found, one per line
 *   _hx_faq      – JSON [ { "q": …, "a": … } ]
 *   _hx_guide    – "1" for overview guides (listed, but not used for term linking)
 *
 * The theme links the first mention of every technology in product descriptions and spec tables, shows a
 * "Tehnologii în acest produs" strip on product pages, and lists products that have the technology on its page.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

const YAMAHA_AUDIO_TECH_CAT = 'tehnologii';

/**
 * [ slug => [ id, title, url, aliases[], related[], cats[], guide ] ], cached for 12 h.
 */
function yamaha_audio_tech_index() {
	static $index = null;
	if ( null !== $index ) {
		return $index;
	}
	$index = get_transient( 'hx_tech_index' );
	if ( is_array( $index ) ) {
		return $index;
	}
	$index = array();
	$term  = get_term_by( 'slug', YAMAHA_AUDIO_TECH_CAT, 'category' );
	if ( $term ) {
		$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 200, 'category' => $term->term_id, 'orderby' => 'title', 'order' => 'ASC' ) );
		foreach ( $posts as $p ) {
			$lines = function ( $key ) use ( $p ) {
				return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) get_post_meta( $p->ID, $key, true ) ) ) ) );
			};
			$aliases = $lines( '_hx_aliases' );
			$index[ $p->post_name ] = array(
				'id'      => $p->ID,
				'title'   => get_the_title( $p ),
				'name'    => $aliases ? $aliases[0] : get_the_title( $p ),
				'url'     => get_permalink( $p ),
				'excerpt' => $p->post_excerpt,
				'aliases' => $aliases,
				'related' => $lines( '_hx_related' ),
				'cats'    => $lines( '_hx_cats' ),
				'guide'   => (bool) get_post_meta( $p->ID, '_hx_guide', true ),
			);
		}
	}
	set_transient( 'hx_tech_index', $index, 12 * HOUR_IN_SECONDS );
	return $index;
}
add_action( 'save_post_post', function () { delete_transient( 'hx_tech_index' ); } );
add_action( 'deleted_post', function () { delete_transient( 'hx_tech_index' ); } );

/**
 * Alias → slug map, longest aliases first (so "YPAO R.S.C." wins over "YPAO").
 */
function yamaha_audio_tech_alias_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array();
	foreach ( yamaha_audio_tech_index() as $slug => $t ) {
		if ( $t['guide'] ) {
			continue;
		}
		foreach ( $t['aliases'] as $a ) {
			if ( mb_strlen( $a ) >= 3 ) {
				$map[ $a ] = $slug;
			}
		}
	}
	uksort( $map, function ( $a, $b ) { return mb_strlen( $b ) - mb_strlen( $a ); } );
	return $map;
}

/**
 * Which technologies does a text mention? Returns slugs in order of first appearance.
 */
function yamaha_audio_tech_detect( $text ) {
	$text  = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$found = array();
	foreach ( yamaha_audio_tech_alias_map() as $alias => $slug ) {
		if ( isset( $found[ $slug ] ) ) {
			continue;
		}
		if ( preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $alias, '/' ) . '(?![\p{L}\p{N}])/iu', $text, $m, PREG_OFFSET_CAPTURE ) ) {
			$found[ $slug ] = $m[0][1];
		}
	}
	asort( $found );
	return array_keys( $found );
}

/**
 * Link the first mention of each technology in an HTML fragment. Text inside <a>, headings, scripts, styles and
 * attributes is left alone.
 */
function yamaha_audio_link_tech_terms( $html, $exclude_slug = '' ) {
	$map = yamaha_audio_tech_alias_map();
	if ( ! $map || ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	$index  = yamaha_audio_tech_index();
	$done   = array();
	$parts  = preg_split( '/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$skip   = 0; // depth inside a/h1-h6/script/style/button
	foreach ( $parts as $i => $part ) {
		if ( '' === $part ) {
			continue;
		}
		if ( '<' === $part[0] ) {
			if ( preg_match( '/^<(\/?)(a|h[1-6]|script|style|button|figcaption)\b/i', $part, $m ) ) {
				$skip = max( 0, $skip + ( $m[1] ? -1 : 1 ) );
			}
			continue;
		}
		if ( $skip > 0 || ! trim( $part ) ) {
			continue;
		}
		foreach ( $map as $alias => $slug ) {
			if ( isset( $done[ $slug ] ) || $slug === $exclude_slug ) {
				continue;
			}
			$pattern = '/(?<![\p{L}\p{N}])(' . preg_quote( $alias, '/' ) . ')(?![\p{L}\p{N}])/iu';
			$count   = 0;
			$url     = $index[ $slug ]['url'];
			$part    = preg_replace_callback( $pattern, function ( $mm ) use ( $url, $index, $slug ) {
				return '<a class="hx-techlink" href="' . esc_url( $url ) . '" title="' . esc_attr( sprintf( __( 'Ce este %s – Tehnologii Yamaha', 'yamaha-audio' ), $index[ $slug ]['name'] ) ) . '">' . $mm[1] . '</a>';
			}, $part, 1, $count );
			if ( $count ) {
				$done[ $slug ] = true;
			}
		}
		$parts[ $i ] = $part;
	}
	return implode( '', $parts );
}

/* ---- product pages: linked descriptions, linked specs, technology strip ---- */

// The theme renders the product description itself (yamaha_audio_single_sections) and passes it through this filter.
add_filter( 'yamaha_audio_product_description_html', 'yamaha_audio_link_tech_terms', 10, 1 );

/**
 * Technologies a product uses: name, short description, description, attribute values and tags.
 */
function yamaha_audio_product_techs( $product ) {
	if ( ! $product ) {
		return array();
	}
	$key   = 'hx_ptech_' . $product->get_id() . '_' . md5( (string) $product->get_date_modified() . count( yamaha_audio_tech_index() ) );
	$slugs = get_transient( $key );
	if ( is_array( $slugs ) ) {
		return $slugs;
	}
	$text = $product->get_name() . "\n" . $product->get_short_description() . "\n" . $product->get_description();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( is_object( $attribute ) && method_exists( $attribute, 'get_options' ) ) {
			$text .= "\n" . ( $attribute->is_taxonomy() ? implode( ' ', wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) ) : implode( ' ', (array) $attribute->get_options() ) );
		}
	}
	$slugs = yamaha_audio_tech_detect( $text );
	set_transient( $key, $slugs, 12 * HOUR_IN_SECONDS );
	return $slugs;
}

function yamaha_audio_single_tech_strip() {
	global $product;
	$slugs = yamaha_audio_product_techs( $product );
	if ( ! $slugs ) {
		return;
	}
	$index = yamaha_audio_tech_index();
	echo '<section class="hx-section hx-section--tight hx-single__tech"><h2 class="hx-section__title">' . esc_html__( 'Tehnologii Yamaha în acest produs', 'yamaha-audio' ) . '</h2><ul class="hx-techlist">';
	foreach ( array_slice( $slugs, 0, 8 ) as $slug ) {
		$t = $index[ $slug ];
		echo '<li><a class="hx-techcard" href="' . esc_url( $t['url'] ) . '"><strong>' . esc_html( $t['name'] ) . '</strong><span>' . esc_html( wp_trim_words( $t['excerpt'] ?: $t['title'], 16, '…' ) ) . '</span></a></li>';
	}
	echo '</ul></section>';
}

/* ---- technology post: products that have it, related, FAQ + schema ---- */

function yamaha_audio_is_tech_post( $post = null ) {
	$post = get_post( $post );
	return $post && 'post' === $post->post_type && has_category( YAMAHA_AUDIO_TECH_CAT, $post );
}

function yamaha_audio_tech_products( $slug, $limit = 8 ) {
	$index = yamaha_audio_tech_index();
	if ( empty( $index[ $slug ] ) || ! class_exists( 'WooCommerce' ) ) {
		return array();
	}
	$key = 'hx_techprod_' . $slug . '_' . $limit;
	$ids = get_transient( $key );
	if ( ! is_array( $ids ) ) {
		global $wpdb;
		$aliases = $index[ $slug ]['aliases'];
		$where   = array();
		foreach ( $aliases as $a ) {
			$like    = '%' . $wpdb->esc_like( $a ) . '%';
			$where[] = $wpdb->prepare( '(p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s)', $like, $like, $like );
		}
		$ids = array();
		if ( $where ) {
			$ids = array_map( 'intval', $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'product' AND p.post_status = 'publish' AND (" . implode( ' OR ', $where ) . ') ORDER BY p.menu_order ASC, p.post_date DESC LIMIT 60' ) );
		}
		// Prefer in-stock, visible products; keep the first $limit.
		$ids = array_values( array_filter( $ids, function ( $id ) {
			$p = wc_get_product( $id );
			return $p && $p->is_visible() && 'outofstock' !== $p->get_stock_status();
		} ) );
		set_transient( $key, $ids, 12 * HOUR_IN_SECONDS );
	}
	return array_slice( $ids, 0, $limit );
}
add_action( 'save_post_product', function () {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_hx\\_techprod\\_%' OR option_name LIKE '\\_transient\\_timeout\\_hx\\_techprod\\_%'" );
} );

function yamaha_audio_tech_faq( $post_id ) {
	$faq = json_decode( (string) get_post_meta( $post_id, '_hx_faq', true ), true );
	return is_array( $faq ) ? array_values( array_filter( $faq, function ( $f ) { return ! empty( $f['q'] ) && ! empty( $f['a'] ); } ) ) : array();
}

// FAQ schema for technology pages (Yoast graph when present, else a standalone block).
add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_singular( 'post' ) || ! yamaha_audio_is_tech_post() || ! is_array( $graph ) ) {
		return $graph;
	}
	$faq = yamaha_audio_tech_faq( get_the_ID() );
	if ( $faq ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => get_permalink() . '#faq',
			'mainEntity' => array_map( function ( $f ) {
				return array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
			}, $faq ),
		);
	}
	return $graph;
}, 98 );

// Technology posts and the overview page link to each other; link mentions in the post body too.
add_filter( 'the_content', function ( $content ) {
	if ( is_singular( 'post' ) && in_the_loop() && is_main_query() && yamaha_audio_is_tech_post() ) {
		return yamaha_audio_link_tech_terms( $content, get_post_field( 'post_name' ) );
	}
	return $content;
}, 30 );

/**
 * Extras printed after a technology post's body: FAQ, products, related technologies.
 */
function yamaha_audio_tech_post_extras() {
	$post_id = get_the_ID();
	$slug    = get_post_field( 'post_name', $post_id );
	$index   = yamaha_audio_tech_index();
	$faq     = yamaha_audio_tech_faq( $post_id );
	if ( $faq ) {
		echo '<section class="hx-techfaq" id="faq"><h2>' . esc_html__( 'Întrebări frecvente', 'yamaha-audio' ) . '</h2>';
		foreach ( $faq as $f ) {
			echo '<details class="hx-techfaq__item"><summary>' . esc_html( $f['q'] ) . '</summary><p>' . esc_html( $f['a'] ) . '</p></details>';
		}
		echo '</section>';
	}
	$ids = yamaha_audio_tech_products( $slug, 8 );
	if ( $ids && function_exists( 'yamaha_audio_product_card' ) ) {
		$name = isset( $index[ $slug ] ) ? $index[ $slug ]['name'] : get_the_title( $post_id );
		echo '<section class="hx-section hx-techproducts">';
		yamaha_audio_section_head( sprintf( __( 'Produse Yamaha cu %s', 'yamaha-audio' ), $name ), '', wc_get_page_permalink( 'shop' ), __( 'Tot magazinul', 'yamaha-audio' ) );
		echo '<div class="hx-grid hx-grid--4">';
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p ) {
				yamaha_audio_product_card( $p );
			}
		}
		echo '</div></section>';
	}
	$related = isset( $index[ $slug ] ) ? $index[ $slug ]['related'] : array();
	$related = array_values( array_filter( $related, function ( $r ) use ( $index ) { return isset( $index[ $r ] ); } ) );
	if ( $related ) {
		echo '<section class="hx-section hx-section--tight hx-techrelated"><h2 class="hx-section__title">' . esc_html__( 'Tehnologii înrudite', 'yamaha-audio' ) . '</h2><ul class="hx-techlist">';
		foreach ( array_slice( $related, 0, 6 ) as $r ) {
			$t = $index[ $r ];
			echo '<li><a class="hx-techcard" href="' . esc_url( $t['url'] ) . '"><strong>' . esc_html( $t['name'] ) . '</strong><span>' . esc_html( wp_trim_words( $t['excerpt'] ?: $t['title'], 16, '…' ) ) . '</span></a></li>';
		}
		echo '</ul></section>';
	}
	$page = get_page_by_path( YAMAHA_AUDIO_TECH_CAT );
	echo '<p class="hx-techback"><a class="hx-link" href="' . esc_url( $page ? get_permalink( $page ) : home_url( '/' . YAMAHA_AUDIO_TECH_CAT . '/' ) ) . '">← ' . esc_html__( 'Toate tehnologiile Yamaha', 'yamaha-audio' ) . '</a></p>';
}

// The category archive is the overview page.
add_action( 'template_redirect', function () {
	if ( is_category( YAMAHA_AUDIO_TECH_CAT ) ) {
		$page = get_page_by_path( YAMAHA_AUDIO_TECH_CAT );
		if ( $page && 'publish' === $page->post_status ) {
			wp_safe_redirect( get_permalink( $page ), 301 );
			exit;
		}
	}
} );

/**
 * Overview page groups: domain label => tech slugs (by their product categories).
 */
function yamaha_audio_tech_groups() {
	$groups = array(
		'receivere' => array( 'label' => __( 'Receivere AV și home cinema', 'yamaha-audio' ), 'cats' => array( 'receivere', 'receivere-surround' ), 'slugs' => array() ),
		'hifi'      => array( 'label' => __( 'Hi-Fi: amplificare, receivere stereo, surse', 'yamaha-audio' ), 'cats' => array( 'receivere-stereo', 'amplificare', 'amplificatoare', 'amplificatoare-integrate', 'amplificatoare-putere', 'amplificatoare-preamplificatoare', 'amplificatoare-streaming', 'surse', 'cd-player', 'streamere', 'pick-up' ), 'slugs' => array() ),
		'boxe'      => array( 'label' => __( 'Boxe și subwoofere', 'yamaha-audio' ), 'cats' => array( 'boxe-yamaha-experienta-traditie-eleganta', 'boxe-podea', 'boxe-raft', 'boxe-centru', 'boxe-active', 'boxe-tavan', 'boxe-exterior', 'boxe-subwoofer' ), 'slugs' => array() ),
		'soundbar'  => array( 'label' => __( 'Soundbar și sisteme wireless', 'yamaha-audio' ), 'cats' => array( 'boxe-soundbar', 'boxe-sisteme-surround', 'sisteme', 'pachete' ), 'slugs' => array() ),
		'casti'     => array( 'label' => __( 'Căști', 'yamaha-audio' ), 'cats' => array( 'casti', 'casti-over-ear', 'casti-in-ear' ), 'slugs' => array() ),
		'general'   => array( 'label' => __( 'Filozofie și ecosistem', 'yamaha-audio' ), 'cats' => array(), 'slugs' => array() ),
	);
	foreach ( yamaha_audio_tech_index() as $slug => $t ) {
		if ( $t['guide'] ) {
			continue;
		}
		$placed = false;
		foreach ( $groups as $k => &$g ) {
			if ( 'general' !== $k && array_intersect( $t['cats'], $g['cats'] ) ) {
				$g['slugs'][] = $slug;
				$placed       = true;
			}
		}
		unset( $g );
		if ( ! $placed ) {
			$groups['general']['slugs'][] = $slug;
		}
	}
	return array_filter( $groups, function ( $g ) { return ! empty( $g['slugs'] ); } );
}
