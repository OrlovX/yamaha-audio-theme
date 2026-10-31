<?php
/**
 * Single blog post.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cats = get_the_category();
	?>
	<article <?php post_class( 'hx-wrap hx-article' ); ?>>
		<header class="hx-article__head">
			<?php
			$hx_tech = function_exists( 'yamaha_audio_is_tech_post' ) && yamaha_audio_is_tech_post();
			if ( $hx_tech ) {
				$hx_tpage = get_page_by_path( 'tehnologii' );
				echo '<a class="hx-eyebrow" href="' . esc_url( $hx_tpage ? get_permalink( $hx_tpage ) : get_category_link( $cats[0] ) ) . '">' . esc_html__( 'Tehnologii Yamaha', 'yamaha-audio' ) . '</a>';
			} elseif ( $cats ) {
				echo '<a class="hx-eyebrow" href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
			}
			?>
			<h1 class="hx-article__title"><?php the_title(); ?></h1>
			<p class="hx-article__meta"><?php echo esc_html( get_the_date() ); ?> · <?php printf( esc_html__( '%s min de citit', 'yamaha-audio' ), (int) max( 1, round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ) ); ?></p>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="hx-stage hx-stage--article"><?php the_post_thumbnail( 'yamaha-audio-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>
		<div class="hx-prose hx-article__body">
			<?php the_content(); ?>
		</div>
		<?php if ( $hx_tech ) { yamaha_audio_tech_post_extras(); } ?>
		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="hx-article__comments">';
			comments_template();
			echo '</div>';
		}
		?>
	</article>
	<?php
	$related = $hx_tech ? array() : get_posts( array( 'numberposts' => 3, 'post__not_in' => array( get_the_ID() ), 'category__in' => wp_list_pluck( $cats, 'term_id' ) ) );
	if ( $related ) :
		?>
		<section class="hx-section hx-wrap">
			<?php yamaha_audio_section_head( __( 'Citește și', 'yamaha-audio' ) ); ?>
			<div class="hx-grid hx-grid--3"><?php foreach ( $related as $r ) { yamaha_audio_post_card( $r->ID ); } ?></div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
