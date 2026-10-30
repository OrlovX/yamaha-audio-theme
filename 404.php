<?php
/**
 * 404.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="hx-wrap hx-404">
	<span class="hx-eyebrow"><?php esc_html_e( 'Eroare 404', 'hifiexpert' ); ?></span>
	<h1 class="hx-404__title"><?php esc_html_e( 'Pagina asta a tăcut.', 'hifiexpert' ); ?></h1>
	<p class="hx-404__text"><?php esc_html_e( 'Linkul e vechi sau greșit. Caută produsul sau pornește de la categorii.', 'hifiexpert' ); ?></p>
	<div class="hx-404__search"><?php hifiexpert_search_form(); ?></div>
	<div class="hx-hero__ctas">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?><a class="hx-btn hx-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Tot magazinul', 'hifiexpert' ); ?></a><?php endif; ?>
		<a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Prima pagină', 'hifiexpert' ); ?></a>
	</div>
</div>
<?php
get_footer();
