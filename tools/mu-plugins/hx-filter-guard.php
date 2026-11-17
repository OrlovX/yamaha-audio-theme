<?php
/**
 * Plugin Name: HX filter guard
 * Description: Runs the theme's filter gate before WordPress loads its plugins, so a refused crawler costs a few milliseconds instead of a full page load. The gate itself lives in the theme: inc/filter-guard.php.
 * Version: 2.0.0
 *
 * Install: copy this file to wp-content/mu-plugins/hx-filter-guard.php. Delete it there to switch the
 * early check off; the theme still runs the same gate later, on init.
 *
 * It only loads the gate from the yamaha-audio theme folder, so the rules are in one place and arrive
 * with the theme's updates. If that folder is missing, this file does nothing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

( static function () {
	$gate = WP_CONTENT_DIR . '/themes/yamaha-audio/inc/filter-guard.php';
	if ( empty( $_GET['f'] ) || ! is_readable( $gate ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	require_once $gate;
	if ( function_exists( 'hx_filter_guard' ) ) {
		hx_filter_guard( true );
	}
} )();
