<?php

namespace Pressbooks\Tracking;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

class BookDownload extends Tracking {

	protected function __construct() {
		parent::__construct();

		$this->type = 'book_download';

		add_action( 'store_download_data', [ $this, 'store' ] );
	}

	/**
	 * Store the download event, unless the request should not be counted.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function store( $value ) {
		if ( ! $this->shouldTrack() ) {
			return;
		}

		parent::store( $value );
	}

	/**
	 * Only count GET requests with a non-empty, non-bot user agent.
	 *
	 * Files are still served to everyone; this stage changes only what is
	 * counted, so direct links (including OPDS consumers) keep working.
	 *
	 * @return bool
	 */
	protected function shouldTrack(): bool {
		if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'GET' ) {
			return false;
		}

		$user_agent = $this->getUserAgent();

		if ( $user_agent === null ) {
			return false;
		}

		return ! ( new CrawlerDetect() )->isCrawler( $user_agent );
	}
}
