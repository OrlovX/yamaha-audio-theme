<?php
/**
 * Dealer / admin price lists — ported from the yamaha-v2 theme.
 *
 *   [admin_products]  editable list (RRP, PA, PA1, SALE, STOC) + CSV export      — page /admin-products/
 *   [pa_products]     read-only list for dealers with a PA price                  — page /pa/
 *   [pa1_products]    read-only list for dealers with a PA1 price                 — page /pa1/
 *
 * PA / PA1 are WooCommerce global attributes (taxonomies pa_pa / pa_pa1); each price is a term, stored on the
 * product or, for variations that share a price, on the parent. Nothing public shows them (filters and spec
 * tables skip them).
 *
 * Access: a username + password per list (Customizer → Yamaha Audio → Liste dealeri, or the ADMIN_USER /
 * ADMIN_PASS / PA_USER_PA / PA_PASS_PA / PA_USER_PA1 / PA_PASS_PA1 constants in wp-config.php, which win).
 * A successful login sets a signed, HttpOnly cookie for 12 hours — no PHP sessions, so the rest of the site
 * stays cacheable; these three pages themselves are never cached and send X-Robots-Tag: noindex.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

/* ---------- credentials ---------- */

function yamaha_audio_dl_credentials( $role ) {
	$map = array(
		'admin' => array( 'ADMIN_USER', 'ADMIN_PASS', 'dealer_admin_user', 'dealer_admin_pass' ),
		'pa'    => array( 'PA_USER_PA', 'PA_PASS_PA', 'dealer_pa_user', 'dealer_pa_pass' ),
		'pa1'   => array( 'PA_USER_PA1', 'PA_PASS_PA1', 'dealer_pa1_user', 'dealer_pa1_pass' ),
	);
	if ( ! isset( $map[ $role ] ) ) {
		return array( '', '' );
	}
	list( $cu, $cp, $ou, $op ) = $map[ $role ];
	$user = defined( $cu ) ? constant( $cu ) : (string) yamaha_audio_opt( $ou );
	$pass = defined( $cp ) ? constant( $cp ) : (string) yamaha_audio_opt( $op );
	return array( (string) $user, (string) $pass );
}

/* ---------- signed cookie auth ---------- */

function yamaha_audio_dl_cookie_name( $role ) {
	return 'hx_dl_' . $role;
}

function yamaha_audio_dl_sign( $role, $exp ) {
	list( , $pass ) = yamaha_audio_dl_credentials( $role );
	return hash_hmac( 'sha256', $role . '|' . $exp, wp_salt( 'auth' ) . '|' . $pass );
}

function yamaha_audio_dl_is_authed( $role ) {
	$c = isset( $_COOKIE[ yamaha_audio_dl_cookie_name( $role ) ] ) ? (string) $_COOKIE[ yamaha_audio_dl_cookie_name( $role ) ] : '';
	if ( ! $c || false === strpos( $c, '|' ) ) {
		return false;
	}
	list( $exp, $sig ) = explode( '|', $c, 2 );
	if ( (int) $exp < time() ) {
		return false;
	}
	return hash_equals( yamaha_audio_dl_sign( $role, (int) $exp ), $sig );
}

