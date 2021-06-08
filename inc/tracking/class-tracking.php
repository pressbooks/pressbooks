<?php

namespace Pressbooks\Tracking;

abstract class Tracking {
	/**
	 * Tracking table, set in constructor
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

	/**
	 * Initialize the constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$this->dbTable = $wpdb->base_prefix . 'pressbooks_tracking';
    }

	/**
	 * Set up the database table.
	 *
	 * @return void
	 */
	public function setup(): void
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS `$this->dbTable` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`blog_id` INT(11) NOT NULL,
				`track_type` varchar(30) NOT NULL,
				`track_data` longtext,
				`created_at` datetime NOT NULL,
				PRIMARY KEY  (id)
				);";

		dbDelta( $sql );
	}
}
