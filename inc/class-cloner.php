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
	 * The structure and contents of the source book as returned by the Pressbooks REST API v2.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceBookStructure;

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

		// Set up $this->sourceBookStructure
		$this->sourceBookStructure = $this->getSourceBookStructure();

		// Set up $this->sourceBookMetadata
		$this->sourceBookMetadata = $this->getSourceBookMetadata();

		if ( $target_url ) {
			$this->targetBookUrl = esc_url( untrailingslashit( $target_url ) );
			$this->targetBookId = $this->getTargetBookId();
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
		if ( ! $this->isCloneable() ) {
			return false; // TODO Explain why
		}

		// Create Book
		$this->targetBook = $this->createBook();

		// Clone Metadata
		$this->cloneMetadata();

		// Clone Front Matter
		foreach ( $this->sourceBookStructure['front-matter'] as $id ) {
			$this->cloneFrontMatter( $id );
		}

		// Clone Parts
		foreach ( $this->sourceBookStructure['parts'] as $id ) {
			$part_id = $this->clonePart( $id );

			// Clone Chapters
			foreach ( $this->sourceBookStructure['parts'][ $part_id ]['chapters'] as $id ) {
				$this->cloneChapter( $id, $part_id );
			}
		}

		// Clone Back Matter
		foreach ( $this->sourceBookStructure['back-matter'] as $id ) {
			$this->cloneBackMatter( $id );
		}

		return true;
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
		$this->cloneSection( $id, 'front-matter' );
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
		$this->cloneSection( $id, 'part' );
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
		$this->cloneSection( $id, 'chapter', $part_id );
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
		$this->cloneSection( $id, 'back-matter' );
	}

	/**
	 * Fetch an array containing the structure and contents of a source book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | array False if the operation failed; the structure and contents array if it succeeded.
	 */
	public function getSourceBookStructure() {
		// Build request URL
		$request_url = sprintf(
			'%1$s/%2$s/pressbooks/v2/toc?_embed',
			$this->sourceBookUrl,
			$this->restBase
		);

		// GET response from API
		$response = wp_remote_get( $request_url, $this->requestArgs );

		// Inform user of failure, bail
		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_error_message() ); // TODO
			return false;
		}

		// Process response
		return json_decode( $response['body'], true );
	}

	/**
	 * Fetch an array containing the metadata of a source book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | array False if the operation failed; the metadata array if it succeeded.
	 */
	public function getSourceBookMetadata() {
		// Build request URL
		$request_url = sprintf(
			'%1$s/%2$s/pressbooks/v2/metadata',
			$this->sourceBookUrl,
			$this->restBase
		);

		// GET response from API
		$response = wp_remote_get( $request_url, $this->requestArgs );

		// Inform user of failure, bail
		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_error_message() ); // TODO
			return false;
		}

		// Process response
		return json_decode( $response['body'], true );
	}

	/**
	 * Get a local book ID from its URL.
	 *
	 * @since 4.1.0
	 *
	 * @return int 0 of no blog was found, or the ID of the matched book.
	 */

	public function getTargetBookId() {
		return get_blog_id_from_url(
			wp_parse_url( $this->targetBook, PHP_URL_HOST ),
			trailingslashit( wp_parse_url( $this->targetBook, PHP_URL_PATH ) )
		);
	}

	/**
	 * Is the source book cloneable?
	 *
	 * @since 4.1.0
	 *
	 * @return bool Whether or not the book is public and licensed for cloning.
	 */
	public function isCloneable() {
		if ( in_array( $this->sourceBookMetadata['license'], [
			'https://creativecommons.org/licenses/by-nd/4.0/',
			'https://creativecommons.org/licenses/by-nc-nd/4.0/',
			'https://choosealicense.com/no-license/',
		], true ) ) {
			return false; // No derivatives OR all rights reserved
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
		// TODO
	}

	/**
	 * Clone book information to the target book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | int False if the creation failed; the ID of the new book's book information post if it succeeded.
	 */
	protected function cloneMetadata() {
		// TODO
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
		$bad_keys = [ 'author', 'link', 'id' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $section[ $bad_key ] );
		}

		// Move title and content up
		$section['title'] = $section['title']['rendered'];
		$section['content'] = $section['content']['rendered'];

		// TODO Set part
		if ( $post_type === 'chapter' ) {
			$section['part'] = $parent_id;
		}

		// Determine endpoint based on $post_type
		$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;

		// POST internal request
		if ( $blog_id !== $this->targetBookId ) {
			switch_to_blog( $this->targetBookId );
		}
		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $section );
		$response = rest_do_request( $request )->get_data();
		if ( $blog_id !== $this->targetBookId ) {
			restore_current_blog();
		}

		// Inform user of failure, bail
		if ( @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			wp_die( $response['message'] ); // TODO
			return false;
		}

		// Clone associated content
		$this->cloneSectionRevisions( $section_id, $response['id'] );
		$this->cloneSectionAttachments( $section_id, $response['id'] );
		$this->cloneSectionComments( $section_id, $response['id'] );

		return $response['id'];
	}

	/**
	 * Clone revisions of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param int $target_id The ID of the section within the target book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new revisions if it succeeded.
	 */
	protected function cloneSectionRevisions( $section_id, $target_id ) {
		// TODO
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
		// TODO
	}

	/**
	 * Clone comments on a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param int $target_id The ID of the section within the target book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new attachments if it succeeded.
	 */
	protected function cloneSectionComments( $section_id, $target_id ) {
		// TODO
	}
}
