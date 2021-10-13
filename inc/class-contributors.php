<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Pressbooks;

use function Pressbooks\Metadata\init_book_data_models;
use function Pressbooks\Utility\explode_remove_and;
use function Pressbooks\Utility\str_starts_with;
use Illuminate\Support\Str;
use Pressbooks\PostType\BackMatter;
use Pressbooks\Utility\AutoDisplayable;
use Pressbooks\Utility\HandlesTransfers;

/**
 *
 */
class Contributors implements BackMatter, Transferable {

	use AutoDisplayable;
	use HandlesTransfers;

	public const TAXONOMY = 'contributor';

	const PICTURE_MIN_PIXELS = 400;

	/**
	 * @var Contributors
	 */
	static $instance = null;

	/**
	 * Valid contributor slugs ordered by preference
	 *
	 * @var array
	 */
	public $valid = [
		'pb_editors',
		'pb_authors',
		'pb_contributors',
		'pb_translators',
		'pb_reviewers',
		'pb_illustrators',
	];

	/**
	 * @var array
	 */
	public $deprecated = [
		'pb_author',
		'pb_section_author',
		'pb_contributing_authors',
		'pb_author_file_as',
		'pb_editor',
		'pb_translator',
	];
	/**
	 * @var bool
	 */
	private $exporting;

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Contributors
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * @param Contributors $obj
	 */
	public static function hooks( Contributors $obj ) {
		add_action( 'delete_' . self::TAXONOMY, [ $obj, 'deleteContributor' ], 10, 3 );

		add_action(
			'pb_pre_export', function () use ( $obj ) {
				$obj->exporting = true; //only set this variable during exports
			}
		);

		add_filter( 'the_content', [ $obj, 'overrideDisplay' ], 13 ); // Run after wpautop to avoid unwanted breaklines.

		$obj->bootExportable( $obj );
	}

	/**
	 * @param string $contributor_type
	 *
	 * @return bool
	 */
	public function isValid( $contributor_type ) {
		return in_array( $contributor_type, $this->valid, true );
	}

	/**
	 * @param string $contributor_type
	 *
	 * @return bool
	 */
	public function isDeprecated( $contributor_type ) {
		return in_array( $contributor_type, $this->deprecated, true );
	}

	/**
	 * Retrieve all author/editor/etc lists for a given Post ID
	 *
	 * @param int $post_id
	 * @param bool $as_strings
	 * @param bool $include_term_meta
	 *
	 * @return array
	 */
	public function getAll( $post_id, $as_strings = true, $include_term_meta = false ) {
		$contributors = [];
		foreach ( $this->valid as $contributor_type ) {
			if ( $as_strings ) {
				$contributors[ $contributor_type ] = $this->get( $post_id, $contributor_type );
			} else {
				$contributors[ $contributor_type ] = $this->getArray( $post_id, $contributor_type, $include_term_meta );
			}
		}
		return $contributors;
	}

	/**
	 * Retrieve author/editor/etc lists for a given Post ID and Contributor type, returns string
	 *
	 * @param int $post_id
	 * @param string $contributor_type
	 *
	 * @return string
	 */
	public function get( $post_id, $contributor_type ) {
		$contributors = $this->getArray( $post_id, $contributor_type );
		return \Pressbooks\Utility\implode_add_and( ';', $contributors );
	}

