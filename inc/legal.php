<?php
/**
 * Legal pages: Termeni și condiții, Confidențialitate (GDPR), Cookie-uri, Livrare & plată,
 * Retur & drept de retragere (cu formular), Garanție & service.
 *
 * The texts live in /legal/*.php and are filled with the company data from the Customizer
 * (Yamaha Audio → Date firmă). An admin screen (Yamaha Audio → Pagini legale) writes them into
 * real WordPress pages, so they can still be edited by hand afterwards. Pages in this set are
 * rendered with page-legal.php (table of contents, legal typography, company card).
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

define( 'YAMAHA_AUDIO_LEGAL_VERSION', '1.0' );

/**
 * The set, in footer order. slug => [ title, file, wc-option ]
 */
function yamaha_audio_legal_pages() {
	return array(
		'termeni-si-conditii'           => array( 'title' => 'Termeni și condiții', 'file' => 'termeni-si-conditii', 'option' => 'woocommerce_terms_page_id' ),
		'politica-de-confidentialitate' => array( 'title' => 'Politica de confidențialitate', 'file' => 'politica-de-confidentialitate', 'option' => 'wp_page_for_privacy_policy' ),
		'politica-cookies'              => array( 'title' => 'Politica de cookie-uri', 'file' => 'politica-cookies', 'option' => '' ),
		'politica-livrare'              => array( 'title' => 'Livrare și plată', 'file' => 'politica-livrare', 'option' => '' ),
		'politica-retur'                => array( 'title' => 'Retur și drept de retragere', 'file' => 'politica-retur', 'option' => '' ),
		'garantie'                      => array( 'title' => 'Garanție și service', 'file' => 'garantie', 'option' => '' ),
	);
}

/**
 * Values the texts are filled with (already HTML-escaped).
 */
function yamaha_audio_legal_vars() {
	static $v = null;
	if ( null !== $v ) {
		return $v;
	}
	$raw = array(
		'company'   => yamaha_audio_opt( 'company_name' ),
		'cui'       => yamaha_audio_opt( 'company_cui' ),
		'reg'       => yamaha_audio_opt( 'company_reg' ),
		'address'   => yamaha_audio_opt( 'company_address' ),
		'capital'   => yamaha_audio_opt( 'company_capital' ),
		'bank'      => yamaha_audio_opt( 'company_bank' ),
		'iban'      => yamaha_audio_opt( 'company_iban' ),
		'showroom'  => yamaha_audio_opt( 'topbar_address' ),
		'hours'     => yamaha_audio_opt( 'topbar_hours' ),
		'email'     => yamaha_audio_opt( 'topbar_email' ),
		'phone'     => yamaha_audio_opt( 'phone' ),
		'site'      => preg_replace( '#^https?://#', '', untrailingslashit( home_url() ) ),
		'site_url'  => home_url( '/' ),
		'free_min'  => number_format_i18n( (float) yamaha_audio_opt( 'free_shipping_min' ), 0 ) . ' lei',
		'courier'   => yamaha_audio_opt( 'company_courier_fee' ),
		'rates'     => (int) yamaha_audio_opt( 'rates_count' ),
		'rates_lbl' => yamaha_audio_opt( 'rates_label' ),
		'rates_min' => number_format_i18n( (float) yamaha_audio_opt( 'rates_min_price' ), 0 ) . ' lei',
		'updated'   => yamaha_audio_opt( 'legal_updated' ) ?: date_i18n( 'j F Y' ),
	);
	$v = array_map( 'esc_html', $raw );
	foreach ( yamaha_audio_legal_pages() as $slug => $p ) {
		$v[ 'url_' . str_replace( '-', '_', $slug ) ] = esc_url( home_url( '/' . $slug . '/' ) );
	}
	$v['url_contact'] = esc_url( home_url( '/contact/' ) );
	$v['url_cont']    = esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) );
	return $v;
}

/**
 * Rendered HTML for one page of the set.
 */
function yamaha_audio_legal_render( $slug ) {
	$pages = yamaha_audio_legal_pages();
	if ( empty( $pages[ $slug ] ) ) {
		return '';
	}
	$file = YAMAHA_AUDIO_DIR . '/legal/' . $pages[ $slug ]['file'] . '.php';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$v = yamaha_audio_legal_vars();
	ob_start();
	include $file;
	$html = ob_get_clean();
	return "<!-- hx-legal:{$slug} v" . YAMAHA_AUDIO_LEGAL_VERSION . " -->\n" . trim( $html );
}

/**
 * Existing pages of the set, for the footer.
 */
