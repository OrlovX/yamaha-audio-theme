<?php
/**
 * Template tags: reusable chunks of markup.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logo: custom logo if set, else a wordmark.
 */
function hifiexpert_logo() {
	$home = esc_url( home_url( '/' ) );
	if ( has_custom_logo() ) {
		$id   = (int) get_theme_mod( 'custom_logo' );
		$cls  = 'hx-logo' . ( hifiexpert_opt( 'logo_invert' ) ? ' hx-logo--invert' : '' );
		$svg  = hifiexpert_inline_logo_svg( $id );
		$logo = $svg ? $svg : wp_get_attachment_image( $id, 'full', false, array( 'class' => 'hx-logo__img', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager', 'fetchpriority' => 'high' ) );
		printf( '<a class="%s" href="%s" rel="home" aria-label="%s">%s</a>', esc_attr( $cls ), $home, esc_attr( get_bloginfo( 'name' ) . ', prima pagină' ), $logo ); // phpcs:ignore
		return;
	}
	printf( '<a class="hx-logo hx-logo--text" href="%s" rel="home" aria-label="%s">HiFi<span>Expert</span></a>', $home, esc_attr( get_bloginfo( 'name' ) . ', prima pagină' ) );
}

/**
 * Inline an SVG logo with its viewBox tightened to the artwork, so the badge
 * fills the space it is given (the source file has a huge empty canvas and a ring
 * drawn outside the viewBox). Cached in a transient; cleared when the logo changes.
 */
function hifiexpert_inline_logo_svg( $attachment_id ) {
	if ( ! $attachment_id || 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
		return '';
	}
	static $n = 0;
	$n++;
	$key = 'hifiexpert_logo_svg_' . $attachment_id . '_' . hifiexpert_asset_ver( '/inc/template-tags.php' );
	$svg = get_transient( $key );
	if ( false === $svg ) {
		$file = get_attached_file( $attachment_id );
		$raw  = $file && is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$svg  = '';
		if ( $raw && false !== stripos( $raw, '<svg' ) && false === stripos( $raw, '<script' ) ) {
			$raw = preg_replace( '/<\?xml[^>]*\?>|<!DOCTYPE[^>]*>/i', '', $raw );
			// Drop fixed width/height from the root <svg> only (children keep theirs).
			$raw = preg_replace_callback( '/<svg\b[^>]*>/i', function ( $m ) {
				return preg_replace( '/\s(width|height)="[^"]*"/i', '', $m[0] );
			}, $raw, 1 );
			$vb  = hifiexpert_opt( 'logo_viewbox' );
			if ( $vb && preg_match( '/^-?\d+(\.\d+)?(\s+-?\d+(\.\d+)?){3}$/', trim( $vb ) ) ) {
				$raw = preg_replace( '/viewBox="[^"]*"/i', 'viewBox="' . esc_attr( trim( $vb ) ) . '"', $raw, 1 );
			}
			$raw = preg_replace( '/<svg\b/i', '<svg class="hx-logo__img" role="img" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '" focusable="false"', $raw, 1 );
			$svg = trim( $raw );
		}
		set_transient( $key, $svg, DAY_IN_SECONDS );
	}
	if ( ! $svg ) {
		return '';
	}
	// Unique ids per instance (the logo is printed in header and footer): id="c" -> id="hxl2-c", url(#c) -> url(#hxl2-c).
	$prefix = 'hxl' . $n . '-';
	$svg    = preg_replace( '/\bid="([^"]+)"/', 'id="' . $prefix . '$1"', $svg );
	$svg    = preg_replace( '/url\(#([^)]+)\)/', 'url(#' . $prefix . '$1)', $svg );
	$svg    = preg_replace( '/(xlink:href|href)="#([^"]+)"/', '$1="#' . $prefix . '$2"', $svg );
	// Class names used by the SVG's own <style> are global once inlined: scope them per instance too.
	if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $svg, $styles ) ) {
		$classes = array();
		foreach ( $styles[1] as $css ) {
			if ( preg_match_all( '/\.([a-zA-Z_][\w-]*)\s*\{/', $css, $cm ) ) {
				$classes = array_merge( $classes, $cm[1] );
			}
		}
		foreach ( array_unique( $classes ) as $c ) {
			$svg = preg_replace( '/\.' . preg_quote( $c, '/' ) . '(?=\s*[{,.:\s])/', '.' . $prefix . $c, $svg ); // in <style>
			$svg = preg_replace_callback( '/\bclass="([^"]*)"/', function ( $m ) use ( $c, $prefix ) {
				$names = preg_split( '/\s+/', trim( $m[1] ) );
				$names = array_map( function ( $x ) use ( $c, $prefix ) { return $x === $c ? $prefix . $c : $x; }, $names );
				return 'class="' . implode( ' ', $names ) . '"';
			}, $svg );
		}
	}
	return $svg;
}
add_action( 'customize_save_after', function () {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hifiexpert_logo_svg_%' OR option_name LIKE '_transient_timeout_hifiexpert_logo_svg_%'" ); // phpcs:ignore
} );

/**
 * Search: FiboSearch if active, otherwise the WooCommerce product search.
 */
function hifiexpert_search_form( $compact = false ) {
	if ( shortcode_exists( 'fibosearch' ) ) {
		// Header: a magnifier icon below 1400px (the menu needs the room), the full field above.
		// Header: a "Caută" pill that opens FiboSearch's full-screen overlay (always, not only on phones).
		$shortcode = $compact ? '[fibosearch layout="icon" mobile_overlay="1" mobile_overlay_breakpoint="99999"]' : '[fibosearch layout="classic"]';
		echo '<div class="hx-search hx-search--fibo' . ( $compact ? ' hx-search--compact' : '' ) . '">' . do_shortcode( $shortcode ) . '</div>';
		return;
	}
	?>
	<form class="hx-search<?php echo $compact ? ' hx-search--compact' : ''; ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="hx-q"><?php esc_html_e( 'Caută produse', 'hifiexpert' ); ?></label>
		<?php echo hifiexpert_icon( 'search', 18 ); ?>
		<input id="hx-q" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Caută: Yamaha, Triangle, DAC…', 'hifiexpert' ); ?>">
		<input type="hidden" name="post_type" value="product">
	</form>
	<?php
}

/**
 * Header action icons: account, wishlist, cart.
 */
function hifiexpert_header_actions() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$account  = wc_get_page_permalink( 'myaccount' );
	$wishlist = function_exists( 'YITH_WCWL' ) ? YITH_WCWL()->get_wishlist_url() : '';
	$count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<div class="hx-actions">
		<?php hifiexpert_theme_switch(); ?>
		<a class="hx-iconbtn" href="<?php echo esc_url( $account ); ?>" aria-label="<?php esc_attr_e( 'Contul meu', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'user' ); ?></a>
		<?php if ( $wishlist ) : ?>
			<a class="hx-iconbtn" href="<?php echo esc_url( $wishlist ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'heart' ); ?></a>
		<?php endif; ?>
		<a class="hx-iconbtn hx-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( sprintf( _n( 'Coș, %d produs', 'Coș, %d produse', $count, 'hifiexpert' ), $count ) ); ?>">
			<?php echo hifiexpert_icon( 'cart' ); ?>
			<span class="hx-cart-count<?php echo $count ? '' : ' is-empty'; ?>" aria-hidden="true"><?php echo (int) $count; ?></span>
		</a>
	</div>
	<?php
}

