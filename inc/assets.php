<?php
/**
 * Scripts & styles: one stylesheet, one small script, nothing we don't use.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting by file mtime so git pulls on staging always refresh.
 */
function yamaha_audio_asset_ver( $rel ) {
	$file = YAMAHA_AUDIO_DIR . $rel;
	return file_exists( $file ) ? (string) filemtime( $file ) : YAMAHA_AUDIO_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		// Plus Jakarta Sans is self-hosted (assets/fonts, @font-face in main.css, preloaded below): no third-party
		// stylesheet in the critical path.
		wp_enqueue_style( 'yamaha-audio-main', YAMAHA_AUDIO_URI . '/assets/css/main.css', array(), yamaha_audio_asset_ver( '/assets/css/main.css' ) );

		wp_enqueue_script( 'yamaha-audio-main', YAMAHA_AUDIO_URI . '/assets/js/main.js', array(), yamaha_audio_asset_ver( '/assets/js/main.js' ), array( 'strategy' => 'defer' ) );
		wp_localize_script(
			'yamaha-audio-main',
			'hxData',
			array(
				'cartUrl' => class_exists( 'WooCommerce' ) ? wc_get_cart_url() : home_url( '/' ),
			)
		);

		// WooCommerce ships ~100 KB of default CSS we replace entirely.
		if ( class_exists( 'WooCommerce' ) ) {
			$keep_blocks = is_cart() || is_checkout() || is_account_page() || ( is_singular() && has_blocks() );
			if ( ! $keep_blocks ) {
				wp_dequeue_style( 'wc-blocks-style' );
				wp_dequeue_style( 'wc-blocks-vendors-style' );
			}
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'classic-theme-styles' );
			wp_dequeue_style( 'global-styles' );
			if ( ! $keep_blocks ) {
				wp_dequeue_style( 'wp-block-library' ); // no blocks on the theme's templates; 4 KB of inline pattern CSS otherwise
			}
		}

		// Comments reply script only where it matters.
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	},
	20
);

// Replace WooCommerce's stylesheet set with ours.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// Print the theme stylesheet last, after every plugin stylesheet, so theme rules win on equal specificity.
add_action( 'wp_print_styles', function () {
	global $wp_styles;
	if ( ! $wp_styles || ! in_array( 'yamaha-audio-main', $wp_styles->queue, true ) ) {
		return;
	}
	$wp_styles->queue = array_values( array_diff( $wp_styles->queue, array( 'yamaha-audio-main' ) ) );
	$wp_styles->queue[] = 'yamaha-audio-main';
	// Anything enqueued late (footer) still prints after; declare the known offenders as our deps instead.
	foreach ( array( 'themecomplete-styles', 'tm-epo', 'nouislider', 'themecomplete-styles-smallscreen' ) as $h ) {
		if ( isset( $wp_styles->registered[ $h ] ) ) {
			$wp_styles->registered['yamaha-audio-main']->deps[] = $h;
		}
	}
}, 999 );

// Font files first: both subsets are needed for Romanian (ă â î in latin, ș ț in latin-ext).
add_action( 'wp_head', function () {
	foreach ( array( 'latin', 'latin-ext' ) as $sub ) {
		$rel = '/assets/fonts/plus-jakarta-sans-' . $sub . '.woff2';
		if ( file_exists( YAMAHA_AUDIO_DIR . $rel ) ) {
			printf( '<link rel="preload" as="font" type="font/woff2" crossorigin href="%s">' . "\n", esc_url( YAMAHA_AUDIO_URI . $rel ) );
		}
	}
}, 1 );

/**
 * Plugin assets that have no business on most pages. Matched by file path, so plugin handle names can change.
 * - Font Awesome (official plugin, CDN): the theme uses inline SVG; nothing on the front end needs it.
 * - Contact Form 7 + reCAPTCHA v3 (+ the GTM form tracker): only where a CF7 form is actually rendered.
 * - prettyPhoto (YITH Wishlist): only on the wishlist page.
 */
