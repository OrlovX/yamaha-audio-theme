<?php
/**
 * Footer: newsletter, menus, showroom, legal.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

$hx_wa    = hifiexpert_whatsapp_url();
$hx_phone = hifiexpert_opt( 'phone' );
$hx_news  = hifiexpert_opt( 'newsletter_shortcode' );
?>
</main>

<footer class="hx-footer">
	<div class="hx-wrap hx-footer__inner">
		<div class="hx-footer__grid">
			<div class="hx-footer__col hx-footer__col--brand" id="newsletter">
				<?php hifiexpert_logo(); ?>
				<span class="hx-footer__heading"><?php esc_html_e( 'Newsletter', 'hifiexpert' ); ?></span>
				<p class="hx-footer__text"><?php echo esc_html( hifiexpert_opt( 'newsletter_text' ) ); ?></p>
				<div class="hx-newsletter"><?php echo hifiexpert_newsletter_form(); // phpcs:ignore ?></div>
			</div>

			<div class="hx-footer__col">
				<span class="hx-footer__heading"><?php esc_html_e( 'Magazin', 'hifiexpert' ); ?></span>
				<?php
				if ( has_nav_menu( 'footer-shop' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-shop', 'container' => false, 'menu_class' => 'hx-footer__links', 'depth' => 1, 'fallback_cb' => false ) );
				} elseif ( class_exists( 'WooCommerce' ) ) {
					echo '<ul class="hx-footer__links">';
					foreach ( hifiexpert_home_categories( 6 ) as $term ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
					}
					echo '</ul>';
				}
				?>
			</div>

			<div class="hx-footer__col">
				<span class="hx-footer__heading"><?php esc_html_e( 'Informații utile', 'hifiexpert' ); ?></span>
				<?php
				if ( has_nav_menu( 'footer-info' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-info', 'container' => false, 'menu_class' => 'hx-footer__links', 'depth' => 1, 'fallback_cb' => false ) );
				} elseif ( function_exists( 'hifiexpert_legal_links' ) && hifiexpert_legal_links() ) {
					echo '<ul class="hx-footer__links">';
					foreach ( hifiexpert_legal_links() as $l ) {
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
				<span class="hx-footer__heading"><?php esc_html_e( 'Showroom', 'hifiexpert' ); ?></span>
				<ul class="hx-footer__links hx-footer__links--contact">
					<li><?php echo hifiexpert_icon( 'pin', 15 ); ?><a href="<?php echo esc_url( hifiexpert_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( hifiexpert_opt( 'topbar_address' ) ); ?></a></li>
					<li><?php echo hifiexpert_icon( 'clock', 15 ); ?><span><?php echo esc_html( hifiexpert_opt( 'topbar_hours' ) ); ?></span></li>
					<?php if ( $hx_wa ) : ?><li><?php echo hifiexpert_icon( 'chat', 15 ); ?><a href="<?php echo esc_url( $hx_wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $hx_phone ); ?> · WhatsApp</a></li><?php endif; ?>
					<li><?php echo hifiexpert_icon( 'mail', 15 ); ?><a href="mailto:<?php echo esc_attr( hifiexpert_opt( 'topbar_email' ) ); ?>"><?php echo esc_html( hifiexpert_opt( 'topbar_email' ) ); ?></a></li>
				</ul>
				<?php $directions = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) hifiexpert_opt( 'showroom_directions' ) ) ) ); ?>
				<?php if ( $directions ) : ?>
					<span class="hx-footer__subheading"><?php esc_html_e( 'Cum ajungi', 'hifiexpert' ); ?></span>
					<ul class="hx-footer__directions">
						<?php foreach ( $directions as $d ) : ?><li><?php echo esc_html( $d ); ?></li><?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="hx-footer__mapbtns">
					<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( hifiexpert_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Deschide în Google Maps', 'hifiexpert' ); ?></a>
					<?php if ( hifiexpert_opt( 'waze_url' ) ) : ?><a class="hx-link" href="<?php echo esc_url( hifiexpert_opt( 'waze_url' ) ); ?>" target="_blank" rel="noopener">Waze →</a><?php endif; ?>
				</div>
			</div>
		</div>

		<?php
		$hx_company = array_filter( array(
			hifiexpert_opt( 'company_name' ),
			hifiexpert_opt( 'company_cui' ) ? 'CUI ' . hifiexpert_opt( 'company_cui' ) : '',
			hifiexpert_opt( 'company_reg' ) ? 'Nr. Reg. Com. ' . hifiexpert_opt( 'company_reg' ) : '',
			hifiexpert_opt( 'company_address' ) ? __( 'Sediu social:', 'hifiexpert' ) . ' ' . hifiexpert_opt( 'company_address' ) : '',
			hifiexpert_opt( 'company_capital' ) ? __( 'Capital social:', 'hifiexpert' ) . ' ' . hifiexpert_opt( 'company_capital' ) : '',
		) );
		?>
		<?php if ( $hx_company ) : ?>
			<div class="hx-footer__company">
				<?php echo hifiexpert_icon( 'building', 16 ); ?>
				<p>
					<?php foreach ( $hx_company as $i => $part ) : ?><?php if ( $i ) : ?><span class="hx-footer__dot" aria-hidden="true">·</span><?php endif; ?><span><?php echo esc_html( $part ); ?></span><?php endforeach; ?>
					<span class="hx-footer__dot" aria-hidden="true">·</span><a href="mailto:<?php echo esc_attr( hifiexpert_opt( 'topbar_email' ) ); ?>"><?php echo esc_html( hifiexpert_opt( 'topbar_email' ) ); ?></a>
					<?php if ( $hx_phone ) : ?><span class="hx-footer__dot" aria-hidden="true">·</span><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', (string) hifiexpert_opt( 'whatsapp' ) ?: $hx_phone ) ); ?>"><?php echo esc_html( $hx_phone ); ?></a><?php endif; ?>
				</p>
			</div>
		<?php endif; ?>
		<div class="hx-footer__bottom">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( hifiexpert_opt( 'company_line' ) ); ?></span>
			<span class="hx-footer__payments"><?php echo esc_html( hifiexpert_opt( 'payments_line' ) ); ?></span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
