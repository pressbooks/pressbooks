<?php
/**
 * Handles cloning content via the Pressbooks REST API v2.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Image\attachment_id_from_url;
use function Pressbooks\Image\default_cover_url;
use function Pressbooks\Image\strip_baseurl;
use function Pressbooks\Metadata\schema_to_book_information;
use function Pressbooks\Metadata\schema_to_section_information;
use function Pressbooks\Utility\getset;
use function Pressbooks\Utility\oxford_comma_explode;
use function Pressbooks\Utility\str_ends_with;
use function Pressbooks\Utility\str_lreplace;
use function Pressbooks\Utility\str_remove_prefix;
use function Pressbooks\Utility\str_starts_with;

use Masterminds\HTML5;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;

class Cloner {

	/**
	 * @var Interactive\Content
	 */
	protected $interactiveContent;

	/**
	 * @var bool
	 */
	protected $isSuperAdmin = false;

	/**
	 * The URL of the source book.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	protected $sourceBookUrl;

	/**
	 * The ID of the source book, or 0 if the source book is not part of this network.
	 *
	 * @since 4.1.0
	 *
	 * @var int
	 */
	protected $sourceBookId;

	/**
	 * The structure and contents of the source book as returned by the Pressbooks REST API v2.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceBookStructure;

	/**
	 * The front matter, chapter and back matter taxonomy terms of the source book as returned by the Pressbooks REST API v2.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceBookTerms;

	/**
	 * The metadata of the source book as returned by the Pressbooks REST API v2.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceBookMetadata;

	/**
	 * The URL of the target book.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	protected $targetBookUrl;

	/**
	 * The ID of the target book.
	 *
	 * @since 4.1.0
	 *
	 * @var int
	 */
	protected $targetBookId;

	/**
	 * Mapping of term IDs from source to target book.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $termMap = [];

	/**
	 * An array with the quantity of items to be cloned.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $itemsToClone = [
		'terms' => 0,
		'front-matter' => 0,
		'back-matter' => 0,
		'parts' => 0,
		'chapters' => 0,
	];
	/**
	 * An array of cloned items.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $clonedItems;

	/**
	 * The REST API base.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	protected $restBase = 'wp-json';

	/**
	 * The Request args for wp_remote_get() and wp_remote_post().
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $requestArgs = [
		'timeout' => 30,
	];

	/**
	 * @var array
	 */
	protected $targetBookTerms = [];

	/**
	 * Array of known images, format: [ 2017/08/foo-bar-300x225.png ] => [ Fullsize URL ], ...
	 *
	 * @var array
	 */
	protected $knownImages = [];

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 *
	 * @param string $source_url The public URL of the source book.
	 * @param string $target_url The public URL of the target book.
	 */
	public function __construct( $source_url, $target_url = '' ) {
		// Disable SSL verification for development
		if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
			$this->requestArgs['sslverify'] = false;
		}

		// Has_cap acts weird when we create a new blog. Figure out who we are before starting.
		$this->isSuperAdmin = current_user_can( 'manage_network_options' );

		// Set up $this->sourceBookUrl
		$this->sourceBookUrl = esc_url( untrailingslashit( $source_url ) );

		// Set up $this->sourceBookId
		$this->sourceBookId = $this->getBookId( $this->sourceBookUrl );

		// Set up $this->targetBookUrl and $this->targetBookId if set
		if ( $target_url ) {
			$this->targetBookUrl = esc_url( untrailingslashit( $target_url ) );
			$this->targetBookId = $this->getBookId( $target_url );
		}

		// Include media utilities
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$this->interactiveContent = \Pressbooks\Interactive\Content::init();
	}

	/**
	 * @return string
	 */
	public function getSourceBookUrl() {
		return $this->sourceBookUrl;
	}

	/**
	 * @return int
	 */
	public function getSourceBookId() {
		return $this->sourceBookId;
	}

	/**
	 * @return array
	 */
	public function getSourceBookStructure() {
		return $this->sourceBookStructure;
	}

	/**
	 * @return array
	 */
	public function getSourceBookTerms() {
		return $this->sourceBookTerms;
	}

	/**
	 * @return array
	 */
	public function getSourceBookMetadata() {
		return $this->sourceBookMetadata;
	}

	/**
	 * @return array
	 */
	public function getClonedItems() {
		return $this->clonedItems;
	}

	/**
	 * Clone a book in its entirety.
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function cloneBook() {

		if ( ! $this->setupSource() ) {
			return false;
		}

		// Create Book
		$this->targetBookId = $this->createBook();
		$this->targetBookUrl = get_blogaddress_by_id( $this->targetBookId );

		switch_to_blog( $this->targetBookId );
		wp_defer_term_counting( true );

		// Clone Metadata
		$this->clonedItems['metadata'] = $this->cloneMetadata();

		// Clone Taxonomy Terms
		$this->targetBookTerms = $this->getBookTerms( $this->targetBookUrl );
		foreach ( $this->sourceBookTerms as $term ) {
			$this->itemsToClone['terms']++;
			$new_term = $this->cloneTerm( $term['id'] );
			if ( $new_term ) {
				$this->termMap[ $term['id'] ] = $new_term;
				$this->clonedItems['terms'][] = $new_term;
			}
		}

		// Clone Front Matter
		foreach ( $this->sourceBookStructure['front-matter'] as $frontmatter ) {
			$new_frontmatter = $this->cloneFrontMatter( $frontmatter['id'] );
			if ( $new_frontmatter !== false ) {
				$this->itemsToClone['front-matter']++;
				$this->clonedItems['front-matter'][] = $new_frontmatter;
			}
		}

		// Clone Parts
		foreach ( $this->sourceBookStructure['parts'] as $key => $part ) {
			$new_part = $this->clonePart( $part['id'] );
			if ( $new_part !== false ) {
				$this->itemsToClone['parts']++;
				$this->clonedItems['parts'][] = $new_part;
				// Clone Chapters
				foreach ( $this->sourceBookStructure['parts'][ $key ]['chapters'] as $chapter ) {
					$new_chapter = $this->cloneChapter( $chapter['id'], $new_part );
					if ( $new_chapter !== false ) {
						$this->itemsToClone['chapters']++;
						$this->clonedItems['chapters'][] = $new_chapter;
					}
				}
			}
		}

		// Clone Back Matter
		foreach ( $this->sourceBookStructure['back-matter'] as $backmatter ) {
			$new_backmatter = $this->cloneBackMatter( $backmatter['id'] );
			if ( $new_backmatter !== false ) {
				$this->itemsToClone['back-matter']++;
				$this->clonedItems['back-matter'][] = $new_backmatter;
			}
		}

		wp_defer_term_counting( false ); // Flush
		restore_current_blog();

		return true;
	}

	/**
	 * @since 5.0.0
	 *
	 * @param bool $respect_book_license
	 *
	 * @return bool
	 */
	public function setupSource( $respect_book_license = true ) {
		if ( ! empty( $this->sourceBookId ) ) {
			// Local book
			switch_to_blog( $this->sourceBookId );
		} elseif ( ! $this->isCompatible( $this->sourceBookUrl ) ) {
			// Remote is not compatible, bail.
			$_SESSION['pb_errors'][] = __( 'You can only clone from a book hosted by Pressbooks 4.1 or later. Please ensure that your source book meets these requirements.', 'pressbooks' );
			return false;
		}

		// Set up $this->sourceBookMetadata
		$this->sourceBookMetadata = $this->getBookMetadata( $this->sourceBookUrl );
		if ( empty( $this->sourceBookMetadata ) ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve metadata from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookUrl ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}

		if ( $respect_book_license ) {
			// Verify license or network administrator override
			if ( ! $this->isSourceCloneable( $this->sourceBookMetadata['license'] ) ) {
				$_SESSION['pb_errors'][] = sprintf( __( '%s is not licensed for cloning.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
				$this->maybeRestoreCurrentBlog();
				return false;
			}
		}

		// Set up $this->sourceBookStructure
		$this->sourceBookStructure = $this->getBookStructure( $this->sourceBookUrl );
		if ( empty( $this->sourceBookStructure ) ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve contents and structure from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}

		// Set up $this->sourceBookTerms
		$this->sourceBookTerms = $this->getBookTerms( $this->sourceBookUrl );
		if ( empty( $this->sourceBookTerms ) ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve taxonomies from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}

		$this->knownImages = $this->buildlistOfKnownImages( $this->sourceBookUrl );
		if ( $this->knownImages === false ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve media from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}

		$this->maybeRestoreCurrentBlog();
		return true;
	}

	/**
	 * Clone term from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $term_id The ID of the term within the source book.
	 * @return bool | int False if creating a new term failed; the ID of the new term if it the clone succeeded or the ID of a matching term if it exists.
	 */
	public function cloneTerm( $term_id ) {
		// Retrieve term
		foreach ( $this->sourceBookTerms as $k => $v ) {
			if ( $v['id'] === absint( $term_id ) ) {
				$term = $this->sourceBookTerms[ $k ];
				break;
			}
		};

		if ( empty( $term['slug'] ) || empty( $term['taxonomy'] ) ) {
			// Doing it wrong...
			return false;
		}

		// Check for matching term
		foreach ( $this->targetBookTerms as $k => $v ) {
			if ( $v['slug'] === $term['slug'] && $v['taxonomy'] === $term['taxonomy'] ) {
				return $v['id'];
			}
		};

		// Set endpoint
		$endpoint = $term['taxonomy'];

		// _links key needs to be removed, pop it out into an ignored variable
		$_links = array_pop( $term );

		// Remove source-specific properties
		$bad_keys = [ 'id', 'count', 'link', 'parent', 'taxonomy' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $term[ $bad_key ] );
		}

		// POST internal request
		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $term );
		$response = rest_do_request( $request )->get_data();

		// Inform user of failure, bail
		if ( is_wp_error( $response ) || @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			return false;
		} else {
			return $response['id'];
		}
	}

	/**
	 * Clone front matter from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the front matter within the source book.
	 * @return bool | int False if the clone failed; the ID of the new front matter if it succeeded.
	 */
	public function cloneFrontMatter( $id ) {
		return $this->cloneSection( $id, 'front-matter' );
	}

	/**
	 * Clone a part from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the part within the source book.
	 * @return bool | int False if the clone failed; the ID of the new part if it succeeded.
	 */
	public function clonePart( $id ) {
		return $this->cloneSection( $id, 'part' );
	}

	/**
	 * Clone a chapter from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the chapter within the source book.
	 * @param int $part_id The ID of the part to which the chapter should be added within the target book.
	 * @return bool | int False if the clone failed; the ID of the new chapter if it succeeded.
	 */
	public function cloneChapter( $id, $part_id ) {
		return $this->cloneSection( $id, 'chapter', $part_id );
	}

	/**
	 * Clone back matter from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the back matter within the source book.
	 * @return bool | int False if the clone failed; the ID of the new back matter if it succeeded.
	 */
	public function cloneBackMatter( $id ) {
		return $this->cloneSection( $id, 'back-matter' );
	}

	/**
	 * Use media endpoint to build an array of known images
	 *
	 * @param string $url The URL of the book.
	 *
	 * @return bool | array False if the operation failed; known images array if succeeded.
	 */
	public function buildListOfKnownImages( $url ) {
		// Handle request (local or global)
		$params = [
			'media_type' => 'image',
			'per_page' => 100,
		];
		$response = $this->handleGetRequest( $url, 'wp/v2', 'media', $params );

		// Handle errors
		if ( is_wp_error( $response ) ) {
			$_SESSION['pb_errors'][] = sprintf(
				'<p>%1$s</p><p>%2$s</p>',
				__( 'The source book&rsquo;s media could not be read.', 'pressbooks' ),
				$response->get_error_message()
			);
			return false;
		}

		$known_images = [];
		foreach ( $response as $item ) {
			$fullsize = $item['source_url'];
			foreach ( $item['media_details']['sizes'] as $size => $info ) {
				$attached_file = strip_baseurl( $info['source_url'] ); // 2017/08/foo-bar-300x225.png
				$known_images[ $attached_file ] = $fullsize;
			}
		}

		return $known_images;
	}

	/**
	 * Fetch an array containing the metadata of a book.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL of the book.
	 * @return bool | array False if the operation failed; the metadata array if it succeeded.
	 */
	public function getBookMetadata( $url ) {
		// Handle request (local or global)
		$response = $this->handleGetRequest( $url, 'pressbooks/v2', 'metadata' );

		// Handle errors
		if ( is_wp_error( $response ) ) {
			$_SESSION['pb_errors'][] = sprintf(
				'<p>%1$s</p><p>%2$s</p>',
				__( 'The source book&rsquo;s metadata could not be read.', 'pressbooks' ),
				$response->get_error_message()
			);
			return false;
		}

		// Return successful response
		return $response;
	}

	/**
	 * Fetch an array containing the structure and contents of a book.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL of the book.
	 * @return bool | array False if the operation failed; the structure and contents array if it succeeded.
	 */
	public function getBookStructure( $url ) {
		// Handle request (local or global)
		$response = $this->handleGetRequest(
			$url, 'pressbooks/v2', 'toc', [
				'_embed' => 1,
			]
		);

		// Handle errors
		if ( is_wp_error( $response ) ) {
			$_SESSION['pb_errors'][] = sprintf(
				'<p>%1$s</p><p>%2$s</p>',
				__( 'The source book&rsquo;s structure and contents could not be read.', 'pressbooks' ),
				$response->get_error_message()
			);
			return false;
		}

		// Return successful response
		return $response;
	}

	/**
	 * Fetch an array containing the terms of a book.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL of the book.
	 * @return array
	 */
	public function getBookTerms( $url ) {
		$terms = [];

		foreach ( [ 'front-matter-type', 'chapter-type', 'back-matter-type' ] as $taxonomy ) {
			// Handle request (local or global)
			$response = $this->handleGetRequest(
				$url, 'pressbooks/v2', "$taxonomy", [
					'per_page' => 25,
				]
			);

			// Bail on error
			if ( is_wp_error( $response ) ) {
				$_SESSION['pb_errors'][] = sprintf(
					'<p>%1$s</p><p>%2$s</p>',
					__( 'The source book&rsquo;s taxonomies could not be read.', 'pressbooks' ),
					$response->get_error_message()
				);
				return [];
			}

			// Remove links
			unset( $response['_links'] );

			// Process response
			$terms = array_merge( $terms, $response );
		}

		return $terms;
	}

	/**
	 * Is the source book cloneable?
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $metadata_license
	 *
	 * @return bool Whether or not the book is public and licensed for cloning (or true if the current user is a network administrator and the book is in the current network).
	 */
	public function isSourceCloneable( $metadata_license ) {
		$restrictive_licenses = [
			'https://creativecommons.org/licenses/by-nd/4.0/',
			'https://creativecommons.org/licenses/by-nc-nd/4.0/',
			'https://choosealicense.com/no-license/',
		];

		if ( is_array( $metadata_license ) ) {
			$license_url = $metadata_license['url'];
		} else { // Backwards compatibility.
			$license_url = $metadata_license;
		}

		$license_url = trailingslashit( trim( $license_url ) );
		if ( ! empty( $this->sourceBookId ) ) {
			if ( $this->isSuperAdmin ) {
				return true; // Network administrators can clone local books no matter how they're licensed
			} elseif ( ! in_array( $license_url, $restrictive_licenses, true ) ) {
				return true; // Anyone can clone local books that aren't restrictively licensed
			} else {
				return false;
			}
		} elseif ( in_array( $license_url, $restrictive_licenses, true ) ) {
			return false; // No one can clone global books that are restrictively licensed
		}
		return true;
	}

	/**
	 * Maybe restore current blog
	 */
	protected function maybeRestoreCurrentBlog() {
		if ( ! empty( $this->sourceBookId ) ) {
			restore_current_blog();
		}
	}

	/**
	 * Create target book if it doesn't already exist.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | int False if the creation failed; the ID of the new book if it succeeded.
	 */
	protected function createBook() {
		$host = wp_parse_url( network_home_url(), PHP_URL_HOST );
		if ( is_subdomain_install() ) {
			$domain = $this->getSubdomainOrSubDirectory( $this->targetBookUrl ) . '.' . $host;
			$path = '/';
		} else {
			$domain = $host;
			$path = '/' . $this->getSubdomainOrSubDirectory( $this->targetBookUrl );
		}

		$title = $this->sourceBookMetadata['name'];
		$user_id = get_current_user_id();
		// Disable automatic redirect to new book dashboard
		add_filter(
			'pb_redirect_to_new_book', function () {
				return false;
			}
		);
		// Remove default content so that the book only contains the results of the clone operation
		add_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		$result = wpmu_create_blog( $domain, $path, $title, $user_id );
		remove_all_filters( 'pb_redirect_to_new_book' );
		remove_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		return false;
	}

	/**
	 * Clone book information to the target book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | int False if the creation failed; the ID of the new book's book information post if it succeeded.
	 */
	protected function cloneMetadata() {
		$metadata_post_id = ( new Metadata )->getMetaPost()->ID;

		if ( ! $metadata_post_id ) {
			return false;
		}

		$book_information = schema_to_book_information( $this->sourceBookMetadata );

		// Cover image
		if ( ! \Pressbooks\Image\is_default_cover( $book_information['pb_cover_image'] ) ) {
			$new_cover_id = $this->fetchAndSaveUniqueImage( $book_information['pb_cover_image'] );
			if ( $new_cover_id ) {
				$book_information['pb_cover_image'] = wp_get_attachment_url( $new_cover_id );
			} else {
				$book_information['pb_cover_image'] = default_cover_url();
			}
		} else {
			$book_information['pb_cover_image'] = default_cover_url();
		}

		// Everything else
		$book_information['pb_is_based_on'] = $this->sourceBookUrl;
		$metadata_array_values = [ 'pb_keywords_tags', 'pb_bisac_subject', 'pb_additional_subjects' ];
		$contributors = new Contributors();
		foreach ( $book_information as $key => $value ) {
			if ( $contributors->isValid( $key ) ) {
				$values = oxford_comma_explode( $value );
				foreach ( $values as $v ) {
					$contributors->insert( $v, $metadata_post_id, $key );
				}
			} elseif ( in_array( $key, $metadata_array_values, true ) ) {
				$values = explode( ', ', $value );
				foreach ( $values as $v ) {
					add_post_meta( $metadata_post_id, $key, $v );
				}
			} else {
				update_post_meta( $metadata_post_id, $key, $value );
				if ( $key === 'pb_book_license' ) {
					wp_set_object_terms( $metadata_post_id, $value, Licensing::TAXONOMY ); // Link
				}
			}
		}

		// Remove the current user from the author field in Book Info
		$user_data = get_userdata( get_current_user_id() );
		$contributors->unlink( $user_data->user_nicename, $metadata_post_id );

		return $metadata_post_id;
	}

	/**
	 * Clone a section (front matter, part, chapter, back matter) of a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section.
	 * @param int $parent_id The ID of the part to which the chapter should be added (only required for chapters) within the target book.
	 * @return bool | int False if the clone failed; the ID of the new section if it succeeded.
	 */
	protected function cloneSection( $section_id, $post_type, $parent_id = null ) {

		// Is the section license OK?
		// The global license is for the 'collection' and within that collection you have stuff with licenses that differ from the global one...
		$metadata = $this->retrieveSectionMetadata( $section_id, $post_type );
		$is_source_clonable = $this->isSourceCloneable( $metadata['license'] ?? $this->sourceBookMetadata['license'] );
		if ( ! $is_source_clonable ) {
			return false;
		}

		// Locate section
		foreach ( $this->sourceBookStructure['_embedded'][ $post_type ] as $k => $v ) {
			if ( $v['id'] === absint( $section_id ) ) {
				$section = $this->sourceBookStructure['_embedded'][ $post_type ][ $k ];
				break;
			}
		};

		// _links key needs to be removed, pop it out into an ignored variable
		$_links = array_pop( $section );

		// Get permalink
		$permalink = $section['link'];

		// Remove source-specific properties
		$bad_keys = [ 'author', 'id', 'link' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $section[ $bad_key ] );
		}

		// Set status
		$section['status'] = 'publish';

		// Set title and content
		list( $content, $attachments ) = $this->retrieveSectionContent( $section );
		$section['title'] = $section['title']['rendered'];
		$section['content'] = $content;

		// Set part
		if ( $post_type === 'chapter' ) {
			$section['part'] = $parent_id;
		}

		// Set mapped term ID
		if ( $post_type !== 'part' ) {
			if ( isset( $section[ "$post_type-type" ] ) ) {
				foreach ( $section[ "$post_type-type" ] as $key => $term_id ) {
					if ( isset( $this->termMap[ $term_id ] ) ) {
						// Use map
						$section[ "$post_type-type" ][ $key ] = $this->termMap[ $term_id ];
					} elseif ( empty( $this->targetBookUrl ) ) {
						// Try to match an existing term
						foreach ( $this->sourceBookTerms as $source_term ) {
							if ( $source_term['id'] === $term_id ) {
								$term = get_term_by( 'slug', $source_term['slug'], $source_term['taxonomy'] );
								if ( $term ) {
									$section[ "$post_type-type" ][ $key ] = $term->term_id;
								}
								break;
							}
						}
					}
				}
			}
		}

		// Determine endpoint based on $post_type
		$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;

		// Remove items handled by cloneSectionMetadata()
		unset( $section['meta']['pb_authors'], $section['meta']['pb_section_license'] );

		// POST internal request
		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $section );
		$response = rest_do_request( $request )->get_data();

		// Inform user of failure, bail
		if ( is_wp_error( $response ) || @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			return false;
		}

		// Set pb_is_based_on property
		update_post_meta( $response['id'], 'pb_is_based_on', $permalink );

		// Clone associated content
		if ( $post_type !== 'part' ) {
			$this->cloneSectionMetadata( $section_id, $post_type, $response['id'] );
		}

		foreach ( $attachments as $attachment ) {
			wp_update_post(
				[
					'ID' => $attachment,
					'post_parent' => $response['id'],
				]
			);
		}

		return $response['id'];
	}

	/**
	 * @param array $section
	 *
	 * @return array{content: string, attachments: array}
	 */
	protected function retrieveSectionContent( $section ) {
		if ( ! empty( $section['content']['raw'] ) ) {
			// Wrap in fake div tags so that we can parse it
			$source_content = '<div><!-- pb_fixme -->' . $section['content']['raw'] . '<!-- pb_fixme --></div>';
		} else {
			$source_content = $section['content']['rendered'];
		}

		// According to the html5 spec section 8.3: https://www.w3.org/TR/2013/CR-html5-20130806/syntax.html#serializing-html-fragments
		// We should replace any occurrences of the U+00A0 NO-BREAK SPACE character (aka "\xc2\xa0") by the string "&nbsp;" when serializing HTML5
		// When cloning, we don't want to modify whitespaces, so we hide them from the parser.
		$characters_to_keep = [ "\xc2\xa0" ];
		foreach ( $characters_to_keep as $c ) {
			$md5 = md5( $c );
			$source_content = str_replace( $c, "<!-- pb_fixme_{$md5} -->", $source_content );
		}

		// Load source content
		$html5 = new HTML5();
		$dom = $html5->loadHTML( $source_content );

		// Download images, change image paths
		$media = $this->scrapeAndKneadImages( $dom );
		$dom = $media['dom'];
		$attachments = $media['attachments'];

		// Fix internal links
		$dom = $this->fixInternalLinks( $dom );

		// Save the destination content
		$content = $html5->saveHTML( $dom );

		// Put back the hidden characters
		foreach ( $characters_to_keep as $c ) {
			$md5 = md5( $c );
			$content = str_replace( "<!-- pb_fixme_{$md5} -->", $c, $content );
		}

		$content = \Pressbooks\Sanitize\strip_container_tags( $content ); // Remove auto-created <html> <body> and <!DOCTYPE> tags.
		if ( ! empty( $section['content']['raw'] ) ) {
			$content = str_replace( [ '<div><!-- pb_fixme -->', '<!-- pb_fixme --></div>' ], '', $content ); // Remove fake div tags
			if ( ! $this->interactiveContent->isCloneable( $content ) ) {
				$content = $this->interactiveContent->replaceCloneable( $content );
			}
		}

		return [ trim( $content ), $attachments ];
	}

	/**
	 * Retrieve metadata
	 *
	 * @since 5.0.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section.
	 *
	 * @return array
	 */
	public function retrieveSectionMetadata( $section_id, $post_type ) {
		if ( in_array( $post_type, [ 'front-matter', 'back-matter' ], true ) ) {
			foreach ( $this->sourceBookStructure[ $post_type ] as $k => $v ) {
				if ( $v['id'] === absint( $section_id ) ) {
					return $v['metadata'];
				}
			}
		} elseif ( $post_type === 'chapter' ) {
			foreach ( $this->sourceBookStructure['parts'] as $key => $part ) {
				foreach ( $part['chapters'] as $k => $v ) {
					if ( $v['id'] === absint( $section_id ) ) {
						return $v['metadata'];
					}
				}
			}
		}
		return []; // Nothing was found
	}

	/**
	 * Clone metadata of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section.
	 * @param int $target_id The ID of the section within the target book.
	 * @return bool False if the clone failed; true if it succeeded.
	 */
	protected function cloneSectionMetadata( $section_id, $post_type, $target_id ) {

		$book_schema = $this->sourceBookMetadata;
		if ( empty( $this->targetBookUrl ) ) {
			// If there's no target then that means this data is going into the current book.
			// Remove invalid $book_schema values so that $section_schema is used instead.
			$book_schema['author'] = [];
			$book_schema['license'] = '';
		}

		$section_information = schema_to_section_information(
			$this->retrieveSectionMetadata( $section_id, $post_type ),
			$book_schema
		);

		$contributors = new Contributors();
		foreach ( $section_information as $key => $value ) {
			if ( $contributors->isValid( $key ) ) {
				$values = oxford_comma_explode( $value );
				foreach ( $values as $v ) {
					$contributors->insert( $v, $target_id, $key );
				}
			} else {
				update_post_meta( $target_id, $key, $value );
				if ( $key === 'pb_section_license' ) {
					wp_set_object_terms( $target_id, $value, Licensing::TAXONOMY ); // Link
				}
			}
		}

		return true;
	}

	/**
	 * Handle a get request against the REST API using either rest_do_request() or wp_remote_get() as appropriate.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL against which the request should be made (not including the REST base)
	 * @param string $namespace The namespace for the request, e.g. 'pressbooks/v2'
	 * @param string $endpoint The endpoint for the request, e.g. 'toc'
	 * @param array $params URL parameters
	 *
	 * @return array|\WP_Error
	 */
	protected function handleGetRequest( $url, $namespace, $endpoint, $params = [] ) {
		global $blog_id;

		// Is the book local? If so, is it the current book? If not, switch to it.
		$local_book = $this->getBookId( $url );
		if ( $local_book ) {
			$switch = ( $local_book !== $blog_id ) ? true : false;
			if ( $switch ) {
				switch_to_blog( $local_book );
			}

			// Set up WP_REST_Request, retrieve response.
			$_GET['_embed'] = 1;
			$request = new \WP_REST_Request( 'GET', "/$namespace/$endpoint" );
			if ( ! empty( $params ) ) {
				$request->set_query_params( $params );
			}
			$response = rest_do_request( $request );

			// TODO: WordPress shows only 10-100 results. We need to paginate on $response->headers['Link']
			// Format: <http://pressbooks.dev/pdfimages/wp-json/wp/v2/media?media_type=image&page=2>; rel="next"

			if ( $switch ) {
				restore_current_blog();
			}

			// Handle errors
			if ( is_wp_error( $response ) ) {
				return $response;
			} else {
				return rest_get_server()->response_to_data( $response, true );
			}
		} else {
			// Build request URL
			$request_url = sprintf(
				'%1$s/%2$s/%3$s/%4$s',
				$this->sourceBookUrl,
				$this->restBase,
				$namespace,
				$endpoint
			);

			// Add params
			if ( ! empty( $params ) ) {
				$request_url .= '?' . build_query( $params );
			}

			// GET response from API
			$response = wp_remote_get( $request_url, $this->requestArgs );

			// Handle errors
			if ( is_wp_error( $response ) ) {
				return $response;
			} elseif ( isset( $response['response']['code'] ) && $response['response']['code'] >= 400 ) {
				return new \WP_Error( $response['response']['code'], $response['response']['message'] );
			} else {
				return json_decode( $response['body'], true );
			}
		}
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @since 4.1.0
	 *
	 * @param \DOMDocument $dom
	 *
	 * @return array An array containing the \DOMDocument and the IDs of created attachments
	 */
	protected function scrapeAndKneadImages( \DOMDocument $dom ) {

		$images = $dom->getElementsByTagName( 'img' );

		$attachments = [];

		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			// Fetch image, change src
			$src_old = $image->getAttribute( 'src' );

			$attachment_id = $this->fetchAndSaveUniqueImage( $src_old );

			if ( $attachment_id ) {
				$image->setAttribute( 'src', $this->replaceImage( $attachment_id, $src_old, $image ) );
				$attachments[] = $attachment_id;
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$src_old}#fixme" );
			}
		}

		return [
			'dom' => $dom,
			'attachments' => $attachments,
		];
	}

	/**
	 * Load remote url of image into WP using media_handle_sideload()
	 * Will return 0 if something went wrong.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @see media_handle_sideload
	 *
	 * @return int attachment ID or 0 if import failed
	 */
	protected function fetchAndSaveUniqueImage( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}

		$filename = $this->basename( $url );
		$attached_file = strip_baseurl( $url );

		if ( $this->sameAsSource( $url ) && isset( $this->knownImages[ $attached_file ] ) ) {
			$remote_img_location = $this->knownImages[ $attached_file ];
			$filename = basename( $this->knownImages[ $attached_file ] );
		} else {
			$remote_img_location = $url;
		}

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $remote_img_location ] ) ) {
			return $already_done[ $remote_img_location ];
		}

		/* Process */

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[ $remote_img_location ] = 0;
			return 0;
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$already_done[ $remote_img_location ] = 0;
			return 0;
		}

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = \Pressbooks\Image\proper_image_extension( $tmp_name, $filename );
				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, don't import
				$already_done[ $remote_img_location ] = 0;
				@unlink( $tmp_name ); // @codingStandardsIgnoreLine
				return 0;
			}
		}

		$pid = media_handle_sideload(
			[
				'name' => $filename,
				'tmp_name' => $tmp_name,
			], 0
		);
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) {
			$pid = 0;
		} else {
			$this->clonedItems['media'][] = $pid;
			$already_done[ $remote_img_location ] = $pid;
		}
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $pid;
	}

	/**
	 * @param int $attachment_id
	 * @param string $src_old
	 * @param \DOMElement $image
	 *
	 * @return string
	 */
	protected function replaceImage( $attachment_id, $src_old, $image ) {

		$src_new = wp_get_attachment_url( $attachment_id );

		if ( $this->sameAsSource( $src_old ) && isset( $this->knownImages[ strip_baseurl( $src_old ) ] ) ) {
			$basename_old = $this->basename( $src_old );
			$basename_new = $this->basename( $src_new );
			$maybe_src_new = str_lreplace( $basename_new, $basename_old, $src_new );
			if ( $attachment_id === attachment_id_from_url( $maybe_src_new ) ) {
				// Our best guess is that this is a cloned image, use old filename to preserve WP resizing
				$src_new = $maybe_src_new;
				// Update image class to new id to preserve WP Size dropdown
				if ( $image->hasAttribute( 'class' ) ) {
					$image->setAttribute( 'class', preg_replace( '/wp-image-\d+/', "wp-image-{$attachment_id}", $image->getAttribute( 'class' ) ) );
				}
				// Update wrapper IDs
				if ( $image->parentNode->tagName === 'div' && strpos( $image->parentNode->getAttribute( 'id' ), 'attachment_' ) !== false ) {
					// <div> id
					$image->parentNode->setAttribute( 'id', preg_replace( '/attachment_\d+/', "attachment_{$attachment_id}", $image->parentNode->getAttribute( 'id' ) ) );
				}
				foreach ( $image->parentNode->childNodes as $child ) {
					if ( $child instanceof \DOMText &&
						strpos( $child->nodeValue, '[caption ' ) !== false &&
						strpos( $child->nodeValue, 'attachment_' ) !== false
					) {
						// [caption] id
						$child->nodeValue = preg_replace( '/attachment_\d+/', "attachment_{$attachment_id}", $child->nodeValue );
					}
				}
			}
		}

		// Update srcset URLs
		if ( $image->hasAttribute( 'srcset' ) ) {
			$image->setAttribute( 'srcset', wp_get_attachment_image_srcset( $attachment_id ) );
		}

		return $src_new;
	}

	/**
	 * Get sanitized basename without query string or anchors
	 *
	 * @param $url
	 *
	 * @return array|mixed|string
	 */
	protected function basename( $url ) {
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );
		$filename = explode( '#', $filename )[0]; // Remove trailing anchors
		$filename = sanitize_file_name( urldecode( $filename ) );

		return $filename;
	}

	/**
	 * @param $url
	 *
	 * @return bool
	 */
	protected function sameAsSource( $url ) {
		return \Pressbooks\Utility\urls_have_same_host( $this->sourceBookUrl, $url );
	}

	/**
	 * @param \DOMDocument $dom
	 *
	 * @return \DOMDocument
	 */
	protected function fixInternalLinks( $dom ) {
		// Setup
		$source_path = $this->getSubdomainOrSubdirectory( $this->sourceBookUrl );
		$target_path = $this->getSubdomainOrSubdirectory( $this->targetBookUrl );

		// Get links, loop through
		$links = $dom->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			/** @var \DOMElement $link */
			$href = $link->getAttribute( 'href' );
			if ( is_subdomain_install() && str_starts_with( $href, "/$source_path/" ) ) {
				// Remove book path (cloning from subdirectory to subdomain)
				$href = str_remove_prefix( $href, "/$source_path" );
			} else {
				if ( str_starts_with( $href, "/$source_path/" ) ) {
					// Replace book path (cloning from subdirectory to subdirectory)
					$href = str_replace( "/$source_path/", "/$target_path/", $href );
				}
				foreach ( [ 'front-matter', 'part', 'chapter', 'back-matter' ] as $post_type ) {
					// Add book path (cloning from subdomain to subdirectory)
					if ( str_starts_with( $href, "/$post_type/" ) ) {
						$href = str_replace( "/$post_type/", "/$target_path/$post_type/", $href );
					}
				}
			}
			// Fix absolute URLs
			$href = str_replace( untrailingslashit( $this->sourceBookUrl ), untrailingslashit( $this->targetBookUrl ), $href );

			// Update href attribute with new href
			$link->setAttribute( 'href', $href );
		}

		return $dom;
	}

	/**
	 * Discover WordPress API
	 *
	 * @see https://developer.wordpress.org/rest-api/using-the-rest-api/discovery/
	 *
	 * @param string $url
	 *
	 * @return string|false Returns (corrected) URL on success, false on failure
	 */
	public function discoverWordPressApi( $url ) {

		// Use redirection because our servers redirect when missing a trailing slash
		$response = wp_safe_remote_head(
			$url, [
				'redirection' => 2,
			]
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$headers = wp_remote_retrieve_headers( $response );

		if ( isset( $headers['link'] ) ) {
			if ( ! is_array( $headers['link'] ) ) {
				$headers['link'] = [ $headers['link'] ];
			}
			foreach ( $headers['link'] as $link ) {
				// Parse: <http://example.com/wp-json/>; rel="https://api.w.org/">, <http://example.com/?rest_route=/>; rel="https://api.w.org/"
				if ( strpos( $link, 'rel="https://api.w.org/"' ) !== false || strpos( $link, "rel='https://api.w.org/'" ) !== false ) {
					preg_match( '#\<(.*?)\>.*?//api\.w\.org/#', $link, $matches );
					if ( empty( $matches[1] ) ) {
						return false;
					}
					// Remove REST base
					if ( str_ends_with( $matches[1], "/{$this->restBase}/" ) ) {
						$fixed_url = esc_url( str_lreplace( "/{$this->restBase}/", '', $matches[1] ) ); // Ends with slash
					} elseif ( str_ends_with( $matches[1], "/{$this->restBase}" ) ) {
						$fixed_url = esc_url( str_lreplace( "/{$this->restBase}", '', $matches[1] ) ); // Doesn't end with slash
					} else {
						$fixed_url = esc_url( $matches[1] ); // Could not find rest base, use as is
					}
					return untrailingslashit( $fixed_url );
				}
			}
		}
		return false;
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	public function isCompatible( $url ) {

		$new_url = $this->discoverWordPressApi( $url );
		if ( $new_url ) {
			$url = $new_url;
			$this->sourceBookUrl = $new_url;
		}

		// Check for taxonomies introduced in Pressbooks 4.1
		// We specifically check for 404 Not Found.
		// If we get another kind of error it will be caught later because we want to know what went wrong.
		$response = $this->handleGetRequest(
			$url, 'pressbooks/v2', 'chapter-type', [
				'per_page' => 1,
			]
		);
		if ( is_wp_error( $response ) && in_array( (int) $response->get_error_code(), [ 404 ], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * When creating a new book as the target of a clone operation, this function removes
	 * default front matter, parts, chapters and back matter from the book creation routines.
	 *
	 * @since 4.1.0
	 * @see apply_filters( 'pb_default_book_content', ... )
	 *
	 * @param array $contents The default book contents
	 * @return array The filtered book contents
	 */
	public static function removeDefaultBookContent( $contents ) {
		foreach ( [
			'introduction',
			'main-body',
			'chapter-1',
			'appendix',
		] as $post ) {
			unset( $contents[ $post ] );
		}
		return $contents;
	}

	/**
	 * Get a book ID from its URL.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @return int 0 of no blog was found, or the ID of the matched book.
	 */

	public static function getBookId( $url ) {
		return get_blog_id_from_url(
			wp_parse_url( $url, PHP_URL_HOST ),
			trailingslashit( wp_parse_url( $url, PHP_URL_PATH ) )
		);
	}

	/**
	 * Given a URL, get the subdomain or subdirectory (depending on the type of multisite install).
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function getSubdomainOrSubDirectory( $url ) {
		$url = untrailingslashit( $url );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( $path ) {
			return ltrim( $path, '/\\' );
		} else {
			$host = explode( '.', $host );
			$subdomain = array_shift( $host );
			return $subdomain;
		}
	}

	/**
	 * Given a book name, see if we can use it to create a new book. Sort of like wpmu_validate_blog_signup().
	 *
	 * @since 4.1.0
	 *
	 * @param string $blogname
	 *
	 * @return string|\WP_Error
	 */
	public static function validateNewBookName( $blogname ) {
		global $wpdb, $domain;

		$current_network = get_network();
		$base = $current_network->path;
		$illegal_names = get_site_option( 'illegal_names' );
		$minimum_site_name_length = apply_filters( 'minimum_site_name_length', 4 );

		if ( is_subdomain_install() ) {
			$host = wp_parse_url( esc_url( $domain ), PHP_URL_HOST );
			$host = explode( '.', $host );
			if ( count( $host ) > 2 ) {
				array_shift( $host );
			}
			$baredomain = implode( '.', $host );
			$mydomain = $blogname . '.' . $baredomain;
			$path = $base;
		} else {
			$illegal_names = array_merge( $illegal_names, get_subdirectory_reserved_names() );
			$mydomain = "$domain";
			$path = $base . $blogname . '/';
		}

		if ( preg_match( '/[^a-z0-9]+/', $blogname ) ) {
			return new \WP_Error( 'blogname', __( 'Your book URL can only contain lowercase letters (a-z) and numbers.', 'pressbooks' ) );
		} elseif ( preg_match( '/^[0-9]*$/', $blogname ) ) {
			return new \WP_Error( 'blogname', __( 'Your book URL must contain at least some letters.', 'pressbooks' ) );
		} elseif ( in_array( $blogname, $illegal_names, true ) ) {
			return new \WP_Error( 'blogname', __( 'That book URL is not allowed.', 'pressbooks' ) );
		} elseif ( strlen( $blogname ) < $minimum_site_name_length ) {
			return new \WP_Error( 'blogname', sprintf( _n( 'Your book URL must be at least %s character.', 'Your book URL must be at least %s characters.', $minimum_site_name_length, 'pressbooks' ), number_format_i18n( $minimum_site_name_length ) ) );
		} elseif ( ! is_subdomain_install() && $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM " . $wpdb->get_blog_prefix( $current_network->site_id ) . "posts WHERE post_type = 'page' AND post_name = %s", $blogname ) ) ) { // @codingStandardsIgnoreLine
			return new \WP_Error( 'blogname', __( 'Sorry, you may not use that book URL.', 'pressbooks' ) );
		} elseif ( domain_exists( $mydomain, $path, $current_network->id ) ) {
			return new \WP_Error( 'blogname', __( 'Sorry, that book URL already exists!', 'pressbooks' ) );
		}
		return $mydomain . $path;
	}

	/**
	 * @return bool
	 */
	public static function isEnabled() {
		$enable_cloning = get_site_option( 'pressbooks_sharingandprivacy_options', [] );
		$enable_cloning = isset( $enable_cloning['enable_cloning'] ) ? $enable_cloning['enable_cloning'] : SharingAndPrivacyOptions::getDefaults()['enable_cloning'];
		return (bool) $enable_cloning;
	}

	/**
	 * Check if a user submitted something to options.php?page=pb_cloner
	 *
	 * @return bool
	 */
	public static function isFormSubmission() {

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'pb_cloner' !== $_REQUEST['page'] ) {
			return false;
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle form submission.
	 */
	public static function formSubmit() {
		if ( ! static::isFormSubmission() ) {
			return;
		}

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'pb-cloner' ) ) {
			if ( isset( $_POST['source_book_url'] ) && ! empty( $_POST['source_book_url'] ) && isset( $_POST['target_book_url'] ) && ! empty( $_POST['target_book_url'] ) ) {
				$bookname = \Pressbooks\Cloner::validateNewBookName( $_POST['target_book_url'] );
				if ( is_wp_error( $bookname ) ) {
					$_SESSION['pb_errors'][] = $bookname->get_error_message();
				} else {
					@set_time_limit( 300 ); // @codingStandardsIgnoreLine
					$cloner = new Cloner( esc_url( $_POST['source_book_url'] ), $bookname );
					if ( $cloner->cloneBook() ) {
						$_SESSION['pb_notices'][] = sprintf(
							__( 'Cloning succeeded! Cloned %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s to %7$s.', 'pressbooks' ),
							sprintf( _n( '%s term', '%s terms', count( getset( $cloner->clonedItems, 'terms', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'terms', [] ) ) ),
							sprintf( _n( '%s front matter', '%s front matter', count( getset( $cloner->clonedItems, 'front-matter', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'front-matter', [] ) ) ),
							sprintf( _n( '%s part', '%s parts', count( getset( $cloner->clonedItems, 'parts', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'parts', [] ) ) ),
							sprintf( _n( '%s chapter', '%s chapters', count( getset( $cloner->clonedItems, 'chapters', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'chapters', [] ) ) ),
							sprintf( _n( '%s back matter', '%s back matter', count( getset( $cloner->clonedItems, 'back-matter', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'back-matter', [] ) ) ),
							sprintf( _n( '%s media attachment', '%s media attachments', count( getset( $cloner->clonedItems, 'media', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'media', [] ) ) ),
							sprintf( '<a href="%1$s"><em>%2$s</em></a>', trailingslashit( $cloner->targetBookUrl ) . 'wp-admin/', $cloner->sourceBookMetadata['name'] )
						);
					} elseif ( empty( $_SESSION['pb_errors'] ) ) {
						$_SESSION['pb_errors'][] = __( 'Cloning failed.', 'pressbooks' );
					}
				}
				\Pressbooks\Redirect\location( admin_url( 'options.php?page=pb_cloner' ) );
			} else {
				$_SESSION['pb_errors'][] = __( 'You must enter a valid URL for a book on a Pressbooks network running Pressbooks 4.1 or greater as well as a new URL for your cloned book.', 'pressbooks' );
			}
		}
	}
}
