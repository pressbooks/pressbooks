<?php
/**
 * Handles cloning content via the Pressbooks REST API v2.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Cloner;

use function Pressbooks\Image\default_cover_url;
use function Pressbooks\Image\strip_baseurl as image_strip_baseurl;
use function Pressbooks\Media\strip_baseurl as media_strip_baseurl;
use function Pressbooks\Metadata\schema_to_book_information;
use function Pressbooks\Metadata\schema_to_section_information;
use function Pressbooks\Utility\str_ends_with;
use function Pressbooks\Utility\str_lreplace;
use function Pressbooks\Utility\str_remove_prefix;
use function Pressbooks\Utility\str_starts_with;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\Container;
use Pressbooks\Shortcodes\Glossary\Glossary;
use Pressbooks\Utility\PercentageYield;

class Cloner {
	public const THEME_OPTIONS_CLONED_OPTION = 'pressbooks_theme_options_cloned';

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
	 * The styles of the source book
	 *
	 * @var array
	 */
	protected $sourceStyles;

	/**
	 * The theme of the source book
	 *
	 * @var array
	 */
	protected $sourceTheme;

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
	 * @see \WP_Http::request
	 *
	 * @var array
	 */
	protected $requestArgs = [
		'timeout' => 300,
		'httpversion' => '1.1',
		'compress' => true,
	];

	/**
	 * @var array
	 */
	protected $targetBookTerms = [];

	/**
	 * Associative array of known media
	 * Key: Relative/truncated sourceUrl
	 * Value: \Pressbooks\Entities\Cloner\Media
	 * Sorted by the length of \Pressbooks\Entities\Cloner\Media()->sourceUrl (for better, left to right, search and replace loops)
	 *
	 * @var \Pressbooks\Entities\Cloner\Media[]
	 */
	protected $knownMedia = [];

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
	 * @var int[]
	 */
	protected $postsWithH5PShortcodesToFix = [];

	/**
	 * @var Downloads
	 */
	protected $downloads;

	/**
	 * @var \Pressbooks\Interactive\H5P
	 */
	protected $h5p;

	/**
	 * @var bool
	 */
	protected $sourceHasH5pApi = false;

	/**
	 * @var bool
	 */
	protected $sourceHasH5p = false;

	/**
	 * @var bool
	 */
	protected $targetHasH5pApi = false;

	/**
	 * Flag if we had problems with fetching H5P
	 * Assume true until proven otherwise
	 * Ie. A source with zero H5P will result in a target that has successfully fetched zero H5P...
	 *
	 * @var bool
	 */
	protected $targetHasFetchedAllTheH5p = true;

	/**
	 * Array of known H5P
	 *
	 * @var \Pressbooks\Entities\Cloner\H5P[]
	 */
	protected $knownH5P = [];

	/**
	 * Flag to indicate the cloner is being used to import content
	 * @var bool
	 */
	public $isImporting = false;

	/**
	 * List of contributors inserted.
	 * @var array
	 */
	private array $contributorsInserted = [];

	/**
	 * Map of chapters, front matters, back matters and parts IDs
	 * @var array
	 */
	private array $idPostsMap = [];

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

		$this->dependencies();
	}

	/**
	 * For testing, ability to mock objects
	 *
	 * @param null \Pressbooks\Interactive\H5P $h5p
	 * @param null Downloads $downloads
	 * @param null \Pressbooks\Contributors $contributors
	 */
	public function dependencies( $h5p = null, $downloads = null, $contributors = null ) {
		$this->h5p = $h5p ?: \Pressbooks\Interactive\Content::init()->getH5P();
		$this->downloads = $downloads ?: new Downloads( $this, $this->h5p );
		$this->contributors = $contributors ?: new \Pressbooks\Contributors();
		// Register glossary shortcode if not already registered.
		Glossary::init();
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
	 * @return string
	 */
	public function getTargetBookUrl() {
		return $this->targetBookUrl;
	}

	/**
	 * @return string
	 */
	public function getTargetBookTitle() {
		return $this->targetBookTitle;
	}

	/**
	 * @return \Pressbooks\Entities\Cloner\Media[]
	 */
	public function getKnownMedia() {
		return $this->knownMedia;
	}

	/**
	 * @return \Pressbooks\Entities\Cloner\H5P[]
	 */
	public function getKnownH5P() {
		return $this->knownH5P;
	}

	/**
	 * Clone a book in its entirety.
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function cloneBook() {
		try {
			foreach ( $this->cloneBookGenerator() as $percentage => $info ) {
				// Do nothing, this is a compatibility wrapper that makes the generator work like a regular function
			}
		} catch ( \Exception ) {
			return false;
		}
		return true;
	}

	/**
	 * Generator that yields values between 1-100, represents the percentage of progress when cloning a book in its entirety
	 *
	 * @since 5.7.0
	 * @throws \Exception
	 */
	public function cloneBookGenerator() : \Generator {
		yield 1 => __( 'Looking up the source book', 'pressbooks' );
		if ( ! $this->setupSource() ) {
			throw new \Exception( ! empty( $_SESSION['pb_errors'][0] ) ? $_SESSION['pb_errors'][0] : __( 'Failed to setup source', 'pressbooks' ) );
		}

		// Create Book
		yield 10 => __( 'Creating the target book', 'pressbooks' );
		$this->targetBookId = $this->createBook();
		$this->targetBookUrl = get_blogaddress_by_id( $this->targetBookId );

		switch_to_blog( $this->targetBookId );
		wp_defer_term_counting( true );

		// Pre-processor
		$this->clonePreProcess();

		// Clone Metadata
		yield 20 => __( 'Cloning metadata', 'pressbooks' );
		$this->clonedItems['metadata'][] = $this->cloneMetadata();

		// Clone Taxonomy Terms
		$y = new PercentageYield( 30, 40, count( (array) $this->sourceBookTerms ) );
		$this->targetBookTerms = $this->getBookTerms( $this->targetBookUrl );
		foreach ( $this->sourceBookTerms as $term ) {
			yield from $y->tick( __( 'Cloning contributors and licenses', 'pressbooks' ) );
			$new_term = $this->cloneTerm( $term['id'] );
			if ( $new_term ) {
				$this->termMap[ $term['id'] ] = $new_term;
				$this->clonedItems['terms'][] = $new_term;
			}
		}

		// Clone Front Matter
		$y = new PercentageYield( 40, 50, is_countable( $this->sourceBookStructure['front-matter'] ) ? count( $this->sourceBookStructure['front-matter'] ) : 0 );
		foreach ( $this->sourceBookStructure['front-matter'] as $frontmatter ) {
			yield from $y->tick( __( 'Cloning front matter', 'pressbooks' ) );
			$new_frontmatter = $this->cloneFrontMatter( $frontmatter['id'] );
			if ( $new_frontmatter !== false ) {
				$this->clonedItems['front-matter'][] = $new_frontmatter;
				$this->idPostsMap[ $frontmatter['id'] ] = $new_frontmatter;
			}
		}

		// Clone Parts and chapters
		$ticks = 0;
		foreach ( $this->sourceBookStructure['parts'] as $key => $part ) {
			$ticks += 1 + ( is_countable( $this->sourceBookStructure['parts'][ $key ]['chapters'] ) ? count( $this->sourceBookStructure['parts'][ $key ]['chapters'] ) : 0 );
		}
		$y = new PercentageYield( 50, 80, $ticks );
		foreach ( $this->sourceBookStructure['parts'] as $key => $part ) {
			yield from $y->tick( __( 'Cloning parts and chapters', 'pressbooks' ) );
			$new_part = $this->clonePart( $part['id'] );
			if ( $new_part !== false ) {
				$this->idPostsMap[ $part['id'] ] = $new_part;
				$this->clonedItems['parts'][] = $new_part;
				foreach ( $this->sourceBookStructure['parts'][ $key ]['chapters'] as $chapter ) {
					yield from $y->tick( __( 'Cloning parts and chapters', 'pressbooks' ) );
					$new_chapter = $this->cloneChapter( $chapter['id'], $new_part );
					if ( $new_chapter !== false ) {
						$this->clonedItems['chapters'][] = $new_chapter;
						$this->idPostsMap[ $chapter['id'] ] = $new_chapter;
					}
				}
			}
		}

		// Clone Back Matter
		$y = new PercentageYield( 80, 90, is_countable( $this->sourceBookStructure['back-matter'] ) ? count( $this->sourceBookStructure['back-matter'] ) : 0 );
		foreach ( $this->sourceBookStructure['back-matter'] as $backmatter ) {
			yield from $y->tick( __( 'Cloning back matter', 'pressbooks' ) );
			$new_backmatter = $this->cloneBackMatter( $backmatter['id'] );
			if ( $new_backmatter !== false ) {
				$this->clonedItems['back-matter'][] = $new_backmatter;
				$this->idPostsMap[ $backmatter['id'] ] = $new_backmatter;
			}
		}

		// Switch theme and clone custom styles
		$this->clonedItems['theme'] = false;
		if ( $this->switchTheme() ) {
			$this->clonedItems['theme'] = true;
			$this->cloneThemeOptions();
			$this->clonedItems['styles'] = $this->cloneStyles();
		}

		// Clone Glossary
		$y = new PercentageYield( 90, 100, count( (array) $this->sourceBookGlossary ) );
		foreach ( $this->sourceBookGlossary as $glossary ) {
			yield from $y->tick( __( 'Cloning glossary terms' ) );
			$new_glossary = $this->cloneGlossary( $glossary['id'] );
			if ( $new_glossary !== false ) {
				$this->clonedItems['glossary'][] = $new_glossary;
			}
		}

		// Post-processor
		$this->clonePostProcess();

		wp_defer_term_counting( false ); // Flush
		restore_current_blog();

		yield 100 => __( 'Finishing up', 'pressbooks' );
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

		// Media
		$this->knownMedia = $this->buildListOfKnownMedia( $this->sourceBookUrl );
		if ( $this->knownMedia === false ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'Could not retrieve media from %s.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->sourceBookMetadata['name'] ) );
			$this->maybeRestoreCurrentBlog();
			return false;
		}
		// Sort by the length of sourceUrls for better, left to right, search and replace loops
		$known_media_sorted = $this->knownMedia;
		uasort(
			$known_media_sorted, fn( $a, $b) => strlen( $b->sourceUrl ) <=> strlen( $a->sourceUrl )
		);
		$this->knownMedia = $known_media_sorted;

		// H5P
		$this->knownH5P = $this->buildListOfKnownH5P( $this->sourceBookUrl );
		if ( $this->knownH5P === false ) {
			// No H5P endpoint was found
			$this->knownH5P = [];
		} else {
			$this->sourceHasH5pApi = true;
		}

		// Set up $this->sourceBookGlossary
		$this->sourceBookGlossary = $this->getBookGlossary( $this->sourceBookUrl );

		// Styles
		$this->sourceStyles = $this->getBookStyles( $this->sourceBookUrl );

		// Theme options
		$this->sourceTheme = $this->getBookTheme( $this->sourceBookUrl );

		$this->maybeRestoreCurrentBlog();
		return true;
	}

	/**
	 * Pre-processor
	 */
	public function clonePreProcess() {
		// H5P
		if ( $this->sourceHasH5pApi ) {
			$this->targetHasH5pApi = $this->h5p->activate();
		}
	}

	/**
	 * Clone term from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $term_id The ID of the term within the source book.
	 *
	 * @return bool | int False if creating a new term failed; the ID of the new term if it the clone succeeded or the ID of a matching term if it exists.
	 */
	public function cloneTerm( $term_id ): bool | int {
		$term = [];
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
		$request->set_param( '_fields', 'id' );
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
	 *
	 * @return bool | int False if the clone failed; the ID of the new front matter if it succeeded.
	 */
	public function cloneFrontMatter( $id ): bool | int {
		return $this->cloneSection( $id, 'front-matter' );
	}

	/**
	 * Clone a part from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the part within the source book.
	 *
	 * @return bool | int False if the clone failed; the ID of the new part if it succeeded.
	 */
	public function clonePart( $id ): bool | int {
		return $this->cloneSection( $id, 'part' );
	}

	/**
	 * Clone a chapter from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the chapter within the source book.
	 * @param int $part_id The ID of the part to which the chapter should be added within the target book.
	 *
	 * @return bool | int False if the clone failed; the ID of the new chapter if it succeeded.
	 */
	public function cloneChapter( $id, $part_id ): bool | int {
		return $this->cloneSection( $id, 'chapter', $part_id );
	}

	/**
	 * Clone back matter from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the back matter within the source book.
	 *
	 * @return bool | int False if the clone failed; the ID of the new back matter if it succeeded.
	 */
	public function cloneBackMatter( $id ): bool | int {
		return $this->cloneSection( $id, 'back-matter' );
	}

	/**
	 * Clone glossary from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The ID of the back matter within the source book.
	 *
	 * @return bool | int False if the clone failed; the ID of the new back matter if it succeeded.
	 */
	public function cloneGlossary( $id ): bool | int {
		return $this->cloneSection( $id, 'glossary' );
	}

	/**
	 * Post-processor
	 */
	public function clonePostProcess() {
		$this->fixInternalShortcodes();
		// H5P
		if ( $this->sourceHasH5p === true && ( $this->sourceHasH5pApi === false || $this->targetHasH5pApi === false || $this->targetHasFetchedAllTheH5p === false ) ) {
			// Add a notice to the user indicating that the H5P could not be cloned
			\Pressbooks\add_notice( __( 'The source book contained H5P content that could not be cloned. Please review the cloned version of your book carefully, as missing H5P content will be indicated. You may want to remove or replace these elements.', 'pressbooks' ) );
		}
	}

	/**
	 * Use media endpoint to build an array of known media
	 *
	 * @param string $url The URL of the book.
	 *
	 * @return bool|\Pressbooks\Entities\Cloner\Media[] False if the operation failed; known images assoc array if succeeded.
	 */
	public function buildListOfKnownMedia( string $url ): bool | array {
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
			if ( $item['media_type'] === 'image' && $item['mime_type'] !== 'image/svg+xml' ) {
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
	 * @param $url
	 *
	 * @return bool|\Pressbooks\Entities\Cloner\H5P[]  False if the operation failed; known H5P array if succeeded.
	 */
	public function buildListOfKnownH5P( $url ): bool | array {
		$response = $this->handleGetRequest( $url, 'h5p/v1', 'all' );
		if ( is_wp_error( $response ) || @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			return false;
		}

		$known_h5p = [];
		foreach ( $response as $item ) {
			$known_h5p[] = $this->createH5PEntity( $item );
		}
		return $known_h5p;
	}

	/**
	 * When cloning from one book to another, the IDs change
	 * Use this method to add a transition, that we can do something with later, if needed
	 *
	 * @param string $type
	 * @param int $old_id
	 * @param int $new_id
	 */
	public function createTransition( $type, $old_id, $new_id ) {
		$transition = new  \Pressbooks\Entities\Cloner\Transition();
		$transition->type = $type;
		$transition->oldId = $old_id;
		$transition->newId = $new_id;
		$this->transitions[] = $transition;
	}

	/**
	 * Fetch an array containing the styles of a book.
	 *
	 * @param string $url
	 * @return array
	 * @throws \JsonException
	 */
	public function getBookStyles( string $url ) : array {
		$response = $this->handleGetRequest( $url, 'pressbooks/v2', 'styles' );
		return is_wp_error( $response ) ? [] : $response;
	}

	/**
	 * Fetch an array containing the theme information of a book.
	 *
	 * @param string $url
	 * @return array
	 * @throws \JsonException
	 */
	public function getBookTheme( string $url ) : array {
		$response = $this->handleGetRequest( $url, 'pressbooks/v2', 'theme' );
		return is_wp_error( $response ) ? [] : $response;
	}

	/**
	 * Fetch an array containing the metadata of a book.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url The URL of the book.
	 *
	 * @return bool | array False if the operation failed; the metadata array if it succeeded.
	 */
	public function getBookMetadata( $url ): bool | array {
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
	 *
	 * @return bool | array False if the operation failed; the structure and contents array if it succeeded.
	 */
	public function getBookStructure( $url ): bool | array {
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
	 *
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
	public function isSourceCloneable( $metadata_license ): bool {
		if ( has_filter( 'pb_set_source_clonable' ) && apply_filters( 'pb_set_source_clonable', [] ) ) {
			return true;
		}

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
		} elseif ( $post_type === 'glossary' ) {
			foreach ( $this->sourceBookGlossary as $k => $v ) {
				if ( $v['id'] === absint( $section_id ) && isset( $v['metadata'] ) ) {
					return $v['metadata'];
				}
			}
		}
		return []; // Nothing was found
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
	public function discoverWordPressApi( $url ): string | false {

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
				if ( str_contains( $link, 'rel="https://api.w.org/"' ) || str_contains( $link, "rel='https://api.w.org/'" ) ) {
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
	 * Get a book ID from its URL.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url
	 *
	 * @return int 0 of no blog was found, or the ID of the matched book.
	 */
	public function getBookId( $url ) {
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
	public function getSubdomainOrSubDirectory( $url ) {
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
	 * When creating a new book as the target of a clone operation, this function removes
	 * default front matter, parts, chapters and back matter from the book creation routines.
	 *
	 * @since 4.1.0
	 * @see apply_filters( 'pb_default_book_content', ... )
	 *
	 * @param array $contents The default book contents
	 *
	 * @return array The filtered book contents
	 */
	public function removeDefaultBookContent( $contents ) {
		foreach (
			[
				'introduction',
				'main-body',
				'chapter-1',
				'appendix',
			] as $post
		) {
			unset( $contents[ $post ] );
		}
		return $contents;
	}

	/**
	 * @param array $item
	 *
	 * @return \Pressbooks\Entities\Cloner\Media
	 */
	protected function createMediaEntity( $item ) {
		$m = new \Pressbooks\Entities\Cloner\Media();
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
	 * @param array $item
	 *
	 * @return \Pressbooks\Entities\Cloner\H5P
	 */
	protected function createH5PEntity( $item ) {
		$h5p = new \Pressbooks\Entities\Cloner\H5P();
		if ( isset( $item['id'] ) ) {
			$h5p->id = $item['id'];
		}
		if ( isset( $item['url'] ) ) {
			$h5p->url = $item['url'];
		}
		return $h5p;
	}

	/**
	 * Check if post content contains shortcodes with references to internal IDs that we will need to fix
	 *
	 * @param int $post_id
	 * @param string $html
	 */
	protected function checkInternalShortcodes( $post_id, $html ) {
		// Glossary
		if ( has_shortcode( $html, \Pressbooks\Shortcodes\Glossary\Glossary::SHORTCODE ) ) {
			$this->postsWithGlossaryShortcodesToFix[] = $post_id;
		}
		// Attachments
		if ( has_shortcode( $html, \Pressbooks\Shortcodes\Attributions\Attachments::SHORTCODE ) ) {
			$this->postsWithAttachmentsShortcodesToFix[] = $post_id;
		}
		// H5P
		if ( has_shortcode( $html, \Pressbooks\Interactive\H5P::SHORTCODE ) ) {
			$this->postsWithH5PShortcodesToFix[] = $post_id;
			$this->sourceHasH5p = true;
		}
	}

	/**
	 * Fix shortcodes with references to internal IDs
	 */
	protected function fixInternalShortcodes() {
		// Because $fix replaces left to right, it might replace a previously inserted value when doing multiple replacements.
		// Solved by creating a placeholder that can't possibly fall into the replacement order gotcha (famous last words)
		$fix = function ( $post_id, $transition_type, $shortcode ) {
			$replace_pairs = [];
			$post = get_post( $post_id );
			foreach ( $this->transitions as $transition ) {
				if ( $transition->type === $transition_type ) {
					$md5 = md5( $transition->oldId . $transition->newId . wp_rand() );
					$to = "<!-- pb_fixme_{$md5} -->";
					$replace_pairs[ $to ] = $transition->newId;
					$post->post_content = \Pressbooks\Utility\shortcode_att_replace(
						$post->post_content,
						$shortcode,
						'id',
						$transition->oldId,
						$to
					);
				}
			}
			if ( ! empty( $replace_pairs ) ) {
				$post->post_content = strtr( $post->post_content, $replace_pairs );
				wp_update_post( $post );
			}
		};

		// Glossary
		foreach ( $this->postsWithGlossaryShortcodesToFix as $post_id ) {
			$fix( $post_id, 'glossary', \Pressbooks\Shortcodes\Glossary\Glossary::SHORTCODE );
		}
		// Attachments
		foreach ( $this->postsWithAttachmentsShortcodesToFix as $post_id ) {
			$fix( $post_id, 'attachment', \Pressbooks\Shortcodes\Attributions\Attachments::SHORTCODE );
		}
		// H5P
		foreach ( $this->postsWithH5PShortcodesToFix as $post_id ) {
			$fix( $post_id, 'h5p', \Pressbooks\Interactive\H5P::SHORTCODE );
		}
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
	protected function createBook(): bool | int {
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
			'pb_redirect_to_new_book', fn() => false
		);
		// Remove default content so that the book only contains the results of the clone operation
		add_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		$result = wpmu_create_blog( $domain, $path, $this->targetBookTitle, $user_id );
		remove_all_filters( 'pb_redirect_to_new_book' );
		remove_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Switch to target book theme only the theme is available and the version matches.
	 *
	 * @return bool
	 */
	public function switchTheme() : bool {
		if ( empty( $this->sourceTheme ) ) {
			return false;
		}
		$theme = wp_get_theme( $this->sourceTheme['stylesheet'] );
		if ( ! $theme->exists() || $theme->get( 'Version' ) !== $this->sourceTheme['version'] ) {
			return false;
		}
		switch_theme( $this->sourceTheme['stylesheet'] );
		return true;
	}

	/**
	 * Clone Theme Options to the targe book.
	 *
	 * @return void
	 */
	public function cloneThemeOptions() : void {
		$clonable_options_classes = [
			'\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'\Pressbooks\Modules\ThemeOptions\WebOptions',
			'\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'\Pressbooks\Modules\ThemeOptions\EbookOptions',
		];

		if (
			isset( $this->sourceTheme['options']['ebook']['ebook_start_point'] ) &&
			isset( $this->idPostsMap[ $this->sourceTheme['options']['ebook']['ebook_start_point'] ] )
		) {
			$this->sourceTheme['options']['ebook']['ebook_start_point'] =
				$this->idPostsMap[ $this->sourceTheme['options']['ebook']['ebook_start_point'] ];
		}

		foreach ( $clonable_options_classes as $option_class ) {
			$slug = call_user_func( $option_class . '::getSlug' );
			if ( isset( $this->sourceTheme['options'][ $slug ] ) ) {
				update_option( 'pressbooks_theme_options_' . $slug, $this->sourceTheme['options'][ $slug ] );
			}
		}
		add_option( self::THEME_OPTIONS_CLONED_OPTION, 1 );
	}

	/**
	 * Get source theme
	 *
	 * @return array
	 */
	public function getSourceTheme() : array {
		return $this->sourceTheme;
	}

	/**
	 * Clone book styles to the target book only if the theme and theme options were cloned.
	 *
	 * @return bool
	 */
	public function cloneStyles() : bool {
		if ( empty( $this->sourceStyles ) || ! $this->clonedItems['theme'] ) {
			return false;
		}
		$styles_container = Container::getInstance()->get( 'Styles' );
		$styles_container->registerPosts();
		$styles_container->initPosts();
		foreach ( $styles_container->getSupported() as $slug => $style_type ) {
			if ( isset( $this->sourceStyles[ $slug ] ) ) {
				$post = $styles_container->getPost( $slug );
				$post_params = [
					'ID' => $post->ID,
					'post_content' => $this->sourceStyles[ $slug ],
				];
				wp_update_post( $post_params, true );
			}
		}
		return true;
	}

	/**
	 * Clone book information to the target book.
	 *
	 * @since 4.1.0
	 *
	 * @return bool | int False if the creation failed; the ID of the new book's book information post if it succeeded.
	 */
	protected function cloneMetadata(): bool | int {
		$metadata_post_id = ( new \Pressbooks\Metadata )->getMetaPostId();

		if ( ! $metadata_post_id ) {
			return false;
		}

		$book_information = schema_to_book_information( $this->sourceBookMetadata );
		// Do not clone ISBN and ebook DOI. https://github.com/pressbooks/pressbooks/issues/1609
		$book_information['pb_ebook_isbn'] = '';
		$book_information['pb_book_doi'] = '';

		// Cover image
		if ( ! \Pressbooks\Image\is_default_cover( $book_information['pb_cover_image'] ) ) {
			$new_cover_id = $this->downloads->fetchAndSaveUniqueImage( $book_information['pb_cover_image'] );
			if ( $new_cover_id > 0 ) {
				$this->clonedItems['media'][] = $new_cover_id; // Counter
				$book_information['pb_cover_image'] = wp_get_attachment_url( $new_cover_id );
			} else {
				$book_information['pb_cover_image'] = default_cover_url();
			}
		} else {
			$book_information['pb_cover_image'] = default_cover_url();
		}

		// Everything else
		$book_information['pb_is_based_on'] = $this->sourceBookUrl;
		$metadata_array_values = [ 'pb_keywords_tags', 'pb_bisac_subject', 'pb_additional_subjects', 'pb_institutions' ];
		$authors_slug = [];
		foreach ( $book_information as $key => $value ) {
			if ( $this->contributors->isValid( $key ) ) {
				foreach ( $value as $contributor_data ) {
					// Compatibility with previous contributors metadata format
					if ( ! isset( $contributor_data['slug'] ) ) {
						$contributor_data['slug'] = sanitize_title_with_dashes( remove_accents( $contributor_data['name'] ), '', 'save' );
					}
					$this->contributors->insert( $contributor_data, $metadata_post_id, $key, $this->downloads, 'slug' );
					if ( $key === 'pb_authors' ) {
						$authors_slug[] = $contributor_data['slug'];
					}
				}
			} elseif ( in_array( $key, $metadata_array_values, true ) ) {
				$values = is_array( $value ) ? $value : explode( ', ', $value );
				foreach ( $values as $v ) {
					add_post_meta( $metadata_post_id, $key, $v );
				}
			} elseif ( $key === 'pb_title' ) {
				update_post_meta( $metadata_post_id, $key, $this->targetBookTitle );
			} else {
				update_post_meta( $metadata_post_id, $key, $value );
				if ( $key === 'pb_book_license' ) {
					wp_set_object_terms( $metadata_post_id, $value, \Pressbooks\Licensing::TAXONOMY ); // Link
				}
			}
		}

		$user_data = get_userdata( get_current_user_id() );
		if ( ! in_array( $user_data->user_nicename, $authors_slug, true ) ) {
			// Remove the current user from the author field in Book Info if it is not the author of the source book
			$this->contributors->unlink( $user_data->user_nicename, $metadata_post_id );
		}

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
	 *
	 * @return bool | int False if the clone failed; the ID of the new section if it succeeded.
	 */
	protected function cloneSection( $section_id, $post_type, $parent_id = null ): bool | int {

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

		// Private and public glossaries can be cloned
		if ( $post_type !== 'glossary' ) {
			$section['status'] ??= 'publish';
		}

		// Download media (images, videos, `select * from wp_posts where post_type = 'attachment'` ... )
		[$content, $attachments] = $this->retrieveSectionContent( $section );
		// Download H5P
		$content = $this->retrieveH5P( $content );

		// Set title and content
		$section['title'] = $section['title']['raw'] ?? $section['title']['rendered'];
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

		if ( array_key_exists( 'pb_part_invisible', $section['meta'] ) ) {
			unset( $section['meta']['pb_part_invisible'] );
		}

		// POST internal request
		$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
		$request->set_body_params( $section );
		$request->set_param( '_fields', 'id' );
		$response = rest_do_request( $request )->get_data();

		// Inform user of failure, bail
		if ( is_wp_error( $response ) || @$response['data']['status'] >= 400 ) { // @codingStandardsIgnoreLine
			return false;
		}

		// Set pb_is_based_on property
		update_post_meta( $response['id'], 'pb_is_based_on', $permalink );
		if ( isset( $section['meta']['pb_part_invisible_string'] ) && $section['meta']['pb_part_invisible_string'] === 'on' ) {
			update_post_meta( $response['id'], 'pb_part_invisible', 'on' );
		}

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
		$this->createTransition( $post_type, $section_id, $response['id'] );

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
		if ( array_key_exists( 'raw', $section['content'] ) ) {
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
		$html5 = new \Pressbooks\HtmlParser();
		$dom = $html5->loadHTML( $source_content );

		// Download images, change image paths
		$media = $this->downloads->scrapeAndKneadImages( $dom );
		$dom = $media['dom'];
		$attachments = $media['attachments'];

		// Download media, change media paths
		$media = $this->downloads->scrapeAndKneadMedia( $dom, $html5->parser );
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

		// Count attachments
		foreach ( $attachments as $pid ) {
			$this->clonedItems['media'][] = $pid;
		}

		return [ trim( $content ), $attachments ];
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	protected function retrieveH5P( $content ) {
		if ( $this->sourceHasH5pApi && $this->targetHasH5pApi ) {
			// Download H5P
			$h5p_ids = $this->downloads->h5p( $content );
			foreach ( $h5p_ids as $h5p_id ) {
				if ( str_starts_with( $h5p_id, '#fixme' ) ) {
					// Flag problem, remove broken H5P shortcode
					$this->targetHasFetchedAllTheH5p = false;
					$h5p_id = str_remove_prefix( $h5p_id, '#fixme' );
					$content = $this->h5p->replaceUncloneable( $content, $h5p_id );
				} else {
					$this->clonedItems['h5p'][] = $h5p_id;
				}
			}
		} else {
			// Remove all H5P shortcodes
			$content = $this->h5p->replaceUncloneable( $content );
		}
		return $content;
	}

	/**
	 * Clone metadata of a section (front matter, part, chapter, back matter) from a source book to a target book.
	 *
	 * @since 4.1.0
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section.
	 * @param int $target_id The ID of the section within the target book.
	 *
	 * @return bool False if the clone failed; true if it succeeded.
	 */
	protected function cloneSectionMetadata( $section_id, $post_type, $target_id ) {

		$book_schema = $this->sourceBookMetadata;
		// Do not clone ISBN and ebook DOI. https://github.com/pressbooks/pressbooks/issues/1609
		$book_schema['isbn'] = '';
		$book_schema['identifier'] = [];

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
				foreach ( $value as $contributor_data ) {
					// Compatibility with previous contributors metadata format
					if ( ! isset( $contributor_data['slug'] ) ) {
						$contributor_data['slug'] = sanitize_title_with_dashes( remove_accents( $contributor_data['name'] ), '', 'save' );
					}
					if ( $this->isImporting && array_key_exists( $contributor_data['slug'], $this->contributorsInserted ) ) {
						$this->contributors->link( $this->contributorsInserted[ $contributor_data['slug'] ], $target_id, $key );
						continue;
					}
					$this->contributorsInserted[ $contributor_data['slug'] ] = $this->contributors->insert(
						$contributor_data,
						$target_id,
						$key,
						$this->downloads,
						$this->isImporting ? 'disambiguate' : 'slug'
					);
				}
			} else {
				update_post_meta( $target_id, $key, $value );
				if ( $key === 'pb_section_license' ) {
					wp_set_object_terms( $target_id, $value, \Pressbooks\Licensing::TAXONOMY ); // Link
				}
			}
		}

		return true;
	}

	/**
	 * Handle a get request against the REST API using either rest_do_request() or wp_remote_get() as appropriate.
	 *
	 * @param string $url The URL against which the request should be made (not including the REST base)
	 * @param string $namespace The namespace for the request, e.g. 'pressbooks/v2'
	 * @param string $endpoint The endpoint for the request, e.g. 'toc'
	 * @param array $params URL parameters
	 * @param bool $paginate (optional, if results are paginated then get next page)
	 * @param array $previous_results (optional, used recursively for when results are paginated)
	 * @throws \JsonException
	 * @since 4.1.0
	 */
	protected function handleGetRequest( $url, $namespace, $endpoint, $params = [], $paginate = true, $previous_results = [] ): array | \WP_Error {
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
				$results = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );
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
	 * @return string|false
	 */
	protected function nextWebLink( \WP_REST_Response | array $response ): string | false {
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
	 * @return string
	 */
	protected function extractLinkHeader( \WP_REST_Response | array $response ) {
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
			[$key, $value] = explode( '=', $part, 2 );
			$key = trim( $key );
			$value = trim( $value, '" ' );
			$attrs[ $key ] = $value;
		}
		return $attrs;
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
	 * Given a book name, see if we can use it to create a new book. Sort of like wpmu_validate_blog_signup().
	 *
	 * @since 4.1.0
	 *
	 * @param string $blogname
	 * @return array|\WP_Error
	 */
	public static function validateNewBookName( $blogname ): string | \WP_Error {
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
	 * Is clonning feature enabled for this network?
	 *
	 * @return bool
	 */
	public static function isEnabled() {
		$enable_cloning = SharingAndPrivacyOptions::getOption( 'enable_cloning' );
		return (bool) $enable_cloning;
	}

}
