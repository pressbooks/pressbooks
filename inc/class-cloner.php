<?php
/**
 * Handles cloning content via the Pressbooks REST API v2.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class Cloner {
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
	 * @var bool
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
	 * An array of cloned item types.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $clonedItems = [ 'term' => 0, 'front-matter' => 0, 'part' => 0, 'chapter' => 0, 'back-matter' => 0 ];

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
	protected $requestArgs;

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 *
	 * @param string $source_url The URL of the source book.
	 * @param string $target_url The URL of the target book.
	 */
	public function __construct( $source_url, $target_url = null ) {
		// Disable SSL verification for development
		if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
			$this->requestArgs = [ 'sslverify' => false ];
		}

		// Set up $this->sourceBookUrl
		$this->sourceBookUrl = esc_url( untrailingslashit( $source_url ) );

		// Set up $this->sourceBookId
		$this->sourceBookId = $this->getBookId( $this->sourceBookUrl );

		// Set up $this->targetBookUrl and $this->targetBookId if set
		if ( $target_url ) {
			$this->targetBookUrl = esc_url( untrailingslashit( $target_url ) );
			$this->targetBookId = $this->getBookId( $url );
		}
	}

	/**
	 * Clone a book in its entirety.
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function cloneBook() {
		// Set up $this->sourceBookMetadata
		$this->sourceBookMetadata = $this->getBookMetadata( $this->sourceBookUrl );
		if ( empty( $this->sourceBookMetadata ) ) {
			return false;
		}

		// Verify license or network administrator override
		if ( ! $this->isBookCloneable() ) {
			$_SESSION['pb_errors'][] = sprintf( __( '%s is not licensed for cloning.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			return false;
		}

		// Set up $this->sourceBookStructure
		$this->sourceBookStructure = $this->getBookStructure( $this->sourceBookUrl );
		if ( empty( $this->sourceBookStructure ) ) {
			return false;
		}

		// Set up $this->sourceBookTerms
		$this->sourceBookTerms = $this->getBookTerms( $this->sourceBookUrl );
		if ( empty( $this->sourceBookTerms ) ) {
			return false;
		}

		// Create Book
		$this->targetBookId = $this->createBook();
		$this->targetBookUrl = get_blogaddress_by_id( $this->targetBookId );

		// Clone Metadata
		$this->cloneMetadata();

		// Clone Taxonomy Terms
		$this->targetBookTerms = $this->getBookTerms( $this->targetBookUrl );

		foreach ( $this->sourceBookTerms as $term ) {
			$this->termMap[ $term['id'] ] = $this->cloneTerm( $term['id'], $term['taxonomy'] );
		}

		// Clone Front Matter
		foreach ( $this->sourceBookStructure['front-matter'] as $frontmatter ) {
			$this->cloneFrontMatter( $frontmatter['id'] );
		}

		// Clone Parts
		foreach ( $this->sourceBookStructure['part'] as $key => $part ) {
			$part_id = $this->clonePart( $part['id'] );

			// Clone Chapters
			foreach ( $this->sourceBookStructure['part'][ $key ]['chapters'] as $chapter ) {
				$this->cloneChapter( $chapter['id'], $part_id );
			}
		}

		// Clone Back Matter
		foreach ( $this->sourceBookStructure['back-matter'] as $backmatter ) {
			$this->cloneBackMatter( $backmatter['id'] );
		}

		return true;
	}

	/**
	 * Clone term from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $term_id The ID of the term within the source book.
	 * @param int $taxonomy The taxonomy of the term within the source book.
	 * @return bool The ID of the new term if it the clone succeeded or the ID of a matching term if it exists.
	 */
	public function cloneTerm( $term_id, $taxonomy ) {
		global $blog_id;

		// Retrieve term
		foreach ( $this->sourceBookTerms as $k => $v ) {
			if ( $v['id'] === absint( $term_id ) ) {
				$term = $this->sourceBookTerms[ $k ];
			}
		};

		// Check for matching term
		foreach ( $this->targetBookTerms as $k => $v ) {
			if ( $v['slug'] === $term['slug'] && $v['taxonomy'] === $term['taxonomy'] ) {
				$this->clonedItems['term']++;
				return $v['id'];
			}
		};

		// Set endpoint
		$endpoint = $term['taxonomy'];

		// Get links
		$links = array_pop( $term );

		// Remove source-specific properties
		$bad_keys = [ 'id', 'count', 'link', 'parent', 'taxonomy' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $term[ $bad_key ] );
		}

		// POST internal request
		$switch = ( $blog_id !== $this->targetBookId ) ? true : false;
		if ( $switch ) {
			switch_to_blog( $this->targetBookId );
		}

		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $term );
		$response = rest_do_request( $request )->get_data();
		if ( $switch ) {
			restore_current_blog();
		}

		// Inform user of failure, bail
		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_message() );
		} else {
			$this->clonedItems['term']++;
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
	 * Fetch an array containing the metadata of a book.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL of the book.
	 * @return false | array False if the operation failed; the metadata array if it succeeded.
	 */
	public function getBookMetadata( $url ) {
		// Handle request (local or global)
		$response = $this->handleRequest( $url, 'pressbooks/v2', 'metadata' );

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
	 * @return WP_Error | array WP_Error object if the operation failed; the structure and contents array if it succeeded.
	 */
	public function getBookStructure( $url ) {
		// Handle request (local or global)
		$response = $this->handleRequest( $url , 'pressbooks/v2', 'toc', [ '_embed' => 1 ] );

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
	 * @return WP_Error | array WP_Error object if the operation failed; the term array if it succeeded.
	 */
	public function getBookTerms( $url ) {
		$terms = [];

		foreach ( [ 'front-matter-type', 'chapter-type', 'back-matter-type' ] as $taxonomy ) {
			// Handle request (local or global)
			$response = $this->handleRequest( $url, 'pressbooks/v2', "$taxonomy", [ 'per_page' => 25 ] );

			// Bail on error
			if ( is_wp_error( $response ) ) {
				$_SESSION['pb_errors'][] = sprintf(
					'<p>%1$s</p><p>%2$s</p>',
					__( 'The source book&rsquo;s taxonomies could not be read.', 'pressbooks' ),
					$response->get_error_message()
				);
				return false;
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
	 * @return bool Whether or not the book is public and licensed for cloning (or true if the current user is a network administrator and the book is in the current network).
	 */
	public function isBookCloneable() {
		$restrictive_licenses = [
			'https://creativecommons.org/licenses/by-nd/4.0/',
			'https://creativecommons.org/licenses/by-nc-nd/4.0/',
			'https://choosealicense.com/no-license/',
		];

		if ( ! empty( $this->sourceBookId ) ) {
			if ( current_user_can( 'manage_network_options' ) ) {
				return true; // Network administrators can clone local books no matter how they're licensed
			} elseif ( ! in_array( $this->sourceBookMetadata['license'], $restrictive_licenses, true ) ) {
				return true; // Anyone can clone local books that aren't restrictively licensed
			} else {
				return false; // TODO Error message
			}
		} elseif ( in_array( $this->sourceBookMetadata['license'], $restrictive_licenses, true ) ) {
			return false; // No one can clone global books that are restrictively licensed TODO Error message
		}
		return true;
	}

	/**
	 * Create target book if it doesn't already exist.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | string False if the creation failed; the URL of the new book if it succeeded.
	 */
	protected function createBook() {
		$host = wp_parse_url( network_home_url(), PHP_URL_HOST );
		if ( is_subdomain_install() ) {
			$domain = $this->getSubdomainOrSubDirectory( $this->sourceBookUrl ) . '.' . $host;
			$path = '/';
			if ( get_blog_id_from_url( $domain, trailingslashit( $path ) ) ) {
				$domain = $this->getSubdomainOrSubDirectory( $this->sourceBookUrl ) . strftime( '%Y%m%d%H%M%S' ) . '.' . $host;
			}
		} else {
			$domain = $host;
			$path = '/' . $this->getSubdomainOrSubDirectory( $this->sourceBookUrl );
			if ( get_blog_id_from_url( $domain, trailingslashit( $path ) ) ) {
				$path = $path . strftime( '%Y%m%d%H%M%S' );
			}
		}

		$title = $this->sourceBookMetadata['name'];
		$user_id = get_current_user_id();
		 // Disable automatic redirect to new book dashboard
		 add_filter( 'pb_redirect_to_new_book', function () {
			 return false;
		 } );
		 // Remove default content so that the book only contains the results of the clone operation
		 add_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		$result = wpmu_create_blog( $domain, $path, $title, $user_id );
		remove_all_filters( 'pb_redirect_to_new_book' );
		remove_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		return false;  // TODO Error message
	}

	/**
	 * Clone book information to the target book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | int False if the creation failed; the ID of the new book's book information post if it succeeded.
	 */
	protected function cloneMetadata() {
		// TODO Write this function
	}

	/**
	 * Clone a section (front matter, part, chapter, back matter) of a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section (default 'chapter').
	 * @param int $parent_id The ID of the part to which the chapter should be added (only required for chapters) within the target book.
	 * @return bool | int False if the clone failed; the ID of the new section if it succeeded.
	 */
	protected function cloneSection( $section_id, $post_type, $parent_id = null ) {
		global $blog_id;

		// Retrieve section
		foreach ( $this->sourceBookStructure['_embedded'][ $post_type ] as $k => $v ) {
			if ( $v['id'] === absint( $section_id ) ) {
				$section = $this->sourceBookStructure['_embedded'][ $post_type ][ $k ];
			}
		};

		// Get links
		$links = array_pop( $section );

		// Remove source-specific properties
		$bad_keys = [ 'author', 'id', 'link' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $section[ $bad_key ] );
		}

		// Set status
		$section['status'] = 'publish';

		// Set title and content
		$section['title'] = ( isset( $section['title']['rendered'] ) ) ? $section['title']['rendered'] : '';
		$section['content'] = ( isset( $section['content']['rendered'] ) ) ? $section['content']['rendered'] : '';

		// Set part
		if ( $post_type === 'chapter' ) {
			$section['part'] = $parent_id;
		}

		// Set mapped term ID
		if ( $post_type !== 'part' ) {
			if ( isset( $section[ "$post_type-type" ] ) ) {
				foreach ( $section[ "$post_type-type" ] as $key => $term_id ) {
					$section[ "$post_type-type" ][ $key ] = $this->termMap[ $term_id ];
				}
			}
		}

		// Determine endpoint based on $post_type
		$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;

		// POST internal request
		$switch = ( $blog_id !== $this->targetBookId ) ? true : false;
		if ( $switch ) {
			switch_to_blog( $this->targetBookId );
		}

		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $section );
		$response = rest_do_request( $request )->get_data();
		if ( $switch ) {
			restore_current_blog();
		}

		// Inform user of failure, bail
		if ( @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			return false;  // TODO Error message
		}

		// Clone associated content
		$this->cloneSectionMetadata( $section_id, $response['id'] );
		$this->cloneSectionAttachments( $section_id, $response['id'] );

		$this->clonedItems[ $post_type ]++;

		return $response['id'];
	}

	/**
	 * Clone metadata of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param int $target_id The ID of the section within the target book.
	 * @return bool False if the clone failed; true if it succeeded.
	 */
	protected function cloneSectionMetadata( $section_id, $target_id ) {
		// TODO Write this function
	}

	/**
	 * Clone attachments of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the section within the source book.
	 * @param int $target_id The ID of the section within the target book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new attachments if it succeeded.
	 */
	protected function cloneSectionAttachments( $section_id, $target_id ) {
		// TODO Write this function
	}

	protected function handleRequest( $url, $namespace, $endpoint, $params = [] ) {
		$local_book = $this->getBookId( $url );
		if ( $local_book ) {
			// GET response from API
			switch_to_blog( $local_book );
			$request = new \WP_REST_Request( 'GET', "/$namespace/$endpoint" );
			if ( ! empty( $params ) ) {
				$request->set_query_params( $params );
			}
			$response = rest_do_request( $request );
			restore_current_blog();

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
	 * @return int 0 of no blog was found, or the ID of the matched book.
	 */

	public static function getBookId( $url ) {
		return get_blog_id_from_url(
			wp_parse_url( $url , PHP_URL_HOST ),
			trailingslashit( wp_parse_url( $url , PHP_URL_PATH ) )
		);
	}

	/**
	 * Given a URL, get the subdomain or subdirectory (depending on the type of multisite install).
	 *
	 * @since 4.1.0
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

		if ( isset( $_POST['_wpnonce'] ) &&  wp_verify_nonce( $_POST['_wpnonce'], 'pb-cloner' ) ) {
			if ( isset( $_POST['source_book_url'] ) && ! empty( $_POST['source_book_url'] ) ) {
				$cloner = new Cloner( esc_url( $_POST['source_book_url'] ) );
				if ( $cloner->cloneBook() ) {
					$_SESSION['pb_notices'][] = sprintf(
						__( 'Cloning succeeded! Cloned %1$s, %2$s, %3$s, %4$s, and %5$s to %6$s.', 'pressbooks' ),
						sprintf( _n( '%s term', '%s terms', $cloner->clonedItems['term'], 'pressbooks' ), $cloner->clonedItems['term'] ),
						sprintf( _n( '%s front matter', '%s front matter', $cloner->clonedItems['front-matter'], 'pressbooks' ), $cloner->clonedItems['front-matter'] ),
						sprintf( _n( '%s part', '%s parts', $cloner->clonedItems['part'], 'pressbooks' ), $cloner->clonedItems['part'] ),
						sprintf( _n( '%s chapter', '%s chapters', $cloner->clonedItems['chapter'], 'pressbooks' ), $cloner->clonedItems['chapter'] ),
						sprintf( _n( '%s back matter', '%s back matter', $cloner->clonedItems['back-matter'], 'pressbooks' ), $cloner->clonedItems['back-matter'] ),
						sprintf( '<a href="%1$s"><em>%2$s</em></a>', $cloner->targetBookUrl, $cloner->sourceBookMetadata['name'] )
					);
				}
				\Pressbooks\Redirect\location( admin_url( 'options.php?page=pb_cloner' ) );
			} else {
				$_SESSION['pb_errors'][] = __( 'You must enter a valid URL to a book on a Pressbooks network running Pressbooks 4.1 or greater.', 'pressbooks' );
			}
		}
	}
}
