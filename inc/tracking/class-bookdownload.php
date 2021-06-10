<?php

namespace Pressbooks\Tracking;

class BookDownload extends Tracking {
	/**
	 * @var BookDownload
	 */
	protected static $instance;

	protected function __construct() {
		parent::__construct();

		$this->type = 'book_download';

		add_action( 'store_download_data', [ $this, 'store' ] );
	}
}
