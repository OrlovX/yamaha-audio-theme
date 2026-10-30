<?php
/**
 * Blog index / generic fallback.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="hx-wrap hx-blog">
	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<h1 class="hx-shop__title">
				<?php
				if ( is_home() && get_option( 'page_for_posts' ) ) {
					echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) );
				} elseif ( is_archive() ) {
					echo wp_kses_post( get_the_archive_title() );
				} elseif ( is_search() ) {
					printf( esc_html__( 'Rezultate pentru „%s”', 'yamaha-audio' ), esc_html( get_search_query() ) );
				} else {
					esc_html_e( 'Blog', 'yamaha-audio' );
				}
				?>
			</h1>
			<?php if ( is_archive() && get_the_archive_description() ) : ?>
				<p class="hx-shop__text"><?php echo wp_kses_post( wp_strip_all_tags( get_the_archive_description() ) ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="hx-grid hx-grid--3">
			<?php
			while ( have_posts() ) {
				the_post();
				yamaha_audio_post_card();
			}
			?>
		</div>
		<?php yamaha_audio_pagination(); ?>
	<?php else : ?>
		<p class="hx-shop__empty"><?php esc_html_e( 'Nimic de afișat încă.', 'yamaha-audio' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
