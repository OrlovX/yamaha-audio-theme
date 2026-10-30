<?php
/**
 * Template tags: reusable chunks of markup.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logo: custom logo if set, else a wordmark.
 */
function yamaha_audio_logo() {
	$home = esc_url( home_url( '/' ) );
	if ( has_custom_logo() ) {
		$id   = (int) get_theme_mod( 'custom_logo' );
		$cls  = 'hx-logo' . ( yamaha_audio_opt( 'logo_invert' ) ? ' hx-logo--invert' : '' );
		$svg  = yamaha_audio_inline_logo_svg( $id );
		$logo = $svg ? $svg : wp_get_attachment_image( $id, 'full', false, array( 'class' => 'hx-logo__img', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager', 'fetchpriority' => 'high' ) );
		printf( '<a class="%s" href="%s" rel="home" aria-label="%s">%s</a>', esc_attr( $cls ), $home, esc_attr( get_bloginfo( 'name' ) . ', prima pagină' ), $logo ); // phpcs:ignore
		return;
	}
	printf( '<a class="hx-logo hx-logo--text" href="%s" rel="home" aria-label="%s">Yamaha<span>Audio</span></a>', $home, esc_attr( get_bloginfo( 'name' ) . ', prima pagină' ) );
}

/**
 * Inline an SVG logo with its viewBox tightened to the artwork, so the badge
 * fills the space it is given (the source file has a huge empty canvas and a ring
 * drawn outside the viewBox). Cached in a transient; cleared when the logo changes.
 */
function yamaha_audio_inline_logo_svg( $attachment_id ) {
	if ( ! $attachment_id || 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
		return '';
	}
	static $n = 0;
	$n++;
	$key = 'yamaha_audio_logo_svg_' . $attachment_id . '_' . yamaha_audio_asset_ver( '/inc/template-tags.php' );
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
			$vb  = yamaha_audio_opt( 'logo_viewbox' );
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
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_yamaha_audio_logo_svg_%' OR option_name LIKE '_transient_timeout_yamaha_audio_logo_svg_%'" ); // phpcs:ignore
} );

/**
 * Search: FiboSearch if active, otherwise the WooCommerce product search.
 */
function yamaha_audio_search_form( $compact = false ) {
	if ( shortcode_exists( 'fibosearch' ) ) {
		// Header: a magnifier icon below 1400px (the menu needs the room), the full field above.
		// Header: a "Caută" pill that opens FiboSearch's full-screen overlay (always, not only on phones).
		$shortcode = $compact ? '[fibosearch layout="icon" mobile_overlay="1" mobile_overlay_breakpoint="99999"]' : '[fibosearch layout="classic"]';
		echo '<div class="hx-search hx-search--fibo' . ( $compact ? ' hx-search--compact' : '' ) . '">' . do_shortcode( $shortcode ) . '</div>';
		return;
	}
	?>
	<form class="hx-search<?php echo $compact ? ' hx-search--compact' : ''; ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="hx-q"><?php esc_html_e( 'Caută produse', 'yamaha-audio' ); ?></label>
		<?php echo yamaha_audio_icon( 'search', 18 ); ?>
		<input id="hx-q" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Caută: RX‑V6A, A‑S701, MusicCast…', 'yamaha-audio' ); ?>">
		<input type="hidden" name="post_type" value="product">
	</form>
	<?php
}

/**
 * Header action icons: account, wishlist, cart.
 */
function yamaha_audio_header_actions() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$account  = wc_get_page_permalink( 'myaccount' );
	$wishlist = function_exists( 'YITH_WCWL' ) ? YITH_WCWL()->get_wishlist_url() : '';
	$count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<div class="hx-actions">
		<?php yamaha_audio_theme_switch(); ?>
		<a class="hx-iconbtn" href="<?php echo esc_url( $account ); ?>" aria-label="<?php esc_attr_e( 'Contul meu', 'yamaha-audio' ); ?>"><?php echo yamaha_audio_icon( 'user' ); ?></a>
		<?php if ( $wishlist ) : ?>
			<a class="hx-iconbtn" href="<?php echo esc_url( $wishlist ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'yamaha-audio' ); ?>"><?php echo yamaha_audio_icon( 'heart' ); ?></a>
		<?php endif; ?>
		<a class="hx-iconbtn hx-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( sprintf( _n( 'Coș, %d produs', 'Coș, %d produse', $count, 'yamaha-audio' ), $count ) ); ?>">
			<?php echo yamaha_audio_icon( 'cart' ); ?>
			<span class="hx-cart-count<?php echo $count ? '' : ' is-empty'; ?>" aria-hidden="true"><?php echo (int) $count; ?></span>
		</a>
	</div>
	<?php
}

/**
 * Light / dark switch (header icon button, or the labelled row in the mobile menu).
 * The current theme is on <html data-theme>; main.js toggles it and remembers the choice.
 */
