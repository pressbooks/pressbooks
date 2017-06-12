<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Book;

class Metadata extends \WP_REST_Controller {

	/**
	 * @var array
	 */
	protected $linkCollector = [];

	/**
	 * Metadata
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'metadata';
	}

	/**
	 *  Registers routes for TOC
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_metadata' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args' => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );
	}

	/**
	 * Retrieves metadata schema, conforming to Schema.org/Book.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = [
			'$schema' => 'http://schema.org',
			'title' => 'book',
			'type' => 'object',
			'properties' => [
				'pb_title' => [
					'type' => 'string',
					'description' => __( 'The name of the thing.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';
		unset( $params['page'], $params['per_page'], $params['search'] );

		return $params;
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
	public function get_metadata( $request ) {

		$meta = Book::getBookInformation();
		$meta = $this->fixBookInformation( $meta, current_user_can( 'edit_posts' ), [
			'pb_bisac_subject' => 'about',
			'pb_subtitle' => 'alternativeHeadline',
			'pb_author' => 'author',
			'pb_contributing_authors' => 'contributor',
			'pb_copyright_holder' => 'copyrightHolder',
			'pb_copyright_year' => 'copyrightYear',
			'pb_publication_date' => 'datePublished',
			'pb_about_50' => 'description',
			'pb_editor' => 'editor',
			'pb_cover_image' => 'image',
			'pb_language' => 'inLanguage',
			'pb_keywords_tags' => 'keywords',
			'pb_publisher' => 'publisher',
			'pb_title' => 'name',
		] );

		$response = rest_ensure_response( $meta );
		$this->linkCollector['self'] = [ 'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) ];
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * @param array $book_information
	 * @param $has_permission
	 * @param array $replacement_keys
	 *
	 * @return array
	 */
	protected function fixBookInformation( array $book_information, $has_permission, array $replacement_keys ) {

		$new_book_information = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
		];

		foreach ( $replacement_keys as $old => $new ) {
			if ( isset( $book_information[ $old ] ) ) {
				$new_book_information[ $new ] = $book_information[ $old ];
			}
		}

		return $new_book_information;
	}

}