/**
 * Light / dark switch (header icon button, or the labelled row in the mobile menu).
 * The current theme is on <html data-theme>; main.js toggles it and remembers the choice.
 */
function hifiexpert_theme_switch( $labelled = false ) {
	if ( ! hifiexpert_opt( 'theme_switch' ) ) {
		return;
	}
	$label = __( 'Schimbă tema luminoasă / întunecată', 'hifiexpert' );
	if ( $labelled ) {
		echo '<button class="hx-mnav__theme" type="button" data-hx-theme aria-label="' . esc_attr( $label ) . '">'
			. hifiexpert_icon( 'sun', 16 )
			. hifiexpert_icon( 'moon', 16 )
			. '<span class="hx-theme__to-light">' . esc_html__( 'Temă luminoasă', 'hifiexpert' ) . '</span>'
			. '<span class="hx-theme__to-dark">' . esc_html__( 'Temă întunecată', 'hifiexpert' ) . '</span></button>';
		return;
	}
	echo '<button class="hx-iconbtn hx-theme" type="button" data-hx-theme aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
		. hifiexpert_icon( 'sun' )
		. hifiexpert_icon( 'moon' )
		. '</button>';
}

/**
 * Section heading row with optional "see all" link.
 */
function hifiexpert_section_head( $title, $text = '', $link = '', $link_label = '' ) {
	echo '<div class="hx-section__head">';
	echo '<div class="hx-section__titles"><h2 class="hx-section__title">' . esc_html( $title ) . '</h2>';
	if ( $text ) {
		echo '<p class="hx-section__text">' . esc_html( $text ) . '</p>';
	}
	echo '</div>';
	if ( $link && $link_label ) {
		echo '<a class="hx-section__more" href="' . esc_url( $link ) . '">' . esc_html( $link_label ) . ' →</a>';
	}
	echo '</div>';
}