add_action( 'wp_enqueue_scripts', function () {
	global $wp_styles, $wp_scripts, $post;
	$has_cf7 = false;
	if ( is_singular() && $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'contact-form-7' ) ) {
		$has_cf7 = true;
	}
	if ( false !== stripos( (string) yamaha_audio_opt( 'newsletter_shortcode' ), 'contact-form-7' ) ) {
		$has_cf7 = true; // the footer newsletter is a CF7 form on every page
	}
	$is_wishlist = function_exists( 'yith_wcwl_is_wishlist_page' ) && yith_wcwl_is_wishlist_page();

	$drop = function ( $src, $handle ) use ( $has_cf7, $is_wishlist ) {
		$src = (string) $src;
		if ( false !== strpos( $src, 'fontawesome.com' ) || false !== strpos( $src, '/uploads/font-awesome/' ) ) {
			return true;
		}
		if ( ! $has_cf7 && ( false !== strpos( $src, '/plugins/contact-form-7/' ) || false !== strpos( $src, 'google.com/recaptcha' ) || false !== strpos( $src, 'gtm4wp-contact-form-7' ) ) ) {
			return true;
		}
		if ( ! $is_wishlist && false !== stripos( $src, 'prettyphoto' ) ) {
			return true;
		}
		return false;
	};
	foreach ( array( $wp_styles, $wp_scripts ) as $deps ) {
		if ( ! $deps ) {
			continue;
		}
		foreach ( (array) $deps->queue as $handle ) {
			if ( isset( $deps->registered[ $handle ] ) && $drop( $deps->registered[ $handle ]->src, $handle ) ) {
				$deps->dequeue( $handle );
			}
		}
	}
	if ( ! $has_cf7 ) {
		add_filter( 'wpcf7_load_js', '__return_false' );
		add_filter( 'wpcf7_load_css', '__return_false' );
	}
}, 100 );

// Preload the hero image on the front page: it is the LCP element.
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() || ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$product = yamaha_audio_hero_product();
		if ( ! $product ) {
			return;
		}
		$img_id = $product->get_image_id();
		$src    = wp_get_attachment_image_url( $img_id, 'yamaha-audio-hero' );
		if ( $src ) {
			// Same candidates and sizes as the <img>, so a phone preloads the small variant, not the 2000 px original.
			$srcset = wp_get_attachment_image_srcset( $img_id, 'yamaha-audio-hero' );
			$sizes  = '(max-width: 900px) 92vw, 560px';
			if ( $srcset ) {
				printf( '<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n", esc_url( $src ), esc_attr( $srcset ), esc_attr( $sizes ) );
			} else {
				printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $src ) );
			}
		}
	},
	2
);

// Theme colour for mobile browser chrome + the light/dark choice, applied before the first paint
// (localStorage "hx-theme" wins, then the Customizer default; "auto" follows the device setting).
add_action( 'wp_head', function () {
	$mode = (string) yamaha_audio_opt( 'theme_mode' );
	$mode = in_array( $mode, array( 'dark', 'light', 'auto' ), true ) ? $mode : 'light';
	// the browser chrome takes the violet top bar's colour in both themes
	echo '<meta name="theme-color" content="#4b1e78" data-hx-dark="#5b2a93" data-hx-light="#4b1e78">' . "\n";
	echo '<script>(function(){var m="' . esc_js( $mode ) . '",t=null;try{t=localStorage.getItem("hx-theme")}catch(e){}'
		. 'if(t!=="light"&&t!=="dark"){t=m==="auto"?(window.matchMedia&&matchMedia("(prefers-color-scheme: light)").matches?"light":"dark"):m}'
		. 'var h=document.documentElement;h.setAttribute("data-theme",t);'
		. 'var c=document.querySelector(\'meta[name="theme-color"]\');if(c){c.setAttribute("content",t==="light"?"#f5f3ee":"#121212")}})();</script>' . "\n";
}, 1 );

/**
 * Plugin stylesheets that only style secondary UI (search overlay, wishlist widgets, consent) are loaded without
 * blocking the first paint: media="print" until loaded, then switched to "all". The theme's own CSS covers the
 * header search pill and the wishlist buttons, so nothing visible waits for them.
 */
add_filter( 'style_loader_tag', function ( $tag, $handle, $href ) {
	if ( is_admin() || ! $href ) {
		return $tag;
	}
	$async = array( 'ajax-search-for-woocommerce', 'yith-wcwl', 'jquery-selectBox', 'woocommerce-tm-extra-product-options-animate' );
	$by_path = array( '/ajax-search-for-woocommerce/', '/yith-woocommerce-wishlist/', '/wp-consent-api/', '/animate.css' );
	$hit = false;
	foreach ( $by_path as $needle ) {
		if ( false !== strpos( $href, $needle ) ) {
			$hit = true;
			break;
		}
	}
	if ( ! $hit && ! in_array( $handle, $async, true ) ) {
		return $tag;
	}
	if ( false !== strpos( $tag, 'onload=' ) ) {
		return $tag;
	}
	$tag = preg_replace( '/\smedia=([\'"])[^\'"]*\1/', '', $tag );
	$tag = str_replace( ' href=', ' media="print" onload="this.media=\'all\';this.onload=null" href=', $tag );
	return $tag . '<noscript>' . str_replace( ' media="print" onload="this.media=\'all\';this.onload=null"', '', $tag ) . '</noscript>' . "\n";
}, 10, 3 );

// Classic theme: WordPress prints its global-styles CSS variables (~9 KB inline) from wp_footer, not from
// wp_enqueue_scripts, so the dequeue above never reaches it. Nothing in the theme uses --wp--preset-* tokens.
add_action( 'init', function () {
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
} );
