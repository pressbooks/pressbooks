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
	static $currentVersion = 2;


	/**
	 * Catalog table, set in constructor
	 *
	 * @var
	 */
	protected $dbTable;


	/**
	 * User ID to construct this object
	 *
	 * @var int
	 */
	protected $userId;


	/**
	 * Column structure of catalog_table
	 *
	 * @var array
	 */
	protected $dbColumns = array(
		'users_id' => '%d',
		'blogs_id' => '%d',
		'deleted' => '%d',
		'featured' => '%d',
		'tag_1' => '%s',
		'tag_2' => '%s',
	);


	/**
	 * @param int $user_id (optional)
	 */
	function __construct( $user_id = 0 ) {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$this->dbTable = $wpdb->base_prefix . 'pressbooks_catalog';

		if ( $user_id ) {
			$this->userId = $user_id;
		} elseif ( isset( $_REQUEST['user_id'] ) && current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) ) {
			$this->userId = (int) $_REQUEST['user_id'];
		} else {
			$this->userId = get_current_user_id();
		}
	}


	/**
	 * Get an entire catalog.
	 *
	 * @return mixed
	 */
	function get() {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->dbTable} WHERE users_id = %d AND deleted = 0 ", $this->userId );

		return $wpdb->get_results( $sql, ARRAY_A );
	}


	/**
	 * Save an entire catalog.
	 *
	 * @param array $items
	 */
	function save( array $items ) {

		foreach ( $items as $item ) {
			if ( isset( $item['blogs_id'] ) ) {
				$this->saveBook( $this->userId, $item['blogs_id'], $item );
			}
		}
	}


	/**
	 * Delete an entire catalog.
	 *
	 * @param bool $for_real (optional)
	 *
	 * @return mixed
	 */
	function delete( $for_real = false ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( $for_real ) {
			return $wpdb->delete( $this->dbTable, array( 'users_id' => $this->userId ), array( '%d' ) );
		} else {
			return $wpdb->update( $this->dbTable, array( 'deleted' => 1 ), array( 'users_id' => $this->userId ), array( '%d' ), array( '%d' ) );
		}
	}


	/**
	 * Get a book from a user catalog.
	 *
	 * @param int $blog_id
	 *
	 * @return mixed
	 */
	function getBook( $blog_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->dbTable} WHERE users_id = %d AND blogs_id = %d AND deleted = 0 ", $this->userId, $blog_id );

		return $wpdb->get_row( $sql, ARRAY_A );
	}


	/**
	 * Get only blog IDs.
	 *
	 * @return array
	 */
	function getBookIds() {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT blogs_id FROM {$this->dbTable} WHERE users_id = %d AND deleted = 0 ", $this->userId );

		return $wpdb->get_col( $sql );
	}


	/**
	 * Save a book to a user catalog.
	 *
	 * @param $blog_id
	 * @param array $item
	 *
	 * @return mixed
	 */
	function saveBook( $blog_id, array $item ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		unset( $item['users_id'], $item['blogs_id'], $item['deleted'] ); // Don't allow spoofing

		$data = array( 'users_id' => $this->userId, 'blogs_id' => $blog_id, 'deleted' => 0 );
		$format = array( 'users_id' => $this->dbColumns['users_id'], 'blogs_id' => $this->dbColumns['blogs_id'], 'deleted' => $this->dbColumns['deleted'] );

		foreach ( $item as $key => $val ) {
			if ( isset( $this->dbColumns[$key] ) ) {
				$data[$key] = $val;
				$format[$key] = $this->dbColumns[$key];
			}
		}

		// INSERT ... ON DUPLICATE KEY UPDATE
		// @see http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html

		$args = array();
		$sql = "INSERT INTO {$this->dbTable} ( ";
		foreach ( $data as $key => $val ) {
			$sql .= "`$key`, ";
		}
		$sql = rtrim( $sql, ', ' ) . ' ) VALUES ( ';

		foreach ( $format as $key => $val ) {
			$sql .= $val . ', ';
			$args[] = $data[$key];
		}
		$sql = rtrim( $sql, ', ' ) . ' ) ON DUPLICATE KEY UPDATE ';

		$i = 0;
		foreach ( $data as $key => $val ) {
			if ( 'users_id' == $key || 'blogs_id' == $key ) continue;
			$sql .= "`$key` = {$format[$key]}, ";
			$args[] = $val;
			++$i;
		}
		$sql = rtrim( $sql, ', ' );
		if ( ! $i ) $sql .= ' users_id = users_id '; // Do nothing

		$sql = $wpdb->prepare( $sql, $args );

		return $wpdb->query( $sql );
	}


	/**
	 * Delete a book from a user catalog.
	 *
	 * @param int $blog_id
	 * @param bool $for_real (optional)
	 *
	 * @return mixed
	 */
	function deleteBook( $blog_id, $for_real = false ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( $for_real ) {
			return $wpdb->delete( $this->dbTable, array( 'users_id' => $this->userId, 'blogs_id' => $blog_id ), array( '%d', '%d' ) );
		} else {
			return $wpdb->update( $this->dbTable, array( 'deleted' => 1 ), array( 'users_id' => $this->userId, 'blogs_id' => $blog_id ), array( '%d' ), array( '%d', '%d' ) );
		}
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

		if ( $version < self::$currentVersion ) {
			$this->createTable();
		}
	}


	/**
	 * DB Delta the initial Catalog table.
	 *
	 * If you change this, then don't forget to also change $this->dbColumns
	 *
	 * @see dbColumns
	 * @see http://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
	 */
	protected function createTable() {

		$sql = "CREATE TABLE {$this->dbTable} (
				users_id INT(11) NOT null,
  				blogs_id INT(11) NOT null,
  				deleted TINYINT(1) NOT null,
  				featured INT(11) DEFAULT 0 NOT null ,
  				tag_1 VARCHAR(255) DEFAULT null,
  				tag_2 VARCHAR(255) DEFAULT null,
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

		$catalog = new self();
		$catalog->delete();
		foreach ( @$_POST['pressbooks_user_catalog'] as $blog_id => $checked ) {
			$catalog->saveBook( $blog_id, array() );
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
