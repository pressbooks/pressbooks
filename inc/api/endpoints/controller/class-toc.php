<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Book;

class Toc extends \WP_REST_Controller {

	// Here initialize our namespace and resource name.
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'toc';
	}

	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args' => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		if ( get_option( 'blog_public' ) ) {
			return true;
		}

		return false;
	}


	/**
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$struct = Book::getBookStructure();
		unset( $struct['__order'], $struct['__export_lookup'] );

		if ( ! current_user_can( 'edit_posts' ) ) {
			$struct = $this->removePrivateNodes( $struct );
		}

		$response = rest_ensure_response( $struct );

		return $response;
	}

	/**
	 * @param array $book_structure
	 *
	 * @return array
	 */
	private function removePrivateNodes( array $book_structure ) {

		$toc = [];

		// Front-matter
		$front_matter = [];
		foreach ( $book_structure['front-matter'] as $fm ) {
			if ( 'publish' === $fm['post_status'] ) {
				$front_matter[] = $fm;
			}
		}
		$toc['front-matter'] = $front_matter;

		// Book parts + chapters
		foreach ( $book_structure['part'] as $p ) {
			$chapters = [];
			foreach ( $p['chapters'] as $c ) {
				if ( 'publish' === $c['post_status'] ) {
					$chapters[] = $c;
				}
			}
			$p['chapters'] = $chapters;
			$toc['part'][] = $p;
		}

		// Back-matter
		$back_matter = [];
		foreach ( $book_structure['back-matter'] as $bm ) {
			if ( 'publish' === $bm['post_status'] ) {
				$back_matter[] = $bm;
			}
		}
		$toc['back-matter'] = $back_matter;

		return $toc;
	}

}
