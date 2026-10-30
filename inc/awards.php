<?php
/**
 * Product awards: stored as `_hx_awards` (JSON list of {award, year, stars, source}),
 * written by hand in the product editor (or by an importer); rendered as badges. Yamaha products carry
 * EISA / What Hi-Fi? awards regularly, so the badges are worth filling in.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Awards for a product (parent for variations). Returns [] when none.
 */
function yamaha_audio_product_awards( $product ) {
	if ( ! $product ) {
		return array();
	}
	$id  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	$raw = get_post_meta( $id, '_hx_awards', true );
	if ( ! $raw ) {
		return array();
	}
	$list = is_array( $raw ) ? $raw : json_decode( $raw, true );
	if ( ! is_array( $list ) ) {
		return array();
	}
	$out = array();
	foreach ( $list as $a ) {
		if ( empty( $a['award'] ) ) {
			continue;
		}
		$out[] = array(
			'award'  => sanitize_text_field( $a['award'] ),
			'year'   => preg_replace( '/[^0-9\/\-–]/', '', (string) ( $a['year'] ?? '' ) ),
			'stars'  => (int) ( $a['stars'] ?? 0 ),
			'source' => esc_url_raw( (string) ( $a['source'] ?? '' ) ),
		);
	}
	return $out;
}

/**
 * Badge markup.
 */
function yamaha_audio_awards_html( $product, $compact = false ) {
	$awards = yamaha_audio_product_awards( $product );
	if ( ! $awards ) {
		return '';
	}
	$html = '<ul class="hx-awards' . ( $compact ? ' hx-awards--compact' : '' ) . '" aria-label="' . esc_attr__( 'Premii și recenzii', 'yamaha-audio' ) . '">';
	foreach ( $compact ? array_slice( $awards, 0, 2 ) : $awards as $a ) {
		$label = $a['award'];
		if ( $a['stars'] ) {
			$label = str_repeat( '★', min( 5, $a['stars'] ) ) . ' ' . $label;
		}
		if ( $a['year'] ) {
			$label .= ' ' . $a['year'];
		}
		$inner = '<span class="hx-award__laurel" aria-hidden="true">' . yamaha_audio_icon( 'laurel', 14 ) . '</span><span class="hx-award__text">' . esc_html( $label ) . '</span>';
		if ( $a['source'] && ! $compact ) {
			$html .= '<li><a class="hx-award" href="' . esc_url( $a['source'] ) . '" target="_blank" rel="noopener nofollow" title="' . esc_attr__( 'Vezi sursa', 'yamaha-audio' ) . '">' . $inner . '</a></li>';
		} else {
			$html .= '<li><span class="hx-award">' . $inner . '</span></li>';
		}
	}
	if ( $compact && count( $awards ) > 2 ) {
		$html .= '<li><span class="hx-award hx-award--more">+' . ( count( $awards ) - 2 ) . '</span></li>';
	}
	return $html . '</ul>';
}

// Product page: under the lead sentence.
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	echo yamaha_audio_awards_html( $product ); // phpcs:ignore
}, 21 );

/* -------------------------------------------------------------------------
 * Admin: edit awards by hand (Product → Edit → "Premii" box)
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'hx_awards', __( 'Premii și recenzii (temă)', 'yamaha-audio' ), function ( $post ) {
		$list = yamaha_audio_product_awards( wc_get_product( $post->ID ) );
		$lines = array();
		foreach ( $list as $a ) {
			$lines[] = implode( ' | ', array( $a['award'], $a['year'], $a['stars'] ? $a['stars'] : '', $a['source'] ) );
		}
		wp_nonce_field( 'hx_awards_save', 'hx_awards_nonce' );
		echo '<p class="description">' . esc_html__( 'Un premiu pe linie: Nume | An | Stele (0–5) | Link sursă. Ex.: What Hi-Fi? | 2024 | 5 | https://…', 'yamaha-audio' ) . '</p>';
		echo '<textarea name="hx_awards" rows="5" style="width:100%;font-family:monospace">' . esc_textarea( implode( "\n", $lines ) ) . '</textarea>';
	}, 'product', 'side', 'default' );
} );

add_action( 'save_post_product', function ( $post_id ) {
	if ( ! isset( $_POST['hx_awards_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hx_awards_nonce'] ) ), 'hx_awards_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$raw  = isset( $_POST['hx_awards'] ) ? wp_unslash( $_POST['hx_awards'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$list = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( empty( $parts[0] ) ) {
			continue;
		}
		$list[] = array(
			'award'  => sanitize_text_field( $parts[0] ),
			'year'   => sanitize_text_field( $parts[1] ?? '' ),
			'stars'  => (int) ( $parts[2] ?? 0 ),
			'source' => esc_url_raw( $parts[3] ?? '' ),
		);
	}
	if ( $list ) {
		update_post_meta( $post_id, '_hx_awards', wp_json_encode( $list, JSON_UNESCAPED_UNICODE ) );
	} else {
		delete_post_meta( $post_id, '_hx_awards' );
	}
} );
