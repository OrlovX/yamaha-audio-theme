<?php
/**
 * Yamaha Audio theme bootstrap (derived from the HiFi Expert theme).
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

define( 'HIFIEXPERT_VERSION', '0.2.0' ); // keep in step with style.css; bumping re-runs the one-time logo pick
define( 'HIFIEXPERT_DIR', get_template_directory() );
define( 'HIFIEXPERT_URI', get_template_directory_uri() );

require HIFIEXPERT_DIR . '/inc/setup.php';
require HIFIEXPERT_DIR . '/inc/assets.php';
require HIFIEXPERT_DIR . '/inc/helpers.php';
require HIFIEXPERT_DIR . '/inc/customizer.php';
require HIFIEXPERT_DIR . '/inc/menu-walker.php';
require HIFIEXPERT_DIR . '/inc/template-tags.php';
require HIFIEXPERT_DIR . '/inc/newsletter.php';
require HIFIEXPERT_DIR . '/inc/legal.php';
require HIFIEXPERT_DIR . '/inc/seo.php';

if ( class_exists( 'WooCommerce' ) ) {
	require HIFIEXPERT_DIR . '/inc/woocommerce.php';
	require HIFIEXPERT_DIR . '/inc/filters.php';
	require HIFIEXPERT_DIR . '/inc/awards.php';
	if ( is_admin() ) {
		require HIFIEXPERT_DIR . '/inc/brand-admin.php';
	}
}
