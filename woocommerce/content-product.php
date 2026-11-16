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

// The first row loads eagerly (two of them with priority: one of them is the LCP image), the rest
// lazily. Own counter: WooCommerce's 'loop' prop only advances when wc_product_class() is used, so
// every card used to be "the first" and all 24 photos loaded at once with high priority.
$hx_loop_index = (int) wc_get_loop_prop( 'hx_index', 0 );
wc_set_loop_prop( 'hx_index', $hx_loop_index + 1 );
$hx_below_fold = is_product() || is_cart(); // related products and cross-sells sit far down the page.
yamaha_audio_product_card( $product, array( 'lazy' => $hx_below_fold || $hx_loop_index > 3, 'priority' => ! $hx_below_fold && $hx_loop_index < 2 ) );
