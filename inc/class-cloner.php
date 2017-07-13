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
		}

		if ( define( 'WP_ENV' ) && WP_ENV === 'development' ) {
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

		$section = json_decode( $response['body'], true );

		// Process response
		foreach ( [ 'guid', 'link', 'id' ] as $bad_key ) {
			unset( $section[ $bad_key ] );
		}
		$title = $section['title']['rendered'];
		$content = $section['content']['rendered'];
		$section['title'] = $title;
		$section['content'] = $content;
		if ( $post_type === 'chapter' ) {
			$section['part'] = $target_parent_id;
		}

		// Build request URL
		$request_url = sprintf(
			'%1$s/%2$s/pressbooks/v2/%3$s',
			$this->targetBook,
			$this->restBase,
			$endpoint
		);

		// Prepare request body
		$args = array_merge( $this->requestArgs, [ 'body' => $section ] );

		// POST data to API
		$response = wp_remote_post( $request_url, $args );

		// Inform user of failure, bail
		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_error_message() ); // TODO
			return false;
		}

		// Get clone ID from response
		$response = json_decode( $response['body'], true );
		$clone_id = 42; // TODO

		// Clone associated content
		$this->cloneSectionRevisions( $section_id, $clone_id );
		$this->cloneSectionAttachments( $section_id, $clone_id );
		$this->cloneSectionComments( $section_id, $clone_id );

		return $clone_id;
	}

	/**
	 * Clone revisions of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new revisions if it succeeded.
	 */
	protected function cloneSectionRevisions( $section_id ) {
		// TODO
	}

	/**
	 * Clone attachments of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the section within the source book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new attachments if it succeeded.
	 */
	protected function cloneSectionAttachments( $section_id ) {
		// TODO
	}

	/**
	 * Clone comments on a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @return bool | int | array False if the clone failed; the ID or IDs of the new attachments if it succeeded.
	 */
	protected function cloneSectionComments( $section_id ) {
		// TODO
	}
}
