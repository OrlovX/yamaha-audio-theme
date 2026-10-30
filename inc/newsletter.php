<?php
/**
 * Newsletter sign-up in the footer.
 * - If Personalizare → Footer → "Shortcode formular newsletter" is set, that shortcode renders.
 * - Otherwise the theme's own form: e-mail + consent, stored as entries in Newsletter (admin menu),
 *   exportable as CSV, with a notification e-mail to the shop. No third-party service required.
 *
 * @package yamaha-audio
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'hx_subscriber', array(
		'labels'       => array( 'name' => __( 'Newsletter', 'yamaha-audio' ), 'singular_name' => __( 'Abonat', 'yamaha-audio' ), 'menu_name' => __( 'Newsletter', 'yamaha-audio' ) ),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-email-alt',
		'supports'     => array( 'title' ),
		'capability_type' => 'post',
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

function yamaha_audio_newsletter_form() {
	$shortcode = yamaha_audio_opt( 'newsletter_shortcode' );
	if ( $shortcode ) {
		return do_shortcode( $shortcode );
	}
	$status = isset( $_GET['nl'] ) ? sanitize_key( wp_unslash( $_GET['nl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	?>
	<form class="hx-nl" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="hx_subscribe">
		<?php wp_nonce_field( 'hx_subscribe', 'hx_nl_nonce' ); ?>
		<input type="text" name="hx_website" value="" tabindex="-1" autocomplete="off" class="hx-nl__hp" aria-hidden="true">
		<div class="hx-nl__row">
			<label for="hx-nl-email" class="screen-reader-text"><?php esc_html_e( 'Adresa de e‑mail', 'yamaha-audio' ); ?></label>
			<input id="hx-nl-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'adresa@email.ro', 'yamaha-audio' ); ?>" autocomplete="email">
			<button class="hx-btn hx-btn--primary" type="submit"><?php esc_html_e( 'Mă abonez', 'yamaha-audio' ); ?></button>
		</div>
		<label class="hx-nl__consent"><input type="checkbox" name="consent" value="1" required> <span><?php printf( wp_kses_post( __( 'Sunt de acord cu <a href="%s">politica de confidențialitate</a>. Te poți dezabona oricând.', 'yamaha-audio' ) ), esc_url( get_privacy_policy_url() ?: home_url( '/politica-de-confidentialitate/' ) ) ); ?></span></label>
		<?php if ( 'ok' === $status ) : ?><p class="hx-nl__msg hx-nl__msg--ok"><?php esc_html_e( 'Mulțumim! Te-am adăugat pe listă.', 'yamaha-audio' ); ?></p><?php endif; ?>
		<?php if ( 'dup' === $status ) : ?><p class="hx-nl__msg"><?php esc_html_e( 'Adresa este deja abonată.', 'yamaha-audio' ); ?></p><?php endif; ?>
		<?php if ( 'err' === $status ) : ?><p class="hx-nl__msg hx-nl__msg--err"><?php esc_html_e( 'Nu am putut salva adresa. Încearcă din nou.', 'yamaha-audio' ); ?></p><?php endif; ?>
	</form>
	<?php
	return ob_get_clean();
}

function yamaha_audio_handle_subscribe() {
	$back = wp_get_referer() ?: home_url( '/' );
	$back = remove_query_arg( 'nl', $back );
	if ( ! isset( $_POST['hx_nl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hx_nl_nonce'] ) ), 'hx_subscribe' ) || ! empty( $_POST['hx_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'nl', 'err', $back ) . '#newsletter' );
		exit;
	}
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) || empty( $_POST['consent'] ) ) {
		wp_safe_redirect( add_query_arg( 'nl', 'err', $back ) . '#newsletter' );
		exit;
	}
	$existing = get_posts( array( 'post_type' => 'hx_subscriber', 'title' => $email, 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'any' ) );
	if ( $existing ) {
		wp_safe_redirect( add_query_arg( 'nl', 'dup', $back ) . '#newsletter' );
		exit;
	}
	$id = wp_insert_post( array( 'post_type' => 'hx_subscriber', 'post_title' => $email, 'post_status' => 'publish' ) );
	if ( ! $id || is_wp_error( $id ) ) {
		wp_safe_redirect( add_query_arg( 'nl', 'err', $back ) . '#newsletter' );
		exit;
	}
	update_post_meta( $id, '_hx_ip', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );
	update_post_meta( $id, '_hx_consent_at', current_time( 'mysql' ) );
	wp_mail( get_option( 'admin_email' ), sprintf( '[%s] Abonat nou la newsletter', get_bloginfo( 'name' ) ), $email . "\n" . current_time( 'mysql' ) );
	wp_safe_redirect( add_query_arg( 'nl', 'ok', $back ) . '#newsletter' );
	exit;
}
add_action( 'admin_post_nopriv_hx_subscribe', 'yamaha_audio_handle_subscribe' );
add_action( 'admin_post_hx_subscribe', 'yamaha_audio_handle_subscribe' );

/* Admin: CSV export button + columns */
add_filter( 'manage_hx_subscriber_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => __( 'E‑mail', 'yamaha-audio' ), 'date' => __( 'Data', 'yamaha-audio' ) );
} );
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( 'hx_subscriber' === $post_type ) {
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hx_subscribers_csv' ), 'hx_subscribers_csv' ) ) . '">' . esc_html__( 'Exportă CSV', 'yamaha-audio' ) . '</a>';
	}
} );
add_action( 'admin_post_hx_subscribers_csv', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hx_subscribers_csv' ) ) {
		wp_die( 'Nope' );
	}
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=newsletter-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array( 'email', 'date' ) );
	foreach ( get_posts( array( 'post_type' => 'hx_subscriber', 'posts_per_page' => -1, 'post_status' => 'publish' ) ) as $p ) {
		fputcsv( $out, array( $p->post_title, $p->post_date ) );
	}
	exit;
} );
