<?php
/**
 * Homepage: hero, categories, products, bundle, trust, audiophile band, blog.
 * Everything is rendered from WooCommerce data + Customizer copy (no page builder).
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! class_exists( 'WooCommerce' ) ) {
	get_template_part( 'index' );
	get_footer();
	return;
}

$hero     = hifiexpert_hero_product();
$cta_url  = hifiexpert_opt( 'hero_cta_url' ) ?: add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) );
$cta2_url = hifiexpert_opt( 'hero_cta2_url' );
$cats     = hifiexpert_home_categories( 6 );
$products = hifiexpert_home_products( (int) hifiexpert_opt( 'home_products_count' ) + 1 );
if ( $hero ) {
	$products = array_values( array_filter( $products, function ( $p ) use ( $hero ) { return $p->get_id() !== $hero->get_id(); } ) );
}
$products = array_slice( $products, 0, (int) hifiexpert_opt( 'home_products_count' ) );
$rows     = hifiexpert_home_rows();
$brands   = hifiexpert_brand_terms( hifiexpert_opt( 'audio_brands' ) );
$wa       = hifiexpert_whatsapp_url( __( 'Bună! Aș vrea să programez o audiție în showroom.', 'hifiexpert' ) );
$rates    = (int) hifiexpert_opt( 'rates_count' );
?>

<section class="hx-hero hx-wrap" id="top">
	<div class="hx-hero__copy">
		<?php if ( hifiexpert_campaign() ) : ?>
			<span class="hx-eyebrow hx-eyebrow--camp"><b><?php echo esc_html( hifiexpert_campaign()['name'] ); ?></b><?php echo hifiexpert_campaign()['tagline'] ? ' · ' . esc_html( hifiexpert_campaign()['tagline'] ) : ''; ?></span>
		<?php elseif ( hifiexpert_opt( 'hero_eyebrow' ) ) : ?>
			<span class="hx-eyebrow"><?php echo esc_html( hifiexpert_opt( 'hero_eyebrow' ) ); ?></span>
		<?php endif; ?>
		<h1 class="hx-hero__title"><?php echo esc_html( hifiexpert_opt( 'hero_title' ) ); ?></h1>
		<p class="hx-hero__text"><?php echo wp_kses_post( hifiexpert_opt( 'hero_text' ) ); ?></p>
		<div class="hx-hero__ctas">
			<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( hifiexpert_opt( 'hero_cta_label' ) ); ?></a>
			<?php if ( $cta2_url && hifiexpert_opt( 'hero_cta2_label' ) ) : ?>
				<a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( hifiexpert_opt( 'hero_cta2_label' ) ); ?></a>
			<?php endif; ?>
		</div>
		<ul class="hx-hero__proof">
			<li><?php echo hifiexpert_icon( 'truck', 16 ); ?><?php printf( esc_html__( 'Transport gratuit peste %d lei', 'hifiexpert' ), (int) hifiexpert_opt( 'free_shipping_min' ) ); ?></li>
			<li><?php echo hifiexpert_icon( 'card', 16 ); ?><?php printf( esc_html__( '%d rate fără dobândă', 'hifiexpert' ), $rates ); ?></li>
			<li><?php echo hifiexpert_icon( 'pin', 16 ); ?><?php esc_html_e( 'Showroom în București', 'hifiexpert' ); ?></li>
		</ul>
	</div>

	<?php if ( $hero ) : ?>
		<?php
		$hero_brand   = hifiexpert_product_brand( $hero );
		$hero_rate    = hifiexpert_installment( hifiexpert_active_price( $hero ) );
		$hero_percent = hifiexpert_discount_percent( $hero );
		$hero_lead    = hifiexpert_product_lead( $hero, 26 );
		$hero_regular = $hero->is_type( 'variable' ) ? (float) $hero->get_variation_regular_price( 'min' ) : (float) $hero->get_regular_price();
		?>
		<div class="hx-spot">
			<a class="hx-spot__media" href="<?php echo esc_url( $hero->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
				<figure class="hx-stage hx-stage--spot">
					<?php echo wp_get_attachment_image( $hero->get_image_id(), 'hifiexpert-hero', false, array( 'class' => 'hx-hero__img', 'alt' => $hero->get_name(), 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
				</figure>
				<?php if ( $hero_percent && hifiexpert_campaign() ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg hx-pill--camp"><small><?php echo esc_html( hifiexpert_campaign()['badge'] ); ?></small>−<?php echo (int) $hero_percent; ?>%</span>
				<?php elseif ( $hero_percent ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg">−<?php echo (int) $hero_percent; ?>%</span><?php endif; ?>
			</a>
			<div class="hx-spot__body">
				<?php if ( $hero_brand ) : ?><span class="hx-eyebrow hx-eyebrow--muted"><?php echo esc_html( $hero_brand->name ); ?></span><?php endif; ?>
				<h2 class="hx-spot__title"><a href="<?php echo esc_url( $hero->get_permalink() ); ?>"><?php echo esc_html( $hero->get_name() ); ?></a></h2>
				<?php if ( $hero_lead ) : ?><p class="hx-spot__lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
				<div class="hx-spot__price">
					<span class="hx-spot__now"><?php echo esc_html( hifiexpert_lei( hifiexpert_active_price( $hero ) ) ); ?></span>
					<?php if ( $hero->is_on_sale() && $hero_regular > hifiexpert_active_price( $hero ) ) : ?><s><?php echo esc_html( hifiexpert_lei( $hero_regular ) ); ?></s><?php endif; ?>
				</div>
				<?php if ( $hero_rate ) : ?>
					<span class="hx-spot__rate"><?php printf( esc_html__( 'sau %1$d × %2$s %3$s', 'hifiexpert' ), $rates, esc_html( hifiexpert_lei( $hero_rate ) ), esc_html( hifiexpert_opt( 'rates_label' ) ) ); ?></span>
				<?php endif; ?>
				<div class="hx-spot__ctas">
					<?php hifiexpert_loop_button( $hero ); ?>
					<a class="hx-link" href="<?php echo esc_url( $hero->get_permalink() ); ?>"><?php esc_html_e( 'Detalii', 'hifiexpert' ); ?> →</a>
				</div>
				<?php echo hifiexpert_stock_badge( $hero ); // phpcs:ignore ?>
			</div>
		</div>
	<?php endif; ?>
</section>

<?php if ( $cats ) : ?>
<section class="hx-section hx-wrap">
	<?php hifiexpert_section_head( __( 'Cumpără după categorie', 'hifiexpert' ), '', wc_get_page_permalink( 'shop' ), __( 'Tot magazinul', 'hifiexpert' ) ); ?>
	<div class="hx-cats">
		<?php foreach ( $cats as $term ) : ?>
			<?php $img_id = hifiexpert_category_image_id( $term ); ?>
			<a class="hx-cat<?php echo $img_id ? ' hx-cat--img' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<?php if ( $img_id ) : ?>
					<span class="hx-cat__bg" aria-hidden="true"><?php echo wp_get_attachment_image( $img_id, 'hifiexpert-card', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?></span>
				<?php endif; ?>
				<span class="hx-cat__sub"><?php echo esc_html( hifiexpert_category_children_label( $term ) ); ?></span>
				<span class="hx-cat__name"><?php echo esc_html( $term->name ); ?> <span class="hx-cat__arrow" aria-hidden="true">→</span></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $products ) : ?>
<section class="hx-section hx-wrap" id="promotii">
	<?php hifiexpert_section_head( hifiexpert_opt( 'home_products_title' ), hifiexpert_opt( 'home_products_text' ), add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ), __( 'Vezi toate', 'hifiexpert' ) ); ?>
	<ul class="hx-grid hx-grid--4 products">
		<?php
		foreach ( $products as $i => $p ) {
			hifiexpert_product_card( $p, array( 'lazy' => $i > 3 ) );
		}
		?>
	</ul>
</section>
<?php endif; ?>

<?php if ( $rates > 1 ) : ?>
<section class="hx-wrap">
	<div class="hx-ribbon">
		<div class="hx-ribbon__text">
			<strong><?php printf( esc_html__( 'Până la %d rate fără dobândă cu Card Avantaj', 'hifiexpert' ), $rates ); ?></strong>
			<span><?php esc_html_e( 'Alegi rata la plată, online sau în showroom. Fără costuri ascunse, fără avans.', 'hifiexpert' ); ?></span>
		</div>
		<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( $cta_url ); ?>"><?php esc_html_e( 'Vezi produsele', 'hifiexpert' ); ?></a>
	</div>
</section>
<?php endif; ?>

<?php foreach ( $rows as $row ) : ?>
	<?php
	$row_products = hifiexpert_brand_products( $row['slug'], 4, $row['tax'] );
	if ( ! $row_products ) {
		continue;
	}
	?>
	<section class="hx-section hx-wrap">
		<?php hifiexpert_section_head( $row['title'], '', get_term_link( $row['term'] ), sprintf( __( 'Tot %s', 'hifiexpert' ), $row['term']->name ) ); ?>
		<ul class="hx-grid hx-grid--4 products">
			<?php foreach ( $row_products as $p ) { hifiexpert_product_card( $p ); } ?>
		</ul>
	</section>
<?php endforeach; ?>

<?php if ( hifiexpert_opt( 'bundle_enabled' ) && hifiexpert_opt( 'bundle_title' ) ) : ?>
<section class="hx-section hx-wrap">
	<div class="hx-bundle">
		<div class="hx-bundle__copy">
			<span class="hx-eyebrow"><?php echo esc_html( hifiexpert_opt( 'bundle_eyebrow' ) ); ?></span>
			<h2 class="hx-bundle__title"><?php echo esc_html( hifiexpert_opt( 'bundle_title' ) ); ?></h2>
			<p class="hx-bundle__text"><?php echo wp_kses_post( hifiexpert_opt( 'bundle_text' ) ); ?></p>
			<?php $items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) hifiexpert_opt( 'bundle_items' ) ) ) ); ?>
			<?php if ( $items ) : ?>
				<ul class="hx-bundle__items">
					<?php foreach ( $items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="hx-bundle__ctas">
				<?php if ( hifiexpert_opt( 'bundle_cta_url' ) ) : ?>
					<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( hifiexpert_opt( 'bundle_cta_url' ) ); ?>"><?php echo esc_html( hifiexpert_opt( 'bundle_cta_label' ) ); ?></a>
				<?php elseif ( $wa ) : ?>
					<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Cere oferta pe WhatsApp', 'hifiexpert' ); ?></a>
				<?php endif; ?>
				<?php if ( hifiexpert_opt( 'bundle_price_note' ) ) : ?><span class="hx-bundle__note"><?php echo esc_html( hifiexpert_opt( 'bundle_price_note' ) ); ?></span><?php endif; ?>
			</div>
		</div>
		<figure class="hx-stage hx-stage--bundle">
			<?php
			$bundle_img = (int) hifiexpert_opt( 'bundle_image' );
			if ( $bundle_img ) {
				echo wp_get_attachment_image( $bundle_img, 'hifiexpert-hero', false, array( 'loading' => 'lazy', 'alt' => '' ) );
			} elseif ( $products ) {
				echo wp_get_attachment_image( $products[0]->get_image_id(), 'hifiexpert-hero', false, array( 'loading' => 'lazy', 'alt' => '' ) );
			}
			?>
		</figure>
	</div>
</section>
<?php endif; ?>

<section class="hx-section hx-wrap">
	<?php hifiexpert_trust_strip(); ?>
</section>

<?php
$wa_system = hifiexpert_whatsapp_url( __( 'Bună! Vreau un sistem high‑end în 24 de ore. Camera, muzica și bugetul meu:', 'hifiexpert' ) );
$logo_items = array();
foreach ( $brands as $b ) {
	$img = hifiexpert_brand_logo_img( $b, 34 );
	$logo_items[] = '<a class="hx-logos__item' . ( $img ? '' : ' hx-logos__item--text' ) . '" href="' . esc_url( get_term_link( $b ) ) . '" title="' . esc_attr( $b->name ) . '">' . ( $img ?: '<span>' . esc_html( $b->name ) . '</span>' ) . '</a>';
}
?>
<section class="hx-band hx-band--highend" id="branduri">
	<div class="hx-band__glow" aria-hidden="true"></div>
	<div class="hx-wrap hx-band__inner">
		<div class="hx-band__grid">
			<div class="hx-band__copy">
				<span class="hx-eyebrow"><?php echo esc_html( hifiexpert_opt( 'audio_eyebrow' ) ); ?></span>
				<h2 class="hx-band__title"><?php echo esc_html( hifiexpert_opt( 'audio_title' ) ); ?></h2>
				<ul class="hx-band__steps" aria-label="<?php esc_attr_e( 'Cum funcționează', 'hifiexpert' ); ?>">
					<li><strong>1</strong><span><?php esc_html_e( 'Ne spui camera, muzica și bugetul', 'hifiexpert' ); ?></span></li>
					<li><strong>2</strong><span><?php esc_html_e( 'Îți propunem sistemul, cu prețuri ferme', 'hifiexpert' ); ?></span></li>
					<li><strong>3</strong><span><?php esc_html_e( 'Îl livrăm în 24 de ore, cu tot know‑how‑ul de instalare și reglaj', 'hifiexpert' ); ?></span></li>
				</ul>
			</div>
			<div class="hx-band__side">
				<p class="hx-band__text"><?php echo wp_kses_post( hifiexpert_opt( 'audio_text' ) ); ?></p>
				<div class="hx-band__ctas">
					<?php if ( $wa_system ) : ?><a class="hx-btn hx-btn--gold" href="<?php echo esc_url( $wa_system ); ?>" target="_blank" rel="noopener"><?php echo hifiexpert_icon( 'chat', 18 ); ?><?php echo esc_html( hifiexpert_opt( 'audio_cta_label' ) ); ?></a><?php endif; ?>
					<?php if ( $wa && hifiexpert_opt( 'audio_cta2_label' ) ) : ?><a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( hifiexpert_opt( 'audio_cta2_label' ) ); ?></a><?php endif; ?>
				</div>
				<p class="hx-band__note"><?php echo hifiexpert_icon( 'clock', 15 ); ?><?php esc_html_e( 'Răspundem în programul showroom‑ului, de obicei în câteva minute.', 'hifiexpert' ); ?></p>
			</div>
		</div>
		<?php if ( $logo_items ) : ?>
			<div class="hx-logos" data-hx-marquee>
				<div class="hx-logos__track">
					<?php echo implode( '', $logo_items ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<div class="hx-logos__track" aria-hidden="true">
					<?php echo implode( '', $logo_items ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<?php if ( hifiexpert_brand_taxonomy() ) : ?>
				<a class="hx-link hx-band__all" href="<?php echo esc_url( home_url( '/brands/' ) ); ?>"><?php esc_html_e( 'Toate brandurile', 'hifiexpert' ); ?> →</a>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>

<?php
$posts = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish' ) );
if ( $posts ) :
	?>
	<section class="hx-section hx-wrap">
		<?php hifiexpert_section_head( __( 'Din blog', 'hifiexpert' ), '', get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ), __( 'Toate articolele', 'hifiexpert' ) ); ?>
		<div class="hx-grid hx-grid--3">
			<?php foreach ( $posts as $p ) { hifiexpert_post_card( $p->ID ); } ?>
		</div>
	</section>
<?php endif; ?>

<?php get_footer(); ?>
