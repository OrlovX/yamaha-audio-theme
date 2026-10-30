<?php
/**
 * Automatic primary navigation for a single-brand shop: built from the product categories
 * (top level → mega panel with the sub-categories), plus a few fixed links (Promoții, Contact).
 * Customizer: `nav_auto` on/off (off = the WordPress menu assigned to "Meniu principal" is used),
 * `nav_links` = fixed links, one per line, "Label|URL".
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category tree for the menu: [ [term, children => [ [term, children => [...]], … ]], … ] (cached per request).
 * Top level in WooCommerce's category order; only categories with products; the Customizer exclusions and
 * "Uncategorized" left out.
 */
function yamaha_audio_nav_tree() {
	static $tree = null;
	if ( null !== $tree ) {
		return $tree;
	}
	$tree = array();
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $tree;
	}
	$exclude = array_filter( array_map( 'trim', explode( ',', (string) yamaha_audio_opt( 'home_categories_exclude' ) ) ) );
	$exclude[] = 'uncategorized';
	$all = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	if ( is_wp_error( $all ) ) {
		return $tree;
	}
	$by_parent = array();
	foreach ( $all as $t ) {
		if ( in_array( $t->slug, $exclude, true ) ) {
			continue;
		}
		$by_parent[ (int) $t->parent ][] = $t;
	}
	$build = function ( $parent_id ) use ( &$build, &$by_parent ) {
		$out = array();
		foreach ( $by_parent[ $parent_id ] ?? array() as $t ) {
			$out[] = array( 'term' => $t, 'children' => $build( (int) $t->term_id ) );
		}
		return $out;
	};
	$tree = $build( 0 );
	return $tree;
}

/**
 * Fixed links after the categories: [ [label, url, sale], … ] from the Customizer ("Label|URL" per line).
 * A relative URL is resolved against the home URL; a line whose label looks like Promoții/Oferte gets the sale colour.
 */
function yamaha_audio_nav_links() {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) yamaha_audio_opt( 'nav_links' ) ) as $line ) {
		$line = trim( $line );
		if ( ! $line ) {
			continue;
		}
		list( $label, $url ) = array_pad( array_map( 'trim', explode( '|', $line, 2 ) ), 2, '' );
		if ( ! $label ) {
			continue;
		}
		if ( ! $url ) {
			$page = get_page_by_path( sanitize_title( $label ) );
			$url  = $page ? get_permalink( $page ) : home_url( '/' . sanitize_title( $label ) . '/' );
		} elseif ( 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		}
		$out[] = array( 'label' => $label, 'url' => $url, 'sale' => (bool) preg_match( '/promo|ofert|reduc|sale/i', $label . ' ' . $url ) );
	}
	return $out;
}

/**
 * Is the current request inside this category (archive of it or of a descendant, or a product in it)?
 */
function yamaha_audio_nav_is_current( $term ) {
	if ( is_tax( 'product_cat' ) ) {
		$q = get_queried_object();
		return $q && ( (int) $q->term_id === (int) $term->term_id || term_is_ancestor_of( $term, $q, 'product_cat' ) );
	}
	if ( is_singular( 'product' ) ) {
		return has_term( (int) $term->term_id, 'product_cat', get_queried_object_id() ) || (bool) array_filter( (array) get_the_terms( get_queried_object_id(), 'product_cat' ), function ( $t ) use ( $term ) { return $t && term_is_ancestor_of( $term, $t, 'product_cat' ); } );
	}
	return false;
}

/**
 * Desktop: the category bar (<ul class="hx-nav__list">) with mega panels, then the fixed links group.
 * Markup matches the mega-menu walker so CSS/JS are shared.
 */
