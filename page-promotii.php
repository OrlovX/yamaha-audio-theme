<?php
/**
 * Template Name: Promoții
 *
 * Every product on sale and in stock, biggest discount first, with category chips and sorting.
 * Applied automatically to the page with the slug "promotii".
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

get_header();

$exclude = array_filter( array_map( 'trim', explode( ',', (string) yamaha_audio_opt( 'home_exclude_cats' ) ) ) );
$cat     = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$sort    = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'discount'; // phpcs:ignore WordPress.Security.NonceVerification
$page    = max( 1, isset( $_GET['pg'] ) ? (int) $_GET['pg'] : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
$per     = 24;
$sorts   = array(
	'discount'   => __( 'Reducerea cea mai mare', 'yamaha-audio' ),
	'popularity' => __( 'Cele mai vândute', 'yamaha-audio' ),
	'price'      => __( 'Preț crescător', 'yamaha-audio' ),
	'price-desc' => __( 'Preț descrescător', 'yamaha-audio' ),
	'newest'     => __( 'Cele mai noi', 'yamaha-audio' ),
);
if ( ! isset( $sorts[ $sort ] ) ) {
	$sort = 'discount';
}

/**
 * The sale set, cached 15 minutes: [ id => [ percent, price, sales, date, cats[] ] ].
 */
$set = get_transient( 'hx_promo_set' );
if ( ! is_array( $set ) ) {
	$set = array();
	$ids = array_unique( array_map( 'intval', (array) wc_get_product_ids_on_sale() ) );
	if ( $ids ) {
		$ids = wc_get_products( array(
			'status'       => 'publish',
			'include'      => $ids,
			'limit'        => -1,
			'return'       => 'ids',
			'stock_status' => 'instock',
		) );
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || ! $p->is_visible() || ! $p->is_on_sale() ) {
				continue;
			}
			$cats = (array) wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
			if ( $exclude && array_intersect( $cats, $exclude ) ) {
				continue;
			}
			// all ancestors too, so top-level chips match
			$all = $cats;
			foreach ( wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) ) as $tid ) {
				foreach ( get_ancestors( $tid, 'product_cat' ) as $aid ) {
					$t = get_term( $aid, 'product_cat' );
					if ( $t && ! is_wp_error( $t ) ) {
						$all[] = $t->slug;
					}
				}
			}
			$set[ $id ] = array(
				'percent' => (int) yamaha_audio_discount_percent( $p ),
				'price'   => (float) $p->get_price(),
				'sales'   => (int) $p->get_total_sales(),
				'date'    => (int) get_post_time( 'U', true, $id ),
				'cats'    => array_values( array_unique( $all ) ),
			);
		}
	}
	set_transient( 'hx_promo_set', $set, 15 * MINUTE_IN_SECONDS );
}

// Category chips: top-level categories present in the set, with counts.
$chip_counts = array();
foreach ( $set as $row ) {
	foreach ( $row['cats'] as $slug ) {
		$chip_counts[ $slug ] = ( $chip_counts[ $slug ] ?? 0 ) + 1;
	}
}
$top = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'orderby' => 'menu_order', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
$chips = array();
foreach ( is_wp_error( $top ) ? array() : $top as $t ) {
	if ( ! empty( $chip_counts[ $t->slug ] ) && ! in_array( $t->slug, $exclude, true ) ) {
		$chips[] = array( 'slug' => $t->slug, 'name' => $t->name, 'count' => $chip_counts[ $t->slug ] );
	}
}

