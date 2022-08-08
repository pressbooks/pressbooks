<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Book;

class Toc extends \WP_REST_Controller {

	/**
	 * @var SectionMetadata
	 */
	protected $frontMatterMetadata;

	/**
	 * @var SectionMetadata
	 */
	protected $backMatterMetadata;

	/**
	 * @var SectionMetadata
	 */
	protected $chapterMetadata;

	/**
	 * @var array
	 */
	protected $linkCollector = [];

	/**
	 * Table of contents
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'toc';

		$this->frontMatterMetadata = new SectionMetadata( 'front-matter' );
		$this->backMatterMetadata = new SectionMetadata( 'back-matter' );
		$this->chapterMetadata = new SectionMetadata( 'chapter' );
	}

	/**
	 *  Registers routes for TOC
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base, [
				[
					'methods' => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args' => [
						'context' => $this->get_context_param(
							[
								'default' => 'view',
							]
						),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Retrieves TOC schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$item = [
			'id' => [
				'description' => __( 'Unique identifier for the object.' ),
				'type' => 'integer',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'title' => [
				'description' => __( 'The title for the object.' ),
				'type' => 'string',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'slug' => [
				'description' => __( 'An alphanumeric identifier for the object unique to its type.' ),
				'type' => 'string',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'author' => [
				'description' => __( 'The ID for the author of the object.' ),
				'type' => 'integer',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'comment_count' => [
				'description' => __( 'Comment count', 'pressbooks' ),
				'type' => 'integer',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'menu_order' => [
				'description' => __( 'The order of the object in relation to other object of its type.' ),
				'type' => 'integer',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'status' => [
				'description' => __( 'A named status for the object.' ),
				'type' => 'string',
				'enum' => array_keys(
					get_post_stati(
						[
							'internal' => false,
						]
					)
				),
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'export' => [
				'description' => __( 'Include in exports.', 'pressbooks' ),
				'type' => 'boolean',
				'context' => [ 'view' ],
				'readonly' => true,
			],
			'has_post_content' => [
				'description' => __( 'Has post content, the content field is not empty.', 'pressbooks' ),
				'type' => 'boolean',
				'readonly' => true,
			],
			'word_count' => [
				'description' => __( 'Word count.', 'pressbooks' ),
				'type' => 'integer',
				'readonly' => true,
			],
			'link' => [
				'description' => __( 'URL to the object.' ),
				'type' => 'string',
				'format' => 'uri',
				'context' => [ 'view' ],
				'readonly' => true,
			],
		];

		$fm_meta_data = $this->frontMatterMetadata->get_item_schema();
		$fm_item = array_merge(
			$item, [
				'metadata' => [
					'description' => __( 'Metadata', 'pressbooks' ),
					'type' => 'object',
					'properties' => $fm_meta_data['properties'],
					'context' => [ 'view' ],
					'readonly' => true,
				],
			]
		);

		$bm_meta_data = $this->backMatterMetadata->get_item_schema();
		$bm_item = array_merge(
			$item, [
				'metadata' => [
					'description' => __( 'Metadata', 'pressbooks' ),
					'type' => 'object',
					'properties' => $bm_meta_data['properties'],
					'context' => [ 'view' ],
					'readonly' => true,
				],
			]
		);

		$ch_meta_data = $this->chapterMetadata->get_item_schema();
		$ch_item = array_merge(
			$item, [
				'metadata' => [
					'description' => __( 'Metadata', 'pressbooks' ),
					'type' => 'object',
					'properties' => $ch_meta_data['properties'],
					'context' => [ 'view' ],
					'readonly' => true,
				],
			]
		);

		$schema = [
			'$schema' => 'http://json-schema.org/schema#',
			'title' => 'toc',
			'type' => 'object',
			'properties' => [
				'front-matter' => [
					'description' => __( 'Front Matter', 'pressbooks' ),
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => $fm_item,
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'parts' => [
					'description' => __( 'Part', 'pressbooks' ),
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => array_merge(
							$item, [
								'chapters' => [
									'description' => __( 'Chapter', 'pressbooks' ),
									'type' => 'array',
									'items' => [
										'type' => 'object',
										'properties' => $ch_item,
									],
								],
								'context' => [ 'view' ],
								'readonly' => true,
							]
						),
					],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'back-matter' => [
					'description' => __( 'Back Matter', 'pressbooks' ),
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => $bm_item,
					],
				],
				'context' => [ 'view' ],
				'readonly' => true,
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ): bool {
		if ( has_filter( 'pb_set_api_items_permission' ) && apply_filters( 'pb_set_api_items_permission', $this->rest_base ) ) {
			return true;
		}
		return current_user_can( 'edit_posts' ) || get_option( 'blog_public' );
	}

	/**
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {

		// Register missing routes
		$this->registerRouteDependencies();

		$struct = Book::getBookStructure();
		unset( $struct['__order'] );
		$has_permission = current_user_can( 'edit_posts' );
		if ( has_filter( 'pb_set_api_items_permission' ) && apply_filters( 'pb_set_api_items_permission', false ) ) {
			$has_permission = true;
		}

		$struct = $this->fixBookStructure( $struct, $has_permission );

		$response = rest_ensure_response( $struct );
		$this->linkCollector['self'] = [
			'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
		];
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * Define route dependencies.
	 * Toc content is built by querying section metadata, but those API routes may not exist at the root level.
	 */
	protected function registerRouteDependencies() {
		$this->frontMatterMetadata->register_routes();
		$this->backMatterMetadata->register_routes();
		$this->chapterMetadata->register_routes();
	}

