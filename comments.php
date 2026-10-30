<?php
/**
 * Comments (blog). Product reviews use WooCommerce's own template.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="hx-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="hx-section__title"><?php printf( esc_html( _n( '%d comentariu', '%d comentarii', get_comments_number(), 'yamaha-audio' ) ), (int) get_comments_number() ); ?></h2>
		<ol class="hx-comments__list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'   => __( 'Lasă un comentariu', 'yamaha-audio' ),
			'class_submit'  => 'hx-btn hx-btn--primary',
			'label_submit'  => __( 'Trimite', 'yamaha-audio' ),
		)
	);
	?>
</div>
