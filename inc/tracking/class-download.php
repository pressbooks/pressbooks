<?php

namespace Pressbooks\Tracking;

class Download extends Tracking {
	/**
	 * Tracking tables, set in constructor
	 *
	 * @var string
	 */
	protected $dbTable;

	/**
	 * Download constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->type = 'download';

		add_action( 'store_download_data', [ $this, 'store'] );
    }

	/**
	 * Store the download event data.
	 *
	 * @param string $format
	 * @return void
	 */
	public function store ( string $format ): void {
		global $wpdb;

		$wpdb->insert( $this->dbTable, [
			'blog_id' => get_current_blog_id(),
			'track_type' => $this->type,
			'track_metadata' => json_encode( [ 'format' => $format ] ),
			'is_logged_in' => is_user_logged_in(),
			'created_at' => date("Y-m-d h:i:s"),
		] );
	}
}
