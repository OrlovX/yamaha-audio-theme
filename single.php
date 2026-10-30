<?php
/**
 * Single blog post.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cats = get_the_category();
	?>
	<article <?php post_class( 'hx-wrap hx-article' ); ?>>
		<header class="hx-article__head">
			<?php if ( $cats ) : ?><a class="hx-eyebrow" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a><?php endif; ?>
			<h1 class="hx-article__title"><?php the_title(); ?></h1>
			<p class="hx-article__meta"><?php echo esc_html( get_the_date() ); ?> · <?php printf( esc_html__( '%s min de citit', 'hifiexpert' ), (int) max( 1, round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ) ); ?></p>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="hx-stage hx-stage--article"><?php the_post_thumbnail( 'hifiexpert-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>
		<div class="hx-prose hx-article__body">
			<?php the_content(); ?>
		</div>
		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="hx-article__comments">';
			comments_template();
			echo '</div>';
		}
		?>
	</article>
	<?php
	$related = get_posts( array( 'numberposts' => 3, 'post__not_in' => array( get_the_ID() ), 'category__in' => wp_list_pluck( $cats, 'term_id' ) ) );
	if ( $related ) :
		?>
		<section class="hx-section hx-wrap">
			<?php hifiexpert_section_head( __( 'Citește și', 'hifiexpert' ) ); ?>
			<div class="hx-grid hx-grid--3"><?php foreach ( $related as $r ) { hifiexpert_post_card( $r->ID ); } ?></div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
