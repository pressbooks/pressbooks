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
	protected $sourceBook;

	/**
	 * The metadata of the source book.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceMetadata;

	/**
	 * The TOC of the source book.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $sourceToc;

	/**
	 * The URL of the target book.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	protected $targetBook;

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
		$this->sourceBook = esc_url( untrailingslashit( $source_url ) );
		if ( $target_url ) {
			$this->targetBook = esc_url( untrailingslashit( $target_url ) );
			$this->targetBookId = $this->getTargetBookId();
		}

		if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
			$this->requestArgs = [ 'sslverify' => false ];
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
		// Populate Metadata
		$this->sourceMetadata = $this->getSourceMetadata();

		if ( ! $this->isCloneable() ) {
			return false; // TODO Explain why
		}

		// Populate TOC
		$this->sourceToc = $this->getSourceToc();

		// Create Book
		$this->targetBook = $this->createBook();

		// Clone Metadata
		$this->cloneMetadata();

		// Clone Front Matter
		foreach ( $this->sourceToc['front-matter'] as $id ) {
			$this->cloneFrontMatter( $id );
		}

		// Clone Parts
		foreach ( $this->sourceToc['parts'] as $id ) {
			$part_id = $this->clonePart( $id );

			// Clone Chapters
			foreach ( $this->sourceToc['parts'][ $part_id ]['chapters'] as $id ) {
				$this->cloneChapter( $id, $part_id );
			}
		}

		// Clone Back Matter
		foreach ( $this->sourceToc['back-matter'] as $id ) {
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
	 * Fetch an array of metadata from a source book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | array False if the operation failed; the array of metadata if it succeeded.
	 */
	public function getSourceMetadata() {
		// TODO
	}

	/**
	 * Fetch a TOC array from a source book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | array False if the operation failed; the TOC array if it succeeded.
	 */
	public function getSourceToc() {
		// TODO
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

		// Determine endpoint based on $post_type
		$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;

		// Build request URL
		$request_url = sprintf(
			'%1$s/%2$s/pressbooks/v2/%3$s/%4$s',
			$this->sourceBook,
			$this->restBase,
			$endpoint,
			$section_id
		);

		// GET response from API
		$response = wp_remote_get( $request_url, $this->requestArgs );

		// Inform user of failure, bail
		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_error_message() ); // TODO
			return false;
		}

		// Process response
		$section = json_decode( $response['body'], true );

		// Get links
		$links = array_pop( $section );

		// Remove source-specific properties
		$bad_keys = [ 'guid', 'link', 'id' ];
		foreach ( $bad_keys as $bad_key ) {
			unset( $section[ $bad_key ] );
		}

		// Move title and content up
		$section['title'] = $section['title']['rendered'];
		$section['content'] = $section['content']['rendered'];

		// Set part
		if ( $post_type === 'chapter' ) {
			$section['part'] = $target_parent_id;
		}

		// TODO Handle authors

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
