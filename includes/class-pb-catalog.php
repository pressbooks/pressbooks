<?php
/**
 * Contains functions for creating and managing a user's PressBooks Catalog.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks;


class Catalog {


	/**
	 * The value for option: pb_catalog_db_version
	 *
	 * @see install()
	 * @var int
	 */
	static $currentVersion = 1;


	/**
	 * Catalog table, set in constructor
	 *
	 * @var
	 */
	protected $dbTable;


	/**
	 * Column structure of catalog_table
	 *
	 * @var array
	 */
	protected $dbColumns = array(
		'users_id' => '%d',
		'blogs_id' => '%d',
		'featured' => '%d',
		'tag_1' => '%s',
		'tag_2' => '%s',
	);


	/**
	 *
	 */
	function __construct() {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$this->dbTable = $wpdb->base_prefix . 'pressbooks_catalog';
	}


	/**
	 * Get an entire catalog.
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	function get( $user_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->dbTable} WHERE users_id = %d ", $user_id );

		return $wpdb->get_results( $sql, ARRAY_A );
	}


	/**
	 * Save to a catalog.
	 *
	 * @param $user_id
	 * @param $blog_id
	 * @param array $item
	 *
	 * @return mixed
	 */
	function saveBook( $user_id, $blog_id, array $item ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		unset( $item['users_id'], $item['blogs_id'] ); // Don't allow spoofing of IDs in the array

		$data = array( 'users_id' => $user_id, 'blogs_id' => $blog_id );
		$format = array( $this->dbColumns['users_id'], $this->dbColumns['blogs_id'] );

		foreach ( $item as $key => $val ) {
			if ( isset( $this->dbColumns[$key] ) ) {
				$data[$key] = $val;
				$format[] = $this->dbColumns[$key];
			}
		}

		return $wpdb->replace( $this->dbTable, $data, $format );
	}


	/**
	 * Delete an entire catalog.
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	function delete( $user_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		return $wpdb->delete( $this->dbTable, array( 'users_id' => $user_id ), array( '%d' ) );
	}


	/**
	 * Get a single book from a catalog.
	 *
	 * @param int $user_id
	 * @param int $blog_id
	 *
	 * @return mixed
	 */
	function getBook( $user_id, $blog_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->dbTable} WHERE users_id = %d AND blogs_id = %d ", $user_id, $blog_id );

		return $wpdb->get_row( $sql, ARRAY_A );
	}


	/**
	 * Get blog IDs only.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	function getBookIds( $user_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT blogs_id FROM {$this->dbTable} WHERE users_id = %d ", $user_id );

		return $wpdb->get_col( $sql );
	}



	// ----------------------------------------------------------------------------------------------------------------
	// Upgrades
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Upgrade catalog.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {

		if ( $version < 1 ) {
			$this->createTable();
		}
	}


	/**
	 * Create the initial Catalog table, SQL
	 *
	 * @see http://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
	 */
	protected function createTable() {

		$sql = "CREATE TABLE {$this->dbTable} (
				users_id INT(11) NOT NULL,
  				blogs_id INT(11) NOT NULL,
  				featured INT(11) DEFAULT 0 NOT NULL ,
  				tag_1 VARCHAR(255) DEFAULT NULL,
  				tag_2 VARCHAR(255) DEFAULT NULL,
  				PRIMARY KEY  (users_id, blogs_id),
  				KEY featured (featured),
  				KEY tag_1 (tag_1),
  				KEY tag_2 (tag_2)
				); ";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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

		/* Sanity check */

		if ( false == static::isFormSubmission() || false == current_user_can( 'read' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		check_admin_referer( 'pb-user-catalog' );

		$user_id = (int) $_POST['user_id'];

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
		}


		/* Save changes */

		$catalog = new static();
		$catalog->delete( $user_id );
		foreach ( @$_POST['pressbooks_user_catalog'] as $blog_id => $checked ) {
			$catalog->saveBook( $user_id, $blog_id, array() );
		}

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );


		/* Redirect back to form */

		if ( get_current_user_id() != $user_id ) {
			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/index.php?page=catalog&user_id=' . $user_id;
		} else {
			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/index.php?page=catalog';
		}

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

		$param_count = count( $_GET );

		if ( 2 == $param_count && isset( $_GET['user_id'] ) ) {
			return false;
		}

		if ( $param_count > 1 ) {
			return true;
		}

		return false;
	}


}
