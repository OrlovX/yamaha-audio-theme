<?php
/**
 * Breadcrumb (theme override of woocommerce/templates/global/breadcrumb.php).
 *
 * - a "back to <parent>" pill first, so one tap goes up a level;
 * - consecutive identical names (parent/child category with the same name) collapse into the deeper one;
 * - chevron separators, pill links, current item truncated; one scrollable row on phones.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package hifiexpert
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $breadcrumb ) || ! is_array( $breadcrumb ) ) {
	return;
}

$crumbs = array();
foreach ( $breadcrumb as $crumb ) {
	$name = isset( $crumb[0] ) ? wp_strip_all_tags( (string) $crumb[0] ) : '';
	if ( '' === trim( $name ) ) {
		continue;
	}
	if ( $crumbs && 0 === strcasecmp( end( $crumbs )[0], $name ) ) {
		array_pop( $crumbs ); // keep the deeper (more specific) link
	}
	$crumbs[] = array( $name, isset( $crumb[1] ) ? (string) $crumb[1] : '' );
}
if ( ! $crumbs ) {
	return;
}

$last = count( $crumbs ) - 1;
$back = null;
for ( $i = $last - 1; $i >= 0; $i-- ) {
	if ( ! empty( $crumbs[ $i ][1] ) ) {
		$back = $crumbs[ $i ];
		break;
	}
}
?>
<nav class="hx-crumbs" aria-label="<?php esc_attr_e( 'Navigare', 'hifiexpert' ); ?>">
	<?php if ( $back ) : ?>
		<a class="hx-crumbs__back" href="<?php echo esc_url( $back[1] ); ?>" title="<?php echo esc_attr( sprintf( __( 'Înapoi la %s', 'hifiexpert' ), $back[0] ) ); ?>">
			<?php echo hifiexpert_icon( 'arrow-left', 16 ); ?><span><?php echo esc_html( sprintf( __( 'Înapoi la %s', 'hifiexpert' ), $back[0] ) ); ?></span>
		</a>
	<?php endif; ?>
	<ol class="hx-crumbs__list">
		<?php foreach ( $crumbs as $i => $crumb ) : ?>
			<?php if ( $i < $last && $crumb[1] ) : ?>
				<li><a href="<?php echo esc_url( $crumb[1] ); ?>"><?php echo esc_html( $crumb[0] ); ?></a></li>
			<?php else : ?>
				<li class="is-current" aria-current="page"><?php echo esc_html( $crumb[0] ); ?></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
