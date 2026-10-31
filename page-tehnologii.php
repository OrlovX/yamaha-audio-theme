<?php
/**
 * Template Name: Tehnologii Yamaha
 * Overview of the technology pages, grouped by product family. Also used automatically for the page with slug "tehnologii".
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();
$hx_index  = yamaha_audio_tech_index();
$hx_groups = yamaha_audio_tech_groups();
$hx_guides = array_filter( $hx_index, function ( $t ) { return $t['guide']; } );
?>
<div class="hx-wrap hx-techpage">
	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<span class="hx-eyebrow"><?php esc_html_e( 'Ghid', 'yamaha-audio' ); ?></span>
			<h1 class="hx-shop__title"><?php the_title(); ?></h1>
			<?php if ( get_the_content() ) : ?>
				<div class="hx-shop__text hx-prose"><?php the_content(); ?></div>
			<?php else : ?>
				<p class="hx-shop__text"><?php esc_html_e( 'Ce înseamnă fiecare nume din fișele produselor Yamaha, explicat pe scurt: cum funcționează, ce schimbă când asculți și pe ce modele îl găsești.', 'yamaha-audio' ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( ! $hx_index ) : ?>
		<p class="hx-empty"><?php esc_html_e( 'Paginile de tehnologii sunt în pregătire.', 'yamaha-audio' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $hx_groups as $gk => $g ) : ?>
		<section class="hx-section hx-techgroup" id="<?php echo esc_attr( $gk ); ?>">
			<?php yamaha_audio_section_head( $g['label'] ); ?>
			<ul class="hx-techlist hx-techlist--grid">
				<?php foreach ( $g['slugs'] as $slug ) : $t = $hx_index[ $slug ]; ?>
					<li><a class="hx-techcard" href="<?php echo esc_url( $t['url'] ); ?>"><strong><?php echo esc_html( $t['name'] ); ?></strong><span><?php echo esc_html( wp_trim_words( $t['excerpt'] ?: $t['title'], 22, '…' ) ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endforeach; ?>

	<?php if ( $hx_guides ) : ?>
		<section class="hx-section hx-techgroup" id="ghiduri">
			<?php yamaha_audio_section_head( __( 'Ghiduri pe game', 'yamaha-audio' ) ); ?>
			<ul class="hx-techlist hx-techlist--grid">
				<?php foreach ( $hx_guides as $t ) : ?>
					<li><a class="hx-techcard hx-techcard--guide" href="<?php echo esc_url( $t['url'] ); ?>"><strong><?php echo esc_html( $t['title'] ); ?></strong><span><?php echo esc_html( wp_trim_words( $t['excerpt'] ?: $t['title'], 22, '…' ) ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
