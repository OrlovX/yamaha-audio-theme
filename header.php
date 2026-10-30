<?php
/**
 * Header: one bar — logo, category navigation (built from the product categories), search, actions.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text hx-skip" href="#hx-main"><?php esc_html_e( 'Sari la conținut', 'yamaha-audio' ); ?></a>

<?php
$hx_address = yamaha_audio_opt( 'topbar_address' );
$hx_email   = yamaha_audio_opt( 'topbar_email' );
$hx_wa      = yamaha_audio_whatsapp_url();
$hx_phone   = yamaha_audio_opt( 'phone' );
?>
<header class="hx-header" id="hx-header">
	<div class="hx-wrap hx-header__inner">
		<button class="hx-iconbtn hx-burger" type="button" aria-controls="hx-mnav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Meniu', 'yamaha-audio' ); ?>"><?php echo yamaha_audio_icon( 'menu', 22 ); ?></button>
		<?php yamaha_audio_logo(); ?>
		<nav class="hx-nav" aria-label="<?php esc_attr_e( 'Categorii', 'yamaha-audio' ); ?>">
			<?php
			if ( yamaha_audio_opt( 'nav_auto' ) || ! has_nav_menu( 'primary' ) ) {
				// Built from the product categories (mega panels with the sub-categories) + the fixed links.
				yamaha_audio_nav_auto_desktop();
			} else {
				// The WordPress menu: categories (mega menu) on the left…
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'hx-nav__list',
						'depth'          => 3,
						'walker'         => new Yamaha_Audio_Mega_Walker(),
						'fallback_cb'    => false,
						'hx_group'       => 'cats',
					)
				);
				// …and the extra items (Promoții, Contact…) as a separate group on the right.
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'hx-nav__list hx-nav__extra',
						'menu_id'        => 'hx-nav-extra',
						'depth'          => 1,
						'walker'         => new Yamaha_Audio_Mega_Walker(),
						'fallback_cb'    => false,
						'hx_group'       => 'extra',
					)
				);
			}
			?>
		</nav>
		<div class="hx-header__right">
			<?php yamaha_audio_search_form( true ); ?>
			<?php yamaha_audio_header_actions(); ?>
		</div>
	</div>
</header>

<?php $hx_camp = yamaha_audio_campaign(); ?>
<?php if ( $hx_camp && yamaha_audio_opt( 'camp_bar' ) && ! ( class_exists( 'WooCommerce' ) && ( is_checkout() || is_cart() ) ) ) : ?>
	<div class="hx-campbar" role="region" aria-label="<?php echo esc_attr( $hx_camp['name'] ); ?>">
		<div class="hx-wrap hx-campbar__inner">
			<a class="hx-campbar__name" href="<?php echo esc_url( $hx_camp['url'] ); ?>"><?php echo esc_html( $hx_camp['name'] ); ?></a>
			<?php if ( $hx_camp['tagline'] ) : ?><span class="hx-campbar__tag"><?php echo esc_html( $hx_camp['tagline'] ); ?></span><?php endif; ?>
			<?php if ( $hx_camp['end_ts'] ) : ?><span class="hx-campbar__timer" data-hx-countdown="<?php echo (int) $hx_camp['end_ts']; ?>"><?php printf( esc_html__( 'până pe %s', 'yamaha-audio' ), esc_html( $hx_camp['end_txt'] ) ); ?></span><?php endif; ?>
			<?php if ( ! is_page( 'promotii' ) ) : ?><a class="hx-btn hx-btn--sm hx-campbar__cta" href="<?php echo esc_url( $hx_camp['url'] ); ?>"><?php esc_html_e( 'Vezi ofertele', 'yamaha-audio' ); ?> →</a><?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<div class="hx-mnav" id="hx-mnav" hidden>
	<div class="hx-mnav__head">
		<span class="hx-mnav__title"><?php esc_html_e( 'Meniu', 'yamaha-audio' ); ?></span>
		<button class="hx-iconbtn" type="button" data-hx-close aria-label="<?php esc_attr_e( 'Închide meniul', 'yamaha-audio' ); ?>"><?php echo yamaha_audio_icon( 'close', 22 ); ?></button>
	</div>
	<div class="hx-mnav__search"><?php yamaha_audio_search_form(); ?></div>
	<?php
	if ( yamaha_audio_opt( 'nav_auto' ) || ! has_nav_menu( 'primary' ) ) {
		yamaha_audio_nav_auto_mobile();
	} else {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'hx-mnav__list',
				'depth'          => 3,
				'fallback_cb'    => false,
			)
		);
	}
	?>
	<div class="hx-mnav__foot">
		<a href="<?php echo esc_url( yamaha_audio_opt( 'topbar_address_url' ) ); ?>"><?php echo yamaha_audio_icon( 'pin', 16 ); ?><?php echo esc_html( $hx_address ); ?></a>
		<?php if ( $hx_wa ) : ?><a href="<?php echo esc_url( $hx_wa ); ?>"><?php echo yamaha_audio_icon( 'chat', 16 ); ?><?php echo esc_html( $hx_phone ); ?> · WhatsApp</a><?php endif; ?>
		<a href="mailto:<?php echo esc_attr( $hx_email ); ?>"><?php echo yamaha_audio_icon( 'mail', 16 ); ?><?php echo esc_html( $hx_email ); ?></a>
		<?php yamaha_audio_theme_switch( true ); ?>
	</div>
</div>
<div class="hx-scrim" data-hx-close hidden></div>

<main id="hx-main" class="hx-main">
<?php
if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_print_notices' ) && ! is_cart() && ! is_checkout() ) {
	echo '<div class="hx-wrap hx-notices">';
	wc_print_notices();
	echo '</div>';
}
