<?php
/**
 * Apply proofread product texts (name, short description, description) from a JSON file.
 *
 * Run on the server, from /var/www/html:
 *   sudo -u www-data wp eval-file wp-content/themes/yamaha-audio/server/apply-product-text.php /tmp/products-text-fixed.json dry-run
 *   sudo -u www-data wp eval-file wp-content/themes/yamaha-audio/server/apply-product-text.php /tmp/products-text-fixed.json
 *
 * The JSON is a list of { id, name, short, desc, fixed: { name?, short?, desc? } } where name/short/desc are the
 * values exported before proofreading and fixed.* the corrected ones. A field is written only when the database
 * still holds the exported original (so nothing edited since the export is overwritten) and the correction differs.
 * Before writing anything, the current values of every touched product are saved to
 * /tmp/products-text-backup-<timestamp>.json (restore with the same script: pass that file and the word "restore").
 * Writes go straight to the posts table (exact bytes, no kses filtering), then caches/transients are cleared.
 *
 * @package yamaha-audio
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run through WP-CLI (wp eval-file).\n" );
}

$file = $args[0] ?? '';
$mode = strtolower( (string) ( $args[1] ?? '' ) ); // '', 'dry-run' or 'restore'
if ( ! $file || ! is_readable( $file ) ) {
	WP_CLI::error( 'Usage: wp eval-file apply-product-text.php /path/to/products-text-fixed.json [dry-run|restore]' );
}
$data = json_decode( (string) file_get_contents( $file ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'The JSON file could not be parsed.' );
}

global $wpdb;
$fields  = array( 'name' => 'post_title', 'short' => 'post_excerpt', 'desc' => 'post_content' );
$dry     = ( 'dry-run' === $mode );
$restore = ( 'restore' === $mode );
$backup  = array();
$written = 0;
$skipped = 0;
$touched = 0;
$now     = current_time( 'mysql' );
$now_gmt = current_time( 'mysql', true );

foreach ( $data as $item ) {
	$id = (int) ( $item['id'] ?? 0 );
	if ( ! $id ) {
		continue;
	}
	$post = get_post( $id );
	if ( ! $post || 'product' !== $post->post_type ) {
		WP_CLI::warning( "#$id: not a product, skipped." );
		continue;
	}
	$update = array();
	foreach ( $fields as $key => $column ) {
		if ( $restore ) {
			// Backup files hold the values to put back directly under name/short/desc.
			if ( array_key_exists( $key, $item ) && (string) $item[ $key ] !== (string) $post->$column ) {
				$update[ $column ] = (string) $item[ $key ];
			}
			continue;
		}
		if ( ! isset( $item['fixed'][ $key ] ) ) {
			continue;
		}
		$orig  = (string) ( $item[ $key ] ?? '' );
		$fixed = (string) $item['fixed'][ $key ];
		if ( $fixed === $orig ) {
			continue;
		}
		if ( (string) $post->$column !== $orig ) {
			WP_CLI::warning( sprintf( '#%d %s: %s changed in the database since the export - left as is.', $id, $post->post_title, $key ) );
			$skipped++;
			continue;
		}
		$update[ $column ] = $fixed;
	}
	if ( ! $update ) {
		continue;
	}
	$touched++;
	$what = implode( ', ', array_map( function ( $c ) use ( $fields ) { return array_search( $c, $fields, true ); }, array_keys( $update ) ) );
	WP_CLI::log( sprintf( '%s#%d %s: %s', $dry ? '[dry-run] ' : '', $id, $post->post_title, $what ) );
	if ( $dry ) {
		continue;
	}
	$backup[] = array( 'id' => $id, 'name' => $post->post_title, 'short' => $post->post_excerpt, 'desc' => $post->post_content );
	$update['post_modified']     = $now;
	$update['post_modified_gmt'] = $now_gmt;
	$ok = $wpdb->update( $wpdb->posts, $update, array( 'ID' => $id ) );
	if ( false === $ok ) {
		WP_CLI::warning( "#$id: database write failed: " . $wpdb->last_error );
		continue;
	}
	$written++;
	clean_post_cache( $id );
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $id );
	}
	do_action( 'yamaha_audio_product_text_applied', $id, array_keys( $update ) );
}

if ( $backup ) {
	$bfile = '/tmp/products-text-backup-' . gmdate( 'Ymd-His' ) . '.json';
	file_put_contents( $bfile, wp_json_encode( $backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
	WP_CLI::log( "Backup of the previous values: $bfile" );
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}
WP_CLI::success( sprintf( '%s: %d products %s, %d fields skipped (edited since export).', $dry ? 'Dry run' : ( $restore ? 'Restore' : 'Apply' ), $dry ? $touched : $written, $dry ? 'would change' : 'updated', $skipped ) );
