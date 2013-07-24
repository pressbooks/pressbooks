<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Users;


/**
 * Adds some custom fields to user profiles
 */
function add_user_meta() {

	x_add_metadata_group( 'profile-information', 'user', array(
		'label' => __( 'Extra Profile Information', 'pressbooks' )
	) );

	x_add_metadata_field( 'user_interface_lang', 'user', array(
		'group' => 'profile-information',
		'field_type' => 'select',
		'values' => array(
			'en_US' => __( 'English', 'pressbooks' ),
			'zh_TW' => __( 'Chinese, Traditional', 'pressbooks' ),
			'et' => __( 'Estonian', 'pressbooks' ),
			'fr_FR' => __( 'French', 'pressbooks' ),
			'de_DE' => __( 'German', 'pressbooks' ),
			'ja' => __( 'Japanese', 'pressbooks' ),
			'pt_BR' => __( 'Portuguese, Brazil', 'pressbooks' ),
			'es_ES' => __( 'Spanish', 'pressbooks' ),
		),
		'label' => __( 'Language', 'pressbooks' )
	) );

}
