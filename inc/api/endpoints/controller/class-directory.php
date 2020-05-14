<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\BookDirectory;

class Directory extends \WP_REST_Controller {
	const VERIFY_DELETION = 'verify_deletion';

	/**
	 * Books
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'directory';
	}

	/**
	 *  Registers routes for Books
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/' . self::VERIFY_DELETION, [
				[
					'methods' => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'verify_removal' ],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function verify_removal( $request ) {
		$params = $request->get_query_params();
		$sid = $params['sid'];
		$removals = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );
		$index = array_search( $sid, $removals, true );

		if ( false !== $index ) {
			unset( $removals[ $index ] );
			update_site_option( BookDirectory::DELETIONS_META_KEY, $removals );
			return true;
		}

		return false;
	}
}
