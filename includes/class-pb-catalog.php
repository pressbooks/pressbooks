<?php
/**
 * Contains functions for creating and managing a user's PressBooks Catalog.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks;


class Catalog {

	function __construct() {

	}


	// ----------------------------------------------------------------------------------------------------------------
	// Catch form submissions
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Save custom CSS to database (and filesystem)
	 *
	 * @see pressbooks/admin/templates/custom-css.php
	 */
	static function formSubmit() {

		if ( false == static::isFormSubmission() || false == current_user_can( 'read' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		check_admin_referer( 'pb-user-catalog' );

		$user_id = get_current_user_id();
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( is_array( @$_POST['pressbooks_user_catalog'] ) ) {
			update_user_meta( $user_id, 'pressbooks_user_catalog', $_POST['pressbooks_user_catalog'] );
		} else {
			delete_user_meta( $user_id, 'pressbooks_user_catalog' );
		}

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );
		$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/index.php?page=catalog';
		\PressBooks\Redirect\location( $redirect_url );
	}


	/**
	 * Check if a user submitted something to index.php?page=catalog
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( 'catalog' != @$_REQUEST['page'] ) {
			return false;
		}

		if ( ! empty( $_POST ) ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		return false;
	}


}