	/**
	 * Retrieve author/editor/etc lists for a given Post ID and Contributor type, returns array
	 *
	 * @param int $post_id
	 * @param bool $include_term_meta
	 * @param string $contributor_type
	 *
	 * @return array
	 */
	public function getArray( $post_id, $contributor_type, $include_term_meta = false ) {
		if ( ! str_starts_with( $contributor_type, 'pb_' ) ) {
			$contributor_type = 'pb_' . $contributor_type;
		}
		if ( ! $this->isValid( $contributor_type ) ) {
			return [];
		}

		// Look if contributors exist as taxonomies (new data model)
		$contributors = [];
		$meta = get_post_meta( $post_id, $contributor_type, false );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $slug ) {
				$term = get_term_by( 'slug', $slug, self::TAXONOMY );
				if ( $term ) {
					$contributor = get_term_meta( $term->term_id );
					if ( ! $include_term_meta ) {
						$contributors[] = $term->name;
					} else {
						if ( $term ) {
							foreach ( $contributor as $field => $property ) {
								$contributor[ $field ] = is_array( $property ) ? $property[0] : $property;
							}
							$contributor['name'] = $term->name;
							$contributor['slug'] = $term->slug;
							$contributors[] = $contributor;
						}
					}
				}
			}
		}

		if ( empty( $contributors ) ) {
			// Look if contributors exist as metadata (old data model)
			// If yes then convert to taxonomies (new data model)
			$map = [
				'pb_authors' => [ 'pb_author', 'pb_section_author', 'pb_author_file_as' ],
				'pb_editors' => [ 'pb_editor' ],
				'pb_translators' => [ 'pb_translator' ],
				'pb_contributors' => [ 'pb_contributing_authors' ],
			];
			if ( isset( $map[ $contributor_type ] ) ) {
				foreach ( $map[ $contributor_type ] as $slug ) {
					$meta = get_post_meta( $post_id, $slug, false );
					if ( is_array( $meta ) ) {
						foreach ( $meta as $name ) {
							$result = $this->insert( $name );
							if ( $result !== false ) {
								$added = $this->link( $result['term_id'], $post_id, $contributor_type );
								if ( $added !== false ) {
									$contributors[] = $name;
									delete_post_meta( $post_id, $slug, $name );
								}
							}
						}
					}
				}
			}
		}

		return $contributors;
	}

	/**
	 * Insert a new contributor by name or fields data and associate it by post_id if present.
	 * If the contributor's already exists by $compare_by parameter and/or name field it will be replaced by the new one.
	 * if $post_id is present ($post_id != 0) and the metadata will be updated anyway.
	 * The contributor_picture will be downloaded to the media if the field and $downloads object is present.
	 *
	 * @param string | array $data
	 * @param int $post_id
	 * @param string $contributor_type
	 * @param \Pressbooks\Cloner\Downloads $downloads (optional)
	 * @return array|false|int|mixed
	 */
	public function insert( $data, $post_id = 0, $contributor_type = 'pb_authors', $downloads = null, $compare_by = 'name' ) {
		if ( is_string( $data ) ) {
			return $this->insertByFullName( $data, $post_id, $contributor_type );
		}
		$term_id = false;
		$term = false;
		if ( is_array( $data ) && isset( $data['name'] ) ) {

			if ( $compare_by !== 'disambiguate' ) {
				$term_by_name = get_term_by( 'name', $data['name'], self::TAXONOMY );
				if ( $term_by_name !== false ) {
					$term = $term_by_name;
				} elseif ( $compare_by !== 'name' && array_key_exists( $compare_by, $data ) ) {
					$term_by_custom = get_term_by( $compare_by, $data[ $compare_by ], self::TAXONOMY );
					if ( $term_by_custom !== false ) {
						$term = $term_by_custom;
					}
				}
			}

			if ( ! $term ) {
				$results = wp_insert_term(
					$data['name'],
					self::TAXONOMY,
					[
						'slug' => $data['slug'],
					]
				);
				if ( $results instanceof \WP_Error && isset( $results->error_data['term_exists'] ) ) {
					if ( $compare_by === 'disambiguate' ) {
						$results = wp_insert_term(
							$data['name'],
							self::TAXONOMY,
							[
								'slug' => $data['slug'] . '-' . str_random( 10 ),
							]
						);
						$term_id = $results['term_id'];
					} else {
						$term_id = $results->error_data['term_exists'];
					}
				} else {
					$term_id = $results['term_id'];
				}
			} else {
				$term_id = $term->term_id;
			}
			unset( $data['name'] );
			unset( $data['slug'] );
			$contributor_fields = self::getContributorFields();
			$terms_contributor_meta = get_term_meta( $term_id );
			foreach ( $data as $field => $property ) {
				if (
					array_key_exists( $field, $contributor_fields ) &&
					(
						! array_key_exists( $field, $terms_contributor_meta ) ||
						$terms_contributor_meta[ $field ][0] !== $property
					)
				) {
					if ( ! is_null( $downloads ) && $field === self::TAXONOMY . '_picture' ) {
						$image_id = $downloads->fetchAndSaveUniqueImage( $property );
						if ( $image_id <= 0 ) {
							continue;
						} else {
							$property = wp_get_attachment_url( $image_id );
						}
					}
					if ( ! array_key_exists( $field, $terms_contributor_meta ) ) {
						add_term_meta( $term_id, $field, $property );
					} else {
						update_term_meta( $term_id, $field, $property );
					}
				}
			}
			if ( $term_id && $post_id ) {
				$this->link( $term_id, $post_id, $contributor_type );
			}
		}
		return $term_id;
	}

	/**
	 * @param string $full_name
	 * @param int $post_id (optional)
	 * @param string $contributor_type (optional)
	 *
	 * @return array|false An array containing the `term_id` and `term_taxonomy_id`, false otherwise.
	 */
	public function insertByFullName( $full_name, $post_id = 0, $contributor_type = 'pb_authors' ) {
		$full_name = trim( $full_name );
		$slug = sanitize_title_with_dashes( remove_accents( $full_name ), '', 'save' );
		$term = get_term_by( 'slug', $slug, self::TAXONOMY );
		if ( $term ) {
			$results = [
				'term_id' => $term->term_id,
				'term_taxonomy_id' => $term->term_taxonomy_id,
			];
		} else {
			$results = wp_insert_term(
				$full_name, self::TAXONOMY, [
					'slug' => $slug,
				]
			);
		}

		if ( $post_id && is_array( $results ) ) {
			$this->link( $results['term_id'], $post_id, $contributor_type );
		}

		return is_array( $results ) ? $results : false;
	}

	/**
	 * Associate a Contributor's Term ID to a Post ID (Taxonomy + Meta)
	 * Technically we are assigning the Term Slug to the Post ID. This function handles either.
	 *
	 * @param int|string $term_id
	 * @param int $post_id
	 * @param string $contributor_type
	 *
	 * @return bool
	 */
	public function link( $term_id, $post_id, $contributor_type = 'pb_authors' ) {
		global $wpdb;
		if ( ! str_starts_with( $contributor_type, 'pb_' ) ) {
			$contributor_type = 'pb_' . $contributor_type;
		}
		if ( $this->isValid( $contributor_type ) ) {
			if ( preg_match( '/\d+/', $term_id ) ) {
				$term = get_term( $term_id, self::TAXONOMY ); // Get slug by Term ID
			} else {
				$term = get_term_by( 'slug', $term_id, self::TAXONOMY ); // Verify that slug is valid
			}
			if ( $term && ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, $term->term_id, self::TAXONOMY, true );
				$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s AND meta_value = %s", $post_id, $contributor_type, $term->slug ) );
				if ( $meta_id ) {
					return true;
				} else {
					return add_post_meta( $post_id, $contributor_type, $term->slug ) !== false;
				}
			}
		}
		return false;
	}

	/**
	 * Disassociate a Contributor's Term ID to a Post ID (Taxonomy + Meta)
	 *
	 * @param int|string $term_id
	 * @param int $post_id
	 * @param string $contributor_type
	 *
	 * @return bool
	 */
	public function unlink( $term_id, $post_id, $contributor_type = 'pb_authors' ) {
		if ( ! str_starts_with( $contributor_type, 'pb_' ) ) {
			$contributor_type = 'pb_' . $contributor_type;
		}
		if ( $this->isValid( $contributor_type ) ) {
			if ( preg_match( '/\d+/', $term_id ) ) {
				$term = get_term( $term_id, self::TAXONOMY ); // Get slug by Term ID
			} else {
				$term = get_term_by( 'slug', $term_id, self::TAXONOMY ); // Verify that slug is valid
			}
			if ( $term && ! is_wp_error( $term ) ) {
				wp_remove_object_terms( $post_id, $term->term_id, self::TAXONOMY );
				delete_post_meta( $post_id, $contributor_type, $term->slug );
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove deleted contributor's post meta references
	 * @param int $term
	 * @param int $tt_id
	 * @param \WP_Term $deleted_term
	 */
	public function deleteContributor( $term, $tt_id, $deleted_term ) {
		global $wpdb;

		$placeholder = implode( ', ', array_fill( 0, count( $this->valid ), '%s' ) );

		$wpdb->query(
			$wpdb->prepare(
			//phpcs:ignore
				"DELETE FROM $wpdb->postmeta WHERE meta_key IN ( $placeholder ) AND meta_value = %s",
				array_merge( $this->valid, [ $deleted_term->slug ] )
			)
		);
	}

	/**
	 * Return contributors' taxonomy terms as an associative array with meta HTML tag information.
	 *
	 * @param string $field
	 * @return array
	 */
	public static function getContributorFields( $field = '' ) {
		$allowed_fields = [
			self::TAXONOMY . '_prefix' => [
				'label' => __( 'Prefix', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-prefix',
				'input_type' => 'text',
				'description' => __( 'Prefix to be displayed before this contributor\'s name, e.g. Dr., Prof., Ms., Rev., Capt.', 'pressbooks' ),
				'sanitization_method' => 'sanitize_text_field',
			],
			self::TAXONOMY . '_first_name' => [
				'label' => __( 'First Name', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-first-name',
				'input_type' => 'text',
				'sanitization_method' => 'sanitize_text_field',
			],
			self::TAXONOMY . '_last_name' => [
				'label' => __( 'Last Name', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-last-name',
				'input_type' => 'text',
				'sanitization_method' => 'sanitize_text_field',
			],
			self::TAXONOMY . '_suffix' => [
				'label' => __( 'Suffix', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-suffix',
				'input_type' => 'text',
				'description' => __( 'Suffix to be displayed after this contributors\'s name, e.g. Jr., Sr., IV, PhD, MD, USN (Ret.).', 'pressbooks' ),
				'sanitization_method' => 'sanitize_text_field',
			],
			self::TAXONOMY . '_picture' => [
				'label' => __( 'Picture', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-picture',
				'input_type' => 'picture',
				'sanitization_method' => '\Pressbooks\Sanitize\validate_url_field',
			],
			self::TAXONOMY . '_description' => [
				'label' => __( 'Biographical Info', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-biography',
				'input_type' => 'tinymce',
			],
			self::TAXONOMY . '_institution' => [
				'label' => __( 'Institution', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-institution',
				'input_type' => 'text',
				'description' => __( 'Institution this contributor is associated with, e.g. Rebus Foundation, Open University, Amnesty International.', 'pressbooks' ),
				'sanitization_method' => 'sanitize_text_field',
			],
			self::TAXONOMY . '_user_url' => [
				'label' => __( 'Website', 'presbooks' ),
				'tag' => self::TAXONOMY . '-website',
				'input_type' => 'text',
				'description' => __( 'Website for this contributor. Must be a valid URL.', 'pressbooks' ),
				'sanitization_method' => '\Pressbooks\Sanitize\validate_url_field',
			],
			self::TAXONOMY . '_twitter' => [
				'label' => __( 'Twitter', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-twitter',
				'input_type' => 'text',
				'description' => __( 'Twitter profile for this contributor. Must be a valid URL.', 'pressbooks' ),
				'sanitization_method' => '\Pressbooks\Sanitize\validate_url_field',
			],
			self::TAXONOMY . '_linkedin' => [
				'label' => __( 'LinkedIn', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-linkedin',
				'input_type' => 'text',
				'description' => __( 'LinkedIn profile for this contributor. Must be a valid URL.', 'pressbooks' ),
				'sanitization_method' => '\Pressbooks\Sanitize\validate_url_field',
			],
			self::TAXONOMY . '_github' => [
				'label' => __( 'GitHub', 'pressbooks' ),
				'tag' => self::TAXONOMY . '-github',
				'input_type' => 'text',
				'description' => __( 'GitHub profile for this contributor. Must be a valid URL.', 'pressbooks' ),
				'sanitization_method' => '\Pressbooks\Sanitize\validate_url_field',
			],
		];

		return array_key_exists( self::TAXONOMY . '_' . $field, $allowed_fields ) ?
			$allowed_fields[ self::TAXONOMY . '_' . $field ] :
			$allowed_fields;
	}

	/**
	 * Get the list of fields that should be exported.
	 *
	 * @return array
	 */
	public function getTransferableFields() {
		return array_keys( self::getContributorFields() );
	}

	/**
	 * Get the list of fields that should be stored as URLs.
	 *
	 * @return array
	 */
	public function getUrlFields() {
		$fields = self::getContributorFields();

		return array_keys(
			array_filter(
				$fields, function( $field ) {
					if ( ! isset( $field['sanitization_method'] ) ) {
						return false;
					}

					return $field['sanitization_method'] === '\Pressbooks\Sanitize\validate_url_field';
				}
			)
		);
	}

	/**
	 * Sanitize input when importing data.
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public function sanitizeField( $name, $value ) {
		$field = self::getContributorFields( str_replace( self::TAXONOMY . '_', '', $name ) );

		// if the given field does not have a sanitization method we simply return the given value.
		if ( ! isset( $field['sanitization_method'] ) ) {
			return $value;
		}

		// If the given field is a URL, we return the given value if it's a valid URL and an empty string otherwise.
		if ( in_array( $name, $this->getUrlFields(), true ) ) {
			return $field['sanitization_method']( $value ) ? $value : '';
		}

		// Apply the sanitization method.
		return $field['sanitization_method']( $value );
	}

	/**
	 * Returns the form title and the hint for the file input.
	 *
	 * @return array
	 */
	public function getFormMessages() {
		$guide_chapter = esc_url( 'https://guide.pressbooks.com/chapter/creating-and-displaying-contributors/#importingcontributors' );
		$hint = __( '<p>Import multiple contributors at once by uploading a valid JSON file. See <a href="%s" target="_blank">our guide</a> for details.</p>', 'pressbooks' );

		return [
			'title' => '<h2>' . __( 'Import Contributors', 'pressbooks' ) . '</h2>',
			'hint' => sprintf( $hint, $guide_chapter ),
		];
	}

	/**
	 * Create a matching Contributor term for a given User ID. Used when a user is added to a blog.
	 *
	 * @param int $user_id
	 *
	 * @return array|false An array containing the `term_id` and `term_taxonomy_id`, false otherwise.
	 */
	public function addBlogUser( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user && user_can( $user, 'edit_posts' ) ) {
			$slug = $user->user_nicename;
			$name = trim( "{$user->first_name} {$user->last_name}" );
			if ( empty( $name ) ) {
				$name = $user->display_name;
				if ( empty( $name ) ) {
					$name = $slug;
				}
			}
			$results = wp_insert_term(
				$name, self::TAXONOMY, [
					'slug' => $slug,
				]
			);
			if ( is_array( $results ) ) {
				$contributors_terms = array_keys( self::getContributorFields() );
				foreach ( $contributors_terms as $term ) {
					$user_term = str_replace( 'contributor_', '', $term );

					if ( $user_term === 'picture' ) {
						// handle gravatar profile picture
						$hash = md5( strtolower( $user->user_email ) );

						$src = \Pressbooks\Utility\handle_image_upload( "https://secure.gravatar.com/avatar/{$hash}?s=400&d=404" );

						if ( $src ) {
							add_term_meta( $results['term_id'], $term, $src, true );
						}

						continue;
					}

					if ( $user->$user_term ) {
						add_term_meta( $results['term_id'], $term, $user->$user_term, true );
					}
				}

				return $results;
			}
		}
		return false;
	}

	/**
	 * Update a matching Contributor term given a User ID. Used when a blog user is updated
	 *
	 * @param int $user_id
	 * @param \WP_User $old_user_data
	 *
	 * @return array|false An array containing the `term_id` and `term_taxonomy_id`, false otherwise.
	 */
	public function updateBlogUser( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );
		if ( $user && user_can( $user, 'edit_posts' ) ) {
			$slug = $user->user_nicename;
			$name = trim( "{$user->first_name} {$user->last_name}" );
			if ( empty( $name ) ) {
				$name = $user->display_name;
				if ( empty( $name ) ) {
					$name = $slug;
				}
			}
			$term = get_term_by( 'slug', $old_user_data->user_nicename, self::TAXONOMY );
			if ( $term ) {
				$results = wp_update_term(
					$term->term_id, self::TAXONOMY, [
						'name' => $name,
						'slug' => $slug,
					]
				);
				update_term_meta( $results['term_id'], 'contributor_first_name', $user->first_name );
				update_term_meta( $results['term_id'], 'contributor_last_name', $user->last_name );
			} else {
				$results = wp_insert_term(
					$name, self::TAXONOMY, [
						'slug' => $slug,
					]
				);
				add_term_meta( $results['term_id'], 'contributor_first_name', $user->first_name, true );
				add_term_meta( $results['term_id'], 'contributor_last_name', $user->last_name, true );
			}
			if ( is_array( $results ) ) {
				return $results;
			}
		}
		return false;
	}

	/**
	 * Get personal name based on available data
	 *
	 * Returns empty string is we can't find anything useful
	 *
	 * A personal name is the set of names by which an individual is known and that can be recited as a word-group,
	 * with the understanding that, taken together, they all relate to that one individual. In many cultures, the
	 * term is synonymous with the birth name or legal name of the individual.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function personalName( $slug ) {
		init_book_data_models();
		$name = '';
		$term = get_term_by( 'slug', $slug, self::TAXONOMY );
		if ( $term ) {
			$prefix = get_term_meta( $term->term_id, 'contributor_prefix', true );
			$suffix = get_term_meta( $term->term_id, 'contributor_suffix', true );
			$first_name = get_term_meta( $term->term_id, 'contributor_first_name', true );
			$last_name = get_term_meta( $term->term_id, 'contributor_last_name', true );
			if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
				$name = $prefix ? "{$prefix} {$first_name} {$last_name}" : "{$first_name} {$last_name}";
				if ( ! empty( $suffix ) ) {
					$does_contains_roman_number = false;
					$roman_numbers_suffix = [ 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII' ];
					foreach ( $roman_numbers_suffix as $roman_number ) {
						if ( strpos( $suffix, $roman_number ) !== false ) {
							$does_contains_roman_number = true;
							break;
						}
					}
					$suffix = $does_contains_roman_number ? " $suffix" : ", $suffix";
				} else {
					$suffix = '';
				}
				$name = $suffix ? "${name}${suffix}" : $name;
			} elseif ( ! empty( $term->name ) ) {
				$name = $term->name;
			}
		}
		return $name;
	}

	/**
	 * @param string $old_slug
	 *
	 * @return string
	 */
	public function maybeUpgradeSlug( $old_slug ) {
		$map = [
			'pb_author' => 'pb_authors',
			'pb_section_author' => 'pb_authors',
			'pb_contributing_authors' => 'pb_contributors',
			'pb_author_file_as' => 'pb_authors',
			'pb_editor' => 'pb_editors',
			'pb_translator' => 'pb_translators',
		];
		if ( isset( $map[ $old_slug ] ) ) {
			return $map[ $old_slug ];
		}
		return $old_slug;
	}

	/**
	 * @param string $old_slug
	 * @param string|array $names
	 * @param int $post_id
	 *
	 * @return array|false An array containing `term_id` and `term_taxonomy_id`, false otherwise.
	 */
	public function convert( $old_slug, $names, $post_id ) {

		$new_slug = $this->maybeUpgradeSlug( $old_slug );
		if ( $new_slug === $old_slug ) {
			return false; // Nothing to convert
		}

		if ( ! is_array( $names ) ) {
			$names = [ $names ];
		}

		$result = false;
		foreach ( $names as $contributors ) {
			$values = explode_remove_and( ';', $contributors );
			foreach ( $values as $v ) {
				$result = $this->insert( $v, $post_id, $new_slug );
			}
		}

		return $result;
	}

	/**
	 * @param int $post_id
	 * @param string $contributor_type
	 *
	 * @return array An array containing a set of matching contributor arrays
	 */
	public function getContributorsWithMeta( $post_id, $contributor_type ) {
		if ( ! str_starts_with( $contributor_type, 'pb_' ) ) {
			$contributor_type = 'pb_' . $contributor_type;
		}
		if ( ! $this->isValid( $contributor_type ) ) {
			return [];
		}

		$full_contributors = [];
		$contributors = get_post_meta( $post_id, $contributor_type, false );
		foreach ( $contributors as $key => $contributor ) {
			$term = get_term_by( 'slug', $contributor, self::TAXONOMY );
			if ( $term ) {
				foreach ( self::getContributorFields() as $field => $value ) {
					$full_contributors[ $key ]['name'] = $this->personalName( $contributor );
					$full_contributors[ $key ][ $field ] = get_term_meta( $term->term_id, $field, true );
				}
			}
		}

		return $full_contributors;
	}

	/**
	 * This function returns an array with all the contributors registered in a book ordered by $valid array default ordering.
	 * @return array
	 */
	public function getAllContributors() {

		$meta = new Metadata();
		$meta_post = $meta->getMetaPost();

		$records = [];
		foreach ( $this->valid as $contributor_type ) {
			$contributors = $this->getContributorsWithMeta( $meta_post->ID, $contributor_type );
			$contributors_count = count( $contributors );
			if ( $contributors_count > 0 ) {
				list( ,$title ) = explode( '_', $contributor_type );
				$records[ $contributor_type ]['title'] = Str::ucfirst( $contributors_count > 1 ? $title : Str::singular( $title ) ); // ex. return Author or Authors
				$records[ $contributor_type ]['records'] = $contributors;
			}
		}
		return $records;
	}

	/**
	 * Automatically displays a contributors page if the back-matter content is empty.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function overrideDisplay( $content ) {

		return $this->display(
			$content, function() {

				$blade = Container::get( 'Blade' );

				return $blade->render(
					'posttypes/contributors', [
						'contributors' => $this->getAllContributors(),
						'exporting' => $this->exporting,
					]
				);

			}, 'contributors'
		);

	}

	public static function changeContributorName( \WP_Roles $roles ) {
		$roles->roles['contributor']['name'] = 'Collaborator';
		$roles->role_names['contributor'] = 'Collaborator';
	}
}
