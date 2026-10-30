<?php
/**
 * Template Name: Pagină legală
 *
 * Terms, privacy, cookies, delivery, returns, warranty: a readable legal layout with a
 * sticky table of contents built from the H2 headings, the company card and links to the
 * other documents of the set.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$content  = apply_filters( 'the_content', get_the_content() );
$toc      = array();
$content  = preg_replace_callback(
	'/<h2([^>]*)>([\s\S]*?)<\/h2>/i',
	function ( $m ) use ( &$toc ) {
		$text = wp_strip_all_tags( $m[2] );
		$id   = 's-' . sanitize_title( $text );
		$toc[] = array( $id, $text );
		return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
	},
	$content
);
$v        = hifiexpert_legal_vars();
$others   = array_filter( hifiexpert_legal_links(), function ( $l ) { return untrailingslashit( $l['url'] ) !== untrailingslashit( get_permalink() ); } );
?>
<div class="hx-wrap hx-legal">
	<header class="hx-legal__head">
		<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>
		<h1 class="hx-legal__title"><?php the_title(); ?></h1>
		<p class="hx-legal__meta">
			<?php echo esc_html( sprintf( __( 'Ultima actualizare: %s', 'hifiexpert' ), get_the_modified_date( 'j F Y' ) ) ); ?>
			<span class="hx-legal__meta-sep" aria-hidden="true">·</span>
			<button type="button" class="hx-link hx-legal__print" onclick="window.print()"><?php esc_html_e( 'Tipărește / salvează PDF', 'hifiexpert' ); ?></button>
		</p>
	</header>

	<div class="hx-legal__layout">
		<?php if ( count( $toc ) > 1 ) : ?>
			<aside class="hx-legal__aside">
				<details class="hx-legal__toc" open>
					<summary><?php esc_html_e( 'Cuprins', 'hifiexpert' ); ?></summary>
					<ol>
						<?php foreach ( $toc as $t ) : ?><li><a href="#<?php echo esc_attr( $t[0] ); ?>"><?php echo esc_html( $t[1] ); ?></a></li><?php endforeach; ?>
					</ol>
				</details>
			</aside>
		<?php endif; ?>

		<article class="hx-legal__body hx-prose">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<section class="hx-legal__company" aria-label="<?php esc_attr_e( 'Date de identificare', 'hifiexpert' ); ?>">
				<h2 id="s-date-de-identificare"><?php esc_html_e( 'Date de identificare', 'hifiexpert' ); ?></h2>
				<dl>
					<div><dt><?php esc_html_e( 'Societate', 'hifiexpert' ); ?></dt><dd><?php echo $v['company']; ?></dd></div>
					<?php if ( $v['cui'] ) : ?><div><dt>CUI</dt><dd><?php echo $v['cui']; ?></dd></div><?php endif; ?>
					<?php if ( $v['reg'] ) : ?><div><dt><?php esc_html_e( 'Nr. Reg. Com.', 'hifiexpert' ); ?></dt><dd><?php echo $v['reg']; ?></dd></div><?php endif; ?>
					<?php if ( $v['address'] ) : ?><div><dt><?php esc_html_e( 'Sediu social', 'hifiexpert' ); ?></dt><dd><?php echo $v['address']; ?></dd></div><?php endif; ?>
					<div><dt><?php esc_html_e( 'Showroom', 'hifiexpert' ); ?></dt><dd><?php echo $v['showroom']; ?> · <?php echo $v['hours']; ?></dd></div>
					<div><dt>E‑mail</dt><dd><a href="mailto:<?php echo $v['email']; ?>"><?php echo $v['email']; ?></a></dd></div>
					<div><dt><?php esc_html_e( 'Telefon', 'hifiexpert' ); ?></dt><dd><?php echo $v['phone']; ?></dd></div>
					<?php if ( $v['capital'] ) : ?><div><dt><?php esc_html_e( 'Capital social', 'hifiexpert' ); ?></dt><dd><?php echo $v['capital']; ?></dd></div><?php endif; ?>
				</dl>
			</section>
		</article>
	</div>

	<?php if ( $others ) : ?>
		<nav class="hx-legal__others" aria-label="<?php esc_attr_e( 'Alte documente', 'hifiexpert' ); ?>">
			<span class="hx-legal__others-label"><?php esc_html_e( 'Alte documente', 'hifiexpert' ); ?></span>
			<div class="hx-chips">
				<?php foreach ( $others as $l ) : ?><a class="hx-chip" href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['title'] ); ?></a><?php endforeach; ?>
			</div>
		</nav>
	<?php endif; ?>
</div>
<?php
get_footer();
