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
	 * @param array $data
	 * @return void
	 */
	public function store ( array $data = [] ): void {
		global $wpdb;

		$wpdb->insert( $this->dbTable, [
			'blog_id' => get_current_blog_id(),
			'track_type' => $this->type,
			'track_data' => maybe_serialize( $data ),
			'created_at' => date("Y-m-d h:i:s"),
		] );
	}
}
