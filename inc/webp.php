<?php
/**
 * WebP copies of uploaded images.
 *
 * Product photos are mostly large PNGs (cut-out backgrounds); as WebP the same picture is a
 * fraction of the size. For every upload "photo.png" a sibling "photo.png.webp" is written next to
 * it, and image tags point at the sibling when it exists. Nothing is replaced or deleted: remove
 * the .webp files and the site serves the originals again.
 *
 * - new uploads are converted as they come in;
 * - existing ones:  wp hx-webp [--limit=<n>] [--quality=<1-100>] [--min-kb=<n>] [--dry-run]
 * - share images (og:image) and structured data keep the original files.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/** The .webp sibling's URL for an uploads URL, or the URL unchanged. */
function yamaha_audio_webp_url( $url ) {
	static $base = null, $seen = array();
	if ( ! is_string( $url ) || '' === $url || ! preg_match( '/\.(png|jpe?g)$/i', $url ) ) {
		return $url;
	}
	if ( isset( $seen[ $url ] ) ) {
		return $seen[ $url ];
	}
	if ( null === $base ) {
		$up   = wp_get_upload_dir();
		$base = array( $up['baseurl'], $up['basedir'], set_url_scheme( $up['baseurl'], 'https' ), set_url_scheme( $up['baseurl'], 'http' ) );
	}
	$out = $url;
	foreach ( array( $base[0], $base[2], $base[3] ) as $prefix ) {
		if ( 0 === strpos( $url, $prefix . '/' ) ) {
			$path = $base[1] . substr( $url, strlen( $prefix ) );
			if ( false === strpos( $path, '..' ) && is_file( $path . '.webp' ) ) {
				$out = $url . '.webp';
			}
			break;
		}
	}
	$seen[ $url ] = $out;
	return $out;
}
function yamaha_audio_webp_srcset( $srcset ) {
	if ( ! is_string( $srcset ) || '' === $srcset ) {
		return $srcset;
	}
	$parts = array();
	foreach ( explode( ',', $srcset ) as $candidate ) {
		$bits = preg_split( '/\s+/', trim( $candidate ), 2 );
		if ( ! empty( $bits[0] ) ) {
			$bits[0] = yamaha_audio_webp_url( $bits[0] );
		}
		$parts[] = implode( ' ', $bits );
	}
	return implode( ', ', $parts );
}
function yamaha_audio_webp_active() {
	return ! is_admin() && ! is_feed() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! wp_doing_cron() && apply_filters( 'yamaha_audio_webp_enabled', true );
}

// Images printed by WordPress / WooCommerce / the theme.
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
	if ( yamaha_audio_webp_active() && is_array( $attr ) ) {
		if ( ! empty( $attr['src'] ) ) {
			$attr['src'] = yamaha_audio_webp_url( $attr['src'] );
		}
		if ( ! empty( $attr['srcset'] ) ) {
			$attr['srcset'] = yamaha_audio_webp_srcset( $attr['srcset'] );
		}
	}
	return $attr;
}, 99 );
// Images inside post and product content.
add_filter( 'wp_content_img_tag', function ( $tag ) {
	if ( ! yamaha_audio_webp_active() ) {
		return $tag;
	}
	$tag = preg_replace_callback( '/\ssrc=(["\'])(.*?)\1/i', function ( $m ) {
		return ' src=' . $m[1] . esc_url( yamaha_audio_webp_url( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) ) ) . $m[1];
	}, $tag, 1 );
	return preg_replace_callback( '/\ssrcset=(["\'])(.*?)\1/i', function ( $m ) {
		return ' srcset=' . $m[1] . esc_attr( yamaha_audio_webp_srcset( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) ) ) . $m[1];
	}, $tag, 1 );
}, 99 );

/**
 * Write "<file>.webp" next to an image. Returns bytes saved, 0 when skipped (already there, not
 * smaller, unreadable), or a WP_Error.
 */
