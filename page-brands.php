<?php
/**
 * Template Name: Branduri
 *
 * All brands (pa_producator) as image tiles, grouped alphabetically with a letter index.
 * Every brand gets a picture: its own (brand edit screen), else the banner the old page used,
 * else the best-selling product of the brand on paper, fading into black.
 * Brands listed in Customizer → "branduri ascunse" are left out.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tax    = hifiexpert_brand_taxonomy();
$terms  = $tax ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'orderby' => 'name' ) ) : array();
$hidden = hifiexpert_hidden_brand_slugs();
if ( is_wp_error( $terms ) ) {
	$terms = array();
}
$terms = array_values( array_filter( $terms, function ( $t ) use ( $hidden ) {
	return ! in_array( $t->slug, $hidden, true );
} ) );

// Merge "Jorma" into "Jorma Audio" style duplicates: keep the one with more products.
$by_norm = array();
foreach ( $terms as $t ) {
	$k = preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( $t->name ) ) );
	$k = preg_replace( '/(audio|hifi|cables?|acoustics?)$/', '', $k );
	if ( ! isset( $by_norm[ $k ] ) || $t->count > $by_norm[ $k ]->count ) {
		$by_norm[ $k ] = $t;
	}
}
$terms = array_values( $by_norm );
usort( $terms, function ( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );

$featured_slugs = array_filter( array_map( 'trim', explode( ',', (string) hifiexpert_opt( 'brands_featured' ) ) ) );
$groups = array();
foreach ( $terms as $t ) {
	$letter = strtoupper( mb_substr( remove_accents( $t->name ), 0, 1 ) );
	$letter = ctype_alpha( $letter ) ? $letter : '#';
	$groups[ $letter ][] = $t;
}
ksort( $groups );

$tile = function ( $t, $big = false ) {
	$kind = 'photo';
	$img  = hifiexpert_brand_image_id( $t, $kind );
	$cls  = 'hx-brandtile' . ( $big ? ' hx-brandtile--big' : '' ) . ( $img ? ' hx-brandtile--img hx-brandtile--' . $kind : '' );
	echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( get_term_link( $t ) ) . '">';
	if ( $img ) {
		echo '<span class="hx-brandtile__bg" aria-hidden="true">' . wp_get_attachment_image( $img, $big ? 'hifiexpert-hero' : 'hifiexpert-card', false, array( 'loading' => 'lazy', 'alt' => '' ) ) . '</span>';
	} else {
		echo '<span class="hx-brandtile__word" aria-hidden="true">' . esc_html( $t->name ) . '</span>';
	}
	echo '<span class="hx-brandtile__body"><span class="hx-brandtile__name">' . esc_html( $t->name ) . '</span><span class="hx-brandtile__count">' . sprintf( esc_html( _n( '%d produs', '%d produse', $t->count, 'hifiexpert' ) ), (int) $t->count ) . ' →</span></span>';
	echo '</a>';
};
?>
<div class="hx-wrap hx-brands-page">
	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<h1 class="hx-shop__title"><?php the_title(); ?></h1>
			<p class="hx-shop__text"><?php printf( esc_html__( '%d branduri alese pe criterii de sunet, nu de catalog. Multe pot fi ascultate în showroom.', 'hifiexpert' ), count( $terms ) ); ?></p>
		</div>
		<nav class="hx-letters" aria-label="<?php esc_attr_e( 'Index alfabetic', 'hifiexpert' ); ?>">
			<?php foreach ( array_keys( $groups ) as $l ) : ?><a href="#brand-<?php echo esc_attr( strtolower( $l ) ); ?>"><?php echo esc_html( $l ); ?></a><?php endforeach; ?>
		</nav>
	</header>

	<?php if ( $featured_slugs ) : ?>
		<?php $featured = array_values( array_filter( $terms, function ( $t ) use ( $featured_slugs ) { return in_array( $t->slug, $featured_slugs, true ); } ) ); ?>
		<?php if ( $featured ) : ?>
			<section class="hx-section hx-section--tight">
				<div class="hx-brandgrid hx-brandgrid--featured">
					<?php foreach ( $featured as $t ) { $tile( $t, true ); } ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php foreach ( $groups as $letter => $list ) : ?>
		<section class="hx-section hx-section--tight hx-brandgroup" id="brand-<?php echo esc_attr( strtolower( $letter ) ); ?>">
			<h2 class="hx-brandgroup__letter"><?php echo esc_html( $letter ); ?></h2>
			<div class="hx-brandgrid">
				<?php foreach ( $list as $t ) { $tile( $t ); } ?>
			</div>
		</section>
	<?php endforeach; ?>

	<?php $wa = hifiexpert_whatsapp_url( __( 'Bună! Caut un brand pe care nu îl văd pe site…', 'hifiexpert' ) ); ?>
	<?php if ( $wa ) : ?>
		<section class="hx-section hx-section--tight">
			<div class="hx-ribbon">
				<div class="hx-ribbon__text">
					<strong><?php esc_html_e( 'Cauți un brand care nu e aici?', 'hifiexpert' ); ?></strong>
					<span><?php esc_html_e( 'Lucrăm cu distribuitorii din toată Europa. Spune‑ne ce cauți și îți spunem în aceeași zi dacă îl putem aduce.', 'hifiexpert' ); ?></span>
				</div>
				<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Întreabă pe WhatsApp', 'hifiexpert' ); ?></a>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
