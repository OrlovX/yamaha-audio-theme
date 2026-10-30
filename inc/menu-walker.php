<?php
/**
 * Mega-menu walker for the primary navigation.
 *
 * Level 0: top bar items. Items with children open a panel.
 * Level 1: columns inside the panel (heading link).
 * Level 2: links inside a column.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slugs/titles of top-level items that are NOT categories (Blog, Promoții, Contact…).
 * They leave the category bar and form a separate group on the right.
 */
function yamaha_audio_nav_extra_slugs() {
	static $slugs = null;
	if ( null === $slugs ) {
		$slugs = array_values( array_filter( array_map( 'sanitize_title', preg_split( '/[,\n]+/', (string) yamaha_audio_opt( 'nav_extra' ) ) ) ) );
	}
	return $slugs;
}

function yamaha_audio_nav_item_is_extra( $item ) {
	$slugs = yamaha_audio_nav_extra_slugs();
	if ( ! $slugs ) {
		return false;
	}
	$path  = trim( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ), '/' );
	$last  = '' !== $path ? sanitize_title( basename( $path ) ) : '';
	$title = sanitize_title( wp_strip_all_tags( (string) $item->title ) );
	return ( $last && in_array( $last, $slugs, true ) ) || in_array( $title, $slugs, true );
}

/**
 * wp_nav_menu( [ 'hx_group' => 'cats' | 'extra' ] ) renders only one of the two groups.
 */
/**
 * Menu labels from the old theme carry HTML line breaks ("Cabluri <br> interconnect"): render them as plain text.
 */
function yamaha_audio_clean_menu_title( $title ) {
	$title = preg_replace( '/<br\s*\/?>|&lt;br\s*\/?&gt;/i', ' ', (string) $title );
	$title = wp_strip_all_tags( $title );
	return trim( preg_replace( '/\s+/u', ' ', $title ) );
}
add_filter( 'nav_menu_item_title', function ( $title ) {
	return yamaha_audio_clean_menu_title( $title );
}, 5 );

add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	foreach ( $items as $it ) {
		$it->title = yamaha_audio_clean_menu_title( $it->title );
	}
	if ( empty( $args->hx_group ) ) {
		return $items;
	}
	$extra = array();
	foreach ( $items as $it ) {
		if ( 0 === (int) $it->menu_item_parent && yamaha_audio_nav_item_is_extra( $it ) ) {
			$extra[ $it->ID ] = true;
		}
	}
	$remove  = $extra;
	$changed = true;
	while ( $changed ) {
		$changed = false;
		foreach ( $items as $it ) {
			if ( ! isset( $remove[ $it->ID ] ) && isset( $remove[ (int) $it->menu_item_parent ] ) ) {
				$remove[ $it->ID ] = true;
				$changed           = true;
			}
		}
	}
	$out = array();
	foreach ( $items as $it ) {
		if ( 'extra' === $args->hx_group ? isset( $extra[ $it->ID ] ) : ! isset( $remove[ $it->ID ] ) ) {
			$out[] = $it;
		}
	}
	return $out;
}, 10, 2 );

// Off-canvas menu (default walker): mark the extra items so CSS can separate them.
add_filter( 'nav_menu_css_class', function ( $classes, $item, $args, $depth ) {
	if ( 0 === (int) $depth && yamaha_audio_nav_item_is_extra( $item ) ) {
		$classes[] = 'hx-extra';
		if ( preg_match( '/promo|ofert|reduc/i', $item->title . ' ' . $item->url ) ) {
			$classes[] = 'hx-sale';
		}
	}
	return $classes;
}, 10, 4 );

class Yamaha_Audio_Mega_Walker extends Walker_Nav_Menu {

	/**
	 * Skip leftovers from the old menu plugin (custom-content rows, placeholders).
	 */
	private function is_junk( $item ) {
		$title = wp_strip_all_tags( (string) $item->title );
		$url   = (string) $item->url;
		return false !== stripos( $url, 'ubermenu' )
			|| false !== stripos( $title, '[custom]' )
			|| false !== stripos( $title, 'placeholder' );
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '<div class="hx-mega" aria-hidden="true"><div class="hx-mega__inner"><ul class="hx-mega__cols">';
		} elseif ( 1 === $depth ) {
			$output .= '<ul class="hx-mega__links">';
		} else {
			$output .= '<ul class="hx-mega__sub">';
		}
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '</ul></div></div>';
		} else {
			$output .= '</ul>';
		}
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( $this->is_junk( $item ) ) {
			$output .= '<li class="hx-hidden" hidden>';
			return;
		}
		$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );
		$current      = in_array( 'current-menu-item', (array) $item->classes, true ) || in_array( 'current-menu-ancestor', (array) $item->classes, true );
		$title        = apply_filters( 'the_title', $item->title, $item->ID );
		$url          = $item->url ? esc_url( $item->url ) : '#';
		$extra_class  = '';
		foreach ( (array) $item->classes as $c ) {
			if ( in_array( $c, array( 'hx-accent', 'hx-sale' ), true ) ) {
				$extra_class .= ' ' . $c;
			}
		}

		if ( 0 === $depth ) {
			$is_extra = ! empty( $args->hx_group ) && 'extra' === $args->hx_group;
			if ( $is_extra ) {
				$has_children = false;
			}
			if ( $is_extra && '' === $extra_class && preg_match( '/promo|ofert|reduc/i', $item->title . ' ' . $item->url ) ) {
				$extra_class = ' hx-sale';
			}
			$output .= sprintf(
				'<li class="hx-nav__item%s%s%s"><a class="hx-nav__link%s" href="%s"%s>%s</a>',
				$has_children ? ' has-mega' : '',
				$current ? ' is-current' : '',
				$is_extra ? ' hx-nav__item--extra' : '',
				$extra_class,
				$url,
				$has_children ? ' aria-haspopup="true" aria-expanded="false"' : '',
				esc_html( $title )
			);
			return;
		}

		if ( 1 === $depth ) {
			$thumb = '';
			$image = get_post_meta( $item->ID, '_hx_menu_image', true );
			if ( $has_children && $item->object === 'product_cat' ) {
				$thumb_id = get_term_meta( (int) $item->object_id, 'thumbnail_id', true );
				if ( $thumb_id ) {
					$thumb = wp_get_attachment_image( (int) $thumb_id, 'thumbnail', false, array( 'class' => 'hx-mega__thumb', 'loading' => 'lazy', 'alt' => '' ) );
				}
			}
			$output .= sprintf(
				'<li class="hx-mega__col"><a class="hx-mega__head" href="%s">%s<span>%s</span></a>',
				$url,
				$thumb,
				esc_html( $title )
			);
			return;
		}

		$output .= sprintf( '<li><a class="hx-mega__link" href="%s">%s</a>', $url, esc_html( $title ) );
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}
