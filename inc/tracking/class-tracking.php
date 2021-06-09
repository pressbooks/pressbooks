<?php

namespace Pressbooks\Tracking;

abstract class Tracking {
	/**
	 * Tracking table
	 *
	 * @var string
	 */
	protected $dbTable;

	/**
	 * Tracking type
	 *
	 * @var string
	 */
	protected $type;

	protected function __construct() {
		global $wpdb;

		$this->dbTable = $wpdb->base_prefix . 'pressbooks_tracking';
	}

	/**
	 * Set up the database table.
	 *
	 * @return void
	 */
	protected function setup() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS `$this->dbTable` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`blog_id` bigint(20) NOT NULL,
				`track_type` varchar(30) NOT NULL,
				`track_value` varchar(255),
				`logged_in` boolean NOT NULL default false,
				`created_at` datetime NOT NULL,
				PRIMARY KEY  (id)
				);";

		dbDelta( $sql );
	}

	/**
	 * Store tracking event data.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function store( $value ) {
		global $wpdb;

		$wpdb->insert( $this->dbTable, [
			'blog_id' => get_current_blog_id(),
			'track_type' => $this->type,
			'track_value' => $value,
			'logged_in' => is_user_logged_in(),
			'created_at' => date( 'Y-m-d h:i:s' ),
		] );
	}

	/**
	 * Store tracking event data.
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->dbTable;
	}
}
