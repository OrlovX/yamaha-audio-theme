<?php
/**
 * 404.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="hx-wrap hx-404">
	<span class="hx-eyebrow"><?php esc_html_e( 'Eroare 404', 'yamaha-audio' ); ?></span>
	<h1 class="hx-404__title"><?php esc_html_e( 'Pagina asta a tăcut.', 'yamaha-audio' ); ?></h1>
	<p class="hx-404__text"><?php esc_html_e( 'Linkul e vechi sau greșit. Caută produsul sau pornește de la categorii.', 'yamaha-audio' ); ?></p>
	<div class="hx-404__search"><?php yamaha_audio_search_form(); ?></div>
	<div class="hx-hero__ctas">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?><a class="hx-btn hx-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Tot magazinul', 'yamaha-audio' ); ?></a><?php endif; ?>
		<a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Prima pagină', 'yamaha-audio' ); ?></a>
	</div>
</div>
<?php
get_footer();
