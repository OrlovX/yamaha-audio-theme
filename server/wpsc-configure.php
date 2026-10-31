<?php
/**
 * Configure WP Super Cache for the shop (simple mode, WooCommerce-safe). Run after the plugin is active:
 *   sudo -u www-data wp eval-file wp-content/themes/yamaha-audio/server/wpsc-configure.php
 *
 * Simple mode = cached HTML served by PHP before WordPress loads (~20–40 ms), no .htaccess rules. Pages are not
 * cached for logged-in users, for visitors with a cart/session/wishlist cookie, or for URLs with a query string
 * (shop filters, search, payment return). Expiry 30 min; the theme purges product/home/category pages on
 * stock and product changes; preload keeps the whole catalogue warm.
 *
 * @package yamaha-audio
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run through WP-CLI (wp eval-file).\n" );
}
if ( ! function_exists( 'wp_cache_setting' ) ) {
	WP_CLI::error( 'WP Super Cache is not active (wp plugin activate wp-super-cache).' );
}
global $wp_cache_config_file;
if ( empty( $wp_cache_config_file ) || ! file_exists( $wp_cache_config_file ) ) {
	if ( function_exists( 'wp_cache_verify_config_file' ) ) {
		wp_cache_verify_config_file();
	}
}
if ( empty( $wp_cache_config_file ) || ! file_exists( $wp_cache_config_file ) ) {
	WP_CLI::error( 'wp-cache-config.php is missing; open Settings → WP Super Cache once in wp-admin, then re-run.' );
}
if ( ! is_writable( $wp_cache_config_file ) ) {
	WP_CLI::error( "Config file not writable: $wp_cache_config_file" );
}

$settings = array(
	'cache_enabled'                => 1,
	'super_cache_enabled'          => 1,
	'wp_cache_mod_rewrite'         => 0,     // simple mode
	'wp_cache_not_logged_in'       => 2,     // never cache for logged-in users
	'wp_cache_no_cache_for_get'    => 1,     // ?f[...] filters, ?s=, payment returns
	'cache_compression'            => 1,
	'wp_supercache_304'            => 1,
	'wp_cache_mobile_enabled'      => 0,     // responsive theme, one HTML for all
	'wp_cache_clear_on_post_edit'  => 0,     // the theme purges product/home/category pages itself
	'wp_cache_refresh_single_only' => 0,
	'wp_cache_front_page_checks'   => 0,
	'wp_cache_make_known_anon'     => 0,
	'wp_cache_mutex_disabled'      => 0,
	'cache_max_time'               => 1800,
	'cache_schedule_type'          => 'interval',
	'cache_time_interval'          => 600,
	'cache_gc_email_me'            => 0,
	'wp_cache_preload_on'          => 1,
	'wp_cache_preload_interval'    => 60,
	'wp_cache_preload_posts'       => 'all',
	'wp_cache_preload_taxonomies'  => 1,
	'wp_cache_preload_email_me'    => 0,
	'wp_cache_preload_email_volume' => 'none',
	'wpsc_rejected_cookies'        => array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash', 'wp_woocommerce_session', 'yith_wcwl_session', 'wordpress_logged_in' ),
	'cache_rejected_uri'           => array( 'wp-.*\.php', 'index\.php', 'cos', 'finalizare-comanda', 'contul-meu', 'cart', 'checkout', 'my-account', 'wishlist', 'track-order', 'status-plata-online', 'wc-api', 'wp-json' ),
	'cache_acceptable_files'       => array( 'wp-comments-popup.php', 'wp-links-opml.php', 'wp-locations.php' ),
	'wp_cache_pages'               => array( 'single' => 0, 'pages' => 0, 'archives' => 0, 'tag' => 0, 'frontpage' => 0, 'home' => 0, 'category' => 0, 'feed' => 1, 'author' => 0, 'search' => 1 ),
);
foreach ( $settings as $k => $v ) {
	wp_cache_setting( $k, $v );
}

// drop-in + cache directory
if ( function_exists( 'wp_cache_verify_cache_dir' ) ) {
	wp_cache_verify_cache_dir();
}
if ( function_exists( 'wp_cache_create_advanced_cache' ) ) {
	wp_cache_create_advanced_cache();
}
if ( ! file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
	WP_CLI::warning( 'advanced-cache.php was not created (another cache plugin still active? wp-content not writable?).' );
}
if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
	WP_CLI::warning( "WP_CACHE is not true in wp-config.php: add  define( 'WP_CACHE', true );  near the top." );
}

// schedules: garbage collection + first preload
if ( ! wp_next_scheduled( 'wp_cache_gc' ) ) {
	wp_schedule_single_event( time() + 600, 'wp_cache_gc' );
}
if ( ! wp_next_scheduled( 'wp_cache_full_preload_hook' ) ) {
	wp_schedule_single_event( time() + 120, 'wp_cache_full_preload_hook' );
}
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
}
WP_CLI::success( 'WP Super Cache configured: simple mode, 30 min expiry, WooCommerce cookies/URLs excluded, preload every 60 min.' );
