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
	 * The user agent is inspected in-memory to classify the request and
	 * is never persisted.
	 *
	 * @return bool
	 */
	protected function shouldTrack(): bool {
		if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'GET' ) {
			return false;
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		if ( $user_agent === '' ) {
			return false;
		}

		return ! ( new CrawlerDetect() )->isCrawler( $user_agent );
	}
}