function yamaha_audio_nav_auto_desktop() {
	$tree = yamaha_audio_nav_tree();
	echo '<ul class="hx-nav__list">';
	foreach ( $tree as $node ) {
		$t        = $node['term'];
		$kids     = $node['children'];
		$current  = yamaha_audio_nav_is_current( $t );
		$has_mega = ! empty( $kids );
		printf(
			'<li class="hx-nav__item%s%s"><a class="hx-nav__link" href="%s"%s>%s</a>',
			$has_mega ? ' has-mega' : '',
			$current ? ' is-current' : '',
			esc_url( get_term_link( $t ) ),
			$has_mega ? ' aria-haspopup="true" aria-expanded="false"' : '',
			esc_html( $t->name )
		);
		if ( $has_mega ) {
			// Columns: children that have their own children become headed columns; leaf children are grouped
			// under the top-level name in one column.
			$cols   = array();
			$leaves = array();
			foreach ( $kids as $k ) {
				if ( $k['children'] ) {
					$cols[] = $k;
				} else {
					$leaves[] = $k;
				}
			}
			if ( $leaves ) {
				array_unshift( $cols, array( 'term' => $t, 'children' => $leaves, 'is_self' => true ) );
			}
			echo '<div class="hx-mega" aria-hidden="true"><div class="hx-mega__inner"><ul class="hx-mega__cols">';
			foreach ( $cols as $col ) {
				$ct    = $col['term'];
				printf( '<li class="hx-mega__col"><a class="hx-mega__head" href="%s"><span>%s</span></a><ul class="hx-mega__links">', esc_url( get_term_link( $ct ) ), esc_html( empty( $col['is_self'] ) ? $ct->name : sprintf( __( 'Toate: %s', 'yamaha-audio' ), $ct->name ) ) );
				foreach ( $col['children'] as $leaf ) {
					printf( '<li><a class="hx-mega__link" href="%s">%s <small>%d</small></a></li>', esc_url( get_term_link( $leaf['term'] ) ), esc_html( $leaf['term']->name ), (int) $leaf['term']->count );
				}
				echo '</ul></li>';
			}
			echo '</ul></div></div>';
		}
		echo '</li>';
	}
	echo '</ul>';

	$links = yamaha_audio_nav_links();
	if ( $links ) {
		echo '<ul class="hx-nav__list hx-nav__extra" id="hx-nav-extra">';
		foreach ( $links as $l ) {
			printf( '<li class="hx-nav__item hx-nav__item--extra"><a class="hx-nav__link%s" href="%s">%s</a></li>', $l['sale'] ? ' hx-sale' : '', esc_url( $l['url'] ), esc_html( $l['label'] ) );
		}
		echo '</ul>';
	}
}

/**
 * Off-canvas (phones): nested list; `menu-item-has-children` makes the accordion JS attach its toggles.
 */
function yamaha_audio_nav_auto_mobile() {
	$render = function ( $nodes, $depth ) use ( &$render ) {
		foreach ( $nodes as $node ) {
			$t    = $node['term'];
			$kids = $node['children'];
			printf( '<li class="menu-item%s%s"><a href="%s">%s</a>', $kids ? ' menu-item-has-children' : '', yamaha_audio_nav_is_current( $t ) ? ' current-menu-item' : '', esc_url( get_term_link( $t ) ), esc_html( $t->name ) );
			if ( $kids ) {
				echo '<ul class="sub-menu">';
				printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( get_term_link( $t ) ), esc_html( sprintf( __( 'Toate: %s', 'yamaha-audio' ), $t->name ) ) );
				$render( $kids, $depth + 1 );
				echo '</ul>';
			}
			echo '</li>';
		}
	};
	echo '<ul class="hx-mnav__list">';
	$render( yamaha_audio_nav_tree(), 0 );
	foreach ( yamaha_audio_nav_links() as $l ) {
		printf( '<li class="menu-item hx-extra%s"><a href="%s">%s</a></li>', $l['sale'] ? ' hx-sale' : '', esc_url( $l['url'] ), esc_html( $l['label'] ) );
	}
	echo '</ul>';
}
