<?php
/**
 * Homepage: hero, categories, products, bundle, trust, audiophile band, blog.
 * Everything is rendered from WooCommerce data + Customizer copy (no page builder).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! class_exists( 'WooCommerce' ) ) {
	get_template_part( 'index' );
	get_footer();
	return;
}

$hero     = yamaha_audio_hero_product();
$cta_url  = yamaha_audio_opt( 'hero_cta_url' ) ?: add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) );
$cta2_url = yamaha_audio_opt( 'hero_cta2_url' );
$cats     = yamaha_audio_home_categories( 6 );
$products = yamaha_audio_home_products( (int) yamaha_audio_opt( 'home_products_count' ) + 1 );
if ( $hero ) {
	$products = array_values( array_filter( $products, function ( $p ) use ( $hero ) { return $p->get_id() !== $hero->get_id(); } ) );
}
$products = array_slice( $products, 0, (int) yamaha_audio_opt( 'home_products_count' ) );
$rows     = yamaha_audio_home_rows();
$wa       = yamaha_audio_whatsapp_url( __( 'Bună! Aș vrea să programez o audiție în showroom.', 'yamaha-audio' ) );
$rates    = (int) yamaha_audio_opt( 'rates_count' );
?>

<div class="hx-herowrap">
<section class="hx-hero hx-wrap" id="top">
	<div class="hx-hero__copy">
		<?php if ( yamaha_audio_campaign() ) : ?>
			<span class="hx-eyebrow hx-eyebrow--camp"><b><?php echo esc_html( yamaha_audio_campaign()['name'] ); ?></b><?php echo yamaha_audio_campaign()['tagline'] ? ' · ' . esc_html( yamaha_audio_campaign()['tagline'] ) : ''; ?></span>
		<?php elseif ( yamaha_audio_opt( 'hero_eyebrow' ) ) : ?>
			<span class="hx-eyebrow"><?php echo esc_html( yamaha_audio_opt( 'hero_eyebrow' ) ); ?></span>
		<?php endif; ?>
		<h1 class="hx-hero__title"><?php echo esc_html( yamaha_audio_opt( 'hero_title' ) ); ?></h1>
		<p class="hx-hero__text"><?php echo wp_kses_post( yamaha_audio_opt( 'hero_text' ) ); ?></p>
		<div class="hx-hero__ctas">
			<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( yamaha_audio_opt( 'hero_cta_label' ) ); ?></a>
			<?php if ( $cta2_url && yamaha_audio_opt( 'hero_cta2_label' ) ) : ?>
				<a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( yamaha_audio_opt( 'hero_cta2_label' ) ); ?></a>
			<?php endif; ?>
		</div>
		<ul class="hx-hero__proof">
			<li><?php echo yamaha_audio_icon( 'truck', 16 ); ?><?php printf( esc_html__( 'Transport gratuit peste %d lei', 'yamaha-audio' ), (int) yamaha_audio_opt( 'free_shipping_min' ) ); ?></li>
			<li><?php echo yamaha_audio_icon( 'card', 16 ); ?><?php printf( esc_html__( '%d rate fără dobândă', 'yamaha-audio' ), $rates ); ?></li>
			<li><?php echo yamaha_audio_icon( 'pin', 16 ); ?><?php esc_html_e( 'Showroom în București', 'yamaha-audio' ); ?></li>
		</ul>
	</div>

	<?php if ( $hero ) : ?>
		<?php
		$hero_cat     = yamaha_audio_product_category( $hero );
		$hero_rate    = yamaha_audio_installment( yamaha_audio_active_price( $hero ) );
		$hero_percent = yamaha_audio_discount_percent( $hero );
		$hero_lead    = yamaha_audio_product_lead( $hero, 26 );
		$hero_regular = $hero->is_type( 'variable' ) ? (float) $hero->get_variation_regular_price( 'min' ) : (float) $hero->get_regular_price();
		?>
		<div class="hx-spot">
			<a class="hx-spot__media" href="<?php echo esc_url( $hero->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
				<figure class="hx-stage hx-stage--spot">
					<?php echo wp_get_attachment_image( $hero->get_image_id(), 'yamaha-audio-hero', false, array( 'class' => 'hx-hero__img', 'alt' => $hero->get_name(), 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 92vw, 560px', 'decoding' => 'async' ) ); ?>
				</figure>
				<?php if ( $hero_percent && yamaha_audio_campaign() ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg hx-pill--camp"><small><?php echo esc_html( yamaha_audio_campaign()['badge'] ); ?></small>−<?php echo (int) $hero_percent; ?>%</span>
				<?php elseif ( $hero_percent ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg">−<?php echo (int) $hero_percent; ?>%</span><?php endif; ?>
			</a>
			<div class="hx-spot__body">
				<?php if ( $hero_cat ) : ?><span class="hx-eyebrow hx-eyebrow--muted"><?php echo esc_html( $hero_cat->name ); ?></span><?php endif; ?>
				<h2 class="hx-spot__title"><a href="<?php echo esc_url( $hero->get_permalink() ); ?>"><?php echo esc_html( $hero->get_name() ); ?></a></h2>
				<?php if ( $hero_lead ) : ?><p class="hx-spot__lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
				<div class="hx-spot__pricebox">
					<div class="hx-spot__price">
						<span class="hx-spot__now"><?php echo esc_html( yamaha_audio_lei( yamaha_audio_active_price( $hero ) ) ); ?></span>
						<?php if ( $hero->is_on_sale() && $hero_regular > yamaha_audio_active_price( $hero ) ) : ?><s><?php echo esc_html( yamaha_audio_lei( $hero_regular ) ); ?></s><?php endif; ?>
					</div>
					<?php if ( $hero_rate ) : ?>
						<span class="hx-spot__rate"><?php printf( esc_html__( 'sau %1$d × %2$s %3$s', 'yamaha-audio' ), $rates, esc_html( yamaha_audio_lei( $hero_rate ) ), esc_html( yamaha_audio_opt( 'rates_label' ) ) ); ?></span>
					<?php endif; ?>
				</div>
				<div class="hx-spot__ctas">
					<?php yamaha_audio_loop_button( $hero ); ?>
					<a class="hx-link" href="<?php echo esc_url( $hero->get_permalink() ); ?>"><?php esc_html_e( 'Detalii', 'yamaha-audio' ); ?> →</a>
				</div>
				<?php echo yamaha_audio_stock_badge( $hero ); // phpcs:ignore ?>
			</div>
		</div>
	<?php endif; ?>
</section>
<?php $chips = yamaha_audio_type_chips( 10 ); ?>
<?php if ( $chips ) : ?>
<nav class="hx-wrap" aria-label="<?php esc_attr_e( 'Cumpără după tip', 'yamaha-audio' ); ?>">
	<ul class="hx-types">
		<?php foreach ( $chips as $chip ) : ?>
			<li><a class="hx-types__item" href="<?php echo esc_url( get_term_link( $chip ) ); ?>">
				<span><?php echo esc_html( $chip->name ); ?></span>
				<span class="hx-types__count"><?php echo (int) $chip->count; ?></span>
			</a></li>
		<?php endforeach; ?>
	</ul>
</nav>
<?php endif; ?>
</div>

<?php if ( $cats ) : ?>
<section class="hx-section hx-wrap">
	<?php yamaha_audio_section_head( __( 'Explorează gama Yamaha', 'yamaha-audio' ), '', wc_get_page_permalink( 'shop' ), __( 'Tot magazinul', 'yamaha-audio' ) ); ?>
	<div class="hx-cats">
		<?php foreach ( $cats as $term ) : ?>
			<?php $img_id = yamaha_audio_category_image_id( $term ); ?>
			<a class="hx-cat<?php echo $img_id ? ' hx-cat--img' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<?php if ( $img_id ) : ?>
					<span class="hx-cat__bg" aria-hidden="true"><?php echo wp_get_attachment_image( $img_id, 'yamaha-audio-card', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 640px) 50vw, 25vw', 'alt' => sprintf( __( '%s Yamaha', 'yamaha-audio' ), $term->name ) ) ); ?></span>
				<?php endif; ?>
				<span class="hx-cat__sub"><?php echo esc_html( yamaha_audio_category_children_label( $term ) ); ?></span>
				<span class="hx-cat__name"><?php echo esc_html( $term->name ); ?> <span class="hx-cat__arrow" aria-hidden="true">→</span></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $products ) : ?>
<section class="hx-section hx-wrap" id="promotii">
	<?php yamaha_audio_section_head( yamaha_audio_opt( 'home_products_title' ), yamaha_audio_opt( 'home_products_text' ), add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ), __( 'Vezi toate', 'yamaha-audio' ) ); ?>
	<ul class="hx-grid hx-grid--4 products">
		<?php
		foreach ( $products as $i => $p ) {
			yamaha_audio_product_card( $p, array( 'lazy' => $i > 3 ) );
		}
		?>
	</ul>
</section>
<?php endif; ?>

<?php if ( $rates > 1 ) : ?>
<section class="hx-wrap">
	<div class="hx-ribbon">
		<div class="hx-ribbon__text">
			<strong><?php printf( esc_html__( 'Până la %d rate fără dobândă cu Card Avantaj', 'yamaha-audio' ), $rates ); ?></strong>
			<span><?php esc_html_e( 'Alegi rata la plată, online sau în showroom. Fără costuri ascunse, fără avans.', 'yamaha-audio' ); ?></span>
		</div>
		<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( $cta_url ); ?>"><?php esc_html_e( 'Vezi produsele', 'yamaha-audio' ); ?></a>
	</div>
</section>
<?php endif; ?>

<?php foreach ( $rows as $row ) : ?>
	<?php
	$row_products = yamaha_audio_term_products( $row['slug'], 4 );
	if ( ! $row_products ) {
		continue;
	}
	?>
	<section class="hx-section hx-wrap">
		<?php yamaha_audio_section_head( $row['title'], '', get_term_link( $row['term'] ), sprintf( __( 'Tot %s', 'yamaha-audio' ), $row['term']->name ) ); ?>
		<ul class="hx-grid hx-grid--4 products">
			<?php foreach ( $row_products as $p ) { yamaha_audio_product_card( $p ); } ?>
		</ul>
	</section>
<?php endforeach; ?>

<?php if ( yamaha_audio_opt( 'bundle_enabled' ) && yamaha_audio_opt( 'bundle_title' ) ) : ?>
<section class="hx-section hx-wrap">
	<div class="hx-bundle">
		<div class="hx-bundle__copy">
			<span class="hx-eyebrow"><?php echo esc_html( yamaha_audio_opt( 'bundle_eyebrow' ) ); ?></span>
			<h2 class="hx-bundle__title"><?php echo esc_html( yamaha_audio_opt( 'bundle_title' ) ); ?></h2>
			<p class="hx-bundle__text"><?php echo wp_kses_post( yamaha_audio_opt( 'bundle_text' ) ); ?></p>
			<?php $items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) yamaha_audio_opt( 'bundle_items' ) ) ) ); ?>
			<?php if ( $items ) : ?>
				<ul class="hx-bundle__items">
					<?php foreach ( $items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="hx-bundle__ctas">
				<?php if ( yamaha_audio_opt( 'bundle_cta_url' ) ) : ?>
					<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( yamaha_audio_opt( 'bundle_cta_url' ) ); ?>"><?php echo esc_html( yamaha_audio_opt( 'bundle_cta_label' ) ); ?></a>
				<?php elseif ( $wa ) : ?>
					<a class="hx-btn hx-btn--primary" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Cere oferta pe WhatsApp', 'yamaha-audio' ); ?></a>
				<?php endif; ?>
				<?php if ( yamaha_audio_opt( 'bundle_price_note' ) ) : ?><span class="hx-bundle__note"><?php echo esc_html( yamaha_audio_opt( 'bundle_price_note' ) ); ?></span><?php endif; ?>
			</div>
		</div>
		<figure class="hx-stage hx-stage--bundle">
			<?php
			$bundle_img = (int) yamaha_audio_opt( 'bundle_image' );
			if ( $bundle_img ) {
				echo wp_get_attachment_image( $bundle_img, 'yamaha-audio-hero', false, array( 'loading' => 'lazy', 'alt' => yamaha_audio_opt( 'bundle_title' ) ) );
			} elseif ( $products ) {
				echo wp_get_attachment_image( $products[0]->get_image_id(), 'yamaha-audio-hero', false, array( 'loading' => 'lazy', 'alt' => $products[0]->get_name() ) );
			}
			?>
		</figure>
	</div>
</section>
<?php endif; ?>

<section class="hx-section hx-wrap">
	<?php yamaha_audio_trust_strip(); ?>
</section>

<?php
$wa_system = yamaha_audio_whatsapp_url( __( 'Bună! Vreau un sistem Yamaha / MusicCast. Camerele, muzica și bugetul meu:', 'yamaha-audio' ) );
?>
<section class="hx-band hx-band--feature" id="musiccast">
	<div class="hx-band__glow" aria-hidden="true"></div>
	<div class="hx-wrap hx-band__inner">
		<div class="hx-band__grid">
			<div class="hx-band__copy">
				<span class="hx-eyebrow"><?php echo esc_html( yamaha_audio_opt( 'audio_eyebrow' ) ); ?></span>
				<h2 class="hx-band__title"><?php echo esc_html( yamaha_audio_opt( 'audio_title' ) ); ?></h2>
				<ul class="hx-band__steps" aria-label="<?php esc_attr_e( 'Cum funcționează', 'yamaha-audio' ); ?>">
					<li><strong>1</strong><span><?php esc_html_e( 'Ne spui camerele, muzica și bugetul', 'yamaha-audio' ); ?></span></li>
					<li><strong>2</strong><span><?php esc_html_e( 'Îți propunem sistemul, cu prețuri ferme', 'yamaha-audio' ); ?></span></li>
					<li><strong>3</strong><span><?php esc_html_e( 'Îl livrăm în 24 de ore și îl configurăm împreună, din aplicația MusicCast', 'yamaha-audio' ); ?></span></li>
				</ul>
			</div>
			<div class="hx-band__side">
				<p class="hx-band__text"><?php echo wp_kses_post( yamaha_audio_opt( 'audio_text' ) ); ?></p>
				<div class="hx-band__ctas">
					<?php if ( $wa_system ) : ?><a class="hx-btn hx-btn--gold" href="<?php echo esc_url( $wa_system ); ?>" target="_blank" rel="noopener"><?php echo yamaha_audio_icon( 'chat', 18 ); ?><?php echo esc_html( yamaha_audio_opt( 'audio_cta_label' ) ); ?></a><?php endif; ?>
					<?php if ( $wa && yamaha_audio_opt( 'audio_cta2_label' ) ) : ?><a class="hx-btn hx-btn--ghost" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( yamaha_audio_opt( 'audio_cta2_label' ) ); ?></a><?php endif; ?>
				</div>
				<p class="hx-band__note"><?php echo yamaha_audio_icon( 'clock', 15 ); ?><?php esc_html_e( 'Răspundem în programul showroom‑ului, de obicei în câteva minute.', 'yamaha-audio' ); ?></p>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