	/**
	 * @param array $book_structure
	 * @param bool $has_permission
	 *
	 * @return array
	 */
	protected function fixBookStructure( array $book_structure, $has_permission ) {

		$toc = [];

		$replacement_keys = [
			'ID' => 'id',
			'post_title' => 'title',
			'post_name' => 'slug',
			'post_author' => 'author',
			'post_status' => 'status',
		];

		$toc['front-matter'] = $this->fixFrontMatterStructure( $book_structure, $has_permission, $replacement_keys );
		$toc['parts'] = $this->fixPartChapterStructure( $book_structure, $has_permission, $replacement_keys );
		$toc['back-matter'] = $this->fixBackMatterStructure( $book_structure, $has_permission, $replacement_keys );

		return $toc;
	}

	/**
	 * @param array $book_structure
	 * @param $has_permission
	 * @param array $replacement_keys
	 *
	 * @return array
	 */
	protected function fixFrontMatterStructure( array $book_structure, $has_permission, array $replacement_keys ) {

		$base = 'front-matter';
		$rest_url = rest_url( sprintf( '%s/%s', $this->namespace, $base ) );

		// Front-matter
		$front_matter = [];
		foreach ( $book_structure['front-matter'] as $old_fm ) {
			if ( $has_permission || in_array( $old_fm['post_status'], [ 'web-only', 'publish' ], true ) ) {
				$new_fm = [];
				foreach ( $old_fm as $old_key => $val ) {
					$new_key = strtr( $old_key, $replacement_keys );
					$new_fm[ $new_key ] = $val;
				}
				$new_fm['link'] = get_permalink( $new_fm['id'] );
				$new_fm['front-matter-type'] = wp_get_object_terms(
					$new_fm['id'], 'front-matter-type', [
						'fields' => 'ids',
					]
				);
				$this->linkCollector['front-matter'][] = [
					'href' => trailingslashit( $rest_url ) . $new_fm['id'],
					'embeddable' => true,
				];

				// Metadata
				$request_metadata = new \WP_REST_Request( 'GET', "/pressbooks/v2/{$base}/{$new_fm['id']}/metadata" );
				$response_metadata = rest_do_request( $request_metadata );
				$new_fm['metadata'] = $this->prepare_response_for_collection( $response_metadata );
				$this->linkCollector['metadata'][] = [
					'href' => trailingslashit( $rest_url ) . "{$new_fm['id']}/metadata",
				];
				unset( $new_fm['metadata']['_links'] );

				$front_matter[] = $new_fm;
			}
		}

		return $front_matter;
	}

