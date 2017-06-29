<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Book;
use Pressbooks\Metadata as Meta;

class SectionMetadata extends \WP_REST_Controller {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * The base of the parent controller's route.
	 *
	 * @var string
	 */
	protected $parent_base;

	/**
	 * @var array
	 */
	protected $linkCollector = [];

	/**
	 * Section Metadata
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'metadata';
		$this->post_type = $post_type;

		switch ( $post_type ) {
			case 'chapter':
				$parent_base = 'chapters';
				break;
			case 'part':
				$parent_base = 'parts';
				break;
			default:
				$parent_base = $post_type;
		}
		$this->parent_base = $parent_base;
	}

	/**
	 *  Registers routes for metadata
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->parent_base . '/(?P<parent>[\d]+)/' . $this->rest_base, [
			'args' => [
				'parent' => [
					'required' => true,
					'description' => __( 'The ID for the parent of the object.' ),
					'type' => 'integer',
				],
			],
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args' => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
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
			'title' => 'chapter',
			'type' => 'object',
			'properties' => [
				'@context' => [
					'type' => 'string',
					'format' => 'uri',
					'enum' => [
						'http://bib.schema.org',
					],
					'description' => __( 'The JSON-LD context.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'@type' => [
					'type' => 'string',
					'enum' => [
						'Chapter',
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
					'description' => __( 'An alias for the item.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'alternativeHeadline' => [
					'type' => 'string',
					'description' => __( 'A secondary title of the Book.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'position' => [
					'type' => 'integer',
					'description' => __( 'The position of an item in a series or sequence of items.' ),
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
				'license' => [
					'type' => 'string',
					'format' => 'uri',
					'description' => __( 'A license document that applies to this content, typically indicated by URL.' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}


	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

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
	public function get_item( $request ) {
		$posts = get_posts( [ 'p' => $request['parent'], 'post_type' => $this->post_type ] );
		$error = new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.' ), [ 'status' => 404 ] );
		if ( empty( $posts ) ) {
			return $error;
		}

		$section_meta = get_post_meta( $request['parent'], '', true );
		$book_meta = Book::getBookInformation();
		$section_meta['pb_title'] = get_the_title( $request['parent'] );
		if ( $this->post_type === 'chapter' ) {
			$section_meta['pb_chapter_number'] = pb_get_chapter_number( get_post_field( 'post_name', $request['parent'] ) );
		}
		foreach ( $section_meta as $key => $value ) {
			if ( is_array( $value ) ) {
				$section_meta[ $key ] = array_pop( $value );
			}
		}
		$section_meta = $this->buildMetadata( $section_meta, $book_meta );

		$response = rest_ensure_response( $section_meta );
		$this->linkCollector['self'] = [ 'href' => rest_url( sprintf( '%s/%s/%d/%s', $this->namespace, $this->parent_base, $request['parent'], 'metadata' ) ) ];
		$this->linkCollector[ $this->post_type ] = [ 'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->parent_base, $request['parent'] ) ) ];
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * @param array $section_information
	 * @param array $book_information
	 *
	 * @return array
	 */
	protected function buildMetadata( array $section_information, array $book_information ) {

		$new_section_information = [];

		$new_section_information['@context'] = 'http://bib.schema.org';
		$new_section_information['@type'] = 'Chapter';

		$mapped_section_properties = [
			'pb_title' => 'name',
			'pb_short_title' => 'alternateName',
			'pb_subtitle' => 'alternativeHeadline',
		];

		$mapped_book_properties = [
			'pb_language' => 'inLanguage',
			'pb_copyright_year' => 'copyrightYear',
		];

		foreach ( $mapped_section_properties as $old => $new ) {
			if ( isset( $section_information[ $old ] ) ) {
				$new_section_information[ $new ] = $section_information[ $old ];
			}
		}

		foreach ( $mapped_book_properties as $old => $new ) {
			if ( isset( $book_information[ $old ] ) ) {
				$new_section_information[ $new ] = $book_information[ $old ];
			}
		}

		if ( ! empty( $section_information['pb_chapter_number'] ) ) {
			$new_section_information['position'] = $section_information['pb_chapter_number'];
		}

		if ( isset( $section_information['pb_section_author'] ) ) {
			$new_section_information['author'] = [
				'@type' => 'Person',
				'name' => $section_information['pb_section_author'],
			];
		} elseif ( isset( $book_information['pb_author'] ) ) {
			$new_section_information['author'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_author'],
			];

			if ( isset( $book_information['pb_author_file_as'] ) ) {
				$new_section_information['author']['alternateName'] = $book_information['pb_author_file_as'];
			}
		}

		if ( isset( $book_information['pb_contributing_authors'] ) ) {
			$contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
			foreach ( $contributing_authors as $contributor ) {
				$new_section_information['contributor'][] = [
					'@type' => 'Person',
					'name' => $contributor,
				];
			}
		}

		if ( isset( $book_information['pb_editor'] ) ) {
			$new_section_information['editor'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_editor'],
			];
		}

		if ( isset( $book_information['pb_translator'] ) ) {
			$new_section_information['translator'] = [
				'@type' => 'Person',
				'name' => $book_information['pb_translator'],
			];
		}

		if ( isset( $book_information['pb_publisher'] ) ) {
			$new_section_information['publisher'] = [
				'@type' => 'Organization',
				'name' => $book_information['pb_publisher'],
			];

			if ( isset( $book_information['pb_publisher_city'] ) ) {
				$new_section_information['publisher']['address'] = [
					'@type' => 'PostalAddress',
					'addressLocality' => $book_information['pb_publisher_city'],
				];
			}
		}

		if ( isset( $book_information['pb_publication_date'] ) ) {
			$new_section_information['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );
		}

		if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
			$new_section_information['copyrightHolder'] = [
				'@type' => 'Organization',
				'name' => $book_information['pb_copyright_holder'],
			];
		}

		if ( ! isset( $section_information['pb_section_license'] ) ) {
			if ( isset( $book_information['pb_license'] ) ) {
				$section_information['pb_section_license'] = $book_information['pb_license'];
			} else {
				$section_information['pb_section_license'] = '';
			}
		}

		$new_section_information['license'] = Meta::getUrlForLicense( $section_information['pb_section_license'] );

		// TODO: audience, educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl

		return $new_section_information;
	}

}
