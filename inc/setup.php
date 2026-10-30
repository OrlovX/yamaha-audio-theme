<?php
/**
 * Theme setup: supports, menus, image sizes, cleanup.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'hifiexpert', HIFIEXPERT_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 56,
				'width'       => 240,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );

		// WooCommerce.
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 600,
				'single_image_width'    => 1200,
				'product_grid'          => array(
					'default_columns' => 3,
					'min_columns'     => 2,
					'max_columns'     => 4,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary'     => __( 'Meniu principal (mega menu)', 'hifiexpert' ),
				'topbar'      => __( 'Bară de sus (linkuri mici: Blog, Branduri, Consignație…)', 'hifiexpert' ),
				'footer-shop' => __( 'Footer: Magazin', 'hifiexpert' ),
				'footer-info' => __( 'Footer: Informații utile', 'hifiexpert' ),
			)
		);

		add_image_size( 'hifiexpert-card', 600, 600, false );
		add_image_size( 'hifiexpert-hero', 1400, 1000, false );
		add_image_size( 'hifiexpert-post', 900, 600, true );
	}
);

add_action(
	'widgets_init',
	function () {
		register_sidebar(
			array(
				'name'          => __( 'Filtre magazin (coloana din stânga)', 'hifiexpert' ),
				'id'            => 'shop-filters',
				'description'   => __( 'Adaugă aici widget-urile WooCommerce de filtrare (atribute, preț). Filtrul de brand și „În stoc” sunt deja incluse de temă.', 'hifiexpert' ),
				'before_widget' => '<div id="%1$s" class="hx-filter %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="hx-filter__title">',
				'after_title'   => '</h3>',
			)
		);
	}
);

/**
 * Trim the <head>: nothing a shop needs.
 */
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'the_generator', '__return_empty_string' );
	}
);

/**
 * One-time: prefer the tightly cropped PNG badge over the SVG with the oversized canvas.
 * Runs once per theme version; the user can still change the logo in the Customizer afterwards.
 */
add_action( 'init', function () {
	if ( get_option( 'hifiexpert_logo_migrated' ) === HIFIEXPERT_VERSION ) {
		return;
	}
	$current = (int) get_theme_mod( 'custom_logo' );
	$png     = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/png', 'posts_per_page' => 1, 'fields' => 'ids', 'title' => 'Logo_vector_hifiexpert-v2' ) );
	if ( $png && ( ! $current || 'image/svg+xml' === get_post_mime_type( $current ) ) ) {
		set_theme_mod( 'custom_logo', (int) $png[0] );
	}
	update_option( 'hifiexpert_logo_migrated', HIFIEXPERT_VERSION, false );
} );

// The "brands" and "promotii" pages get their templates automatically (their old content is Flatsome shortcodes).
add_filter( 'template_include', function ( $template ) {
	$map = array( 'brands' => 'page-brands.php', 'branduri' => 'page-brands.php', 'promotii' => 'page-promotii.php', 'promotii-2' => 'page-promotii.php', 'oferte' => 'page-promotii.php', 'contact' => 'page-contact.php' );
	if ( is_page() ) {
		$slug = get_queried_object() ? get_queried_object()->post_name : '';
		if ( isset( $map[ $slug ] ) && ( 'page-promotii.php' !== $map[ $slug ] || class_exists( 'WooCommerce' ) ) ) {
			$t = locate_template( $map[ $slug ] );
			if ( $t ) {
				return $t;
			}
		}
	}
	return $template;
}, 20 );

/**
 * WooCommerce pages carry English titles from the old install: show Romanian ones.
 */
function hifiexpert_page_title_override( $id, $title ) {
	if ( ! $id || ! class_exists( 'WooCommerce' ) ) {
		return $title;
	}
	static $map = null;
	if ( null === $map ) {
		$map = array(
			(int) wc_get_page_id( 'cart' )      => __( 'Coșul tău', 'hifiexpert' ),
			(int) wc_get_page_id( 'checkout' )  => __( 'Finalizare comandă', 'hifiexpert' ),
			(int) wc_get_page_id( 'myaccount' ) => __( 'Contul meu', 'hifiexpert' ),
			(int) wc_get_page_id( 'shop' )      => __( 'Magazin', 'hifiexpert' ),
		);
		$wl = get_page_by_path( 'wishlist' );
		if ( $wl ) {
			$map[ (int) $wl->ID ] = __( 'Lista de dorințe', 'hifiexpert' );
		}
		$br = get_page_by_path( 'brands' );
		if ( $br ) {
			$map[ (int) $br->ID ] = __( 'Branduri', 'hifiexpert' );
		}
		$pr = get_page_by_path( 'promotii' );
		if ( $pr ) {
			$map[ (int) $pr->ID ] = __( 'Promoții', 'hifiexpert' );
		}
		unset( $map[0], $map[-1] );
	}
	if ( isset( $map[ (int) $id ] ) && preg_match( '/^(cart|checkout|my account|shop|wishlist|brands|promotii)$/i', trim( (string) $title ) ) ) {
		return $map[ (int) $id ];
	}
	return $title;
}
add_filter( 'the_title', function ( $title, $id = 0 ) {
	return is_admin() ? $title : hifiexpert_page_title_override( (int) $id, $title );
}, 10, 2 );
// <title> too (WordPress and Rank Math build it from the raw post title).
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_page() && ! empty( $parts['title'] ) ) {
		$parts['title'] = hifiexpert_page_title_override( get_queried_object_id(), $parts['title'] );
	}
	return $parts;
} );
add_filter( 'rank_math/frontend/title', function ( $title ) {
	if ( is_page() && preg_match( '/^(cart|checkout|my account|shop|wishlist|brands|promotii)(\s*[-–|].*)?$/i', (string) $title, $m ) ) {
		$new = hifiexpert_page_title_override( get_queried_object_id(), $m[1] );
		return $new . ( $m[2] ?? '' );
	}
	return $title;
} );

/**
 * The cart page block markup was saved with English headings: translate them on output.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! class_exists( 'WooCommerce' ) || ! ( is_cart() || is_checkout() ) ) {
		return $content;
	}
	return str_replace(
		array( 'Your cart is currently empty!', 'New in store', 'You may be interested in…', 'You may be interested in&hellip;', 'Browse store', 'Start shopping', 'Return to shop' ),
		array( __( 'Coșul tău este gol', 'hifiexpert' ), __( 'Noutăți în magazin', 'hifiexpert' ), __( 'S‑ar putea să te intereseze', 'hifiexpert' ), __( 'S‑ar putea să te intereseze', 'hifiexpert' ), __( 'Vezi magazinul', 'hifiexpert' ), __( 'Vezi magazinul', 'hifiexpert' ), __( 'Înapoi la magazin', 'hifiexpert' ) ),
		$content
	);
}, 9 );

/**
 * Prices everywhere as "2.799 lei" (store settings may still say "2799lei").
 */
add_filter( 'pre_option_woocommerce_price_thousand_sep', function () { return '.'; } );
add_filter( 'pre_option_woocommerce_price_decimal_sep', function () { return ','; } );
add_filter( 'pre_option_woocommerce_currency_pos', function () { return 'right_space'; } );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'hx';
	if ( function_exists( 'hifiexpert_campaign' ) && hifiexpert_campaign() ) {
		$classes[] = 'hx-camp hx-camp--' . hifiexpert_campaign()['style'];
	}
	if ( class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$classes[] = 'hx-checkout-flow';
	}
	return $classes;
} );
