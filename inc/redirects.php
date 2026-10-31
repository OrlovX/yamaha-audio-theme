<?php
/**
 * Legacy URLs → current pages (301). Landing pages from past campaigns keep their inbound links and
 * search listings; they now land on the Promoții page instead of a dated page (or a 404).
 * Extend or override with the 'yamaha_audio_legacy_redirects' filter (request path => URL).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

function yamaha_audio_legacy_redirects() {
	$promo = home_url( '/promotii/' );
	$map   = array(
		'black-friday'            => $promo,
		'black-friday-2024'       => $promo,
		'black-friday-2025'       => $promo,
		'craciun-2022'            => $promo,
		'craciun_2021'            => $promo,
		'promotii-primavara-2023' => $promo,
		'promotii-active'         => $promo,
	);
	return apply_filters( 'yamaha_audio_legacy_redirects', $map );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_preview() ) {
		return;
	}
	global $wp;
	$path = strtolower( trim( (string) ( $wp->request ?? '' ), '/' ) );
	if ( '' === $path ) {
		return;
	}
	$map = yamaha_audio_legacy_redirects();
	if ( isset( $map[ $path ] ) && $map[ $path ] ) {
		$target = $map[ $path ];
		if ( untrailingslashit( $target ) === untrailingslashit( home_url( '/' . $path ) ) ) {
			return; // would loop
		}
		wp_safe_redirect( $target, 301 );
		exit;
	}
}, 1 );
