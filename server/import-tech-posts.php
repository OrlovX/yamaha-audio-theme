<?php
/**
 * Import / update the "Tehnologii Yamaha" posts from a JSON file (see inc/tech.php for the data model).
 *
 *   sudo -u www-data wp eval-file wp-content/themes/yamaha-audio/server/import-tech-posts.php /tmp/tech-posts.json dry-run
 *   sudo -u www-data wp eval-file wp-content/themes/yamaha-audio/server/import-tech-posts.php /tmp/tech-posts.json
 *
 * JSON: [ { slug, title, seo_title, excerpt, aliases[], related[], categories[], content, faq[], id?, guide? }, … ]
 * Links in content are placeholders — [[cat:slug|text]], [[prod:slug|text]], [[tech:slug|text]] — resolved here.
 * Idempotent: posts are matched by id, then by slug; existing posts keep their date. Also: creates the category
 * and the overview page (/tehnologii/), switches post permalinks to /%category%/%postname%/ when they are still
 * date-based, flushes rewrites and clears the page cache.
 *
 * @package yamaha-audio
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run through WP-CLI (wp eval-file).\n" );
}
$file = $args[0] ?? '';
$dry  = 'dry-run' === strtolower( (string) ( $args[1] ?? '' ) );
if ( ! $file || ! is_readable( $file ) ) {
	WP_CLI::error( 'Usage: wp eval-file import-tech-posts.php /path/tech-posts.json [dry-run]' );
}
$items = json_decode( (string) file_get_contents( $file ), true );
if ( ! is_array( $items ) ) {
	WP_CLI::error( 'JSON could not be parsed.' );
}

$cat_slug = 'tehnologii';
$cat      = get_term_by( 'slug', $cat_slug, 'category' );
if ( ! $cat ) {
	if ( $dry ) {
		WP_CLI::log( '[dry-run] would create category "Tehnologii Yamaha" (' . $cat_slug . ')' );
		$cat_id = 0;
	} else {
		$r = wp_insert_term( 'Tehnologii Yamaha', 'category', array( 'slug' => $cat_slug, 'description' => 'Ce înseamnă fiecare tehnologie Yamaha, explicat pe înțelesul tuturor.' ) );
		if ( is_wp_error( $r ) ) {
			WP_CLI::error( $r->get_error_message() );
		}
		$cat_id = (int) $r['term_id'];
		WP_CLI::log( "category created: $cat_id" );
	}
} else {
	$cat_id = (int) $cat->term_id;
}

$find_post = function ( $item ) {
	if ( ! empty( $item['id'] ) ) {
		$p = get_post( (int) $item['id'] );
		if ( $p && 'post' === $p->post_type ) {
			return $p;
		}
	}
	$p = get_page_by_path( $item['slug'], OBJECT, 'post' );
	if ( $p ) {
		return $p;
	}
	$q = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_hx_tech_slug', 'meta_value' => $item['slug'] ) );
	return $q ? $q[0] : null;
};

// Pass 1: create / update posts with raw content so every slug has a permalink.
$ids = array();
foreach ( $items as $item ) {
	if ( empty( $item['slug'] ) || empty( $item['title'] ) || empty( $item['content'] ) ) {
		WP_CLI::warning( 'skipping an item without slug/title/content' );
		continue;
	}
	$existing = $find_post( $item );
	$data     = array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $item['title'],
		'post_name'    => $item['slug'],
		'post_content' => $item['content'],
		'post_excerpt' => (string) ( $item['excerpt'] ?? '' ),
		'comment_status' => 'closed',
	);
	if ( $cat_id ) {
		$data['post_category'] = array( $cat_id );
	}
	if ( $existing ) {
		$data['ID'] = $existing->ID;
		WP_CLI::log( sprintf( '%supdate #%d %s → /%s/%s/', $dry ? '[dry-run] ' : '', $existing->ID, $existing->post_name, $cat_slug, $item['slug'] ) );
	} else {
		WP_CLI::log( sprintf( '%screate %s → /%s/%s/', $dry ? '[dry-run] ' : '', $item['title'], $cat_slug, $item['slug'] ) );
	}
	if ( $dry ) {
		continue;
	}
	$post_id = wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( $item['slug'] . ': ' . $post_id->get_error_message() );
		continue;
	}
	$ids[ $item['slug'] ] = (int) $post_id;
	$lines = function ( $v ) { return implode( "\n", array_filter( array_map( 'trim', (array) $v ) ) ); };
	update_post_meta( $post_id, '_hx_tech_slug', $item['slug'] );
	update_post_meta( $post_id, '_hx_aliases', $lines( $item['aliases'] ?? array() ) );
	update_post_meta( $post_id, '_hx_related', $lines( $item['related'] ?? array() ) );
	update_post_meta( $post_id, '_hx_cats', $lines( $item['categories'] ?? array() ) );
	update_post_meta( $post_id, '_hx_faq', wp_json_encode( array_values( (array) ( $item['faq'] ?? array() ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	if ( ! empty( $item['guide'] ) ) {
		update_post_meta( $post_id, '_hx_guide', '1' );
	} else {
		delete_post_meta( $post_id, '_hx_guide' );
	}
	if ( ! empty( $item['seo_title'] ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_title', $item['seo_title'] . ' %%sep%% %%sitename%%' );
	}
	if ( ! empty( $item['excerpt'] ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $item['excerpt'] );
	}
	if ( ! empty( $item['focus'] ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $item['focus'] );
	}
}

// Pass 2: resolve link placeholders now that every technology post exists.
if ( ! $dry ) {
	$resolve = function ( $content ) use ( $ids, $cat_slug ) {
		return preg_replace_callback( '/\[\[(cat|prod|tech):([a-z0-9\-]+)\|([^\]]+)\]\]/u', function ( $m ) use ( $ids ) {
			list( , $kind, $slug, $text ) = $m;
			$url = '';
			if ( 'cat' === $kind ) {
				$t = get_term_by( 'slug', $slug, 'product_cat' );
				$url = $t ? get_term_link( $t ) : '';
			} elseif ( 'prod' === $kind ) {
				$p = get_page_by_path( $slug, OBJECT, 'product' );
				$url = $p && 'publish' === $p->post_status ? get_permalink( $p ) : '';
			} else {
				$p = isset( $ids[ $slug ] ) ? get_post( $ids[ $slug ] ) : get_page_by_path( $slug, OBJECT, 'post' );
				$url = $p ? get_permalink( $p ) : '';
			}
			if ( ! $url || is_wp_error( $url ) ) {
				WP_CLI::warning( "unresolved link [[$kind:$slug]] – kept as text" );
				return $text;
			}
			return '<a href="' . esc_url( $url ) . '">' . $text . '</a>';
		}, $content );
	};
	foreach ( $ids as $slug => $post_id ) {
		$raw = get_post_field( 'post_content', $post_id );
		$new = $resolve( $raw );
		if ( $new !== $raw ) {
			wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_content' => $new ) ) );
		}
	}
}

// Overview page.
$page = get_page_by_path( $cat_slug );
if ( ! $page ) {
	if ( $dry ) {
		WP_CLI::log( '[dry-run] would create page /tehnologii/ (template page-tehnologii.php)' );
	} else {
		$pid = wp_insert_post( wp_slash( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Tehnologii Yamaha',
			'post_name'    => $cat_slug,
			'post_content' => '<p>Fiecare nume din fișa unui produs Yamaha – YPAO, CINEMA DSP, ToP-ART, True X – înseamnă ceva concret pentru felul în care sună acasă la tine. Aici le explicăm pe scurt: cum funcționează, ce schimbă când asculți și pe ce modele le găsești.</p>',
			'comment_status' => 'closed',
		) ), true );
		if ( ! is_wp_error( $pid ) ) {
			update_post_meta( $pid, '_wp_page_template', 'page-tehnologii.php' );
			update_post_meta( $pid, '_yoast_wpseo_title', 'Tehnologii Yamaha explicate: YPAO, CINEMA DSP, MusicCast, True X %%sep%% %%sitename%%' );
			update_post_meta( $pid, '_yoast_wpseo_metadesc', 'Ghidul tehnologiilor Yamaha, pe înțelesul tuturor: ce fac YPAO, CINEMA DSP, Surround:AI, ToP-ART, True X Surround și restul, și pe ce produse le găsești.' );
			WP_CLI::log( "page /tehnologii/ created: $pid" );
		}
	}
}

// Permalinks: /%category%/%postname%/ gives /tehnologii/<slug>/; old date URLs redirect through WordPress' canonical redirect.
$structure = (string) get_option( 'permalink_structure' );
if ( false !== strpos( $structure, '%year%' ) || '' === $structure ) {
	if ( $dry ) {
		WP_CLI::log( "[dry-run] would change permalink structure from '$structure' to /%category%/%postname%/" );
	} else {
		update_option( 'permalink_structure', '/%category%/%postname%/' );
		WP_CLI::log( 'permalink structure → /%category%/%postname%/' );
	}
}
if ( ! $dry ) {
	flush_rewrite_rules();
	delete_transient( 'hx_tech_index' );
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_hx\\_techprod\\_%' OR option_name LIKE '\\_transient\\_timeout\\_hx\\_techprod\\_%' OR option_name LIKE '\\_transient\\_hx\\_ptech\\_%' OR option_name LIKE '\\_transient\\_timeout\\_hx\\_ptech\\_%'" );
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	if ( function_exists( 'YoastSEO' ) ) {
		WP_CLI::log( 'Yoast: run  wp yoast index --reindex  if the new URLs do not show up in the sitemap within a few minutes.' );
	}
}
WP_CLI::success( sprintf( '%s: %d technology posts processed.', $dry ? 'Dry run' : 'Import', count( $ids ) ?: count( $items ) ) );
