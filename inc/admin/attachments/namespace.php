<?php

namespace Pressbooks\Admin\Attachments;

use Pressbooks\Utility;

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
	$attributions = get_post_meta( $post->ID, 'pb_attachment_attributions', true );
	$url          = isset( $attributions['pb_attribution_title_url'] ) ? $attributions['pb_attribution_title_url'] : '';

	$form_fields['pb_attribution_title'] = [
		'value' => isset( $attributions['pb_attribution_title'] ) ? $attributions['pb_attribution_title'] : '',
		'label' => __( 'Attribution Title', 'pressbooks' ),
		'input' => 'text',
		'helps' => __( 'What is the name of the material?', 'pressbooks' ),
	];

	$form_fields['pb_attribution_title_url'] = [
		'value' => $url,
		'label' => __( 'Attribution Source', 'pressbooks' ),
		'input' => 'html',
		'html'  => "<input type='url' class='text urlfield' name='attachments[$post->ID][pb_attribution_title_url]' value='" . esc_attr( $url ) . "' />",
		'helps' => __( 'Where can I find it?', 'pressbooks' ),
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
		'pb_attribution_title_url',
	];
	$attributions = [];

	foreach ( $expected as $key ) {
		if ( isset( $form_fields[ $key ] ) ) {
			$attributions[ $key ] = validate_attachment_metadata( $key, $form_fields[ $key ] );
		}
	}

	update_post_meta( $post['ID'], 'pb_attachment_attributions', $attributions );

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

	return $form_field;
}