function yamaha_audio_legal_links() {
	static $links = null;
	if ( null !== $links ) {
		return $links;
	}
	$links = array();
	foreach ( yamaha_audio_legal_pages() as $slug => $p ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$links[] = array( 'title' => $page->post_title, 'url' => get_permalink( $page ) );
		}
	}
	return $links;
}

/**
 * Pages of the set (by slug or by the marker meta) use page-legal.php.
 */
add_filter( 'template_include', function ( $template ) {
	if ( is_page() ) {
		$post = get_queried_object();
		if ( $post && ( isset( yamaha_audio_legal_pages()[ $post->post_name ] ) || get_post_meta( $post->ID, '_hx_legal', true ) ) ) {
			$t = locate_template( 'page-legal.php' );
			if ( $t ) {
				return $t;
			}
		}
	}
	return $template;
}, 21 );

/**
 * Keep the generated markup intact (no wpautop on our own HTML).
 */
add_filter( 'the_content', function ( $content ) {
	if ( is_page() && false !== strpos( $content, '<!-- hx-legal:' ) ) {
		remove_filter( 'the_content', 'wpautop' );
	}
	return $content;
}, 1 );

/* -------------------------------------------------------------------------
 * Admin: Yamaha Audio → Pagini legale
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_menu_page( 'Yamaha Audio', 'Yamaha Audio', 'manage_options', 'yamaha-audio', 'yamaha_audio_legal_admin_page', 'dashicons-admin-customizer', 58 );
	add_submenu_page( 'yamaha-audio', __( 'Pagini legale', 'yamaha-audio' ), __( 'Pagini legale', 'yamaha-audio' ), 'manage_options', 'yamaha-audio', 'yamaha_audio_legal_admin_page' );
} );

function yamaha_audio_legal_admin_page() {
	$pages  = yamaha_audio_legal_pages();
	$notice = isset( $_GET['hx_done'] ) ? sanitize_text_field( wp_unslash( $_GET['hx_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$vars   = yamaha_audio_legal_vars();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Pagini legale', 'yamaha-audio' ); ?></h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( __( 'Gata: %s.', 'yamaha-audio' ), $notice ) ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Tema conține textele legale necesare unui magazin online din România (OUG 34/2014, OUG 140/2021, Legea 365/2002, GDPR, Legea 506/2004). Ele se completează automat cu datele firmei din Customizer → Yamaha Audio → Date firmă și se scriu în paginile de mai jos; după aceea le poți edita ca pe orice pagină.', 'yamaha-audio' ); ?></p>
		<p class="description"><?php esc_html_e( 'Textele sunt un model solid, nu consultanță juridică: cere unui avocat să le verifice înainte de publicare.', 'yamaha-audio' ); ?></p>

		<h2 class="title"><?php esc_html_e( 'Datele folosite', 'yamaha-audio' ); ?></h2>
		<p><code><?php echo $vars['company']; ?></code> · CUI <code><?php echo $vars['cui']; ?></code> · <code><?php echo $vars['reg']; ?></code> · <?php echo $vars['address']; ?> · <?php echo $vars['email']; ?> · <?php echo $vars['phone']; ?> · <?php esc_html_e( 'transport gratuit peste', 'yamaha-audio' ); ?> <?php echo $vars['free_min']; ?>
			&nbsp;<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=hx_company' ) ); ?>"><?php esc_html_e( 'Modifică', 'yamaha-audio' ); ?></a></p>

		<table class="widefat striped" style="max-width:980px">
			<thead><tr><th><?php esc_html_e( 'Pagină', 'yamaha-audio' ); ?></th><th><?php esc_html_e( 'Stare', 'yamaha-audio' ); ?></th><th><?php esc_html_e( 'Acțiuni', 'yamaha-audio' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $pages as $slug => $p ) : ?>
				<?php
				$page   = get_page_by_path( $slug );
				$ver    = $page ? get_post_meta( $page->ID, '_hx_legal', true ) : '';
				$status = ! $page ? __( 'Lipsește', 'yamaha-audio' ) : ( $ver ? sprintf( __( 'Generată de temă (v%s)', 'yamaha-audio' ), $ver ) : __( 'Există (text vechi, negenerat de temă)', 'yamaha-audio' ) );
				$url    = wp_nonce_url( admin_url( 'admin-post.php?action=hx_legal_install&slug=' . $slug ), 'hx_legal_' . $slug );
				?>
				<tr>
					<td><strong><?php echo esc_html( $p['title'] ); ?></strong><br><code>/<?php echo esc_html( $slug ); ?>/</code></td>
					<td><?php echo esc_html( $status ); ?><?php if ( $page ) : ?><br><a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank"><?php esc_html_e( 'Vezi', 'yamaha-audio' ); ?></a> · <a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Editează', 'yamaha-audio' ); ?></a><?php endif; ?></td>
					<td>
						<?php if ( ! $page ) : ?>
							<a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Creează pagina', 'yamaha-audio' ); ?></a>
						<?php else : ?>
							<a class="button" href="<?php echo esc_url( $url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Textul actual al paginii va fi înlocuit cu textul generat de temă. Continui?', 'yamaha-audio' ) ); ?>')"><?php esc_html_e( 'Rescrie din temă', 'yamaha-audio' ); ?></a>
						<?php endif; ?>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hx_legal_preview&slug=' . $slug ), 'hx_legal_' . $slug ) ); ?>" target="_blank"><?php esc_html_e( 'Previzualizează', 'yamaha-audio' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p style="margin-top:16px">
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hx_legal_install&slug=all' ), 'hx_legal_all' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Toate paginile de mai sus vor fi create sau rescrise cu textul temei. Continui?', 'yamaha-audio' ) ); ?>')"><?php esc_html_e( 'Creează / rescrie toate paginile', 'yamaha-audio' ); ?></a>
			<span class="description" style="margin-left:10px"><?php esc_html_e( 'Setează și pagina de Termeni din WooCommerce și pagina de Confidențialitate din WordPress, dacă nu sunt setate.', 'yamaha-audio' ); ?></span>
		</p>
	</div>
	<?php
}

add_action( 'admin_post_hx_legal_preview', function () {
	$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
	if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'hx_legal_' . $slug ) ) {
		wp_die( esc_html__( 'Acces interzis.', 'yamaha-audio' ) );
	}
	$pages = yamaha_audio_legal_pages();
	if ( empty( $pages[ $slug ] ) ) {
		wp_die( esc_html__( 'Pagină necunoscută.', 'yamaha-audio' ) );
	}
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><title>' . esc_html( $pages[ $slug ]['title'] ) . '</title><style>body{max-width:860px;margin:40px auto;padding:0 20px;font:16px/1.65 system-ui,sans-serif;color:#1d1d1f}h1{font-size:34px}h2{margin-top:40px;font-size:22px}h3{font-size:17px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px;text-align:left;vertical-align:top}pre{white-space:pre-wrap;background:#f6f6f6;padding:16px;border-radius:8px}</style></head><body>';
	echo '<h1>' . esc_html( $pages[ $slug ]['title'] ) . '</h1>';
	echo wp_kses_post( yamaha_audio_legal_render( $slug ) );
	echo '</body></html>';
	exit;
} );

add_action( 'admin_post_hx_legal_install', function () {
	$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
	if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'hx_legal_' . $slug ) ) {
		wp_die( esc_html__( 'Acces interzis.', 'yamaha-audio' ) );
	}
	$pages = yamaha_audio_legal_pages();
	$todo  = 'all' === $slug ? array_keys( $pages ) : ( isset( $pages[ $slug ] ) ? array( $slug ) : array() );
	$done  = array();
	foreach ( $todo as $s ) {
		$id = yamaha_audio_legal_install_page( $s );
		if ( $id ) {
			$done[] = $pages[ $s ]['title'];
		}
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'yamaha-audio', 'hx_done' => rawurlencode( implode( ', ', $done ) ) ), admin_url( 'admin.php' ) ) );
	exit;
} );

/**
 * Create or overwrite one page of the set. Returns the page ID.
 */
function yamaha_audio_legal_install_page( $slug ) {
	$pages = yamaha_audio_legal_pages();
	if ( empty( $pages[ $slug ] ) ) {
		return 0;
	}
	$html = yamaha_audio_legal_render( $slug );
	if ( ! $html ) {
		return 0;
	}
	$page = get_page_by_path( $slug );
	$data = array(
		'post_title'   => $pages[ $slug ]['title'],
		'post_name'    => $slug,
		'post_content' => $html,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'comment_status' => 'closed',
		'ping_status'  => 'closed',
	);
	if ( $page ) {
		$data['ID'] = $page->ID;
		$id         = wp_update_post( wp_slash( $data ), true );
	} else {
		$id = wp_insert_post( wp_slash( $data ), true );
	}
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_hx_legal', YAMAHA_AUDIO_LEGAL_VERSION );
	update_post_meta( $id, '_wp_page_template', 'page-legal.php' );
	// Hook the page up where WordPress / WooCommerce expect it.
	$opt = $pages[ $slug ]['option'];
	if ( $opt && ! (int) get_option( $opt ) ) {
		update_option( $opt, $id );
	}
	return (int) $id;
}
