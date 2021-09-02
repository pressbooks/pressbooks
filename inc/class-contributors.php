<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Metadata\init_book_data_models;
use function Pressbooks\Utility\oxford_comma_explode;
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
	 *
	 * @return array
	 */
	public function getAll( $post_id, $as_strings = true ) {
		$contributors = [];
		foreach ( $this->valid as $contributor_type ) {
			if ( $as_strings ) {
				$contributors[ $contributor_type ] = $this->get( $post_id, $contributor_type );
			} else {
				$contributors[ $contributor_type ] = $this->getArray( $post_id, $contributor_type );
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
		return \Pressbooks\Utility\oxford_comma( $contributors );
	}

	/**
	 * Retrieve author/editor/etc lists for a given Post ID and Contributor type, returns array
	 *
	 * @param int $post_id
	 * @param string $contributor_type
	 *
	 * @return array
	 */
	public function getArray( $post_id, $contributor_type ) {
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
				$name = $this->personalName( $slug );
				if ( $name ) {
					$contributors[] = $name;
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
	 * @param string $full_name
	 * @param int $post_id (optional)
	 * @param string $contributor_type (optional)
	 *
	 * @return array|false An array containing the `term_id` and `term_taxonomy_id`, false otherwise.
	 */
	public function insert( $full_name, $post_id = 0, $contributor_type = 'pb_authors' ) {
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
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s AND meta_value = %s", $post_id, $contributor_type, $term->slug ) ) ) {
					return true;
				} else {
					return is_int( add_post_meta( $post_id, $contributor_type, $term->slug ) );
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
	 * Returns the form title and the hint for the file input.
	 *
	 * @return array
	 */
	public function getFormMessages() {
		$guide_chapter = esc_url( 'https://networkmanagerguide.pressbooks.com/' );
		$hint = __( '<p>Import multiple contributors at once by uploading a valid CSV file. See <a href="%s" target="_blank">our guide</a> for details.</p>', 'pressbooks' );

		return [
			'title' => __( 'Import Contributors', 'pressbooks' ),
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
				$suffix = ! empty( $suffix ) ? ( preg_match( '/^[MDCLXVI]+$/', $suffix ) ? " $suffix" : ", $suffix" ) : '';
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
			$values = oxford_comma_explode( $contributors );
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

				return $blade->render( 'posttypes/contributors', [ 'contributors' => $this->getAllContributors() ] );

			}, 'contributors'
		);

	}
}