	/**
	 * @param array $book_structure
	 * @param $has_permission
	 * @param array $replacement_keys
	 *
	 * @return array
	 */
	protected function fixPartChapterStructure( array $book_structure, $has_permission, array $replacement_keys ) {

		$part_base = 'parts';
		$part_rest_url = rest_url( sprintf( '%s/%s', $this->namespace, $part_base ) );
		$chapter_base = 'chapters';
		$chapter_rest_url = rest_url( sprintf( '%s/%s', $this->namespace, $chapter_base ) );

		$part = [];
		foreach ( $book_structure['part'] as $old_p ) {
			$new_p = [];
			foreach ( $old_p as $old_key => $val ) {
				$new_key = strtr( $old_key, $replacement_keys );
				$new_p[ $new_key ] = $val;
			}
			$chapters = [];
			foreach ( $new_p['chapters'] as $old_ch ) {
				if ( $has_permission || in_array( $old_ch['post_status'], [ 'web-only', 'publish' ], true ) ) {
					$new_ch = [];
					foreach ( $old_ch as $old_key => $val ) {
						$new_key = strtr( $old_key, $replacement_keys );
						$new_ch[ $new_key ] = $val;
					}
					$new_ch['link'] = get_permalink( $new_ch['id'] );
					$new_ch['chapter-type'] = wp_get_object_terms(
						$new_ch['id'], 'chapter-type', [
							'fields' => 'ids',
						]
					);
					$this->linkCollector['chapter'][] = [
						'href' => trailingslashit( $chapter_rest_url ) . $new_ch['id'],
						'embeddable' => true,
					];

					// Metadata
					$request_metadata = new \WP_REST_Request( 'GET', "/pressbooks/v2/{$chapter_base}/{$new_ch['id']}/metadata" );
					$response_metadata = rest_do_request( $request_metadata );
					$new_ch['metadata'] = $this->prepare_response_for_collection( $response_metadata );
					$this->linkCollector['metadata'][] = [
						'href' => trailingslashit( $chapter_rest_url ) . "{$new_ch['id']}/metadata",
					];
					unset( $new_ch['metadata']['_links'] );

					$chapters[] = $new_ch;
				}
			}
			$new_p['chapters'] = $chapters;
			$new_p['link'] = get_permalink( $new_p['id'] );
			$this->linkCollector['part'][] = [
				'href' => trailingslashit( $part_rest_url ) . $new_p['id'],
				'embeddable' => true,
			];
			$part[] = $new_p;
		}

		return $part;
	}

	/**
	 * @param array $book_structure
	 * @param $has_permission
	 * @param array $replacement_keys
	 *
	 * @return array
	 */
	protected function fixBackMatterStructure( array $book_structure, $has_permission, array $replacement_keys ) {

		$base = 'back-matter';
		$rest_url = rest_url( sprintf( '%s/%s', $this->namespace, $base ) );

		$back_matter = [];
		foreach ( $book_structure['back-matter'] as $old_bm ) {
			if ( $has_permission || in_array( $old_bm['post_status'], [ 'web-only', 'publish' ], true ) ) {
				$new_bm = [];
				foreach ( $old_bm as $old_key => $val ) {
					$new_key = strtr( $old_key, $replacement_keys );
					$new_bm[ $new_key ] = $val;
				}
				$new_bm['link'] = get_permalink( $new_bm['id'] );
				$new_bm['back-matter-type'] = wp_get_object_terms(
					$new_bm['id'], 'back-matter-type', [
						'fields' => 'ids',
					]
				);
				$this->linkCollector['back-matter'][] = [
					'href' => trailingslashit( $rest_url ) . $new_bm['id'],
					'embeddable' => true,
				];

				// Metadata
				$request_metadata = new \WP_REST_Request( 'GET', "/pressbooks/v2/{$base}/{$new_bm['id']}/metadata" );
				$response_metadata = rest_do_request( $request_metadata );
				$new_bm['metadata'] = $this->prepare_response_for_collection( $response_metadata );
				$this->linkCollector['metadata'][] = [
					'href' => trailingslashit( $rest_url ) . "{$new_bm['id']}/metadata",
				];
				unset( $new_bm['metadata']['_links'] );

				$back_matter[] = $new_bm;
			}
		}

		return $back_matter;
	}

}
