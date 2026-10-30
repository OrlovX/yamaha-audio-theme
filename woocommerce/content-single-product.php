<?php
/**
 * Single product layout: gallery left, sticky buy box right, sections below.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore
	return;
}
$percent = yamaha_audio_discount_percent( $product );
?>
<div class="hx-wrap hx-single">
	<?php woocommerce_breadcrumb(); ?>

	<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'hx-single__top', $product ); ?>>
		<div class="hx-single__gallery">
			<?php if ( $percent && yamaha_audio_in_campaign( $product ) ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg hx-pill--camp"><small><?php echo esc_html( yamaha_audio_campaign()['badge'] ); ?></small>−<?php echo (int) $percent; ?>%</span>
			<?php elseif ( $percent ) : ?><span class="hx-pill hx-pill--sale hx-pill--lg">−<?php echo (int) $percent; ?>%</span><?php endif; ?>
			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
		</div>
		<div class="hx-single__summary summary entry-summary" id="hx-summary">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>
	</div>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
