<?php
/**
 * Template Name: Contact
 *
 * Showroom card (address, hours with live open/closed state, phone, WhatsApp, e-mail, directions),
 * map, and the contact form (Contact Form 7 if present). Applied to the page with the slug "contact".
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

list( $open, $open_label ) = yamaha_audio_open_state();
$wa      = yamaha_audio_whatsapp_url( __( 'Bună! Am o întrebare:', 'yamaha-audio' ) );
$phone   = yamaha_audio_opt( 'phone' );
$tel     = preg_replace( '/\s+/', '', (string) ( yamaha_audio_opt( 'whatsapp' ) ?: $phone ) );
$email   = yamaha_audio_opt( 'topbar_email' );
$address = yamaha_audio_opt( 'topbar_address' );
$map_q   = rawurlencode( $address . ', România' );
$hours   = array(
	__( 'Luni – Vineri', 'yamaha-audio' ) => yamaha_audio_opt( 'hours_weekday' ),
	__( 'Sâmbătă', 'yamaha-audio' )       => yamaha_audio_opt( 'hours_saturday' ),
	__( 'Duminică', 'yamaha-audio' )      => yamaha_audio_opt( 'hours_sunday' ),
);
$directions = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) yamaha_audio_opt( 'showroom_directions' ) ) ) );

// The form: a [contact-form-7] shortcode in the page content, else the first CF7 form on the site.
$content = (string) get_post_field( 'post_content', get_the_ID() );
$form    = '';
if ( preg_match( '/\[contact-form-7[^\]]*\]/', $content, $m ) ) {
	$form = do_shortcode( $m[0] );
} elseif ( post_type_exists( 'wpcf7_contact_form' ) ) {
	$cf = get_posts( array( 'post_type' => 'wpcf7_contact_form', 'numberposts' => 1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	if ( $cf ) {
		$form = do_shortcode( '[contact-form-7 id="' . (int) $cf[0]->ID . '"]' );
	}
}
?>
<div class="hx-wrap hx-contact">
	<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>

	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<span class="hx-eyebrow"><?php esc_html_e( 'Showroom Cotroceni', 'yamaha-audio' ); ?></span>
			<h1 class="hx-shop__title"><?php esc_html_e( 'Vino să asculți. Sau scrie‑ne.', 'yamaha-audio' ); ?></h1>
			<p class="hx-shop__text"><?php esc_html_e( 'Răspundem la telefon și pe WhatsApp în programul showroom‑ului, iar la e‑mail în aceeași zi lucrătoare. Pentru o audiție, dă‑ne un semn înainte ca să pregătim sistemul.', 'yamaha-audio' ); ?></p>
		</div>
	</header>

	<div class="hx-contact__grid">
		<div class="hx-contact__col">
			<section class="hx-contact__card">
				<div class="hx-contact__status hx-open<?php echo $open ? ' is-open' : ' is-closed'; ?>"><span class="hx-open__led" aria-hidden="true"></span><span class="hx-open__label"><?php echo esc_html( $open_label ); ?></span></div>
				<ul class="hx-contact__list">
					<li><?php echo yamaha_audio_icon( 'pin', 18 ); ?><div><strong><?php echo esc_html( $address ); ?></strong><span><?php esc_html_e( 'Intrarea la stradă, parter', 'yamaha-audio' ); ?></span></div></li>
					<li><?php echo yamaha_audio_icon( 'clock', 18 ); ?><div>
						<?php foreach ( $hours as $label => $h ) : ?>
							<span class="hx-contact__hours"><em><?php echo esc_html( $label ); ?></em><strong><?php echo $h ? esc_html( str_replace( '-', '–', $h ) ) : esc_html__( 'închis', 'yamaha-audio' ); ?></strong></span>
						<?php endforeach; ?>
					</div></li>
					<?php if ( $phone ) : ?><li><?php echo yamaha_audio_icon( 'chat', 18 ); ?><div><a href="tel:<?php echo esc_attr( $tel ); ?>"><strong><?php echo esc_html( $phone ); ?></strong></a><span><?php esc_html_e( 'telefon și WhatsApp', 'yamaha-audio' ); ?></span></div></li><?php endif; ?>
					<li><?php echo yamaha_audio_icon( 'mail', 18 ); ?><div><a href="mailto:<?php echo esc_attr( $email ); ?>"><strong><?php echo esc_html( $email ); ?></strong></a><span><?php esc_html_e( 'comenzi, garanții, service', 'yamaha-audio' ); ?></span></div></li>
				</ul>
				<div class="hx-contact__ctas">
					<?php if ( $wa ) : ?><a class="hx-btn hx-btn--gold hx-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo yamaha_audio_icon( 'chat', 16 ); ?><?php esc_html_e( 'Scrie‑ne pe WhatsApp', 'yamaha-audio' ); ?></a><?php endif; ?>
					<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( yamaha_audio_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Google Maps', 'yamaha-audio' ); ?></a>
					<?php if ( yamaha_audio_opt( 'waze_url' ) ) : ?><a class="hx-link" href="<?php echo esc_url( yamaha_audio_opt( 'waze_url' ) ); ?>" target="_blank" rel="noopener">Waze →</a><?php endif; ?>
				</div>
			</section>

			<div class="hx-contact__map">
				<iframe title="<?php esc_attr_e( 'Harta showroom', 'yamaha-audio' ); ?>" src="https://www.google.com/maps?q=<?php echo esc_attr( $map_q ); ?>&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
			</div>

			<?php if ( $directions ) : ?>
				<section class="hx-contact__dirs">
					<span class="hx-footer__heading"><?php esc_html_e( 'Cum ajungi', 'yamaha-audio' ); ?></span>
					<ul>
						<?php foreach ( $directions as $d ) : ?><li><?php echo esc_html( $d ); ?></li><?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>

		<div class="hx-contact__col">
			<section class="hx-contact__form" id="formular">
				<h2 class="hx-contact__h2"><?php esc_html_e( 'Trimite‑ne un mesaj', 'yamaha-audio' ); ?></h2>
				<p class="hx-contact__sub"><?php esc_html_e( 'Spune‑ne ce cauți, ce ai deja și bugetul; revenim cu o propunere concretă, nu cu un catalog.', 'yamaha-audio' ); ?></p>
				<?php
				if ( $form ) {
					echo $form; // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<p class="hx-contact__sub">' . esc_html__( 'Scrie‑ne la', 'yamaha-audio' ) . ' <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p>';
				}
				?>
			</section>
		</div>
	</div>
</div>
<?php
get_footer();
