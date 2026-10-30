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
		wp_enqueue_style(
			'yamaha-audio-fonts',
			'https://fonts.googleapis.com/css2?family=Manrope:wght@400..800&display=swap',
			array(),
			null
		);
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

// Font preconnects before the stylesheet request.
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array( 'href' => 'https://fonts.googleapis.com', 'crossorigin' );
			$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
		}
		return $urls;
	},
	10,
	2
);

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
		$src = wp_get_attachment_image_url( $product->get_image_id(), 'yamaha-audio-hero' );
		if ( $src ) {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $src ) );
		}
	},
	2
);

// Theme colour for mobile browser chrome + the light/dark choice, applied before the first paint
// (localStorage "hx-theme" wins, then the Customizer default; "auto" follows the device setting).
add_action( 'wp_head', function () {
	$mode = (string) yamaha_audio_opt( 'theme_mode' );
	$mode = in_array( $mode, array( 'dark', 'light', 'auto' ), true ) ? $mode : 'dark';
	echo '<meta name="theme-color" content="#121212" data-hx-dark="#121212" data-hx-light="#f5f3ee">' . "\n";
	echo '<script>(function(){var m="' . esc_js( $mode ) . '",t=null;try{t=localStorage.getItem("hx-theme")}catch(e){}'
		. 'if(t!=="light"&&t!=="dark"){t=m==="auto"?(window.matchMedia&&matchMedia("(prefers-color-scheme: light)").matches?"light":"dark"):m}'
		. 'var h=document.documentElement;h.setAttribute("data-theme",t);'
		. 'var c=document.querySelector(\'meta[name="theme-color"]\');if(c){c.setAttribute("content",t==="light"?"#f5f3ee":"#121212")}})();</script>' . "\n";
}, 1 );
