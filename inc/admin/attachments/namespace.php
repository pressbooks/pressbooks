<?php

namespace Pressbooks\Admin\Attachments;

use Pressbooks\Licensing;
use Pressbooks\Utility;

/**
 * Hooks into 'attachment_fields_to_edit' filter to add custom attachment
 * metadata. Currently, on the display side of capturing this metadata
 * unlike images, there is no easy way to determine an audio or video post id
 * from the markup that's generated: (wp-image-33), therefore this can only
 * apply to images, for now
 *
 * @since 5.5.0
 *
 * @param $form_fields
 * @param $post
 *
 * @return mixed
 */
function add_metadata_attachment( $form_fields, $post ) {

	if ( substr( $post->post_mime_type, 0, 5 ) === 'image' ) {

		$author      = get_post_meta( $post->ID, 'pb_media_attribution_author', true );
		$author_url  = get_post_meta( $post->ID, 'pb_media_attribution_author_url', true );
		$source      = get_post_meta( $post->ID, 'pb_media_attribution_title_url', true );
		$license     = get_post_meta( $post->ID, 'pb_media_attribution_license', true );
		$figure      = get_post_meta( $post->ID, 'pb_media_attribution_figure', true );
		$adapted     = get_post_meta( $post->ID, 'pb_media_attribution_adapted', true );
		$adapted_url = get_post_meta( $post->ID, 'pb_media_attribution_adapted_url', true );

		$form_fields['pb_attribution'] = [
			'value' => '',
			'label' => __( 'ATTRIBUTIONS', 'pressbooks' ),
			'input' => 'html',
			'html'  => '<span></span>',
		];

		$form_fields['pb_media_attribution_figure'] = [
			'value' => isset( $figure ) ? $figure : '',
			'label' => __( 'Figure #', 'pressbooks' ),
			'input' => 'text',
		];

		$form_fields['pb_media_attribution_title_url'] = [
			'value' => isset( $source ) ? $source : '',
			'label' => __( 'Source', 'pressbooks' ),
			'input' => 'html',
			'html'  => "<input type='url' class='text urlfield' placeholder='https://creativecommons.org/' name='attachments[$post->ID][pb_media_attribution_title_url]' value='" . esc_attr( $source ) . "' />",
		];

		$form_fields['pb_media_attribution_author'] = [
			'value' => isset( $author ) ? $author : '',
			'label' => __( 'Author', 'pressbooks' ),
			'input' => 'text',
		];

		$form_fields['pb_media_attribution_author_url'] = [
			'value' => isset( $author_url ) ? $author_url : '',
			'label' => __( 'Author URL', 'pressbooks' ),
			'input' => 'html',
			'html'  => "<input type='url' class='text urlfield' placeholder='https://creativecommons.org/' name='attachments[$post->ID][pb_media_attribution_author_url]' value='" . esc_attr( $author_url ) . "' />",
		];

		$form_fields['pb_media_attribution_license'] = [
			'value' => isset( $license ) ? $license : '',
			'label' => __( 'License', 'pressbooks' ),
			'input' => 'html',
			'html'  => render_attachment_license_options( $post->ID, $license ),
		];

		$form_fields['pb_media_attribution_adapted'] = [
			'value' => isset( $adapted ) ? $adapted : '',
			'label' => __( 'Adapted by', 'pressbooks' ),
			'input' => 'text',
		];

		$form_fields['pb_media_attribution_adapted_url'] = [
			'value' => isset( $adapted_url ) ? $adapted_url : '',
			'label' => __( 'Adapted by URL', 'pressbooks' ),
			'input' => 'html',
			'html'  => "<input type='url' class='text urlfield' placeholder='https://creativecommons.org/' name='attachments[$post->ID][pb_media_attribution_adapted_url]' value='" . esc_attr( $adapted_url ) . "' />",
		];

	}

	return $form_fields;
}

/**
 * Hooks into 'attachment_fields_to_save' filter to save custom attachment
 * metadata
 *
 * @since 5.5.0
 *
 * @param $post
 * @param $form_fields
 *
 * @return mixed
 */
function save_metadata_attachment( $post, $form_fields ) {
	$expected     = [
		'pb_media_attribution_figure',
		'pb_media_attribution_author',
		'pb_media_attribution_author_url',
		'pb_media_attribution_adapted',
		'pb_media_attribution_adapted_url',
		'pb_media_attribution_title_url',
		'pb_media_attribution_license',
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
	} elseif ( Utility\str_starts_with( $key, 'pb_' ) ) {
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
	$html     = "<select name='attachments[$post_id][pb_media_attribution_license]' id='attachments-{$post_id}-pb_media_attribution_license'>";

	$html .= '<option value=""></option>';
	foreach ( $licenses as $key => $license ) {
		$html .= "<option value='{$key}'" . selected( $license_meta, $key, false ) . ">{$license['desc']}</option>";
	}
	$html .= '</select>';

	return $html;
}