function yamaha_audio_dl_set_cookie( $role, $on ) {
	$exp  = $on ? time() + 12 * HOUR_IN_SECONDS : time() - YEAR_IN_SECONDS;
	$val  = $on ? $exp . '|' . yamaha_audio_dl_sign( $role, $exp ) : '';
	setcookie( yamaha_audio_dl_cookie_name( $role ), $val, array( 'expires' => $exp, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
}

function yamaha_audio_dl_csrf( $role ) {
	$c = isset( $_COOKIE[ yamaha_audio_dl_cookie_name( $role ) ] ) ? (string) $_COOKIE[ yamaha_audio_dl_cookie_name( $role ) ] : '';
	return hash_hmac( 'sha256', 'csrf|' . $role . '|' . $c, wp_salt( 'nonce' ) );
}

/**
 * Brute-force brake: after 6 failed logins from one address, 15 minutes of refusals.
 */
function yamaha_audio_dl_throttle( $fail = null ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? preg_replace( '/[^0-9a-f.:]/i', '', (string) $_SERVER['REMOTE_ADDR'] ) : '0';
	$key = 'hx_dl_fail_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( null === $fail ) {
		return $n >= 6;
	}
	if ( $fail ) {
		set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
	} else {
		delete_transient( $key );
	}
	return false;
}

/**
 * Login / logout handling runs before any output (the shortcode only renders).
 */
add_action( 'template_redirect', function () {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$content = (string) get_post_field( 'post_content', get_queried_object_id() );
	$role    = '';
	foreach ( array( 'admin_products' => 'admin', 'pa_products' => 'pa', 'pa1_products' => 'pa1' ) as $sc => $r ) {
		if ( has_shortcode( $content, $sc ) ) {
			$role = $r;
			break;
		}
	}
	if ( ! $role ) {
		return;
	}
	// never cached, never indexed
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );

	if ( isset( $_GET['logout'] ) ) {
		yamaha_audio_dl_set_cookie( $role, false );
		wp_safe_redirect( remove_query_arg( 'logout' ) );
		exit;
	}
	if ( isset( $_POST['pa_user'], $_POST['pa_pass'] ) ) {
		if ( yamaha_audio_dl_throttle() ) {
			set_query_var( 'hx_dl_error', __( 'Prea multe încercări. Reîncearcă în 15 minute.', 'yamaha-audio' ) );
			return;
		}
		list( $u, $p ) = yamaha_audio_dl_credentials( $role );
		$ok = $u && $p && hash_equals( $u, (string) wp_unslash( $_POST['pa_user'] ) ) && hash_equals( $p, (string) wp_unslash( $_POST['pa_pass'] ) );
		yamaha_audio_dl_throttle( ! $ok );
		if ( $ok ) {
			yamaha_audio_dl_set_cookie( $role, true );
			wp_safe_redirect( get_permalink() );
			exit;
		}
		set_query_var( 'hx_dl_error', __( 'Utilizator sau parolă greșite.', 'yamaha-audio' ) );
	}
	if ( 'admin' === $role && isset( $_GET['pa_export'] ) && '1' === $_GET['pa_export'] ) {
		yamaha_audio_dl_export_csv();
	}
}, 1 );

/* ---------- data helpers (as before) ---------- */

function yamaha_audio_dl_items() {
	$items = array();
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		if ( $p->is_type( 'variable' ) ) {
			foreach ( $p->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( $v ) {
					$items[] = $v;
				}
			}
		} else {
			$items[] = $p;
		}
	}
	return $items;
}

function yamaha_audio_dl_int_str( $val ) {
	if ( '–' === $val || '' === $val || null === $val ) {
		return '';
	}
	$s = trim( wp_strip_all_tags( (string) $val ) );
	if ( '' === $s ) {
		return '';
	}
	if ( preg_match( '/[0-9][0-9\.\,\s]*/', $s, $m ) ) {
		$digits = preg_replace( '/\D+/', '', $m[0] );
		return '' !== $digits ? $digits : $s;
	}
	return $s;
}

function yamaha_audio_dl_attr_value( $prod, $attr ) {
	$tax = 0 === strpos( $attr, 'pa_' ) ? $attr : 'pa_' . $attr;
	if ( $prod->is_type( 'variation' ) ) {
		$val = $prod->get_attribute( $tax );
		if ( $val ) {
			return $val;
		}
		$va  = $prod->get_variation_attributes();
		$key = 'attribute_' . $tax;
		if ( isset( $va[ $key ] ) && '' !== $va[ $key ] ) {
			$slug = $va[ $key ];
			if ( taxonomy_exists( $tax ) ) {
				$term = get_term_by( 'slug', $slug, $tax );
				if ( $term && ! is_wp_error( $term ) ) {
					return $term->name;
				}
			}
			return ucwords( str_replace( '-', ' ', $slug ) );
		}
		$parent = $prod->get_parent_id() ? wc_get_product( $prod->get_parent_id() ) : null;
		if ( $parent ) {
			$pv = $parent->get_attribute( $tax );
			if ( $pv ) {
				return $pv;
			}
		}
		return '–';
	}
	$val = $prod->get_attribute( $tax );
	return $val ? $val : '–';
}

