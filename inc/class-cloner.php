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
use function Pressbooks\Image\strip_baseurl as image_strip_baseurl;
use function Pressbooks\Media\strip_baseurl as media_strip_baseurl;
use function Pressbooks\Metadata\schema_to_book_information;
use function Pressbooks\Metadata\schema_to_section_information;
use function Pressbooks\Utility\getset;
use function Pressbooks\Utility\oxford_comma_explode;
use function Pressbooks\Utility\str_ends_with;
use function Pressbooks\Utility\str_lreplace;
use function Pressbooks\Utility\str_remove_prefix;
use function Pressbooks\Utility\str_starts_with;

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
	 * The structure and contents of the source book (TOC) as returned by the Pressbooks REST API v2.
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
	 * The glossary posts as returned by the Pressbooks REST API v2.
	 *
	 * @since 5.6.0
	 *
	 * @var array
	 */
	protected $sourceBookGlossary;

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
	 * The title of the target book.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	protected $targetBookTitle;

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
	 * Array of known media
	 *
	 * @var \Pressbooks\Entities\Cloner\Media[]
	 */
	protected $knownMedia = [];

	/**
	 * Regular expression for image extensions that Pressbooks knows how to resize, analyse, etc.
	 *
	 * @var string
	 */
	protected $pregSupportedImageExtensions = '/\.(jpe?g|gif|png)$/i';

	/**
	 * @var \Pressbooks\Contributors;
	 */
	protected $contributors;

	/**
	 * @var \Pressbooks\Entities\Cloner\Transition[]
	 */
	protected $transitions;

	/**
	 * @var int[]
	 */
	protected $postsWithGlossaryShortcodesToFix = [];

	/**
	 * @var int[]
	 */
	protected $postsWithAttachmentsShortcodesToFix = [];


	/**
	 * @var array
	 */
	protected $imageWasAlreadyDownloaded = [];

	/**
	 * @var array
	 */
	protected $mediaWasAlreadyDownloaded = [];

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 *
	 * @param string $source_url The public URL of the source book.
	 * @param string $target_url The public URL of the target book.
	 * @param string $target_title The title of the target book.
	 */
	public function __construct( $source_url, $target_url = '', $target_title = '' ) {
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

		// Set up $this->targetBookTitle if set
		if ( $target_title ) {
			$this->targetBookTitle = esc_attr( $target_title );
		}

		// Include media utilities
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$this->interactiveContent = \Pressbooks\Interactive\Content::init();
		$this->contributors = new Contributors();
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
	 *  /wp-json/pressbooks/v2/toc
	 *
	 * @return array
	 */
	public function getSourceBookStructure() {
		return $this->sourceBookStructure;
	}

	/**
	 * /wp-json/pressbooks/v2/<post>-type
	 *
	 * @return array
	 */
	public function getSourceBookTerms() {
		return $this->sourceBookTerms;
	}

	/**
	 * /wp-json/pressbooks/v2/glossary
	 *
	 * @return array
	 */
	public function getSourceBookGlossary() {
		return $this->sourceBookGlossary;
	}

	/**
	 * /wp-json/pressbooks/v2/metadata
	 *
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

		// Pre-processor
		$this->clonePreProcess();

		// Clone Metadata
		$this->clonedItems['metadata'][] = $this->cloneMetadata();

		// Clone Taxonomy Terms
		$this->targetBookTerms = $this->getBookTerms( $this->targetBookUrl );
		foreach ( $this->sourceBookTerms as $term ) {
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
				$this->clonedItems['front-matter'][] = $new_frontmatter;
			}
		}

		// Clone Parts
		foreach ( $this->sourceBookStructure['parts'] as $key => $part ) {
			$new_part = $this->clonePart( $part['id'] );
			if ( $new_part !== false ) {
				$this->clonedItems['parts'][] = $new_part;
				// Clone Chapters
				foreach ( $this->sourceBookStructure['parts'][ $key ]['chapters'] as $chapter ) {
					$new_chapter = $this->cloneChapter( $chapter['id'], $new_part );
					if ( $new_chapter !== false ) {
						$this->clonedItems['chapters'][] = $new_chapter;
					}
				}
			}
		}

		// Clone Back Matter
		foreach ( $this->sourceBookStructure['back-matter'] as $backmatter ) {
			$new_backmatter = $this->cloneBackMatter( $backmatter['id'] );
			if ( $new_backmatter !== false ) {
				$this->clonedItems['back-matter'][] = $new_backmatter;
			}
		}

		// Clone Glossary
		foreach ( $this->sourceBookGlossary as $glossary ) {
			$new_glossary = $this->cloneGlossary( $glossary['id'] );
			if ( $new_glossary !== false ) {
				$this->clonedItems['glossary'][] = $new_glossary;
			}
		}

		// Post-processor
		$this->clonePostProcess();

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

		$this->knownMedia = $this->buildListOfKnownMedia( $this->sourceBookUrl );
		if ( $this->knownMedia === false ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve media from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}
		// Sort by the length of sourceUrls for better search and replace
		$known_media_sorted = $this->knownMedia;
		uasort(
			$known_media_sorted, function ( $a, $b ) {
				return strlen( $b->sourceUrl ) <=> strlen( $a->sourceUrl );
			}
		);
		$this->knownMedia = $known_media_sorted;

		// Set up $this->sourceBookGlossary
		$this->sourceBookGlossary = $this->getBookGlossary( $this->sourceBookUrl );

		$this->maybeRestoreCurrentBlog();
		return true;
	}

	/**
	 * Pre-processor
	 */
	public function clonePreProcess() {
		// TODO
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
	 * Clone glossary from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the back matter within the source book.
	 * @return bool | int False if the clone failed; the ID of the new back matter if it succeeded.
	 */
	public function cloneGlossary( $id ) {
		return $this->cloneSection( $id, 'glossary' );
	}

	/**
	 * Post-processor
	 */
	public function clonePostProcess() {
		$this->fixInternalShortcodes();
	}

	/**
	 * Use media endpoint to build an array of known media
	 *
	 * @param string $url The URL of the book.
	 *
	 * @return bool |\Pressbooks\Entities\Cloner\Media[] False if the operation failed; known images array if succeeded.
	 */
	public function buildListOfKnownMedia( $url ) {
		// Handle request (local or global)
		$params = [
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

		$known_media = [];
		foreach ( $response as $item ) {
			$m = $this->createMediaEntity( $item );
			if ( $item['media_type'] === 'image' ) {
				foreach ( $item['media_details']['sizes'] as $size => $info ) {
					$attached_file = image_strip_baseurl( $info['source_url'] ); // 2017/08/foo-bar-300x225.png
					$known_media[ $attached_file ] = $m;
				}
			} else {
				$attached_file = media_strip_baseurl( $m->sourceUrl ); // 2017/08/foo-bar.ext
				$known_media[ $attached_file ] = $m;
			}
		}

		return $known_media;
	}

	/**
	 * @param string $type
	 * @param int $old_id
	 * @param int $new_id
	 *
	 * @return \Pressbooks\Entities\Cloner\Transition
	 */
	protected function createTransition( $type, $old_id, $new_id ) {
		$transition = new  Entities\Cloner\Transition();
		$transition->type = $type;
		$transition->oldId = $old_id;
		$transition->newId = $new_id;
		return $transition;
	}

	/**
	 * @param array $item
	 *
	 * @return \Pressbooks\Entities\Cloner\Media
	 */
	protected function createMediaEntity( $item ) {
		$m = new Entities\Cloner\Media();
		if ( isset( $item['id'] ) ) {
			$m->id = $item['id'];
		}
		if ( isset( $item['title'], $item['title']['raw'] ) ) {
			$m->title = $item['title']['raw'];
		}
		if ( isset( $item['description'], $item['description']['raw'] ) ) {
			$m->description = $item['description']['raw'];
		}
		if ( isset( $item['caption'], $item['caption']['raw'] ) ) {
			$m->caption = $item['caption']['raw'];
		}
		if ( isset( $item['meta'] ) ) {
			$m->meta = $item['meta'];
		}
		if ( isset( $item['alt_text'] ) ) {
			$m->altText = $item['alt_text'];
		}
		if ( isset( $item['source_url'] ) ) {
			$m->sourceUrl = $item['source_url'];
		}
		return $m;
	}

	/**
	 * @param \Pressbooks\Entities\Cloner\Media $media
	 *
	 * @return array
	 */
	protected function createMediaPatch( $media ) {
		return [
			'title' => $media->title,
			'meta' => $media->meta,
			'description' => $media->description,
			'caption' => $media->caption,
			'alt_text' => $media->altText,
		];
	}

	/**
	 * Check if post content contains shortcodes with references to internal IDs that we will need to fix
	 *
	 * @param int $post_id
	 * @param string $html
	 */
	protected function checkInternalShortcodes( $post_id, $html ) {
		// Glossary
		if ( has_shortcode( $html, Shortcodes\Glossary\Glossary::SHORTCODE ) ) {
			$this->postsWithGlossaryShortcodesToFix[] = $post_id;
		}
		// Attachments
		if ( has_shortcode( $html, Shortcodes\Attributions\Attachments::SHORTCODE ) ) {
			$this->postsWithAttachmentsShortcodesToFix[] = $post_id;
		}
	}

	/**
	 * Fix shortcodes with references to internal IDs
	 */
	protected function fixInternalShortcodes() {
		// Glossary
		foreach ( $this->postsWithGlossaryShortcodesToFix as $post_id ) {
			$post = get_post( $post_id );
			foreach ( $this->transitions as $transition ) {
				if ( $transition->type === 'glossary' ) {
					$post->post_content = \Pressbooks\Utility\shortcode_att_replace(
						$post->post_content,
						Shortcodes\Glossary\Glossary::SHORTCODE,
						'id',
						$transition->oldId,
						$transition->newId
					);
				}
			}
			wp_update_post( $post );
		}
		// Attachments
		foreach ( $this->postsWithAttachmentsShortcodesToFix as $post_id ) {
			$post = get_post( $post_id );
			foreach ( $this->transitions as $transition ) {
				if ( $transition->type === 'attachment' ) {
					$post->post_content = \Pressbooks\Utility\shortcode_att_replace(
						$post->post_content,
						Shortcodes\Attributions\Attachments::SHORTCODE,
						'id',
						$transition->oldId,
						$transition->newId
					);
				}
			}
			wp_update_post( $post );
		}
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

		foreach ( [ 'front-matter-type', 'chapter-type', 'back-matter-type', 'glossary-type' ] as $taxonomy ) {
			// Handle request (local or global)
			$response = $this->handleGetRequest(
				$url, 'pressbooks/v2', "$taxonomy", [
					'per_page' => 25,
				]
			);

			if ( is_wp_error( $response ) ) {
				continue;
			}

			// Remove links
			unset( $response['_links'] );

			// Process response
			$terms = array_merge( $terms, $response );
		}

		if ( empty( $terms ) ) {
			$_SESSION['pb_errors'][] = sprintf( '<p>%1$s</p>', __( 'The source book&rsquo;s taxonomies could not be read.', 'pressbooks' ) );
		}

		return $terms;
	}

	/**
	 * @since 5.6.0
	 *
	 * @param $url
	 *
	 * @return array
	 */
	public function getBookGlossary( $url ) {
		// Handle request (local or global)
		$response = $this->handleGetRequest(
			$url, 'pressbooks/v2', 'glossary', [
				'per_page' => 100,
			]
		);

		// Handle errors
		if ( is_wp_error( $response ) ) {
			return [];
		} else {
			return $response;
		}
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

		if ( ! $this->targetBookTitle ) {
			$this->targetBookTitle = $this->sourceBookMetadata['name'];
		}

		$user_id = get_current_user_id();
		// Disable automatic redirect to new book dashboard
		add_filter(
			'pb_redirect_to_new_book', function () {
				return false;
			}
		);
		// Remove default content so that the book only contains the results of the clone operation
		add_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		$result = wpmu_create_blog( $domain, $path, $this->targetBookTitle, $user_id );
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
			if ( $new_cover_id > 0 ) {
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
		foreach ( $book_information as $key => $value ) {
			if ( $this->contributors->isValid( $key ) ) {
				$values = oxford_comma_explode( $value );
				foreach ( $values as $v ) {
					$this->contributors->insert( $v, $metadata_post_id, $key );
				}
			} elseif ( in_array( $key, $metadata_array_values, true ) ) {
				$values = explode( ', ', $value );
				foreach ( $values as $v ) {
					add_post_meta( $metadata_post_id, $key, $v );
				}
			} elseif ( $key === 'pb_title' ) {
				update_post_meta( $metadata_post_id, $key, $this->targetBookTitle );
			} else {
				update_post_meta( $metadata_post_id, $key, $value );
				if ( $key === 'pb_book_license' ) {
					wp_set_object_terms( $metadata_post_id, $value, Licensing::TAXONOMY ); // Link
				}
			}
		}

		// Remove the current user from the author field in Book Info
		$user_data = get_userdata( get_current_user_id() );
		$this->contributors->unlink( $user_data->user_nicename, $metadata_post_id );

		return $metadata_post_id;
	}

	/**
	 * Clone a section (front matter, part, chapter, back matter, glossary) of a source book to a target book.
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

		$section = $this->locateSection( $section_id, $post_type );
		if ( $section === false ) {
			return false;
		}

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

		// Set menu order
		static $menu_order_guess = 1;
		if ( ! isset( $section['menu_order'] ) ) {
			$section['menu_order'] = $menu_order_guess;
			$menu_order_guess++;
		}

		// Set mapped term ID
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

		// Attach attachments to post
		foreach ( $attachments as $attachment ) {
			wp_update_post(
				[
					'ID' => $attachment,
					'post_parent' => $response['id'],
				]
			);
		}

		// Shortcode hacker, no ease up tonight.
		$this->checkInternalShortcodes( $response['id'], $section['content'] );

		// Store a transitional state
		$this->transitions[] = $this->createTransition( $post_type, $section_id, $response['id'] );

		return $response['id'];
	}

	/**
	 * @param int $section_id
	 * @param string $post_type
	 *
	 * @return mixed
	 */
	protected function locateSection( $section_id, $post_type ) {
		if ( $post_type === 'glossary' ) {
			foreach ( $this->sourceBookGlossary as $k => $v ) {
				if ( $v['id'] === absint( $section_id ) ) {
					return $this->sourceBookGlossary[ $k ];
				}
			}
		} else {
			foreach ( $this->sourceBookStructure['_embedded'][ $post_type ] as $k => $v ) {
				if ( $v['id'] === absint( $section_id ) ) {
					return $this->sourceBookStructure['_embedded'][ $post_type ][ $k ];
				}
			};
		}
		return false;
	}

	/**
	 * Download media found in a section's `post_content` node, change the href links to point to newly downloaded media, etc
	 *
	 * @param array $section
	 *
	 * @return array{content: string, attachments: array}
	 */
	protected function retrieveSectionContent( $section ) {
		if ( ! empty( $section['content']['raw'] ) ) {
			// Wrap in fake div tags so that we can parse it
			$source_content = $section['content']['raw'];
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
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $source_content );

		// Download images, change image paths
		$media = $this->scrapeAndKneadImages( $dom );
		$dom = $media['dom'];
		$attachments = $media['attachments'];

		// Download media, change media paths
		$media = $this->scrapeAndKneadMedia( $dom, $html5->parser );
		$dom = $media['dom'];
		$attachments = array_merge( $attachments, $media['attachments'] );

		// Fix internal links
		$dom = $this->fixInternalLinks( $dom );

		// Save the destination content
		$content = $html5->saveHTML( $dom );

		// Put back the hidden characters
		foreach ( $characters_to_keep as $c ) {
			$md5 = md5( $c );
			$content = str_replace( "<!-- pb_fixme_{$md5} -->", $c, $content );
		}

		if ( ! empty( $section['content']['raw'] ) ) {
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
		} elseif ( $post_type === 'glossary ' ) {
			foreach ( $this->sourceBookGlossary as $k => $v ) {
				if ( $v['id'] === absint( $section_id ) ) {
					return $v['metadata'];
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

		foreach ( $section_information as $key => $value ) {
			if ( $this->contributors->isValid( $key ) ) {
				$values = oxford_comma_explode( $value );
				foreach ( $values as $v ) {
					$this->contributors->insert( $v, $target_id, $key );
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
	 * @param bool $paginate (optional, if results are paginated then get next page)
	 * @param array $previous_results (optional, used recursively for when results are paginated)
	 *
	 * @return array|\WP_Error
	 */
	protected function handleGetRequest( $url, $namespace, $endpoint, $params = [], $paginate = true, $previous_results = [] ) {
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

			if ( $switch ) {
				restore_current_blog();
			}

			// Handle errors
			if ( is_wp_error( $response ) ) {
				return $response;
			} else {
				$results = rest_get_server()->response_to_data( $response, true );
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
				$results = json_decode( $response['body'], true );
			}
		}

		if ( ! empty( $previous_results ) ) {
			$results = array_merge( $previous_results, $results );
		}

		if ( $paginate ) {
			$next_url = $this->nextWebLink( $response );
			if ( $next_url ) {
				parse_str( wp_parse_url( $next_url, PHP_URL_QUERY ), $next_params );
				$next_url = strtok( $next_url, '?' );
				return $this->handleGetRequest( $next_url, $namespace, $endpoint, $next_params, $paginate, $results );
			}
		}

		return $results;
	}

	/**
	 * Format: <http://pressbooks.dev/test/wp-json/wp/v2/media?media_type=image&page=2>; rel="next"
	 * Or: <http://pressbooks.dev/test/wp-json/wp/v2/media?media_type=image&page=1>; rel="prev", <http://pressbooks.dev/test/wp-json/wp/v2/media?media_type=image&page=3>; rel="next"
	 *
	 * @param \WP_REST_Response|array $response
	 *
	 * @return string|false
	 */
	protected function nextWebLink( $response ) {
		$header = $this->extractLinkHeader( $response );
		$links = explode( ',', $header );
		foreach ( $links as $link ) {
			$link = $this->parseLinkHeader( $link );
			if ( isset( $link['rel'] ) && strtolower( $link['rel'] ) === 'next' ) {
				return $link['href'];
			}
		}
		return false;
	}

	/**
	 * @param \WP_REST_Response|array $response
	 *
	 * @return string
	 */
	protected function extractLinkHeader( $response ) {
		if ( is_object( $response ) && property_exists( $response, 'headers' ) && is_array( $response->headers ) && isset( $response->headers['Link'] ) ) {
			return $response->headers['Link'];
		}
		if ( is_array( $response ) && isset( $response['headers'], $response['headers']['Link'] ) ) {
			return $response['headers']['Link'];
		}
		return '';
	}

	/**
	 * Parse a Link header into attributes.
	 *
	 * @param string $link Link header from the response.
	 *
	 * @return array Map of attribute key => attribute value, with link href in `href` key.
	 */
	protected function parseLinkHeader( $link ) {
		$parts = explode( ';', $link );
		$attrs = [
			'href' => trim( array_shift( $parts ), '<>' ),
		];
		foreach ( $parts as $part ) {
			if ( ! strpos( $part, '=' ) ) {
				continue;
			}
			list( $key, $value ) = explode( '=', $part, 2 );
			$key = trim( $key );
			$value = trim( $value, '" ' );
			$attrs[ $key ] = $value;
		}
		return $attrs;
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
			if ( $attachment_id === -1 ) {
				// Do nothing because image is not hosted on the source Pb network
			} elseif ( $attachment_id ) {
				$image->setAttribute( 'src', $this->replaceImage( $attachment_id, $src_old, $image ) );

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
	 * Will return -1 if image is not hosted on the source Pb network, or 0 if something went wrong.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @see media_handle_sideload
	 *
	 * @return int attachment ID, -1 if image is not hosted on the source Pb network, or 0 if import failed
	 */
	protected function fetchAndSaveUniqueImage( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}
		if ( ! $this->sameAsSource( $url ) ) {
			return -1;
		}

		$filename = $this->basename( $url );
		$attached_file = image_strip_baseurl( $url );

		if ( isset( $this->knownMedia[ $attached_file ] ) ) {
			$remote_img_location = $this->knownMedia[ $attached_file ]->sourceUrl;
			$filename = basename( $remote_img_location );
		} else {
			$remote_img_location = $url;
		}

		if ( isset( $this->imageWasAlreadyDownloaded[ $remote_img_location ] ) ) {
			return $this->imageWasAlreadyDownloaded[ $remote_img_location ];
		}

		/* Process */

		if ( ! preg_match( $this->pregSupportedImageExtensions, $filename ) ) {
			// Unsupported image type
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
			return 0;
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
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
				$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
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
			if ( isset( $this->knownMedia[ $attached_file ] ) ) {
				// Patch
				$m = $this->knownMedia[ $attached_file ];
				$request = new \WP_REST_Request( 'PATCH', "/wp/v2/media/{$pid}" );
				$request->set_body_params( $this->createMediaPatch( $m ) );
				rest_do_request( $request );
				// Store a transitional state
				$this->transitions[] = $this->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = $pid;
			// Counter
			$this->clonedItems['media'][] = $pid;
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

		if ( $this->sameAsSource( $src_old ) && isset( $this->knownMedia[ image_strip_baseurl( $src_old ) ] ) ) {
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
	 * Parse HTML snippet, save all found media using media_handle_sideload(), return the HTML with changed URLs.
	 *
	 * Because we clone using WordPress raw format, we have to brute force against the text because the DOM
	 * can't see shortcodes, text urls, hrefs with no identifying info, etc.
	 *
	 * @since 4.1.0
	 *
	 * @param \DOMDocument $dom
	 * @param \Masterminds\HTML5 $html5
	 *
	 * @return array An array containing the \DOMDocument and the IDs of created attachments
	 */
	protected function scrapeAndKneadMedia( \DOMDocument $dom, $html5 ) {

		$dom_as_string = $html5->saveHTML( $dom );
		$dom_as_string = \Pressbooks\Sanitize\strip_container_tags( $dom_as_string );

		$attachments = [];
		$changed = false;
		foreach ( $this->knownMedia as $alt => $media ) {
			if ( preg_match( $this->pregSupportedImageExtensions, $this->basename( $media->sourceUrl ) ) ) {
				// Skip images, these have already been done
				continue;
			}
			if ( strpos( $dom_as_string, $media->sourceUrl ) !== false ) {
				$src_old = $media->sourceUrl;
				$attachment_id = $this->fetchAndSaveUniqueMedia( $src_old );
				if ( $attachment_id === -1 ) {
					// Do nothing because media is not hosted on the source Pb network
				} elseif ( $attachment_id ) {
					$dom_as_string = str_replace( $src_old, wp_get_attachment_url( $attachment_id ), $dom_as_string );
					$attachments[] = $attachment_id;
					$changed = true;
				} else {
					// Tag broken media
					$dom_as_string = str_replace( $src_old, "{$src_old}#fixme", $dom_as_string );
					$changed = true;
				}
			}
		}

		return [
			'dom' => $changed ? $html5->loadHTML( $dom_as_string ) : $dom,
			'attachments' => $attachments,
		];
	}

	/**
	 * Load remote media into WP using media_handle_sideload()
	 * Will return -1 if media is not hosted on the source Pb network, or 0 if something went wrong.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @see media_handle_sideload
	 *
	 * @return int attachment ID, -1 if media is not hosted on the source Pb network, or 0 if import failed
	 */
	protected function fetchAndSaveUniqueMedia( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}
		if ( ! $this->sameAsSource( $url ) ) {
			return -1;
		}

		$filename = $this->basename( $url );
		$attached_file = media_strip_baseurl( $url );

		if ( isset( $this->knownMedia[ $attached_file ] ) ) {
			$remote_media_location = $this->knownMedia[ $attached_file ]->sourceUrl;
			$filename = basename( $remote_media_location );
		} else {
			$remote_media_location = $url;
		}

		if ( isset( $this->mediaWasAlreadyDownloaded[ $remote_media_location ] ) ) {
			return $this->mediaWasAlreadyDownloaded[ $remote_media_location ];
		}

		/* Process */

		$tmp_name = download_url( $remote_media_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$this->mediaWasAlreadyDownloaded[ $remote_media_location ] = 0;
			return 0;
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
			if ( isset( $this->knownMedia[ $attached_file ] ) ) {
				// Patch
				$m = $this->knownMedia[ $attached_file ];
				$request = new \WP_REST_Request( 'PATCH', "/wp/v2/media/{$pid}" );
				$request->set_body_params( $this->createMediaPatch( $m ) );
				rest_do_request( $request );
				// Store a transitional state
				$this->transitions[] = $this->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->mediaWasAlreadyDownloaded[ $remote_media_location ] = $pid;
			// Counter
			$this->clonedItems['media'][] = $pid;
		}
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $pid;
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
		$is_subdomain_install = is_subdomain_install();

		// Get links, loop through
		$links = $dom->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			/** @var \DOMElement $link */
			$href = $link->getAttribute( 'href' );
			if ( $is_subdomain_install ) {
				if ( str_starts_with( $href, "/$source_path/" ) ) {
					// Remove book path (cloning from subdirectory to subdomain)
					$href = str_remove_prefix( $href, "/$source_path" );
				}
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
		$response = $this->handleGetRequest( $url, 'pressbooks/v2', 'chapter-type', [ 'per_page' => 1 ], false );
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
				$booktitle = $_POST['target_book_title'] ?? '';
				if ( is_wp_error( $bookname ) ) {
					$_SESSION['pb_errors'][] = $bookname->get_error_message();
				} else {
					/**
					 * Maximum execution time, in seconds. If set to zero, no time limit
					 * Overrides PHP's max_execution_time of a Nginx->PHP-FPM->PHP configuration
					 * See also request_terminate_timeout (PHP-FPM) and fastcgi_read_timeout (Nginx)
					 *
					 * @since 5.6.0
					 *
					 * @param int $seconds
					 * @param string $some_action
					 *
					 * @return int
					 */
					@set_time_limit( apply_filters( 'pb_set_time_limit', 600, 'clone' ) ); // @codingStandardsIgnoreLine
					$cloner = new Cloner( esc_url( $_POST['source_book_url'] ), $bookname, $booktitle );
					if ( $cloner->cloneBook() ) {
						$_SESSION['pb_notices'][] = sprintf(
							__( 'Cloning succeeded! Cloned %1$s, %2$s, %3$s, %4$s, %5$s, %6$s, and %7$s to %8$s.', 'pressbooks' ),
							sprintf( _n( '%s term', '%s terms', count( getset( $cloner->clonedItems, 'terms', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'terms', [] ) ) ),
							sprintf( _n( '%s front matter', '%s front matter', count( getset( $cloner->clonedItems, 'front-matter', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'front-matter', [] ) ) ),
							sprintf( _n( '%s part', '%s parts', count( getset( $cloner->clonedItems, 'parts', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'parts', [] ) ) ),
							sprintf( _n( '%s chapter', '%s chapters', count( getset( $cloner->clonedItems, 'chapters', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'chapters', [] ) ) ),
							sprintf( _n( '%s back matter', '%s back matter', count( getset( $cloner->clonedItems, 'back-matter', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'back-matter', [] ) ) ),
							sprintf( _n( '%s media attachment', '%s media attachments', count( getset( $cloner->clonedItems, 'media', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'media', [] ) ) ),
							sprintf( _n( '%s glossary term', '%s glossary terms', count( getset( $cloner->clonedItems, 'glossary', [] ) ), 'pressbooks' ), count( getset( $cloner->clonedItems, 'glossary', [] ) ) ),
							sprintf( '<a href="%1$s"><em>%2$s</em></a>', trailingslashit( $cloner->targetBookUrl ) . 'wp-admin/', $cloner->targetBookTitle )
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
