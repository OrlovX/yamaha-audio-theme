<?php
/**
 * Shop / category / brand archive: title block, sub-category chips, faceted filter rail, grid.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$term     = is_tax() ? get_queried_object() : null;
$shop_url = wc_get_page_permalink( 'shop' );
$base_url = hifiexpert_filter_url( array( 'tax' => array_fill_keys( array_keys( hifiexpert_filter_state()['tax'] ), array() ), 'min' => 0, 'max' => 0, 'stock' => false, 'showroom' => false ) );

// Sub-category chips: children of the current category, or top-level on the shop page.
$chips = array();
if ( is_shop() || ( $term && 'product_cat' === $term->taxonomy ) ) {
	$chips = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term ? $term->term_id : 0, 'hide_empty' => true, 'orderby' => 'menu_order', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
	if ( is_wp_error( $chips ) ) {
		$chips = array();
	}
	if ( is_shop() ) {
		$chips = array_values( array_filter( $chips, function ( $c ) { return 'uncategorized' !== $c->slug; } ) );
	}
}
$wa    = hifiexpert_whatsapp_url( __( 'Bună! Am nevoie de o recomandare pentru…', 'hifiexpert' ) );
$total = (int) wc_get_loop_prop( 'total' );
?>

<div class="hx-wrap hx-shop">

	<?php woocommerce_breadcrumb(); ?>

	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<h1 class="hx-shop__title"><?php woocommerce_page_title(); ?></h1>
			<?php
			if ( $term && $term->description ) {
				echo '<p class="hx-shop__text">' . wp_kses_post( wp_trim_words( $term->description, 40 ) ) . '</p>';
			} elseif ( is_shop() ) {
				echo '<p class="hx-shop__text">' . esc_html__( 'Tot ce vindem: în stoc, ascultat de noi, livrat în 24h.', 'hifiexpert' ) . '</p>';
			}
			?>
		</div>
		<?php if ( $total ) : ?>
			<span class="hx-shop__count"><?php printf( esc_html( _n( '%d produs', '%d produse', $total, 'hifiexpert' ) ), $total ); ?></span>
		<?php endif; ?>
	</header>

	<?php if ( $chips ) : ?>
		<div class="hx-chips" role="navigation" aria-label="<?php esc_attr_e( 'Subcategorii', 'hifiexpert' ); ?>">
			<?php foreach ( $chips as $chip ) : ?>
				<a class="hx-chip" href="<?php echo esc_url( get_term_link( $chip ) ); ?>"><?php echo esc_html( $chip->name ); ?> <small><?php echo (int) $chip->count; ?></small></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="hx-shop__layout">

		<aside class="hx-rail" id="hx-rail" aria-label="<?php esc_attr_e( 'Filtre', 'hifiexpert' ); ?>">
			<div class="hx-rail__head">
				<span><?php esc_html_e( 'Filtre', 'hifiexpert' ); ?></span>
				<button class="hx-iconbtn" type="button" data-hx-rail-close aria-label="<?php esc_attr_e( 'Închide filtrele', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'close', 20 ); ?></button>
			</div>
			<?php hifiexpert_render_filters(); ?>
			<?php if ( is_active_sidebar( 'shop-filters' ) ) { dynamic_sidebar( 'shop-filters' ); } ?>
			<div class="hx-rail__apply"><button class="hx-btn hx-btn--primary" type="button" data-hx-rail-close><?php printf( esc_html( _n( 'Vezi %d produs', 'Vezi %d produse', $total, 'hifiexpert' ) ), $total ); ?></button></div>
		</aside>

		<div class="hx-shop__results">
			<div class="hx-toolbar">
				<div class="hx-toolbar__left">
					<button class="hx-chip hx-chip--filter" type="button" data-hx-rail-open><?php echo hifiexpert_icon( 'filter', 16 ); ?><?php esc_html_e( 'Filtre', 'hifiexpert' ); ?></button>
					<?php hifiexpert_render_applied_chips(); ?>
				</div>
				<div class="hx-toolbar__right"><?php woocommerce_catalog_ordering(); ?></div>
			</div>

			<?php
			if ( woocommerce_product_loop() ) {
				woocommerce_product_loop_start();
				while ( have_posts() ) {
					the_post();
					do_action( 'woocommerce_shop_loop' );
					wc_get_template_part( 'content', 'product' );
				}
				if ( $wa ) :
					?>
					<li class="hx-helpcard">
						<div>
							<span class="hx-eyebrow"><?php esc_html_e( 'Nu știi ce ți se potrivește?', 'hifiexpert' ); ?></span>
							<span class="hx-helpcard__title"><?php esc_html_e( 'Spune‑ne camera, muzica și bugetul. Îți compunem sistemul și îl ai acasă în 24 de ore.', 'hifiexpert' ); ?></span>
						</div>
						<div>
							<a class="hx-btn hx-btn--primary hx-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Scrie‑ne pe WhatsApp', 'hifiexpert' ); ?></a>
							<span class="hx-helpcard__note"><?php echo esc_html( hifiexpert_opt( 'topbar_hours' ) ); ?></span>
						</div>
					</li>
					<?php
				endif;
				woocommerce_product_loop_end();
				woocommerce_pagination();
			} else {
				echo '<p class="hx-shop__empty">' . esc_html__( 'Niciun produs nu se potrivește filtrelor alese.', 'hifiexpert' ) . ' <a href="' . $base_url . '">' . esc_html__( 'Resetează filtrele', 'hifiexpert' ) . '</a></p>'; // phpcs:ignore
			}
			?>
		</div>
	</div>
</div>

<?php get_footer( 'shop' ); ?>