function yamaha_audio_dl_name_link( $prod ) {
	if ( $prod->is_type( 'variation' ) ) {
		$par   = wc_get_product( $prod->get_parent_id() );
		$pn    = $par ? $par->get_name() : '';
		$parts = array();
		foreach ( $prod->get_variation_attributes() as $tax => $slug ) {
			if ( $slug ) {
				$t       = get_term_by( 'slug', $slug, str_replace( 'attribute_', '', $tax ) );
				$parts[] = $t ? $t->name : ucwords( str_replace( '-', ' ', $slug ) );
			}
		}
		return array( $pn . ( $parts ? ' – ' . implode( ', ', $parts ) : '' ), get_permalink( $prod->get_parent_id() ) . '#variation-' . $prod->get_id() );
	}
	return array( $prod->get_name(), $prod->get_permalink() );
}

function yamaha_audio_dl_attr_is_shared( $prod, $attr ) {
	return $prod->is_type( 'variation' ) && ! array_key_exists( 'attribute_pa_' . $attr, $prod->get_variation_attributes() );
}

function yamaha_audio_dl_price_term( $tax, $digits ) {
	static $map = array();
	if ( ! isset( $map[ $tax ] ) ) {
		$map[ $tax ] = array();
		$terms       = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$k = yamaha_audio_dl_int_str( $t->name );
				if ( '' !== $k && ! isset( $map[ $tax ][ $k ] ) ) {
					$map[ $tax ][ $k ] = $t;
				}
			}
		}
	}
	if ( isset( $map[ $tax ][ $digits ] ) ) {
		return $map[ $tax ][ $digits ];
	}
	$res = wp_insert_term( $digits, $tax );
	$tid = is_wp_error( $res ) ? $res->get_error_data( 'term_exists' ) : $res['term_id'];
	$t   = $tid ? get_term( (int) $tid, $tax ) : null;
	if ( ! $t || is_wp_error( $t ) ) {
		return null;
	}
	$map[ $tax ][ $digits ] = $t;
	return $t;
}

function yamaha_audio_dl_set_attr_term( $product, $tax, $term, $append = false ) {
	$attrs = $product->get_attributes( 'edit' );
	if ( isset( $attrs[ $tax ] ) ) {
		$a = clone $attrs[ $tax ];
	} elseif ( $term ) {
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$a->set_name( $tax );
		$a->set_position( count( $attrs ) );
		$a->set_visible( false );
		$a->set_variation( false );
	} else {
		return false;
	}
	if ( ! $term ) {
		unset( $attrs[ $tax ] );
	} else {
		$opts = $append ? array_map( 'intval', (array) $a->get_options() ) : array();
		if ( in_array( (int) $term->term_id, $opts, true ) ) {
			return false;
		}
		$opts[] = (int) $term->term_id;
		$a->set_options( $opts );
		$attrs[ $tax ] = $a;
	}
	$product->set_attributes( $attrs );
	return true;
}

function yamaha_audio_dl_save_attr( $prod, $attr, $digits ) {
	$tax = 'pa_' . $attr;
	if ( ! taxonomy_exists( $tax ) ) {
		return false;
	}
	$term = null;
	if ( '' !== $digits ) {
		$term = yamaha_audio_dl_price_term( $tax, $digits );
		if ( ! $term ) {
			return false;
		}
	}
	if ( ! $prod->is_type( 'variation' ) ) {
		yamaha_audio_dl_set_attr_term( $prod, $tax, $term );
		return 'self';
	}
	$parent = wc_get_product( $prod->get_parent_id() );
	if ( ! $parent ) {
		return false;
	}
	if ( yamaha_audio_dl_attr_is_shared( $prod, $attr ) ) {
		yamaha_audio_dl_set_attr_term( $parent, $tax, $term );
		$parent->save();
		return 'parent';
	}
	if ( $term && yamaha_audio_dl_set_attr_term( $parent, $tax, $term, true ) ) {
		$parent->save();
	}
	$vattrs         = $prod->get_attributes( 'edit' );
	$vattrs[ $tax ] = $term ? $term->slug : '';
	$prod->set_attributes( $vattrs );
	return 'self';
}

function yamaha_audio_dl_parse_number( $raw ) {
	$s = trim( wp_strip_all_tags( (string) $raw ) );
	if ( '' === $s ) {
		return '';
	}
	$s = preg_replace( '/[^0-9]+$/', '', $s );
	if ( ! preg_match( '/[0-9]/', $s ) ) {
		return null;
	}
	if ( preg_match( '/^(.*?)[\.,]([0-9]{1,2})$/', $s, $m ) ) {
		$int = preg_replace( '/\D+/', '', $m[1] );
		$num = (float) ( ( '' === $int ? '0' : $int ) . '.' . $m[2] );
	} else {
		$num = (float) preg_replace( '/\D+/', '', $s );
	}
	return (string) (int) round( $num );
}

