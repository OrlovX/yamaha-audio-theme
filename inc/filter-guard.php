<?php
/**
 * The gate in front of filtered product lists (?f[pa_culoare]=negru…).
 *
 * A filtered list is an uncached, expensive page and the options combine without end. Crawlers that
 * ignore robots.txt and rel="nofollow" walk those links from thousands of addresses. In November 2026
 * that stalled the sister shop, hifiexpert.ro, twice; this is the gate that stopped it there, brought
 * here before the same crawlers arrive. What was learned:
 *
 *  1. A cookie set by script on any page view is not enough: the crawlers run scripts like a browser
 *     does. They received the little page that sets the cookie, reloaded, and got the full list.
 *  2. So the cookie is a mark that a person touched the page. main.js writes it on the first real
 *     mouse movement, touch, click or key press (event.isTrusted), and it carries the time it was
 *     written, so one copied value doesn't work forever. Scrolling doesn't count (crawlers scroll to
 *     load images), nor does a mousemove that stays on one spot (Chrome makes those up by itself).
 *     A crawler loads pages and reads links; it doesn't move a mouse. Someone who opens a filtered
 *     link cold sees a small page that goes on by itself at the first movement, with a button for
 *     touch screens.
 *  3. A ceiling, whatever happens: at most HX_FILTER_CAP filtered lists a minute for the whole shop
 *     (default 90, far above what people ask for). Above it visitors get a page that retries by
 *     itself. It needs a persistent object cache (Redis); without one there is no ceiling.
 *
 * The check runs twice. tools/mu-plugins/hx-filter-guard.php calls it before the plugins and the
 * theme are loaded, so a refusal costs a few milliseconds instead of a full page load. inc/filters.php
 * calls it again on init, where a sign-in can be verified. This file must therefore work without the
 * theme and without the plugins: plain PHP and what WordPress has loaded by then.
 *
 * The mark is not a secret (the recipe is in main.js). It only has to be something that reading
 * pages doesn't produce. Signed-in people are never asked.
 *
 * The same file, apart from this introduction, is in the hifiexpert theme: keep the two alike.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'hx_filter_guard' ) ) :

	/**
	 * Is this cookie value a mark written by main.js or by the gate page, and recent enough?
	 * Shape: <unix time in base 36>.<fnv1a32 of "<that>|hx-filter">.
	 */
	function hx_filter_mark_ok( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^([0-9a-z]{5,8})\.([0-9a-f]{8})$/', $value, $m ) ) {
			return false;
		}
		// Accepted for 15 days (main.js renews it once a day; the cookie itself lasts a year), with
		// two days of slack for a visitor whose clock is ahead.
		$age = time() - (int) base_convert( $m[1], 36, 10 );
		if ( $age < -2 * 86400 || $age > 15 * 86400 ) {
			return false;
		}
		return hash_equals( hash( 'fnv1a32', $m[1] . '|hx-filter' ), $m[2] );
	}

	/**
	 * Counts this filtered list against the shop-wide ceiling. True when the minute is already full.
	 */
	function hx_filter_over_cap() {
		static $answer = null;
		if ( null !== $answer ) {
			return $answer; // the early and the late check count one request once.
		}
		$answer = false;
		$cap    = defined( 'HX_FILTER_CAP' ) ? (int) HX_FILTER_CAP : 90;
		if ( $cap < 1 || ! function_exists( 'wp_cache_incr' ) || ! function_exists( 'wp_using_ext_object_cache' ) || ! wp_using_ext_object_cache() ) {
			return $answer;
		}
		$key = 'm' . (int) floor( time() / 60 );
		wp_cache_add( $key, 0, 'hx_filter_guard', 180 );
		$count  = wp_cache_incr( $key, 1, 'hx_filter_guard' );
		$answer = false !== $count && (int) $count > $cap;
		return $answer;
	}

	/**
	 * The gate. $early = called from the must-use plugin, before plugins, theme and the signed-in user exist.
	 */
	function hx_filter_guard( $early = false ) {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( empty( $_GET['f'] ) || ! is_array( $_GET['f'] ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '';
		if ( 'GET' !== $method && 'HEAD' !== $method ) {
			return;
		}
		if ( ( defined( 'WP_ADMIN' ) && WP_ADMIN ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( false !== strpos( $uri, '/wp-json/' ) || false !== strpos( $uri, '/wp-admin/' ) || false !== strpos( $uri, 'rest_route=' ) || false !== strpos( $uri, 'wc-ajax=' ) ) {
			return;
		}
		if ( $early ) {
			// A sign-in cookie can't be verified this early: leave those requests to the check on init.
			foreach ( array_keys( $_COOKIE ) as $name ) {
				if ( 0 === strpos( (string) $name, 'wordpress_logged_in_' ) ) {
					return;
				}
			}
		} elseif ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			return;
		}
		$marked = hx_filter_mark_ok( isset( $_COOKIE['hx_h'] ) ? $_COOKIE['hx_h'] : '' );
		// phpcs:enable
		if ( $marked && ! hx_filter_over_cap() ) {
			return;
		}
		hx_filter_guard_page( $marked ? 'busy' : 'check', $method );
	}

	/**
	 * The small page shown instead of the list, then exit.
	 * 'check' = no mark yet: goes on at the first real movement, touch or key. 'busy' = the ceiling: retries by itself.
	 */
	function hx_filter_guard_page( $mode, $method = 'GET' ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			http_response_code( 429 );
			header( 'Retry-After: ' . ( 'busy' === $mode ? '15' : '5' ) );
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'X-HX-Filter: ' . $mode );
		}
		if ( 'HEAD' === $method ) {
			exit;
		}
		$host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^a-z0-9.\-]/i', '', (string) $_SERVER['HTTP_HOST'] ) : ''; // phpcs:ignore
		$head = '<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . htmlspecialchars( $host, ENT_QUOTES, 'UTF-8' ) . '</title>'
			. '<style>body{font:16px/1.5 -apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:64px 20px;text-align:center;background:#f6f5f3;color:#1a1a1a}a{color:inherit}'
			. 'button{font:inherit;font-weight:600;margin-top:8px;padding:12px 28px;border:0;border-radius:999px;background:#1a1a1a;color:#fff;cursor:pointer}</style></head><body>';

		if ( 'busy' === $mode ) {
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $head
				. '<p id="m">Sunt multe cereri în acest moment. Reîncercăm automat în câteva secunde…</p>'
				. '<script>(function(){var n=0;try{n=+sessionStorage.getItem("hx_f_busy")||0;sessionStorage.setItem("hx_f_busy",n+1)}catch(e){}'
				. 'if(n<5){setTimeout(function(){location.reload()},6000+Math.floor(Math.random()*4000))}'
				. 'else{try{sessionStorage.removeItem("hx_f_busy")}catch(e){}document.getElementById("m").innerHTML=\'Sunt multe cereri în acest moment. <a href="">Reîncearcă</a> sau mergi <a href="/">înapoi la produse</a>.\'}})();</script>'
				. '<noscript><p><a href="/">Înapoi la produse</a></p></noscript></body></html>';
			exit;
		}

		// The server's clock goes into the mark, so a visitor whose own clock is wrong still gets through.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $head
			. '<p id="m">Se încarcă produsele…</p><p><button type="button" id="b">Arată produsele</button></p>'
			. '<script>(function(){var d=document,done=false,mx=null,my=null,T=' . (int) time() . ';'
			. 'function h(s){var x=0x811c9dc5,i;for(i=0;i<s.length;i++){x^=s.charCodeAt(i);x=Math.imul(x,0x01000193)>>>0}return("0000000"+x.toString(16)).slice(-8)}'
			. 'function go(e){if(done||!e||e.isTrusted!==true){return}'
			. 'if(e.type==="mousemove"){if(mx===null){mx=e.clientX;my=e.clientY;return}if(e.clientX===mx&&e.clientY===my){return}}'
			. 'done=true;var t=T.toString(36),n=0,ok=false;'
			. 'try{d.cookie="hx_h="+t+"."+h(t+"|hx-filter")+"; path=/; max-age=31536000; SameSite=Lax"+(location.protocol==="https:"?"; Secure":"");ok=d.cookie.indexOf("hx_h="+t+".")>-1}catch(x){}'
			. 'try{n=+sessionStorage.getItem("hx_h_try")||0;sessionStorage.setItem("hx_h_try",n+1)}catch(x){}'
			. 'if(ok&&n<3){location.replace(location.href);return}'
			. 'd.getElementById("b").hidden=true;d.getElementById("m").innerHTML=\'Filtrele au nevoie de cookie-uri active. <a href="/">Înapoi la produse</a>\'}'
			. '["pointerdown","touchstart","mousemove","keydown","click"].forEach(function(n){d.addEventListener(n,go,{capture:true,passive:true})})})();</script>'
			. '<noscript><p>Filtrele au nevoie de JavaScript. <a href="/">Înapoi la produse</a></p></noscript></body></html>';
		exit;
	}

endif;
