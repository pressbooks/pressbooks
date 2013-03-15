<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Users;


/**
 * Lists restricted Administrative URLs so we can redirect them
 *
 * @param int $blogId Blog ID
 *
 * @return array $disallowed Disallowed Files
 */
function get_restricted( $blogId ) {

	$disallowed = array(
		'edit',
		'edit-link-categories',
		'export',
		'import',
		'link-(manager|add)',
		'nav-menus',
		'plugin-(install|editor)',
		'plugins',
		'post',
		'post-new',
		'theme-editor',
		'themes',
		'tools',
		'widgets',
	);

	if ( ( current_user_can_for_blog( $blogId, 'subscriber' ) || current_user_can_for_blog( $blogId, 'contributor' ) ) ) $disallowed[] = 'index';

	return apply_filters( 'pb_restricted_pages', $disallowed, $blogId );
}


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
		),
		'label' => __( 'Language', 'pressbooks' )
	) );

}