/* ---------- CSV export (admin) ---------- */

function yamaha_audio_dl_export_csv() {
	if ( ! yamaha_audio_dl_is_authed( 'admin' ) ) {
		status_header( 403 );
		exit( 'Not authorized' );
	}
	$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
	$min    = ( isset( $_GET['min'] ) && '' !== $_GET['min'] ) ? floatval( $_GET['min'] ) : 0;
	$max    = ( isset( $_GET['max'] ) && '' !== $_GET['max'] ) ? floatval( $_GET['max'] ) : 1000000000000;
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=admin-products-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	echo "\xEF\xBB\xBF";
	fputcsv( $out, array( 'Product', 'RRP LEI, TVA INCLUS', 'PA LEI, TVA INCLUS', 'PA1 LEI, TVA INCLUS', 'SALE LEI, TVA INCLUS', 'STOC' ) );
	foreach ( yamaha_audio_dl_items() as $prod ) {
		list( $name ) = yamaha_audio_dl_name_link( $prod );
		$rrp   = (float) $prod->get_regular_price();
		$saleF = $prod->get_sale_price();
		$sale  = ( '' !== $saleF && null !== $saleF ) ? (float) $saleF : $rrp;
		$paRaw = yamaha_audio_dl_attr_value( $prod, 'pa' );
		$pa1Raw = yamaha_audio_dl_attr_value( $prod, 'pa1' );
		$hay   = strtolower( $name . ' ' . $paRaw . ' ' . $pa1Raw );
		if ( '' !== $search && false === strpos( $hay, strtolower( $search ) ) ) {
			continue;
		}
		if ( $sale < $min || $sale > $max ) {
			continue;
		}
		fputcsv( $out, array( $name, number_format( $rrp, 0, '', '' ), yamaha_audio_dl_int_str( $paRaw ), yamaha_audio_dl_int_str( $pa1Raw ), number_format( $sale, 0, '', '' ), $prod->get_manage_stock() ? (int) $prod->get_stock_quantity() : 0 ) );
	}
	fclose( $out );
	exit;
}

/* ---------- rendering ---------- */

