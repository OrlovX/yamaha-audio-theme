<?php
/**
 * Header: top bar (showroom / hours / e-mail), main bar with mega menu, search, actions.
 *
 * @package hifiexpert
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
<a class="screen-reader-text hx-skip" href="#hx-main"><?php esc_html_e( 'Sari la conținut', 'hifiexpert' ); ?></a>

<?php
$hx_address = hifiexpert_opt( 'topbar_address' );
$hx_hours   = hifiexpert_opt( 'topbar_hours' );
$hx_email   = hifiexpert_opt( 'topbar_email' );
$hx_wa      = hifiexpert_whatsapp_url();
$hx_phone   = hifiexpert_opt( 'phone' );
list( $hx_open, $hx_open_label ) = hifiexpert_open_state();
$hx_hours_json = wp_json_encode( array_map( function ( $r ) { return $r ?: null; }, hifiexpert_hours_table() ) );
?>
<div class="hx-topbar">
	<div class="hx-wrap hx-topbar__inner" data-hx-ticker>
		<a class="hx-topbar__item" href="<?php echo esc_url( hifiexpert_opt( 'topbar_address_url' ) ); ?>" target="_blank" rel="noopener">
			<?php echo hifiexpert_icon( 'pin', 15 ); ?><strong><?php esc_html_e( 'Showroom', 'hifiexpert' ); ?></strong> <span><?php echo esc_html( $hx_address ); ?></span>
		</a>
		<span class="hx-topbar__item hx-topbar__hours hx-open<?php echo $hx_open ? ' is-open' : ' is-closed'; ?>" data-hx-hours='<?php echo esc_attr( $hx_hours_json ); ?>' title="<?php echo esc_attr( $hx_hours ); ?>"><span class="hx-open__led" aria-hidden="true"></span><span class="hx-open__label"><?php echo esc_html( $hx_open_label ); ?></span></span>
		<a class="hx-topbar__item hx-topbar__mail" href="mailto:<?php echo esc_attr( $hx_email ); ?>"><?php echo hifiexpert_icon( 'mail', 15 ); ?><span><?php echo esc_html( $hx_email ); ?></span></a>
		<?php if ( $hx_wa ) : ?>
			<a class="hx-topbar__item hx-topbar__wa" href="<?php echo esc_url( $hx_wa ); ?>" target="_blank" rel="noopener"><?php echo hifiexpert_icon( 'chat', 15 ); ?><span><?php echo esc_html( $hx_phone ); ?> · WhatsApp</span></a>
		<?php endif; ?>
		<?php
		if ( has_nav_menu( 'topbar' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'topbar',
					'container'      => 'nav',
					'container_class'=> 'hx-topbar__nav',
					'menu_class'     => 'hx-topbar__links',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
		}
		?>
	</div>
</div>

<header class="hx-header" id="hx-header">
	<div class="hx-wrap hx-header__inner">
		<button class="hx-iconbtn hx-burger" type="button" aria-controls="hx-mnav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Meniu', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'menu', 22 ); ?></button>
		<?php hifiexpert_logo(); ?>
		<nav class="hx-nav" aria-label="<?php esc_attr_e( 'Categorii', 'hifiexpert' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				// Categories (mega menu) on the left…
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'hx-nav__list',
						'depth'          => 3,
						'walker'         => new HiFiExpert_Mega_Walker(),
						'fallback_cb'    => false,
						'hx_group'       => 'cats',
					)
				);
				// …and Blog / Branduri / Consignație / Promoții as a separate group on the right.
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'hx-nav__list hx-nav__extra',
						'menu_id'        => 'hx-nav-extra',
						'depth'          => 1,
						'walker'         => new HiFiExpert_Mega_Walker(),
						'fallback_cb'    => false,
						'hx_group'       => 'extra',
					)
				);
			} elseif ( class_exists( 'WooCommerce' ) ) {
				echo '<ul class="hx-nav__list">';
				foreach ( hifiexpert_home_categories( 7 ) as $term ) {
					printf( '<li class="hx-nav__item"><a class="hx-nav__link" href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
				}
				echo '</ul>';
			}
			?>
		</nav>
		<div class="hx-header__right">
			<?php hifiexpert_search_form( true ); ?>
			<?php hifiexpert_header_actions(); ?>
		</div>
	</div>
</header>

<?php $hx_camp = hifiexpert_campaign(); ?>
<?php if ( $hx_camp && hifiexpert_opt( 'camp_bar' ) && ! ( class_exists( 'WooCommerce' ) && ( is_checkout() || is_cart() ) ) ) : ?>
	<div class="hx-campbar" role="region" aria-label="<?php echo esc_attr( $hx_camp['name'] ); ?>">
		<div class="hx-wrap hx-campbar__inner">
			<a class="hx-campbar__name" href="<?php echo esc_url( $hx_camp['url'] ); ?>"><?php echo esc_html( $hx_camp['name'] ); ?></a>
			<?php if ( $hx_camp['tagline'] ) : ?><span class="hx-campbar__tag"><?php echo esc_html( $hx_camp['tagline'] ); ?></span><?php endif; ?>
			<?php if ( $hx_camp['end_ts'] ) : ?><span class="hx-campbar__timer" data-hx-countdown="<?php echo (int) $hx_camp['end_ts']; ?>"><?php printf( esc_html__( 'până pe %s', 'hifiexpert' ), esc_html( $hx_camp['end_txt'] ) ); ?></span><?php endif; ?>
			<?php if ( ! is_page( 'promotii' ) ) : ?><a class="hx-btn hx-btn--sm hx-campbar__cta" href="<?php echo esc_url( $hx_camp['url'] ); ?>"><?php esc_html_e( 'Vezi ofertele', 'hifiexpert' ); ?> →</a><?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<div class="hx-mnav" id="hx-mnav" hidden>
	<div class="hx-mnav__head">
		<span class="hx-mnav__title"><?php esc_html_e( 'Meniu', 'hifiexpert' ); ?></span>
		<button class="hx-iconbtn" type="button" data-hx-close aria-label="<?php esc_attr_e( 'Închide meniul', 'hifiexpert' ); ?>"><?php echo hifiexpert_icon( 'close', 22 ); ?></button>
	</div>
	<div class="hx-mnav__search"><?php hifiexpert_search_form(); ?></div>
	<?php
	if ( has_nav_menu( 'primary' ) ) {
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
		<a href="<?php echo esc_url( hifiexpert_opt( 'topbar_address_url' ) ); ?>"><?php echo hifiexpert_icon( 'pin', 16 ); ?><?php echo esc_html( $hx_address ); ?></a>
		<?php if ( $hx_wa ) : ?><a href="<?php echo esc_url( $hx_wa ); ?>"><?php echo hifiexpert_icon( 'chat', 16 ); ?><?php echo esc_html( $hx_phone ); ?> · WhatsApp</a><?php endif; ?>
		<a href="mailto:<?php echo esc_attr( $hx_email ); ?>"><?php echo hifiexpert_icon( 'mail', 16 ); ?><?php echo esc_html( $hx_email ); ?></a>
		<?php hifiexpert_theme_switch( true ); ?>
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