// Filter + sort.
$rows = $cat ? array_filter( $set, function ( $r ) use ( $cat ) { return in_array( $cat, $r['cats'], true ); } ) : $set;
uasort( $rows, function ( $a, $b ) use ( $sort ) {
	switch ( $sort ) {
		case 'popularity':
			return $b['sales'] <=> $a['sales'] ?: $b['percent'] <=> $a['percent'];
		case 'price':
			return $a['price'] <=> $b['price'];
		case 'price-desc':
			return $b['price'] <=> $a['price'];
		case 'newest':
			return $b['date'] <=> $a['date'];
		default:
			return $b['percent'] <=> $a['percent'] ?: $b['sales'] <=> $a['sales'];
	}
} );
$total    = count( $rows );
$pages    = max( 1, (int) ceil( $total / $per ) );
$page     = min( $page, $pages );
$page_ids = array_slice( array_keys( $rows ), ( $page - 1 ) * $per, $per );
$base     = get_permalink();
$url      = function ( $args ) use ( $base, $cat, $sort ) {
	$q = array_filter( array_merge( array( 'cat' => $cat, 'sort' => 'discount' === $sort ? '' : $sort ), $args ) );
	return $q ? add_query_arg( $q, $base ) : $base;
};
$wa    = yamaha_audio_whatsapp_url( __( 'Bună! Am văzut o ofertă pe site și aș vrea să…', 'yamaha-audio' ) );
$rates = (int) yamaha_audio_opt( 'rates_count' );
?>
<div class="hx-wrap hx-shop hx-promo">
	<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) { woocommerce_breadcrumb(); } ?>

	<header class="hx-shop__head">
		<div class="hx-shop__titles">
			<?php $camp = yamaha_audio_campaign(); ?>
			<span class="hx-eyebrow"><?php echo $camp && $camp['end_txt'] ? esc_html( sprintf( __( 'Până pe %s · doar produse în stoc · livrare în 24h', 'yamaha-audio' ), $camp['end_txt'] ) ) : esc_html__( 'Doar produse în stoc · livrare în 24h', 'yamaha-audio' ); ?></span>
			<h1 class="hx-shop__title<?php echo $camp ? ' hx-shop__title--camp' : ''; ?>"><?php echo $camp ? esc_html( $camp['name'] ) : esc_html( get_the_title() ); ?></h1>
			<?php if ( $camp && $camp['tagline'] ) : ?><p class="hx-shop__text hx-shop__text--camp"><?php echo esc_html( $camp['tagline'] ); ?></p><?php endif; ?>
			<p class="hx-shop__text"><?php esc_html_e( 'Reduceri reale, calculate față de cel mai mic preț din ultimele 30 de zile. Fiecare produs de aici este în stoc și pleacă în aceeași zi.', 'yamaha-audio' ); ?></p>
		</div>
		<?php if ( $total ) : ?>
			<span class="hx-shop__count"><?php printf( esc_html( _n( '%d produs la ofertă', '%d produse la ofertă', $total, 'yamaha-audio' ) ), (int) $total ); ?></span>
		<?php endif; ?>
	</header>

	<?php if ( $chips ) : ?>
		<div class="hx-chips" role="navigation" aria-label="<?php esc_attr_e( 'Categorii', 'yamaha-audio' ); ?>">
			<a class="hx-chip<?php echo $cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( $url( array( 'cat' => '', 'pg' => '' ) ) ); ?>"><?php esc_html_e( 'Toate', 'yamaha-audio' ); ?> <small><?php echo (int) count( $set ); ?></small></a>
			<?php foreach ( $chips as $c ) : ?>
				<a class="hx-chip<?php echo $cat === $c['slug'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url( array( 'cat' => $c['slug'], 'pg' => '' ) ) ); ?>"><?php echo esc_html( $c['name'] ); ?> <small><?php echo (int) $c['count']; ?></small></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="hx-toolbar">
		<div class="hx-toolbar__left">
			<?php if ( $rates ) : ?>
				<span class="hx-promo__rates"><?php echo yamaha_audio_icon( 'card', 16 ); ?><?php printf( esc_html__( 'Până la %d rate %s', 'yamaha-audio' ), $rates, esc_html( yamaha_audio_opt( 'rates_label' ) ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="hx-toolbar__right">
			<form class="woocommerce-ordering hx-ordering" method="get" action="<?php echo esc_url( $base ); ?>">
				<?php if ( $cat ) : ?><input type="hidden" name="cat" value="<?php echo esc_attr( $cat ); ?>"><?php endif; ?>
				<label class="screen-reader-text" for="hx-promo-sort"><?php esc_html_e( 'Sortare', 'yamaha-audio' ); ?></label>
				<select name="sort" id="hx-promo-sort" class="orderby" onchange="this.form.submit()">
					<?php foreach ( $sorts as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $sort, $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>
	</div>

	<?php if ( $page_ids ) : ?>
		<ul class="hx-grid hx-grid--products products">
			<?php
			foreach ( $page_ids as $i => $id ) {
				$p = wc_get_product( $id );
				if ( $p ) {
					yamaha_audio_product_card( $p, array( 'lazy' => $i > 5 ) );
				}
			}
			if ( $wa ) :
				?>
				<li class="hx-helpcard">
					<div>
						<span class="hx-eyebrow"><?php esc_html_e( 'Vrei un pachet la preț de ofertă?', 'yamaha-audio' ); ?></span>
						<span class="hx-helpcard__title"><?php esc_html_e( 'Combinăm ofertele într‑un sistem complet, cu preț ferm, gata de ascultat în 24 de ore.', 'yamaha-audio' ); ?></span>
					</div>
					<div>
						<a class="hx-btn hx-btn--primary hx-btn--sm" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Scrie‑ne pe WhatsApp', 'yamaha-audio' ); ?></a>
						<span class="hx-helpcard__note"><?php echo esc_html( yamaha_audio_opt( 'topbar_hours' ) ); ?></span>
					</div>
				</li>
			<?php endif; ?>
		</ul>
		<?php if ( $pages > 1 ) : ?>
			<nav class="woocommerce-pagination hx-pagination" aria-label="<?php esc_attr_e( 'Paginare', 'yamaha-audio' ); ?>">
				<?php
				echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput
					'base'      => str_replace( 'HXPAGE', '%#%', $url( array( 'pg' => 'HXPAGE' ) ) ),
					'format'    => '',
					'current'   => $page,
					'total'     => $pages,
					'mid_size'  => 1,
					'prev_text' => '‹',
					'next_text' => '›',
					'type'      => 'list',
				) );
				?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<p class="hx-shop__empty"><?php esc_html_e( 'Nu avem oferte active în această categorie chiar acum.', 'yamaha-audio' ); ?> <a href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Vezi toate promoțiile', 'yamaha-audio' ); ?></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();