/**
 * Price block: current, old, savings; variable products get "de la".
 */
function hifiexpert_price_block( $product, $size = 'card' ) {
	if ( ! $product ) {
		return;
	}
	$variable = $product->is_type( 'variable' );
	$price    = hifiexpert_active_price( $product );
	$on_sale  = $product->is_on_sale();
	$regular  = $variable ? (float) $product->get_variation_regular_price( 'min' ) : (float) $product->get_regular_price();
	$percent  = hifiexpert_discount_percent( $product );

	$camp = $on_sale && $price > 0 ? hifiexpert_campaign() : null;
	echo '<div class="hx-price hx-price--' . esc_attr( $size ) . ( $camp ? ' hx-price--camp' : '' ) . '">';
	if ( $camp ) {
		echo '<span class="hx-price__camp">' . esc_html( $camp['badge'] ) . ( $percent ? ' <b>−' . (int) $percent . '%</b>' : '' ) . '</span>';
	}
	if ( $price > 0 ) {
		echo '<span class="hx-price__now">' . ( $variable ? '<span class="hx-price__from">de la</span> ' : '' ) . esc_html( hifiexpert_lei( $price ) ) . '</span>';
		if ( $on_sale && $regular > $price ) {
			echo '<s class="hx-price__was">' . esc_html( hifiexpert_lei( $regular ) ) . '</s>';
			if ( 'single' === $size ) {
				echo '<span class="hx-price__save">' . sprintf( esc_html__( 'Economisești %s', 'hifiexpert' ), esc_html( hifiexpert_lei( $regular - $price ) ) ) . '</span>';
			}
		}
	} else {
		echo '<span class="hx-price__now hx-price__now--ask">' . esc_html__( 'Preț la cerere', 'hifiexpert' ) . '</span>';
	}
	echo '</div>';
}

/**
 * Installment + stock line (cards) or the full promise box (single).
 */
function hifiexpert_rate_line( $product ) {
	$rate = hifiexpert_installment( hifiexpert_active_price( $product ) );
	if ( ! $rate ) {
		return '';
	}
	return sprintf( '%d × %s', (int) hifiexpert_opt( 'rates_count' ), hifiexpert_lei( $rate ) );
}

