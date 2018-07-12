<?php

namespace Pressbooks\Admin\Attachments;

use Pressbooks\Utility;
use Pressbooks\Licensing;


/**
 * Hooks into 'attachment_fields_to_edit' filter to add custom attachment
 * metadata
 *
 * @since 5.4.0
 *
 * @param $form_fields
 * @param $post
 *
 * @return mixed
 */
function add_metadata_attachment( $form_fields, $post ) {
	$title   = get_post_meta( $post->ID, 'pb_attribution_title', true );
	$author  = get_post_meta( $post->ID, 'pb_attribution_author', true );
	$source  = get_post_meta( $post->ID, 'pb_attribution_title_url', true );
	$license = get_post_meta( $post->ID, 'pb_attribution_license', true );

	$form_fields['pb_attribution'] = [
		'value' => '',
		'label' => __( 'ATTRIBUTIONS', 'pressbooks' ),
		'input' => 'html',
		'html'  => '<span></span>',
	];

	$form_fields['pb_attribution_title'] = [
		'value' => isset( $title ) ? $title : '',
		'label' => __( 'Title', 'pressbooks' ),
		'input' => 'text',
	];

	$form_fields['pb_attribution_author'] = [
		'value' => isset( $author ) ? $author : '',
		'label' => __( 'Author', 'pressbooks' ),
		'input' => 'text',
	];

	$form_fields['pb_attribution_title_url'] = [
		'value' => isset( $source ) ? $source : '',
		'label' => __( 'Source', 'pressbooks' ),
		'input' => 'html',
		'html'  => "<input type='url' class='text urlfield' name='attachments[$post->ID][pb_attribution_title_url]' value='" . esc_attr( $source ) . "' />",
	];

	$form_fields['pb_attribution_license'] = [
		'value' => isset( $license ) ? $license : '',
		'label' => __( 'License', 'pressbooks' ),
		'input' => 'html',
		'html'  => render_attachment_license_options( $post->ID, $license ),
	];

	return $form_fields;
}

/**
 * Hooks into 'attachment_fields_to_save' filter to save custom attachment
 * metadata
 *
 * @since 5.4.0
 *
 * @param $post
 * @param $form_fields
 *
 * @return mixed
 */
function save_metadata_attachment( $post, $form_fields ) {
	$expected     = [
		'pb_attribution_title',
		'pb_attribution_author',
		'pb_attribution_title_url',
		'pb_attribution_license',
	];
	$attributions = [];

	// take only the ones we care about
	foreach ( $expected as $key ) {
		if ( isset( $form_fields[ $key ] ) ) {
			$attributions[ $key ] = validate_attachment_metadata( $key, $form_fields[ $key ] );
			update_post_meta( $post['ID'], $key, $attributions[ $key ] );
		}
	}

	return $post;
}

/**
 * Validates custom attachment metadata input fields
 *
 * @param $key
 * @param $form_field
 *
 * @return false|string
 */
function validate_attachment_metadata( $key, $form_field ) {

	if ( Utility\str_ends_with( $key, '_url' ) && Utility\str_starts_with( $key, 'pb_' ) ) {
		$form_field = ( wp_http_validate_url( $form_field ) ) ? wp_http_validate_url( $form_field ) : '';
	}

	if ( Utility\str_starts_with( $key, 'pb_' ) ) {
		$form_field = sanitize_text_field( $form_field );
	}

	return $form_field;
}

/**
 * Creates an HTML blob for selecting a valid license type
 *
 * @param $post_id
 * @param $license_meta
 *
 * @return string
 */
function render_attachment_license_options( $post_id, $license_meta ) {
	$licenses = ( new Licensing() )->getSupportedTypes();
	$html     = "<select name='attachments[$post_id][pb_attribution_license]' id='attachments-{$post_id}-pb_attribution_license'>";

	$html .= '<option value=""></option>';
	foreach ( $licenses as $key => $license ) {
		$html .= "<option value='{$key}'" . selected( $license_meta, $key, false ) . ">{$license['desc']}</option>";
	}
	$html .= '</select>';

	return $html;
}
