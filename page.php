<?php
/**
 * Static pages (contact…). Cart / checkout / account / wishlist use this too, with a reassurance strip.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$wc       = class_exists( 'WooCommerce' );
	$is_cart  = $wc && is_cart();
	$is_chk   = $wc && is_checkout() && ! is_order_received_page();
	$is_done  = $wc && is_order_received_page();
	$is_acct  = $wc && is_account_page();
	$is_wl    = 'wishlist' === get_post_field( 'post_name', get_the_ID() );
	$is_flow  = $is_cart || $is_chk || $is_done || $is_acct || $is_wl;
	$free_min = (float) yamaha_audio_opt( 'free_shipping_min' );
	?>
	<article <?php post_class( 'hx-wrap hx-page' . ( $is_flow ? ' hx-page--flow' : '' ) . ( $is_chk ? ' hx-page--checkout' : '' ) ); ?>>
		<header class="hx-page__head<?php echo $is_flow ? ' hx-page__head--flow' : ''; ?>">
			<h1 class="hx-page__title"><?php the_title(); ?></h1>
			<?php if ( $is_cart || $is_chk ) : ?>
				<ul class="hx-assure" aria-label="<?php esc_attr_e( 'Garanțiile noastre', 'yamaha-audio' ); ?>">
					<li><?php echo yamaha_audio_icon( 'truck', 16 ); ?><span><?php $free_min ? printf( esc_html__( 'Transport gratuit peste %s', 'yamaha-audio' ), esc_html( yamaha_audio_lei( $free_min ) ) ) : esc_html_e( 'Livrare prin curier în toată țara', 'yamaha-audio' ); ?></span></li>
					<li><?php echo yamaha_audio_icon( 'card', 16 ); ?><span><?php esc_html_e( 'Plată securizată · rate fără dobândă', 'yamaha-audio' ); ?></span></li>
					<li><?php echo yamaha_audio_icon( 'check', 16 ); ?><span><?php echo esc_html( yamaha_audio_opt( 'returns_text' ) ); ?> · <?php echo esc_html( yamaha_audio_opt( 'warranty_text' ) ); ?></span></li>
					<?php if ( yamaha_audio_opt( 'phone' ) ) : ?><li><?php echo yamaha_audio_icon( 'chat', 16 ); ?><span><?php esc_html_e( 'Ajutor la comandă:', 'yamaha-audio' ); ?> <a href="<?php echo esc_url( yamaha_audio_whatsapp_url( __( 'Bună! Am o întrebare despre comanda mea.', 'yamaha-audio' ) ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( yamaha_audio_opt( 'phone' ) ); ?></a></span></li><?php endif; ?>
				</ul>
			<?php elseif ( $is_done ) : ?>
				<p class="hx-page__sub"><?php esc_html_e( 'Mulțumim! Ai primit un e‑mail cu detaliile comenzii; te anunțăm când pleacă spre tine.', 'yamaha-audio' ); ?></p>
			<?php endif; ?>
		</header>
		<div class="hx-prose hx-page__body">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