function yamaha_audio_dl_login_form( $role ) {
	$error = get_query_var( 'hx_dl_error' );
	$title = array( 'admin' => __( 'Administrare prețuri', 'yamaha-audio' ), 'pa' => __( 'Listă prețuri dealeri (PA)', 'yamaha-audio' ), 'pa1' => __( 'Listă prețuri dealeri (PA1)', 'yamaha-audio' ) );
	ob_start();
	?>
	<div class="hx-dl-login">
		<h2><?php echo esc_html( $title[ $role ] ); ?></h2>
		<p class="hx-dl-login__sub"><?php esc_html_e( 'Acces rezervat partenerilor. Autentifică-te cu datele primite de la Yamaha Audio România.', 'yamaha-audio' ); ?></p>
		<?php if ( $error ) : ?><p class="hx-dl-login__error"><?php echo esc_html( $error ); ?></p><?php endif; ?>
		<form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<label><span><?php esc_html_e( 'Utilizator', 'yamaha-audio' ); ?></span><input name="pa_user" type="text" autocomplete="username" required></label>
			<label><span><?php esc_html_e( 'Parolă', 'yamaha-audio' ); ?></span><input name="pa_pass" type="password" autocomplete="current-password" required></label>
			<button type="submit" class="hx-btn hx-btn--primary"><?php esc_html_e( 'Autentificare', 'yamaha-audio' ); ?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

function yamaha_audio_dl_toolbar( $extra = '' ) {
	?>
	<div class="hx-dl-bar">
		<div class="hx-dl-bar__filters">
			<input id="pa-search" type="search" placeholder="<?php esc_attr_e( 'Caută produs…', 'yamaha-audio' ); ?>" aria-label="<?php esc_attr_e( 'Caută', 'yamaha-audio' ); ?>">
			<input id="pa-min-price" type="number" inputmode="numeric" placeholder="<?php esc_attr_e( 'Preț min', 'yamaha-audio' ); ?>">
			<input id="pa-max-price" type="number" inputmode="numeric" placeholder="<?php esc_attr_e( 'Preț max', 'yamaha-audio' ); ?>">
			<select id="pa-sort" aria-label="<?php esc_attr_e( 'Sortare', 'yamaha-audio' ); ?>">
				<option value="name_asc"><?php esc_html_e( 'Nume A→Z', 'yamaha-audio' ); ?></option>
				<option value="name_desc"><?php esc_html_e( 'Nume Z→A', 'yamaha-audio' ); ?></option>
				<option value="price_asc"><?php esc_html_e( 'Preț crescător', 'yamaha-audio' ); ?></option>
				<option value="price_desc"><?php esc_html_e( 'Preț descrescător', 'yamaha-audio' ); ?></option>
			</select>
			<span class="hx-dl-bar__count" id="pa-count"></span>
		</div>
		<div class="hx-dl-bar__actions">
			<?php echo $extra; // phpcs:ignore ?>
			<a class="hx-btn hx-btn--ghost hx-btn--sm" href="<?php echo esc_url( add_query_arg( 'logout', '1' ) ); ?>"><?php esc_html_e( 'Deconectare', 'yamaha-audio' ); ?></a>
		</div>
	</div>
	<?php
}

function yamaha_audio_dl_script() {
	?>
	<script>
	(function(){
		function f(){ return { s: (document.getElementById('pa-search')||{}).value || '', min: parseFloat((document.getElementById('pa-min-price')||{}).value) || 0, max: parseFloat((document.getElementById('pa-max-price')||{}).value) || Infinity, sort: (document.getElementById('pa-sort')||{}).value || 'name_asc' }; }
		function run(){
			var o = f(), tb = document.querySelector('.hx-dl-table tbody'); if (!tb) { return; }
			var rows = Array.prototype.slice.call(tb.querySelectorAll('tr')), q = o.s.toLowerCase();
			rows.forEach(function(r){ var price = parseFloat(r.dataset.price) || 0; r.hidden = !(r.textContent.toLowerCase().indexOf(q) !== -1 && price >= o.min && price <= o.max); });
			var vis = rows.filter(function(r){ return !r.hidden; });
			vis.sort(function(a, b){ if (o.sort.indexOf('name') === 0) { var an = a.querySelector('td').textContent.toLowerCase(), bn = b.querySelector('td').textContent.toLowerCase(); return o.sort === 'name_asc' ? an.localeCompare(bn) : bn.localeCompare(an); } var ap = parseFloat(a.dataset.price) || 0, bp = parseFloat(b.dataset.price) || 0; return o.sort === 'price_asc' ? ap - bp : bp - ap; });
			vis.forEach(function(r){ tb.appendChild(r); });
			var c = document.getElementById('pa-count'); if (c) { c.textContent = vis.length + ' / ' + rows.length; }
		}
		['pa-search', 'pa-min-price', 'pa-max-price', 'pa-sort'].forEach(function(id){ var el = document.getElementById(id); if (el) { el.addEventListener('input', run); el.addEventListener('change', run); } });
		var exp = document.getElementById('pa-export-btn');
		if (exp) { exp.addEventListener('click', function(e){ e.preventDefault(); var o = f(), u = new URL(this.dataset.url || this.href, location.href); u.searchParams.set('pa_export', '1'); u.searchParams.set('search', o.s); u.searchParams.set('min', isFinite(o.min) ? o.min : ''); u.searchParams.set('max', isFinite(o.max) ? o.max : ''); location.href = u.toString(); }); }
		var form = document.getElementById('bulk-stock-form');
		if (form) {
			var inputs = Array.prototype.slice.call(form.querySelectorAll('.pa-edit')), btn = document.getElementById('pa-save-btn');
			function mark(el){ el.classList.toggle('is-dirty', el.value !== el.getAttribute('data-orig')); }
			inputs.forEach(function(el){ el.addEventListener('input', function(){ var g = el.getAttribute('data-group'); if (g) { inputs.forEach(function(o){ if (o !== el && o.getAttribute('data-group') === g) { o.value = el.value; mark(o); } }); } mark(el); }); });
			form.addEventListener('submit', function(){ inputs.forEach(function(el){ if (el.value === el.getAttribute('data-orig')) { el.disabled = true; } }); if (btn) { btn.textContent = 'Se salvează…'; btn.disabled = true; } });
			window.addEventListener('pageshow', function(){ inputs.forEach(function(el){ el.disabled = false; }); if (btn) { btn.textContent = 'Salvează tot'; btn.disabled = false; } });
			var n = document.getElementById('pa-save-notice'); if (n) { setTimeout(function(){ n.hidden = true; }, 8000); }
		}
		run();
	})();
	</script>
	<?php
}

function yamaha_audio_dl_edit_input( $id, $field, $value, $args = array() ) {
	$args = wp_parse_args( $args, array( 'class' => '', 'group' => '' ) );
	printf(
		'<input class="pa-edit %s" name="rows[%d][%s]" type="text" inputmode="numeric" autocomplete="off" placeholder="—" value="%s" data-orig="%s"%s>',
		esc_attr( $args['class'] ),
		(int) $id,
		esc_attr( $field ),
		esc_attr( $value ),
		esc_attr( $value ),
		$args['group'] ? ' data-group="' . esc_attr( $args['group'] ) . '" title="' . esc_attr__( 'Valoare comună tuturor variațiilor acestui produs', 'yamaha-audio' ) . '"' : ''
	);
}

/**
 * [admin_products]
 */
function yamaha_audio_dl_admin_shortcode() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}
	if ( ! yamaha_audio_dl_is_authed( 'admin' ) ) {
		return yamaha_audio_dl_login_form( 'admin' );
	}
	$msg    = '';
	$errors = array();
	if ( isset( $_POST['bulk_update'] ) ) {
		$token = isset( $_POST['pa_csrf'] ) ? (string) wp_unslash( $_POST['pa_csrf'] ) : '';
		if ( ! hash_equals( yamaha_audio_dl_csrf( 'admin' ), $token ) ) {
			$errors[] = __( 'Nu s-a salvat nimic: verificarea de securitate a eșuat. Reîncarcă pagina și încearcă din nou.', 'yamaha-audio' );
		} else {
			$rows = ( isset( $_POST['rows'] ) && is_array( $_POST['rows'] ) ) ? wp_unslash( $_POST['rows'] ) : array(); // phpcs:ignore
			$num  = function ( $f, $k ) use ( &$errors ) {
				if ( ! isset( $f[ $k ] ) ) {
					return null;
				}
				$v = yamaha_audio_dl_parse_number( $f[ $k ] );
				if ( null === $v ) {
					$errors[] = sprintf( __( 'Ignorat „%s” – nu este un număr.', 'yamaha-audio' ), sanitize_text_field( $f[ $k ] ) );
				}
				return $v;
			};
			$as_int = function ( $v ) {
				return ( '' !== $v && null !== $v ) ? number_format( (float) $v, 0, '', '' ) : '';
			};
			$updated     = 0;
			$shared_done = array();
			foreach ( $rows as $pid => $f ) {
				$p = is_array( $f ) ? wc_get_product( intval( $pid ) ) : null;
				if ( ! $p ) {
					continue;
				}
				$dirty   = false;
				$changed = false;
				$v = $num( $f, 'rrp' );
				if ( null !== $v && '' !== $v && $v !== $as_int( $p->get_regular_price( 'edit' ) ) ) {
					$p->set_regular_price( $v );
					$dirty = true;
				}
				$v = $num( $f, 'sale' );
				if ( null !== $v ) {
					if ( '0' === $v ) {
						$v = '';
					}
					if ( $v !== $as_int( $p->get_sale_price( 'edit' ) ) ) {
						$p->set_sale_price( $v );
						$dirty = true;
					}
				}
				foreach ( array( 'pa', 'pa1' ) as $attr ) {
					$v = $num( $f, $attr );
					if ( null === $v ) {
						continue;
					}
					if ( '0' === $v ) {
						$v = '';
					}
					if ( $v === yamaha_audio_dl_int_str( yamaha_audio_dl_attr_value( $p, $attr ) ) ) {
						continue;
					}
					if ( yamaha_audio_dl_attr_is_shared( $p, $attr ) ) {
						$key = $p->get_parent_id() . '|' . $attr;
						if ( isset( $shared_done[ $key ] ) ) {
							continue;
						}
						$shared_done[ $key ] = true;
					}
					$res = yamaha_audio_dl_save_attr( $p, $attr, $v );
					if ( 'self' === $res ) {
						$dirty = true;
					} elseif ( 'parent' === $res ) {
						$changed = true;
					} else {
						$errors[] = sprintf( __( 'Nu s-a putut salva %1$s pentru „%2$s”.', 'yamaha-audio' ), strtoupper( $attr ), $p->get_name() );
					}
				}
				$v = $num( $f, 'stock' );
				if ( null !== $v && '' !== $v ) {
					$new = (int) $v;
					if ( $p->get_manage_stock() ) {
						if ( (int) $p->get_stock_quantity() !== $new ) {
							$p->set_stock_quantity( $new );
							$dirty = true;
						}
					} elseif ( $new > 0 ) {
						$p->set_manage_stock( true );
						$p->set_stock_quantity( $new );
						$dirty = true;
					}
				}
				if ( $dirty ) {
					$p->save();
					$changed = true;
				}
				if ( $changed ) {
					$updated++;
				}
			}
			$msg = $updated ? sprintf( __( 'Salvat – %d produs(e) actualizate.', 'yamaha-audio' ), $updated ) : __( 'Nicio modificare de salvat.', 'yamaha-audio' );
			if ( $updated && function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
			}
		}
	}
	$items      = yamaha_audio_dl_items();
	$export_url = esc_url( add_query_arg( 'pa_export', '1' ) );
	$extra_btn  = '<a id="pa-export-btn" class="hx-btn hx-btn--sm" data-url="' . $export_url . '" href="' . $export_url . '">' . esc_html__( 'Export CSV', 'yamaha-audio' ) . '</a>';
	ob_start();
	?>
	<div class="hx-dl hx-dl--admin">
		<?php if ( $msg || $errors ) : ?>
			<div id="pa-save-notice" class="hx-dl-notice" onclick="this.hidden=true" title="<?php esc_attr_e( 'Închide', 'yamaha-audio' ); ?>">
				<?php if ( $msg ) : ?><div class="hx-dl-notice__ok"><?php echo esc_html( $msg ); ?></div><?php endif; ?>
				<?php if ( $errors ) : ?><div class="hx-dl-notice__err"><?php echo implode( '<br>', array_map( 'esc_html', $errors ) ); ?></div><?php endif; ?>
			</div>
		<?php endif; ?>
		<?php yamaha_audio_dl_toolbar( $extra_btn ); ?>
		<form method="post" id="bulk-stock-form" novalidate action="<?php echo esc_url( get_permalink() ); ?>">
			<input type="hidden" name="bulk_update" value="1">
			<input type="hidden" name="pa_csrf" value="<?php echo esc_attr( yamaha_audio_dl_csrf( 'admin' ) ); ?>">
			<div class="hx-dl-scroll"><table class="hx-dl-table">
				<thead><tr><th><?php esc_html_e( 'Produs', 'yamaha-audio' ); ?></th><th>RRP LEI, TVA INCLUS</th><th class="is-pa">PA LEI, TVA INCLUS</th><th class="is-pa1">PA1 LEI, TVA INCLUS</th><th>SALE LEI, TVA INCLUS</th><th>STOC</th></tr></thead>
				<tbody>
				<?php foreach ( $items as $prod ) :
					list( $name, $link ) = yamaha_audio_dl_name_link( $prod );
					$id     = $prod->get_id();
					$rrpRaw = $prod->get_regular_price();
					$saleF  = $prod->get_sale_price();
					$rrp    = (float) $rrpRaw;
					$sale   = ( '' !== $saleF && null !== $saleF ) ? (float) $saleF : $rrp;
					$rrp_v  = ( '' !== $rrpRaw && null !== $rrpRaw ) ? number_format( $rrp, 0, '', '' ) : '';
					$sale_v = ( '' !== $saleF && null !== $saleF ) ? number_format( (float) $saleF, 0, '', '' ) : '';
					$pa_v   = yamaha_audio_dl_int_str( yamaha_audio_dl_attr_value( $prod, 'pa' ) );
					$pa1_v  = yamaha_audio_dl_int_str( yamaha_audio_dl_attr_value( $prod, 'pa1' ) );
					$stock  = $prod->get_manage_stock() ? (int) $prod->get_stock_quantity() : 0;
					$g_pa   = yamaha_audio_dl_attr_is_shared( $prod, 'pa' ) ? 'p' . $prod->get_parent_id() . '-pa' : '';
					$g_pa1  = yamaha_audio_dl_attr_is_shared( $prod, 'pa1' ) ? 'p' . $prod->get_parent_id() . '-pa1' : '';
					?>
					<tr data-price="<?php echo esc_attr( $sale ); ?>">
						<td><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></td>
						<td><?php yamaha_audio_dl_edit_input( $id, 'rrp', $rrp_v ); ?></td>
						<td><?php yamaha_audio_dl_edit_input( $id, 'pa', $pa_v, array( 'class' => 'is-pa', 'group' => $g_pa ) ); ?></td>
						<td><?php yamaha_audio_dl_edit_input( $id, 'pa1', $pa1_v, array( 'class' => 'is-pa1', 'group' => $g_pa1 ) ); ?></td>
						<td><?php yamaha_audio_dl_edit_input( $id, 'sale', $sale_v ); ?></td>
						<td><?php yamaha_audio_dl_edit_input( $id, 'stock', $stock, array( 'class' => 'is-stock' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<button type="submit" id="pa-save-btn" class="hx-btn hx-btn--primary hx-dl-save"><?php esc_html_e( 'Salvează tot', 'yamaha-audio' ); ?></button>
		</form>
		<?php yamaha_audio_dl_script(); ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * [pa_products] / [pa1_products]
 */
function yamaha_audio_dl_readonly_shortcode( $attr ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}
	if ( ! yamaha_audio_dl_is_authed( $attr ) ) {
		return yamaha_audio_dl_login_form( $attr );
	}
	$items = array_values( array_filter( yamaha_audio_dl_items(), function ( $prod ) use ( $attr ) {
		$t = trim( wp_strip_all_tags( (string) yamaha_audio_dl_attr_value( $prod, $attr ) ) );
		return '' !== $t && '–' !== $t && 'n/a' !== strtolower( $t );
	} ) );
	$label = 'pa' === $attr ? 'PA LEI, TVA INCLUS' : 'PA1 LEI, TVA INCLUS';
	ob_start();
	?>
	<div class="hx-dl">
		<?php yamaha_audio_dl_toolbar(); ?>
		<div class="hx-dl-scroll"><table class="hx-dl-table">
			<thead><tr><th><?php esc_html_e( 'Produs', 'yamaha-audio' ); ?></th><th>RRP LEI, TVA INCLUS</th><th class="is-<?php echo esc_attr( $attr ); ?>"><?php echo esc_html( $label ); ?></th><th>SALE LEI, TVA INCLUS</th><th>STOC</th></tr></thead>
			<tbody>
			<?php foreach ( $items as $prod ) :
				list( $name, $link ) = yamaha_audio_dl_name_link( $prod );
				$rrp    = (float) $prod->get_regular_price();
				$saleF  = $prod->get_sale_price();
				$sale   = ( '' !== $saleF && null !== $saleF ) ? (float) $saleF : $rrp;
				$attr_i = yamaha_audio_dl_int_str( yamaha_audio_dl_attr_value( $prod, $attr ) );
				?>
				<tr data-price="<?php echo esc_attr( $sale ); ?>">
					<td><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></td>
					<td><strong><?php echo esc_html( number_format( $rrp, 0, '', '' ) ); ?></strong></td>
					<td><strong class="is-<?php echo esc_attr( $attr ); ?>"><?php echo '' !== $attr_i ? esc_html( $attr_i ) : '–'; ?></strong></td>
					<td><strong><?php echo ( '' !== $saleF && null !== $saleF ) ? esc_html( number_format( (float) $saleF, 0, '', '' ) ) : '–'; ?></strong></td>
					<td><?php echo esc_html( $prod->get_manage_stock() ? (int) $prod->get_stock_quantity() : 0 ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<?php yamaha_audio_dl_script(); ?>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'admin_products', 'yamaha_audio_dl_admin_shortcode' );
add_shortcode( 'pa_products', function () { return yamaha_audio_dl_readonly_shortcode( 'pa' ); } );
add_shortcode( 'pa1_products', function () { return yamaha_audio_dl_readonly_shortcode( 'pa1' ); } );
