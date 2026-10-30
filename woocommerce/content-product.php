<?php
/**
 * Product card in loops (shop, category, related, upsells).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// the first row loads eagerly (LCP); the rest lazily
$hx_loop_index = (int) wc_get_loop_prop( 'loop' );
yamaha_audio_product_card( $product, array( 'lazy' => $hx_loop_index > 3 ) );
