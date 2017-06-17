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
				'name' => [
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
		$meta = $this->buildMetadata( $meta, current_user_can( 'edit_posts' ) );

		$response = rest_ensure_response( $meta );
		// TODO Do we need the linkCollector here?

		return $response;
	}

	/**
	 * @param array $book_information
	 * @param $has_permission
	 *
	 * @return array
	 */
	protected function buildMetadata( array $book_information, $has_permission ) {

		$mapped_properties = [
			'pb_bisac_subject' => 'about',
			'pb_title' => 'name',
			'pb_short_title' => 'alternateName',
			'pb_keywords_tags' => 'keywords',
			'pb_subtitle' => 'alternativeHeadline',
			'pb_language' => 'inLanguage',
			'pb_copyright_year' => 'copyrightYear',
			'pb_about_50' => 'description',
			'pb_cover_image' => 'image',
		];

		$new_book_information = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
		];

		foreach ( $mapped_properties as $old => $new ) {
			if ( isset( $book_information[ $old ] ) ) {
				$new_book_information[ $new ] = $book_information[ $old ];
			}
		}

		if ( isset( $book_information['pb_author'] ) ) {
			$new_book_information['author'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_author'],
			];

			if ( isset( $book_information['pb_author_file_as'] ) ) {
				$new_book_information['author']['alternateName'] = $book_information['pb_author_file_as'];
			}
		}

		if ( isset( $book_information['pb_contributing_authors'] ) ) {
			$contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
			foreach ( $contributing_authors as $contributor ) {
				$new_book_information['contributor'][] = [
					'@type' => 'Person',
					'name' => $contributor,
				];
			}
		}

		if ( isset( $book_information['pb_editor'] ) ) {
			$new_book_information['editor'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_editor'],
			];
		}

		if ( isset( $book_information['pb_translator'] ) ) {
			$new_book_information['translator'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_translator'],
			];
		}

		if ( isset( $book_information['pb_publisher'] ) ) {
			$new_book_information['publisher'] = [
				'@type' => 'Organization',
				'name' => $book_information['pb_publisher'],
			];

			if ( isset( $book_information['pb_publisher_city'] ) ) {
				$new_book_information['publisher']['address'] = [
					'@type' => 'PostalAddress',
					'addressLocality' => $book_information['pb_publisher_city'],
				];
			}
		}

		if ( isset( $book_information['pb_publication_date'] ) ) {
			$new_book_information['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );
		}

		if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
			$new_book_information['copyrightHolder'] = [
				'@type' => 'Organization',
				'name' => $book_information['pb_copyright_holder'],
			];
		}

		return $new_book_information;
	}

}