function yamaha_audio_theme_switch( $labelled = false ) {
	if ( ! yamaha_audio_opt( 'theme_switch' ) ) {
		return;
	}
	$label = __( 'Schimbă tema luminoasă / întunecată', 'yamaha-audio' );
	if ( $labelled ) {
		echo '<button class="hx-mnav__theme" type="button" data-hx-theme aria-label="' . esc_attr( $label ) . '">'
			. yamaha_audio_icon( 'sun', 16 )
			. yamaha_audio_icon( 'moon', 16 )
			. '<span class="hx-theme__to-light">' . esc_html__( 'Temă luminoasă', 'yamaha-audio' ) . '</span>'
			. '<span class="hx-theme__to-dark">' . esc_html__( 'Temă întunecată', 'yamaha-audio' ) . '</span></button>';
		return;
	}
	echo '<button class="hx-iconbtn hx-theme" type="button" data-hx-theme aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
		. yamaha_audio_icon( 'sun' )
		. yamaha_audio_icon( 'moon' )
		. '</button>';
}

/**
 * Section heading row with optional "see all" link.
 */
function yamaha_audio_section_head( $title, $text = '', $link = '', $link_label = '' ) {
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
function yamaha_audio_price_block( $product, $size = 'card' ) {
	if ( ! $product ) {
		return;
	}
	$variable = $product->is_type( 'variable' );
	$price    = yamaha_audio_active_price( $product );
	$on_sale  = $product->is_on_sale();
	$regular  = $variable ? (float) $product->get_variation_regular_price( 'min' ) : (float) $product->get_regular_price();
	$percent  = yamaha_audio_discount_percent( $product );

	$camp = $on_sale && $price > 0 ? yamaha_audio_campaign() : null;
	echo '<div class="hx-price hx-price--' . esc_attr( $size ) . ( $camp ? ' hx-price--camp' : '' ) . '">';
	if ( $camp ) {
		echo '<span class="hx-price__camp">' . esc_html( $camp['badge'] ) . ( $percent ? ' <b>−' . (int) $percent . '%</b>' : '' ) . '</span>';
	}
	if ( $price > 0 ) {
		echo '<span class="hx-price__now">' . ( $variable ? '<span class="hx-price__from">de la</span> ' : '' ) . esc_html( yamaha_audio_lei( $price ) ) . '</span>';
		if ( $on_sale && $regular > $price ) {
			echo '<s class="hx-price__was">' . esc_html( yamaha_audio_lei( $regular ) ) . '</s>';
			if ( 'single' === $size ) {
				echo '<span class="hx-price__save">' . sprintf( esc_html__( 'Economisești %s', 'yamaha-audio' ), esc_html( yamaha_audio_lei( $regular - $price ) ) ) . '</span>';
			}
		}
	} else {
		echo '<span class="hx-price__now hx-price__now--ask">' . esc_html__( 'Preț la cerere', 'yamaha-audio' ) . '</span>';
	}
	echo '</div>';
}

/**
 * Installment + stock line (cards) or the full promise box (single).
 */
function yamaha_audio_rate_line( $product ) {
	$rate = yamaha_audio_installment( yamaha_audio_active_price( $product ) );
	if ( ! $rate ) {
		return '';
	}
	return sprintf( '%d × %s', (int) yamaha_audio_opt( 'rates_count' ), yamaha_audio_lei( $rate ) );
}

function yamaha_audio_stock_badge( $product ) {
	list( $state, $label ) = yamaha_audio_stock_state( $product );
	if ( ! $label ) {
		return '';
	}
	return '<span class="hx-stock hx-stock--' . esc_attr( $state ) . '"><span class="hx-stock__dot"></span>' . esc_html( $label ) . '</span>';
}

/**
 * A product card. Used by the shop loop and the homepage.
 */
function yamaha_audio_product_card( $product, $args = array() ) {
	if ( ! $product || ! $product->is_visible() ) {
		return;
	}
	$args    = wp_parse_args( $args, array( 'tag' => 'li', 'lazy' => true, 'show_button' => true ) );
	$cat     = yamaha_audio_product_category( $product );
	$percent = yamaha_audio_discount_percent( $product );
	$link    = $product->get_permalink();
	$rate    = yamaha_audio_rate_line( $product );
	$img_args = array( 'class' => 'hx-card__img', 'alt' => $product->get_name() );
	$img_args['loading'] = $args['lazy'] ? 'lazy' : 'eager';
	if ( ! $args['lazy'] ) {
		$img_args['fetchpriority'] = 'high';
	}
	$image = $product->get_image_id()
		? wp_get_attachment_image( $product->get_image_id(), 'yamaha-audio-card', false, $img_args )
		: wc_placeholder_img( 'yamaha-audio-card', $img_args );

	$tag = $args['tag'];
	printf( '<%s class="hx-card product">', $tag );
	?>
	<a class="hx-card__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
		<figure class="hx-stage"><?php echo $image; // phpcs:ignore ?></figure>
		<?php if ( $percent && yamaha_audio_in_campaign( $product ) ) : ?>
			<span class="hx-pill hx-pill--sale hx-pill--camp"><small><?php echo esc_html( yamaha_audio_campaign()['badge'] ); ?></small>−<?php echo (int) $percent; ?>%</span>
		<?php elseif ( $percent ) : ?>
			<span class="hx-pill hx-pill--sale">−<?php echo (int) $percent; ?>%</span>
		<?php elseif ( $product->is_featured() ) : ?>
			<span class="hx-pill hx-pill--muted"><?php esc_html_e( 'Recomandat', 'yamaha-audio' ); ?></span>
		<?php endif; ?>
	</a>
	<div class="hx-card__body">
		<?php if ( $cat ) : ?>
			<a class="hx-card__kicker" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php else : ?>
			<span class="hx-card__kicker" aria-hidden="true"></span>
		<?php endif; ?>
		<h3 class="hx-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<?php if ( function_exists( 'yamaha_audio_awards_html' ) ) { echo yamaha_audio_awards_html( $product, true ); } // phpcs:ignore ?>
		<?php $lead = yamaha_audio_product_lead( $product, 14 ); ?>
		<p class="hx-card__lead<?php echo $lead ? '' : ' hx-card__lead--empty'; ?>"><?php echo esc_html( $lead ); ?></p>
		<?php yamaha_audio_price_block( $product, 'card' ); ?>
		<div class="hx-card__meta">
			<?php if ( $rate ) : ?><span class="hx-card__rate"><?php echo esc_html( $rate ); ?></span><?php endif; ?>
			<?php echo yamaha_audio_stock_badge( $product ); // phpcs:ignore ?>
		</div>
		<?php if ( $args['show_button'] ) : ?>
			<div class="hx-card__cta"><?php yamaha_audio_loop_button( $product ); ?></div>
		<?php endif; ?>
	</div>
	<?php
	printf( '</%s>', $tag );
}

/**
 * Loop add-to-cart button with our labels.
 */
function yamaha_audio_loop_button( $product ) {
	$label = $product->add_to_cart_text();
	if ( $product->is_type( 'variable' ) ) {
		$label = __( 'Alege opțiunile', 'yamaha-audio' );
	} elseif ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		$label = __( 'Adaugă în coș', 'yamaha-audio' );
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
function yamaha_audio_post_card( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$cats    = get_the_category( $post_id );
	?>
	<article class="hx-postcard">
		<a class="hx-postcard__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<figure class="hx-stage hx-stage--post">
				<?php echo get_the_post_thumbnail( $post_id, 'yamaha-audio-post', array( 'loading' => 'lazy' ) ); ?>
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
function yamaha_audio_trust_strip() {
	$min   = (int) yamaha_audio_opt( 'free_shipping_min' );
	$rates = (int) yamaha_audio_opt( 'rates_count' );
	$items = array(
		array( 'truck', __( 'Livrare gratuită', 'yamaha-audio' ), sprintf( __( 'La comenzi peste %d lei. Expediem din București în aceeași zi.', 'yamaha-audio' ), $min ) ),
		array( 'card', sprintf( __( '%d rate fără dobândă', 'yamaha-audio' ), $rates ), __( 'Cu Card Avantaj. Plată online sau ramburs la curier.', 'yamaha-audio' ) ),
		array( 'pin', __( 'Ascultă înainte să cumperi', 'yamaha-audio' ), sprintf( __( 'Showroom în %s. Programează o audiție.', 'yamaha-audio' ), yamaha_audio_opt( 'topbar_address' ) ) ),
		array( 'chat', __( 'Un expert, pe WhatsApp', 'yamaha-audio' ), __( 'Răspundem în câteva minute, în programul showroom-ului. Sfaturi reale, nu scripturi.', 'yamaha-audio' ) ),
	);
	echo '<div class="hx-trust" id="showroom">';
	foreach ( $items as $i ) {
		echo '<div class="hx-trust__item">' . yamaha_audio_icon( $i[0], 26 ) . '<span class="hx-trust__title">' . esc_html( $i[1] ) . '</span><span class="hx-trust__text">' . esc_html( $i[2] ) . '</span></div>';
	}
	echo '</div>';
}

/**
 * Numbered pagination styled as pills.
 */
function yamaha_audio_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => '‹ ' . __( 'Anterior', 'yamaha-audio' ),
			'next_text' => __( 'Următor', 'yamaha-audio' ) . ' ›',
			'class'     => 'hx-pagination',
		)
	);
}
