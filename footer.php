<?php
/**
 * Footer: newsletter, menus, showroom, legal.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

$hx_wa    = yamaha_audio_whatsapp_url();
$hx_phone = yamaha_audio_opt( 'phone' );
$hx_news  = yamaha_audio_opt( 'newsletter_shortcode' );
?>
</main>

<footer class="hx-footer">
	<div class="hx-wrap hx-footer__inner">
		<div class="hx-footer__grid">
			<div class="hx-footer__col hx-footer__col--brand" id="newsletter">
				<?php yamaha_audio_logo(); ?>
				<span class="hx-footer__heading"><?php esc_html_e( 'Newsletter', 'yamaha-audio' ); ?></span>
				<p class="hx-footer__text"><?php echo esc_html( yamaha_audio_opt( 'newsletter_text' ) ); ?></p>
				<div class="hx-newsletter"><?php echo yamaha_audio_newsletter_form(); // phpcs:ignore ?></div>
			</div>

			<div class="hx-footer__col">
				<span class="hx-footer__heading"><?php esc_html_e( 'Magazin', 'yamaha-audio' ); ?></span>
				<?php
				if ( has_nav_menu( 'footer-shop' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-shop', 'container' => false, 'menu_class' => 'hx-footer__links', 'depth' => 1, 'fallback_cb' => false ) );
				} elseif ( class_exists( 'WooCommerce' ) ) {
					echo '<ul class="hx-footer__links">';
					foreach ( yamaha_audio_home_categories( 6 ) as $term ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
					}
					echo '</ul>';
				}
				?>
			</div>

			<div class="hx-footer__col">
				<span class="hx-footer__heading"><?php esc_html_e( 'Informații utile', 'yamaha-audio' ); ?></span>
				<?php
				if ( has_nav_menu( 'footer-info' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-info', 'container' => false, 'menu_class' => 'hx-footer__links', 'depth' => 1, 'fallback_cb' => false ) );
				} elseif ( function_exists( 'yamaha_audio_legal_links' ) && yamaha_audio_legal_links() ) {
					echo '<ul class="hx-footer__links">';
					foreach ( yamaha_audio_legal_links() as $l ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( $l['url'] ), esc_html( $l['title'] ) );
					}
					echo '</ul>';
				}
				?>
				<ul class="hx-footer__links hx-footer__links--anpc">
					<li><a href="https://anpc.ro" target="_blank" rel="noopener">ANPC – Protecția Consumatorilor</a></li>
					<li><a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">Soluționarea alternativă a litigiilor (SAL)</a></li>
				</ul>
			</div>

			<div class="hx-footer__col hx-footer__col--showroom">
				<span class="hx-footer__heading"><?php esc_html_e( 'Showroom', 'yamaha-audio' ); ?></span>
				<ul class="hx-footer__links hx-footer__links--contact">
					<li><?php echo yamaha_audio_icon( 'pin', 15 ); ?><a href="<?php echo esc_url( yamaha_audio_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( yamaha_audio_opt( 'topbar_address' ) ); ?></a></li>
					<li><?php echo yamaha_audio_icon( 'clock', 15 ); ?><span><?php echo esc_html( yamaha_audio_opt( 'topbar_hours' ) ); ?></span></li>
					<?php if ( $hx_wa ) : ?><li><?php echo yamaha_audio_icon( 'chat', 15 ); ?><a href="<?php echo esc_url( $hx_wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $hx_phone ); ?> · WhatsApp</a></li><?php endif; ?>
					<li><?php echo yamaha_audio_icon( 'mail', 15 ); ?><a href="mailto:<?php echo esc_attr( yamaha_audio_opt( 'topbar_email' ) ); ?>"><?php echo esc_html( yamaha_audio_opt( 'topbar_email' ) ); ?></a></li>
				</ul>
				<?php $directions = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) yamaha_audio_opt( 'showroom_directions' ) ) ) ); ?>
				<?php if ( $directions ) : ?>
					<span class="hx-footer__subheading"><?php esc_html_e( 'Cum ajungi', 'yamaha-audio' ); ?></span>
					<ul class="hx-footer__directions">
						<?php foreach ( $directions as $d ) : ?><li><?php echo esc_html( $d ); ?></li><?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="hx-footer__mapbtns">
					<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( yamaha_audio_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Deschide în Google Maps', 'yamaha-audio' ); ?></a>
					<?php if ( yamaha_audio_opt( 'waze_url' ) ) : ?><a class="hx-link" href="<?php echo esc_url( yamaha_audio_opt( 'waze_url' ) ); ?>" target="_blank" rel="noopener">Waze →</a><?php endif; ?>
				</div>
			</div>
		</div>

		<?php
		$hx_company = array_filter( array(
			yamaha_audio_opt( 'company_name' ),
			yamaha_audio_opt( 'company_cui' ) ? 'CUI ' . yamaha_audio_opt( 'company_cui' ) : '',
			yamaha_audio_opt( 'company_reg' ) ? 'Nr. Reg. Com. ' . yamaha_audio_opt( 'company_reg' ) : '',
			yamaha_audio_opt( 'company_address' ) ? __( 'Sediu social:', 'yamaha-audio' ) . ' ' . yamaha_audio_opt( 'company_address' ) : '',
			yamaha_audio_opt( 'company_capital' ) ? __( 'Capital social:', 'yamaha-audio' ) . ' ' . yamaha_audio_opt( 'company_capital' ) : '',
		) );
		?>
		<?php if ( $hx_company ) : ?>
			<div class="hx-footer__company">
				<?php echo yamaha_audio_icon( 'building', 16 ); ?>
				<p>
					<?php foreach ( $hx_company as $i => $part ) : ?><?php if ( $i ) : ?><span class="hx-footer__dot" aria-hidden="true">·</span><?php endif; ?><span><?php echo esc_html( $part ); ?></span><?php endforeach; ?>
					<span class="hx-footer__dot" aria-hidden="true">·</span><a href="mailto:<?php echo esc_attr( yamaha_audio_opt( 'topbar_email' ) ); ?>"><?php echo esc_html( yamaha_audio_opt( 'topbar_email' ) ); ?></a>
					<?php if ( $hx_phone ) : ?><span class="hx-footer__dot" aria-hidden="true">·</span><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', (string) yamaha_audio_opt( 'whatsapp' ) ?: $hx_phone ) ); ?>"><?php echo esc_html( $hx_phone ); ?></a><?php endif; ?>
				</p>
			</div>
		<?php endif; ?>
		<div class="hx-footer__bottom">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( yamaha_audio_opt( 'company_line' ) ); ?></span>
			<span class="hx-footer__payments"><?php echo esc_html( yamaha_audio_opt( 'payments_line' ) ); ?></span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
