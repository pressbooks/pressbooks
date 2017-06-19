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
	 *  Registers routes for metadata
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
			'$schema' => 'http://json-schema.org/schema#',
			'title' => 'book',
			'type' => 'object',
			'properties' => [
				'@context' => [
					'type' => 'string',
					'format' => 'uri',
					'enum' => [
						'http://schema.org',
					],
					'description' => __( 'The JSON-LD context.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'@type' => [
					'type' => 'string',
					'enum' => [
						'Book',
					],
					'description' => __( 'The type of the thing.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'name' => [
					'type' => 'string',
					'description' => __( 'The name of the thing.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'about' => [
					'type' => 'string',
					'description' => __( 'The subject matter of the content.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'alternateName' => [
					'type' => 'string',
					'description' => __( 'An alias for the item.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'keywords' => [
					'type' => 'string',
					'description' => __( 'Keywords or tags used to describe this content. Multiple entries in a keywords list are typically delimited by commas.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'alternativeHeadline' => [
					'type' => 'string',
					'description' => __( 'A secondary title of the Book.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'inLanguage' => [
					'type' => 'string',
					'description' => __( 'The language of the content, expressed as one of the language codes from the IETF BCP 47 standard.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'copyrightYear' => [
					'type' => 'integer',
					'description' => __( 'The year during which the claimed copyright for the Book was first asserted.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'description' => [
					'type' => 'string',
					'description' => __( 'A description of the item.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'image' => [
					'type' => 'string',
					'format' => 'uri',
					'description' => __( 'An image of the item.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'author' => [
					'type' => 'object',
					'description' => __( 'The author of this content.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Person',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'alternateName' => [
							'type' => 'string',
							'description' => __( 'An alias for the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'contributor' => [
					'type' => 'object',
					'description' => __( 'A secondary contributor to the Book.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Person',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'editor' => [
					'type' => 'object',
					'description' => __( 'Specifies the Person who edited the Book.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Person',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'translator' => [
					'type' => 'object',
					'description' => __( 'Organization or person who adapts a Book to different languages, regional differences and technical requirements of a target market.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Person',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'publisher' => [
					'type' => 'object',
					'description' => __( 'The publisher of the Book.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Organization',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'address' => [
							'type' => 'object',
							'description' => __( 'Physical address of the item.' ),
							'properties' => [
								'@type' => [
									'type' => 'string',
									'enum' => [
										'PostalAddress',
									],
									'description' => __( 'The type of the thing.' ),
									'context' => [ 'view' ],
									'readonly' => true,
								],
								'addressLocality' => [
									'type' => 'string',
									'description' => __( 'The locality.' ),
									'context' => [ 'view' ],
									'readonly' => true,
								],
							],
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'datePublished' => [
					'type' => 'string',
					'description' => __( 'Date of first publication. A date value in ISO 8601 date format.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'copyrightHolder' => [
					'type' => 'object',
					'description' => __( 'The party holding the legal copyright to the CreativeWork.' ),
					'properties' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Person',
								'Organization',
							],
							'description' => __( 'The type of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'name' => [
							'type' => 'string',
							'description' => __( 'The name of the thing.' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
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
		$meta = $this->buildMetadata( $meta, get_option( 'blog_public' ) );

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

		$new_book_information = [];

		if ( $has_permission ) {

			$new_book_information['@context'] = 'http://schema.org';
			$new_book_information['@type'] = 'Book';

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

			// TODO: license, audience, educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl
		}

		return $new_book_information;
	}

}
