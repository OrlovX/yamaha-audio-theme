<?php
/**
 * One-off, run once from WP-CLI after the cookie notice moved into the theme:
 *   wp eval-file wp-content/themes/yamaha-audio/tools/consent-migrate.php
 * Brings the live "Politica de cookie-uri" page in line with the new notice (only the sentences that
 * changed; anything edited by hand elsewhere on the page stays).
 * Also: three product names get their brand, and the CC-T1A cradle becomes a simple product.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

$hx_page = get_page_by_path( 'politica-cookies' );
if ( $hx_page ) {
	$hx_reps = array(
		'<tr><td>cookie‑ul bannerului de consimțământ</td><td>reține alegerile tale privind cookie‑urile</td><td>6‑12 luni</td></tr>'
			=> "<tr><td><code>hx_consent</code></td><td>reține alegerile tale privind cookie‑urile</td><td>6 luni</td></tr>\n\t\t<tr><td><code>hx_h</code></td><td>protejează filtrele catalogului de accesări automate</td><td>1 an</td></tr>\n\t\t<tr><td><code>hxr_c</code></td><td>reține răspunsul tău la întrebarea despre reamintirile pe e‑mail</td><td>6 luni</td></tr>",
		'<p>Rețin, de exemplu, ultimul filtru folosit în catalog sau faptul că ai închis un anunț. Nu te identifică personal.</p>'
			=> '<p>Rețin, de exemplu, tema aleasă (luminoasă sau întunecată), ultimul produs afișat pe prima pagină (<code>hx_hero</code>, 7 zile) sau faptul că ai închis un anunț. Nu te identifică personal.</p>',
		'<p>Permit afișarea de reclame relevante pe alte site‑uri și măsurarea campaniilor noastre: de exemplu Meta Pixel (<code>_fbp</code>, 3 luni) sau Google Ads. Se activează doar dacă le accepți în banner.</p>'
			=> '<p>Permit măsurarea campaniilor noastre și afișarea de reclame relevante pe alte site‑uri: Google Ads (<code>_gcl_au</code>, 3 luni) și atribuirea comenzilor din magazin (<code>sbjs_*</code>, până la 6 luni), care reține din ce sursă ai ajuns pe site. Se activează doar dacă le accepți.</p>',
		'<li>La prima vizită, bannerul de cookie‑uri îți permite să accepți sau să refuzi categoriile opționale; poți reveni oricând la alegere din butonul de setări cookie‑uri afișat pe site.</li>'
			=> '<li>La prima vizită, anunțul despre cookie‑uri îți permite să accepți sau să refuzi categoriile opționale. Poți reveni oricând asupra alegerii din linkul „Setări cookie‑uri”, aflat în subsolul fiecărei pagini, sau chiar de aici: <a href="#setari-cookie">deschide setările cookie‑urilor</a>. Dacă îți retragi acordul, cookie‑urile de analiză și de marketing deja salvate sunt șterse.</li>',
		'doar cu acordul tău din bannerul de cookie‑uri, cookie‑uri de analiză'
			=> 'doar cu acordul tău, cookie‑uri de analiză',
	);
	$hx_content = $hx_page->post_content;
	$hx_done    = 0;
	foreach ( $hx_reps as $hx_old => $hx_new ) {
		if ( false !== strpos( $hx_content, $hx_old ) ) {
			$hx_content = str_replace( $hx_old, $hx_new, $hx_content );
			$hx_done++;
		}
	}
	if ( $hx_done ) {
		kses_remove_filters();
		wp_update_post( array( 'ID' => $hx_page->ID, 'post_content' => wp_slash( $hx_content ) ) );
	}
	echo 'policy page: ' . $hx_done . ' of ' . count( $hx_reps ) . " parts updated\n";
} else {
	echo "policy page: not found\n";
}

// Product names that lacked the brand.
foreach ( array( 34402 => 'MusicCast RX-A2A', 173843 => 'YH-E700A', 3888 => 'A-S2200' ) as $hx_id => $hx_key ) {
	$hx_title = (string) get_post_field( 'post_title', $hx_id );
	if ( '' !== $hx_title && false === stripos( $hx_title, 'yamaha' ) && false !== strpos( $hx_title, $hx_key ) ) {
		update_post_meta( $hx_id, '_hx_name_backup', wp_slash( $hx_title ) );
		wp_update_post( array( 'ID' => $hx_id, 'post_title' => wp_slash( str_replace( $hx_key, 'Yamaha ' . $hx_key, $hx_title ) ) ) );
	}
	echo $hx_id . ': ' . get_post_field( 'post_title', $hx_id ) . "\n";
}

// CC-T1A was a product "with options" that has no options, so nobody could add it to the cart.
$hx_pid = 177139;
$hx_p   = function_exists( 'wc_get_product' ) ? wc_get_product( $hx_pid ) : null;
if ( $hx_p && $hx_p->is_type( 'variable' ) && ! $hx_p->get_children() ) {
	$hx_price = (string) get_post_meta( $hx_pid, '_price', true );
	wp_set_object_terms( $hx_pid, 'simple', 'product_type' );
	clean_post_cache( $hx_pid );
	wc_delete_product_transients( $hx_pid );
	$hx_s = new WC_Product_Simple( $hx_pid );
	if ( '' !== $hx_price && '' === (string) $hx_s->get_regular_price() ) {
		$hx_s->set_regular_price( $hx_price );
	}
	$hx_s->save();
}
$hx_p = function_exists( 'wc_get_product' ) ? wc_get_product( $hx_pid ) : null;
if ( $hx_p ) {
	echo $hx_pid . ': ' . $hx_p->get_type() . ', price ' . $hx_p->get_price() . ', ' . $hx_p->get_stock_status() . ', purchasable ' . ( $hx_p->is_purchasable() ? 'yes' : 'no' ) . "\n";
}