function hifiexpert_stock_badge( $product ) {
	list( $state, $label ) = hifiexpert_stock_state( $product );
	if ( ! $label ) {
		return '';
	}
	return '<span class="hx-stock hx-stock--' . esc_attr( $state ) . '"><span class="hx-stock__dot"></span>' . esc_html( $label ) . '</span>';
}

/**
 * A product card. Used by the shop loop and the homepage.
 */
function hifiexpert_product_card( $product, $args = array() ) {
	if ( ! $product || ! $product->is_visible() ) {
		return;
	}
	$args    = wp_parse_args( $args, array( 'tag' => 'li', 'lazy' => true, 'show_button' => true ) );
	$brand   = hifiexpert_product_brand( $product );
	$percent = hifiexpert_discount_percent( $product );
	$link    = $product->get_permalink();
	$rate    = hifiexpert_rate_line( $product );
	$img_args = array( 'class' => 'hx-card__img', 'alt' => $product->get_name() );
	$img_args['loading'] = $args['lazy'] ? 'lazy' : 'eager';
	if ( ! $args['lazy'] ) {
		$img_args['fetchpriority'] = 'high';
	}
	$image = $product->get_image_id()
		? wp_get_attachment_image( $product->get_image_id(), 'hifiexpert-card', false, $img_args )
		: wc_placeholder_img( 'hifiexpert-card', $img_args );

	$tag = $args['tag'];
	printf( '<%s class="hx-card product">', $tag );
	?>
	<a class="hx-card__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
		<figure class="hx-stage"><?php echo $image; // phpcs:ignore ?></figure>
		<?php if ( $percent && hifiexpert_in_campaign( $product ) ) : ?>
			<span class="hx-pill hx-pill--sale hx-pill--camp"><small><?php echo esc_html( hifiexpert_campaign()['badge'] ); ?></small>−<?php echo (int) $percent; ?>%</span>
		<?php elseif ( $percent ) : ?>
			<span class="hx-pill hx-pill--sale">−<?php echo (int) $percent; ?>%</span>
		<?php elseif ( $product->is_featured() ) : ?>
			<span class="hx-pill hx-pill--muted"><?php esc_html_e( 'Recomandat', 'hifiexpert' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="hx-card__body">
		<?php if ( $brand ) : ?>
			<a class="hx-card__brand" href="<?php echo esc_url( get_term_link( $brand ) ); ?>"><?php echo esc_html( $brand->name ); ?></a>
		<?php else : ?>
			<span class="hx-card__brand" aria-hidden="true"></span>
		<?php endif; ?>
		<h3 class="hx-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<?php if ( function_exists( 'hifiexpert_awards_html' ) ) { echo hifiexpert_awards_html( $product, true ); } // phpcs:ignore ?>
		<?php $lead = hifiexpert_product_lead( $product, 14 ); ?>
		<p class="hx-card__lead<?php echo $lead ? '' : ' hx-card__lead--empty'; ?>"><?php echo esc_html( $lead ); ?></p>
		<?php hifiexpert_price_block( $product, 'card' ); ?>
		<div class="hx-card__meta">
			<?php if ( $rate ) : ?><span class="hx-card__rate"><?php echo esc_html( $rate ); ?></span><?php endif; ?>
			<?php echo hifiexpert_stock_badge( $product ); // phpcs:ignore ?>
		</div>
		<?php if ( $args['show_button'] ) : ?>
			<div class="hx-card__cta"><?php hifiexpert_loop_button( $product ); ?></div>
		<?php endif; ?>
	</div>
	<?php
	printf( '</%s>', $tag );
}

/**
 * Loop add-to-cart button with our labels.
 */
function hifiexpert_loop_button( $product ) {
	if ( hifiexpert_is_inquiry_only( $product ) ) {
		$url = hifiexpert_inquiry_url( $product );
		if ( $url ) {
			echo '<a class="hx-btn hx-btn--ghost hx-btn--sm hx-btn--ask" href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">' . hifiexpert_icon( 'chat', 16 ) . esc_html( hifiexpert_opt( 'music_inquire_text' ) ) . '</a>';
			return;
		}
	}
	$label = $product->add_to_cart_text();
	if ( $product->is_type( 'variable' ) ) {
		$label = __( 'Alege opțiunile', 'hifiexpert' );
	} elseif ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		$label = __( 'Adaugă în coș', 'hifiexpert' );
	}
	$args = array(
		'quantity'   => 1,
		'class'      => implode( ' ', array_filter( array(
			'hx-btn hx-btn--primary hx-btn--sm',
			'product_type_' . $product->get_type(),
			$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
			$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
		) ) ),
		'attributes' => array(
			'data-product_id'  => $product->get_id(),
			'data-product_sku' => $product->get_sku(),
			'aria-label'       => $product->add_to_cart_description(),
			'rel'              => 'nofollow',
		),
	);
	echo apply_filters( // phpcs:ignore
		'woocommerce_loop_add_to_cart_link',
		sprintf(
			'<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
			esc_url( $product->add_to_cart_url() ),
			esc_attr( $args['quantity'] ),
			esc_attr( $args['class'] ),
			wc_implode_html_attributes( $args['attributes'] ),
			esc_html( $label )
		),
		$product,
		$args
	);
}

