<?php
/**
 * Yamaha Audio theme bootstrap (derived from the Yamaha Audio theme).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

define( 'YAMAHA_AUDIO_VERSION', '0.2.0' ); // keep in step with style.css; bumping re-runs the one-time logo pick
define( 'YAMAHA_AUDIO_DIR', get_template_directory() );
define( 'YAMAHA_AUDIO_URI', get_template_directory_uri() );

require YAMAHA_AUDIO_DIR . '/inc/setup.php';
require YAMAHA_AUDIO_DIR . '/inc/assets.php';
require YAMAHA_AUDIO_DIR . '/inc/helpers.php';
require YAMAHA_AUDIO_DIR . '/inc/customizer.php';
require YAMAHA_AUDIO_DIR . '/inc/menu-walker.php';
require YAMAHA_AUDIO_DIR . '/inc/template-tags.php';
require YAMAHA_AUDIO_DIR . '/inc/newsletter.php';
require YAMAHA_AUDIO_DIR . '/inc/legal.php';
require YAMAHA_AUDIO_DIR . '/inc/seo.php';

if ( class_exists( 'WooCommerce' ) ) {
	require YAMAHA_AUDIO_DIR . '/inc/woocommerce.php';
	require YAMAHA_AUDIO_DIR . '/inc/filters.php';
	require YAMAHA_AUDIO_DIR . '/inc/awards.php';
}