function yamaha_audio_webp_convert( $path, $quality = 82 ) {
	if ( ! preg_match( '/\.(png|jpe?g)$/i', $path ) || ! is_file( $path ) ) {
		return 0;
	}
	$target = $path . '.webp';
	if ( is_file( $target ) && filemtime( $target ) >= filemtime( $path ) ) {
		return 0;
	}
	if ( ! function_exists( 'imagewebp' ) ) {
		return new WP_Error( 'no_webp', 'PHP GD has no WebP support on this server.' );
	}
	$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $info || $info[0] * $info[1] > 25000000 ) {
		return 0; // not an image, or too large to hold in memory safely.
	}
	wp_raise_memory_limit( 'image' );
	$im = IMAGETYPE_PNG === $info[2] ? @imagecreatefrompng( $path ) : ( IMAGETYPE_JPEG === $info[2] ? @imagecreatefromjpeg( $path ) : false ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $im ) {
		return 0;
	}
	if ( ! imageistruecolor( $im ) ) {
		imagepalettetotruecolor( $im );
	}
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	$tmp = $target . '.tmp';
	$ok  = @imagewebp( $im, $tmp, (int) $quality ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	imagedestroy( $im );
	if ( ! $ok || ! is_file( $tmp ) || filesize( $tmp ) < 100 ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return 0;
	}
	$saved = filesize( $path ) - filesize( $tmp );
	if ( $saved < filesize( $path ) * 0.1 ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return 0; // not worth a second file.
	}
	rename( $tmp, $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $saved;
}

// New uploads: the original and every generated size.
add_filter( 'wp_generate_attachment_metadata', function ( $meta, $id ) {
	if ( ! is_array( $meta ) || empty( $meta['file'] ) || ! apply_filters( 'yamaha_audio_webp_enabled', true ) ) {
		return $meta;
	}
	$file = get_attached_file( $id );
	if ( $file ) {
		yamaha_audio_webp_convert( $file );
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
			if ( ! empty( $size['file'] ) ) {
				yamaha_audio_webp_convert( path_join( dirname( $file ), $size['file'] ) );
			}
		}
	}
	return $meta;
}, 20, 2 );
// A deleted image takes its siblings with it.
add_filter( 'wp_delete_file', function ( $file ) {
	if ( is_string( $file ) && is_file( $file . '.webp' ) ) {
		@unlink( $file . '.webp' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	return $file;
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Create WebP copies for the images already in uploads. Safe to stop and run again.
	 *
	 * ## OPTIONS
	 * [--limit=<n>]    Convert at most this many files in this run (default 0 = all).
	 * [--quality=<q>]  WebP quality 1-100 (default 82).
	 * [--min-kb=<n>]   Skip files smaller than this (default 8).
	 * [--dry-run]      Only count what would be converted.
	 */
	WP_CLI::add_command( 'hx-webp', function ( $args, $assoc ) {
		$limit   = (int) ( $assoc['limit'] ?? 0 );
		$quality = max( 1, min( 100, (int) ( $assoc['quality'] ?? 82 ) ) );
		$min     = (int) ( $assoc['min-kb'] ?? 8 ) * 1024;
		$dry     = isset( $assoc['dry-run'] );
		$base    = wp_get_upload_dir()['basedir'];
		$done    = 0;
		$skipped = 0;
		$saved   = 0;
		$seen    = 0;
		$it      = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			$path = $file->getPathname();
			if ( ! preg_match( '/\.(png|jpe?g)$/i', $path ) || $file->getSize() < $min || is_file( $path . '.webp' ) ) {
				continue;
			}
			++$seen;
			if ( $dry ) {
				continue;
			}
			$result = yamaha_audio_webp_convert( $path, $quality );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
			if ( $result > 0 ) {
				++$done;
				$saved += $result;
			} else {
				++$skipped;
			}
			if ( 0 === ( $done + $skipped ) % 200 ) {
				WP_CLI::log( sprintf( '%d converted, %d left as they are, %.1f MB saved so far', $done, $skipped, $saved / 1048576 ) );
			}
			if ( $limit && $done >= $limit ) {
				break;
			}
		}
		if ( $dry ) {
			WP_CLI::success( sprintf( '%d images have no WebP copy yet.', $seen ) );
			return;
		}
		WP_CLI::success( sprintf( '%d converted, %d left as they are (no gain or unreadable), %.1f MB saved.', $done, $skipped, $saved / 1048576 ) );
	} );
}