/**
 * Blog card.
 */
function hifiexpert_post_card( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$cats    = get_the_category( $post_id );
	?>
	<article class="hx-postcard">
		<a class="hx-postcard__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<figure class="hx-stage hx-stage--post">
				<?php echo get_the_post_thumbnail( $post_id, 'hifiexpert-post', array( 'loading' => 'lazy' ) ); ?>
			</figure>
		</a>
		<?php if ( $cats ) : ?>
			<span class="hx-eyebrow hx-eyebrow--muted"><?php echo esc_html( $cats[0]->name ); ?></span>
		<?php endif; ?>
		<h3 class="hx-postcard__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
	</article>
	<?php
}

/**
 * Trust strip (homepage) — four promises.
 */
function hifiexpert_trust_strip() {
	$min   = (int) hifiexpert_opt( 'free_shipping_min' );
	$rates = (int) hifiexpert_opt( 'rates_count' );
	$items = array(
		array( 'truck', __( 'Livrare gratuită', 'hifiexpert' ), sprintf( __( 'La comenzi peste %d lei. Expediem din București în aceeași zi.', 'hifiexpert' ), $min ) ),
		array( 'card', sprintf( __( '%d rate fără dobândă', 'hifiexpert' ), $rates ), __( 'Cu Card Avantaj. Plată online sau ramburs la curier.', 'hifiexpert' ) ),
		array( 'pin', __( 'Ascultă înainte să cumperi', 'hifiexpert' ), sprintf( __( 'Showroom în %s. Programează o audiție.', 'hifiexpert' ), hifiexpert_opt( 'topbar_address' ) ) ),
		array( 'chat', __( 'Un expert, pe WhatsApp', 'hifiexpert' ), __( 'Răspundem în câteva minute, în programul showroom-ului. Sfaturi reale, nu scripturi.', 'hifiexpert' ) ),
	);
	echo '<div class="hx-trust" id="showroom">';
	foreach ( $items as $i ) {
		echo '<div class="hx-trust__item">' . hifiexpert_icon( $i[0], 26 ) . '<span class="hx-trust__title">' . esc_html( $i[1] ) . '</span><span class="hx-trust__text">' . esc_html( $i[2] ) . '</span></div>';
	}
	echo '</div>';
}

/**
 * Numbered pagination styled as pills.
 */
function hifiexpert_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => '‹ ' . __( 'Anterior', 'hifiexpert' ),
			'next_text' => __( 'Următor', 'hifiexpert' ) . ' ›',
			'class'     => 'hx-pagination',
		)
	);
}
