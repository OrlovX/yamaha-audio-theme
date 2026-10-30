<?php
/**
 * Brand (pa_producator) admin: an image field on the brand edit screen, used by the Branduri page.
 *
 * @package hifiexpert
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {
	$tax = hifiexpert_brand_taxonomy();
	if ( ! $tax ) {
		return;
	}
	add_action( "{$tax}_add_form_fields", function () {
		echo '<div class="form-field"><label>' . esc_html__( 'Imagine brand (pagina Branduri)', 'hifiexpert' ) . '</label>';
		hifiexpert_brand_image_control( 0, 'hx_brand_image' );
		echo '</div><div class="form-field"><label>' . esc_html__( 'Logo (banda High‑End de pe homepage)', 'hifiexpert' ) . '</label>';
		hifiexpert_brand_image_control( 0, 'hx_brand_logo' );
		echo '</div>';
	} );
	add_action( "{$tax}_edit_form_fields", function ( $term ) {
		echo '<tr class="form-field"><th scope="row"><label>' . esc_html__( 'Imagine brand (pagina Branduri)', 'hifiexpert' ) . '</label></th><td>';
		hifiexpert_brand_image_control( (int) get_term_meta( $term->term_id, 'hx_brand_image', true ), 'hx_brand_image' );
		echo '<p class="description">' . esc_html__( 'Fără imagine, tema folosește bannerul vechii pagini de branduri sau fotografia celui mai vândut produs al brandului.', 'hifiexpert' ) . '</p></td></tr>';
		$auto = hifiexpert_brand_logo_id( $term );
		echo '<tr class="form-field"><th scope="row"><label>' . esc_html__( 'Logo (banda High‑End de pe homepage)', 'hifiexpert' ) . '</label></th><td>';
		hifiexpert_brand_image_control( (int) get_term_meta( $term->term_id, 'hx_brand_logo', true ), 'hx_brand_logo' );
		echo '<p class="description">' . esc_html__( 'PNG/SVG cu fundal transparent, de preferință; tema îl afișează în alb. Fără logo propriu, tema caută în Media un fișier cu numele brandului și „logo”.', 'hifiexpert' ) . ( $auto && ! get_term_meta( $term->term_id, 'hx_brand_logo', true ) ? ' ' . sprintf( esc_html__( 'Găsit automat: %s', 'hifiexpert' ), '<code>' . esc_html( wp_basename( (string) get_attached_file( $auto ) ) ) . '</code>' ) : '' ) . '</p></td></tr>';
	} );
	$save = function ( $term_id ) {
		if ( ! isset( $_POST['hx_brand_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hx_brand_image_nonce'] ) ), 'hx_brand_image' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( array( 'hx_brand_image', 'hx_brand_logo' ) as $key ) {
			$id = isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : 0;
			if ( $id ) {
				update_term_meta( $term_id, $key, $id );
			} else {
				delete_term_meta( $term_id, $key );
			}
		}
		delete_transient( 'hx_brand_img_' . $term_id );
		delete_transient( 'hx_brand_logo2_' . $term_id );
	};
	add_action( "created_{$tax}", $save );
	add_action( "edited_{$tax}", $save );

	add_filter( "manage_edit-{$tax}_columns", function ( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			if ( 'name' === $k ) {
				$out['hx_image'] = __( 'Imagine', 'hifiexpert' );
			}
			$out[ $k ] = $v;
		}
		return $out;
	} );
	add_filter( "manage_{$tax}_custom_column", function ( $content, $column, $term_id ) {
		if ( 'hx_image' !== $column ) {
			return $content;
		}
		$term = get_term( $term_id );
		$kind = 'photo';
		$img  = $term && ! is_wp_error( $term ) ? hifiexpert_brand_image_id( $term, $kind ) : 0;
		if ( ! $img ) {
			return '<span class="dashicons dashicons-format-image" style="color:#c3c4c7" title="' . esc_attr__( 'Fără imagine', 'hifiexpert' ) . '"></span>';
		}
		$own = (int) get_term_meta( $term_id, 'hx_brand_image', true );
		$src = wp_get_attachment_image_url( $img, 'thumbnail' );
		return '<img src="' . esc_url( $src ) . '" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;' . ( $own ? '' : 'opacity:.6' ) . '" title="' . esc_attr( $own ? __( 'Imagine proprie', 'hifiexpert' ) : ( 'product' === $kind ? __( 'Automat: produs', 'hifiexpert' ) : __( 'Automat: banner vechi', 'hifiexpert' ) ) ) . '">';
	}, 10, 3 );
} );

function hifiexpert_brand_image_control( $id, $name = 'hx_brand_image' ) {
	static $nonce_done = false;
	if ( ! $nonce_done ) {
		wp_nonce_field( 'hx_brand_image', 'hx_brand_image_nonce' );
		$nonce_done = true;
	}
	$src  = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	$fit  = 'hx_brand_logo' === $name ? 'contain' : 'cover';
	echo '<div class="hx-brand-image" style="display:flex;gap:12px;align-items:flex-start">';
	echo '<img class="hx-brand-image__preview" src="' . esc_url( $src ) . '" alt="" style="width:120px;height:80px;object-fit:' . $fit . ';border-radius:8px;background:#f0f0f1;padding:' . ( 'contain' === $fit ? '8px' : '0' ) . ';box-sizing:border-box;' . ( $src ? '' : 'display:none' ) . '">';
	echo '<div><input type="hidden" class="hx-brand-image__id" name="' . esc_attr( $name ) . '" value="' . esc_attr( $id ) . '">';
	echo '<button type="button" class="button hx-brand-image__pick">' . esc_html__( 'Alege imagine', 'hifiexpert' ) . '</button> ';
	echo '<button type="button" class="button-link-delete hx-brand-image__clear" style="' . ( $id ? '' : 'display:none' ) . '">' . esc_html__( 'Elimină', 'hifiexpert' ) . '</button></div></div>';
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || $screen->taxonomy !== hifiexpert_brand_taxonomy() ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', "
jQuery(function($){
	var frame;
	$(document).on('click','.hx-brand-image__pick',function(e){
		e.preventDefault();
		var box=$(this).closest('.hx-brand-image');
		frame=wp.media({title:'Imagine brand',button:{text:'Folosește imaginea'},multiple:false,library:{type:'image'}});
		frame.on('select',function(){
			var a=frame.state().get('selection').first().toJSON();
			var url=(a.sizes&&a.sizes.medium)?a.sizes.medium.url:a.url;
			box.find('.hx-brand-image__id').val(a.id);
			box.find('.hx-brand-image__preview').attr('src',url).show();
			box.find('.hx-brand-image__clear').show();
		});
		frame.open();
	});
	$(document).on('click','.hx-brand-image__clear',function(e){
		e.preventDefault();
		var box=$(this).closest('.hx-brand-image');
		box.find('.hx-brand-image__id').val('');
		box.find('.hx-brand-image__preview').hide();
		$(this).hide();
	});
	// after an AJAX 'add term', reset the field
	$(document).ajaxComplete(function(ev,xhr,s){ if(s.data&&String(s.data).indexOf('action=add-tag')>-1&&xhr.status===200){ $('.hx-brand-image__id').val(''); $('.hx-brand-image__preview').hide(); $('.hx-brand-image__clear').hide(); } });
});
" );
} );
