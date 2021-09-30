<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\WordPress;

use function Pressbooks\Media\strip_baseurl as media_strip_baseurl;
use function Pressbooks\Sanitize\maybe_safer_unserialize;
use function Pressbooks\Sanitize\safer_unserialize;
use function Pressbooks\Utility\str_starts_with;
use Pressbooks\Book;
use Pressbooks\Contributors;
use Pressbooks\HtmlParser;
use Pressbooks\Licensing;
use Pressbooks\Metadata;
use Pressbooks\Modules\Import\Import;

class Wxr extends Import {

	const TYPE_OF = 'wxr';

	/**
	 * If Pressbooks generated the WXR file
	 *
	 * @var boolean
	 */
	protected $isPbWxr = false;

	/**
	 * The URL of the source book.
	 *
	 * @var string
	 */
	protected $sourceBookUrl;

	/**
	 * Array of known media
	 *
	 * @var \Pressbooks\Entities\Cloner\Media[]
	 */
	protected $knownMedia = [];

	/**
	 * @var \Pressbooks\Contributors;
	 */
	protected $contributors;

	/**
	 * @var Downloads;
	 */
	protected $downloads;

	/**
	 * @var \Pressbooks\Entities\Cloner\Transition[]
	 */
	protected $transitions;

	/**
	 * This variable will help us to match new created slugs for imported contributors
	 * @var array
	 */
	protected $contributorsSlugsToFix = [];

	/**
	 * @var int[]
	 */
	protected $postsWithGlossaryShortcodesToFix = [];

	/**
	 * @var int[]
	 */
	protected $postsWithAttachmentsShortcodesToFix = [];

	/**
	 *
	 */
	function __construct() {
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
	 * @param null Downloads $downloads
	 * @param null \Pressbooks\Contributors $contributors
	 */
	public function dependencies( $downloads = null, $contributors = null ) {
		$this->downloads = $downloads ? $downloads : new Downloads( $this );
		$this->contributors = $contributors ? $contributors : new Contributors();
	}

	/**
	 * @return \Pressbooks\Entities\Cloner\Media[]
	 */
	public function getKnownMedia() {
		return $this->knownMedia;
	}

	/**
	 * @return string
	 */
	public function getSourceBookUrl() {
		return $this->sourceBookUrl;
	}

	/**
	 * @param $sourceBookUrl
	 */
	public function setSourceBookUrl( $source ) {
		$this->sourceBookUrl = $source;
	}

	/**
	 * @param array $upload
	 *
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ) {

		try {
			$parser = new Parser();
			$xml = $parser->parse( $upload['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$this->pbCheck( $xml );

		$this->sourceBookUrl = $xml['base_url'];

		$option = [
			'file' => $upload['file'],
			'url' => $upload['url'] ?? null,
			'file_type' => $upload['type'],
			'type_of' => self::TYPE_OF,
			'chapters' => [],
			'post_types' => [],
			'allow_parts' => true,
		];

		/**
		 * Allow custom post types to be imported.
		 *
		 * @since 3.6.0
		 *
		 * @param array $value
		 */
		$supported_post_types = apply_filters( 'pb_import_custom_post_types', [ 'post', 'page', 'front-matter', 'chapter', 'part', 'back-matter', 'metadata', 'glossary' ] );

		if ( $this->isPbWxr ) {
			//put the posts in correct part / menu_order order
			$xml['posts'] = $this->customNestedSort( $xml['posts'] );
		}

		foreach ( $xml['posts'] as $p ) {

			// Skip unsupported post types.
			if ( ! in_array( $p['post_type'], $supported_post_types, true ) ) {
				continue;
			}

			// Skip deleted post types.
			if ( $p['status'] === 'trash' ) {
				continue;
			}

			// Skip webbook required pages.
			if ( '<!-- Here be dragons.-->' === $p['post_content'] || '<!-- Here be dragons. -->' === $p['post_content'] ) {
				continue;
			}

			// Set
			$option['chapters'][ $p['post_id'] ] = $p['post_title'];
			$option['post_types'][ $p['post_id'] ] = $p['post_type'];
		}

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 * This function save a term into the database
	 * @param $term
	 * @param false $override_slug when true it will add a random string at the end to disambiguate from existent terms
	 * @return array|int[]|\WP_Error
	 */
	private function saveTerm( $term, $override_slug = false ) {
		return wp_insert_term(
			$term['term_name'],
			$term['term_taxonomy'],
			[
				'description' => $term['term_description'],
				'slug' => $override_slug === false ? $term['slug'] : $term['slug'] . '-' . str_random( 10 ),
			]
		);
	}

	/**
	 * Save Term meta like contributors_first_name
	 * @param $term
	 * @param $last_inserted
	 */
	private function saveMeta( $term, $last_inserted ) {
		if ( ! empty( $term['termmeta'] ) && is_array( $last_inserted ) ) {
			foreach ( $term['termmeta'] as $termmeta ) {
				$value = $termmeta['value'];
				// Copy contributor_picture from remote server to local
				if ( $termmeta['key'] === 'contributor_picture' && $value ) {
					$image_id = $this->downloads->fetchAndSaveUniqueImage( $value );
					if ( $image_id > 0 ) {
						$value = wp_get_attachment_url( $image_id );
					}
				}
				add_term_meta( $last_inserted['term_id'], $termmeta['key'], $value, true );
			}
		}
	}

	/**
	 * Insert a term with the meta associated
	 * @param $term
	 * @return array|int[]|mixed|\WP_Error
	 */
	public function insertTerm( $term ) {

		$last_inserted = $this->saveTerm( $term );

		if ( is_wp_error( $last_inserted ) ) { // trying to insert with a different disambiguation slug
			$last_inserted = $this->saveTerm( $term, true );
		}

		$this->saveMeta( $term, $last_inserted );

		return $last_inserted;
	}


	/**
	 * @param array $current_import
	 *
	 * @return bool
	 */
	function import( array $current_import ) {

		try {
			$parser = new Parser();
			$xml = $parser->parse( $current_import['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		wp_defer_term_counting( true );

		$this->pbCheck( $xml );

		$this->sourceBookUrl = $xml['base_url'];
		$this->knownMedia = $this->buildListOfKnownMedia( $xml );
		// Sort by the length of sourceUrls for better search and replace
		$known_media_sorted = $this->knownMedia;
		uasort(
			$known_media_sorted, function ( $a, $b ) {
				return strlen( $b->sourceUrl ) <=> strlen( $a->sourceUrl );
			}
		);
		$this->knownMedia = $known_media_sorted;

		if ( $this->isPbWxr ) {
			$xml['posts'] = $this->customNestedSort( $xml['posts'] );
		}

		$match_ids = array_flip( array_keys( $current_import['chapters'] ) );
		$chapter_parent = $this->getChapterParent();
		$totals = [
			'front-matter' => 0,
			'chapter' => 0,
			'part' => 0,
			'back-matter' => 0,
			'glossary' => 0,
			'media' => 0,
		];

		/**
		 * Allow custom post taxonomies to be imported.
		 *
		 * @since 3.6.0
		 *
		 * @param array $value
		 */
		$taxonomies = apply_filters( 'pb_import_custom_taxonomies', [ 'front-matter-type', 'chapter-type', 'back-matter-type', 'glossary-type' ] );

		$custom_post_types = apply_filters( 'pb_import_custom_post_types', [ 'post', 'page', 'front-matter', 'chapter', 'part', 'back-matter', 'metadata', 'glossary' ] );

		// set custom terms...
		$terms = apply_filters( 'pb_import_custom_terms', $xml['terms'] );

		// and import them if they don't already exist.
		foreach ( $terms as $t ) {
			if ( $t['term_taxonomy'] === Contributors::TAXONOMY ) {
				$term = $this->insertTerm( $t );
				$new_term = get_term( $term['term_id'], Contributors::TAXONOMY );
				$this->contributorsSlugsToFix[ $t['slug'] ] = is_wp_error( $term ) ? $t['slug'] : $new_term->slug; // fallback to found slug
			} else {
				$term = term_exists( $t['term_name'], $t['term_taxonomy'] );
				if ( ( null === $term || 0 === $term ) ) {
					$this->insertTerm( $t );
				}
			}
		}

		// -----------------------------------------------------------------------------
		// Import posts, start!
		// -----------------------------------------------------------------------------

		foreach ( $xml['posts'] as $p ) {

			// Skip
			if ( ! $this->flaggedForImport( $p['post_id'] ) ) {
				continue;
			}
			if ( ! isset( $match_ids[ $p['post_id'] ] ) ) {
				continue;
			}

			// Insert
			$post_type = $this->determinePostType( $p['post_id'] );

			$html5 = new HtmlParser();
			$dom = $html5->loadHtml( $p['post_content'] );

			// Download images, change image paths
			$media = $this->downloads->scrapeAndKneadImages( $dom );
			$dom = $media['dom'];
			$attachments = $media['attachments'];

			// Download media, change media paths
			$media = $this->downloads->scrapeAndKneadMedia( $dom, $html5->parser );
			$dom = $media['dom'];
			$attachments = array_merge( $attachments, $media['attachments'] );

			// TODO? We should probably do the same thing as seen in Cloner::fixInternalLinks( $dom )

			$html = $html5->saveHTML( $dom );

			if ( 'metadata' === $post_type ) {
				$pid = $this->bookInfoPid();
			} else {
				$pid = $this->insertNewPost( $post_type, $p, $html, $chapter_parent, $current_import['default_post_status'] );
				if ( 'part' === $post_type ) {
					$chapter_parent = $pid;
				}
			}

			// if this is a custom post type,
			// and it has terms associated with it...
			if ( ( in_array( $post_type, $custom_post_types, true ) && isset( $p['terms'] ) ) ) {
				// associate post with terms.
				foreach ( $p['terms'] as $t ) {
					if ( in_array( $t['domain'], $taxonomies, true ) ) {
						wp_set_object_terms(
							$pid,
							$t['slug'],
							$t['domain'],
							true
						);
					}
				}
			}

			if ( isset( $p['postmeta'] ) && is_array( $p['postmeta'] ) ) {
				if ( 'metadata' === $post_type ) {
					$this->importMetaBoxes( $pid, $p );
				} else {
					$this->importPbPostMeta( $pid, $p );
				}
			}

			Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
			if ( 'metadata' !== $post_type ) {
				++$totals[ $post_type ];
			}

			// Attach attachments to post
			foreach ( $attachments as $attachment ) {
				wp_update_post(
					[
						'ID' => $attachment,
						'post_parent' => $pid,
					]
				);
			}
			$totals['media'] = $totals['media'] + count( $attachments );

			// Shortcode hacker, no ease up tonight.
			$this->checkInternalShortcodes( $pid, $html );

			// Store a transitional state
			$this->createTransition( $post_type, $p['post_id'], $pid );
		}

		$this->fixInternalShortcodes();

		// -----------------------------------------------------------------------------
		// Import posts, done!
		// -----------------------------------------------------------------------------

		wp_defer_term_counting( false ); // Flush

		// Done
		$_SESSION['pb_notices'][] = sprintf(
			_x( 'Imported %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s.', 'String which tells user how many front matter, parts, chapters, back matter, media attachments, and glossary terms were imported.', 'pressbooks' ),
			sprintf( _n( '%s front matter', '%s front matter', $totals['front-matter'], 'pressbooks' ), $totals['front-matter'] ),
			sprintf( _n( '%s part', '%s parts', $totals['part'], 'pressbooks' ), $totals['part'] ),
			sprintf( _n( '%s chapter', '%s chapters', $totals['chapter'], 'pressbooks' ), $totals['chapter'] ),
			sprintf( _n( '%s back matter', '%s back matter', $totals['back-matter'], 'pressbooks' ), $totals['back-matter'] ),
			sprintf( _n( '%s media attachment', '%s media attachments', $totals['media'], 'pressbooks' ), $totals['media'] ),
			sprintf( _n( '%s glossary term', '%s glossary terms', $totals['glossary'], 'pressbooks' ), $totals['glossary'] )
		);

		return $this->revokeCurrentImport();
	}

	/**
	 * Is it a WXR generated by PB?
	 *
	 * @param array $xml
	 */
	protected function pbCheck( array $xml ) {

		$pt = 0;
		$ch = 0;
		$fm = 0;
		$bm = 0;
		$meta = 0;

		foreach ( $xml['posts'] as $p ) {

			if ( 'part' === $p['post_type'] ) {
				$pt = 1;
			} elseif ( 'chapter' === $p['post_type'] ) {
				$ch = 1;
			} elseif ( 'front-matter' === $p['post_type'] ) {
				$fm = 1;
			} elseif ( 'back-matter' === $p['post_type'] ) {
				$bm = 1;
			} elseif ( 'metadata' === $p['post_type'] ) {
				$meta = 1;
			}

			if ( $pt + $ch + $fm + $bm + $meta >= 2 ) {
				$this->isPbWxr = true;
				break;
			}
		}

	}

	/**
	 * Custom sort for the xml posts to put them in correct nested order
	 *
	 * @param array $xml
	 *
	 * @return array sorted $xml
	 */
	protected function customNestedSort( $xml ) {
		$array = [];

		//first, put them in ascending menu_order
		usort(
			$xml, function ( $a, $b ) {
				return ( $a['menu_order'] - $b['menu_order'] );
			}
		);

		// Start with book info
		foreach ( $xml as $p ) {
			if ( 'metadata' === $p['post_type'] ) {
				$array[] = $p;
				break;
			}
		}

		//now, list all front matter
		foreach ( $xml as $p ) {
			if ( 'front-matter' === $p['post_type'] ) {
				$array[] = $p;
			}
		}

		//now, list all parts, then their associated chapters
		foreach ( $xml as $p ) {
			if ( 'part' === $p['post_type'] ) {
				$array[] = $p;
				foreach ( $xml as $psub ) {
					if ( 'chapter' === $psub['post_type'] && $psub['post_parent'] === $p['post_id'] ) {
						$array[] = $psub;
					}
				}
			}
		}

		//now, list all back matter
		foreach ( $xml as $p ) {
			if ( 'back-matter' === $p['post_type'] ) {
				$array[] = $p;
			}
		}

		// Glossary
		foreach ( $xml as $p ) {
			if ( 'glossary' === $p['post_type'] ) {
				$array[] = $p;
			}
		}

		// Remaining custom post types
		$custom_post_types = apply_filters( 'pb_import_custom_post_types', [] );

		foreach ( $xml as $p ) {
			if ( in_array( $p['post_type'], $custom_post_types, true ) ) {
				$array[] = $p;
			}
		}

		return $array;
	}


	/**
	 * Get existing Meta Post, if none exists create one
	 *
	 * @return int Post ID
	 */
	protected function bookInfoPid() {

		$post = ( new Metadata() )->getMetaPost();
		if ( empty( $post->ID ) ) {
			$new_post = [
				'post_title' => __( 'Book Info', 'pressbooks' ),
				'post_type' => 'metadata',
				'post_status' => 'publish',
			];
			$pid = wp_insert_post( add_magic_quotes( $new_post ) );
		} else {
			$pid = $post->ID;
		}

		return $pid;
	}

	/**
	 * Insert a new post
	 *
	 * @param string $post_type Post Type
	 * @param array $p Single Item Returned From \Pressbooks\Modules\Import\WordPress\Parser::parse
	 * @param string $html
	 * @param int $chapter_parent
	 * @param string $post_status
	 *
	 * @return int Post ID
	 */
	protected function insertNewPost( $post_type, $p, $html, $chapter_parent, $post_status ) {

		$new_post = [
			'post_title' => wp_strip_all_tags( $p['post_title'] ),
			'post_name' => $p['post_name'],
			'post_type' => $post_type,
			'post_status' => ( 'part' === $post_type ) ? 'publish' : $post_status,
			'post_content' => $html,
		];

		if ( 'chapter' === $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		return wp_insert_post( add_magic_quotes( $new_post ) );
	}

	/**
	 * Import Pressbooks specific post meta
	 *
	 * @param int $pid Post ID
	 * @param array $p Single Item Returned From \Pressbooks\Modules\Import\WordPress\Parser::parse
	 */
	protected function importPbPostMeta( $pid, $p ) {

		$data_model = $this->figureOutDataModel( $p['postmeta'] );

		$meta_to_update = apply_filters( 'pb_import_metakeys', [ 'pb_section_license', 'pb_short_title', 'pb_subtitle', 'pb_show_title' ] );
		foreach ( $meta_to_update as $meta_key ) {
			$meta_val = $this->searchForMetaValue( $meta_key, $p['postmeta'] );
			if ( $meta_val ) {
				update_post_meta( $pid, $meta_key, $meta_val );
				if ( $meta_key === 'pb_section_license' ) {
					wp_set_object_terms( $pid, $meta_val, Licensing::TAXONOMY ); // Link
				}
			}
		}

		if ( $data_model === 5 ) {
			$meta_values = $this->searchMultipleContributorValues( 'pb_authors', $p['postmeta'] );
			if ( is_array( $meta_values ) ) {
				// PB5 contributors (slugs)
				foreach ( $meta_values as $slug ) {
					add_post_meta( $pid, 'pb_authors', $this->contributorsSlugsToFix[ $slug ] );
					wp_set_object_terms( $pid, $this->contributorsSlugsToFix[ $slug ], Contributors::TAXONOMY );
				}
			}
		} else {
			$meta_val = $this->searchForMetaValue( 'pb_section_author', $p['postmeta'] );
			if ( $meta_val ) {
				// PB4 contributors (full names)
				$this->contributors->convert( 'pb_section_author', $meta_val, $pid );
			}
		}
	}

	/**
	 * @see \Pressbooks\Admin\Metaboxes\add_meta_boxes
	 *
	 * @param int $pid Post ID
	 * @param array $p Single Item Returned From \Pressbooks\Modules\Import\WordPress\Parser::parse
	 */
	protected function importMetaBoxes( $pid, $p ) {

		$data_model = $this->figureOutDataModel( $p['postmeta'] );

		// List of meta data keys that can support multiple values:
		$metadata_array_values = [
			'pb_keywords_tags',
			'pb_bisac_subject',
			'pb_additional_subjects',
		];

		// Clear old meta boxes
		$metadata = get_post_meta( $pid );
		foreach ( $metadata as $key => $val ) {
			// Does key start with pb_ prefix?
			if ( 0 === strpos( $key, 'pb_' ) ) {
				delete_post_meta( $pid, $key );
			}
		}
		// Import contributors
		foreach ( $p['postmeta'] as $meta ) {
			if ( $data_model === 5 && $this->contributors->isValid( $meta['key'] ) ) {
				// PB5 contributors (slugs)
				$contributor = get_term_by( 'slug', $meta['value'], Contributors::TAXONOMY );
				add_post_meta( $pid, $meta['key'], $this->contributorsSlugsToFix[ $meta['value'] ] );
				wp_set_object_terms( $pid, $this->contributorsSlugsToFix[ $meta['value'] ], Contributors::TAXONOMY );
			} elseif ( $data_model === 4 && $this->contributors->isDeprecated( $meta['key'] ) ) {
				// PB4 contributors (full names)
				$this->contributors->convert( $meta['key'], $meta['value'], $pid );
			}
		}

		// Import post meta
		foreach ( $p['postmeta'] as $meta ) {
			if ( 0 !== strpos( $meta['key'], 'pb_' ) ) {
				continue; // Skip
			}
			// Skip contributor meta (already done, look up)
			if ( $this->contributors->isValid( $meta['key'] ) || $this->contributors->isDeprecated( $meta['key'] ) ) {
				continue;
			}

			if ( isset( $metadata_array_values[ $meta['key'] ] ) ) {
				// Multi value
				add_post_meta( $pid, $meta['key'], $meta['value'] );
			} else {
				// Single value
				if ( ! add_post_meta( $pid, $meta['key'], $meta['value'], true ) ) {
					update_post_meta( $pid, $meta['key'], $meta['value'] );
				}
				if ( $meta['key'] === 'pb_book_license' ) {
					wp_set_object_terms( $pid, $meta['value'], Licensing::TAXONOMY ); // Link
				}
			}
		}
	}

	/**
	 * Check for PB specific metadata, returns empty string if not found.
	 *
	 * @param string $meta_key
	 * @param array $postmeta
	 *
	 * @return string meta field value
	 */
	protected function searchForMetaValue( $meta_key, array $postmeta ) {

		if ( empty( $postmeta ) ) {
			return '';
		}

		$values = [];

		foreach ( $postmeta as $meta ) {
			// prefer this value, if it's set
			if ( $meta_key === $meta['key'] ) {
				return maybe_safer_unserialize( $meta['value'] );
			}
		}

		return '';
	}

	/**
	 * This method returns only the contributors in the postmeta array
	 *
	 * @param $meta_key
	 * @param array $postmeta
	 * @return array
	 */
	public function searchMultipleContributorValues( $meta_key, array $postmeta = [] ) {

		$values = [];

		foreach ( $postmeta as $meta ) {
			// prefer this value, if it's set
			if ( $this->contributors->isValid( $meta_key ) && $meta_key === $meta['key'] ) {
				$values[] = maybe_safer_unserialize( $meta['value'] );
			}
		}

		return $values;
	}

	/**
	 * Parse XML to build an array of known images
	 *
	 * @param array $xml
	 *
	 * @return \Pressbooks\Entities\Cloner\Media[]
	 */
	public function buildListOfKnownMedia( $xml ) {

		$known_media = [];

		foreach ( $xml['posts'] as $item ) {
			if ( $item['post_type'] !== 'attachment' ) {
				continue; // Not an attachment, skip
			}
			$x = [];
			foreach ( $item['postmeta'] as $meta ) {
				if ( $meta['key'] === '_wp_attachment_metadata' ) {
					$x = safer_unserialize( $meta['value'] );
					break;
				}
			}
			if ( ! is_array( $x ) || empty( $x ) ) {
				continue; // Something went wrong, skip
			}

			$m = $this->createMediaEntity( $item );
			if ( preg_match( $this->downloads->getPregSupportedImageExtensions(), $m->sourceUrl ) ) {
				$prefix = str_replace( $this->downloads->basename( $m->sourceUrl ), '', $x['file'] ); // 2017/08
				foreach ( $x['sizes'] as $size => $info ) {
					$attached_file = $prefix . $info['file']; // 2017/08/foo-bar-300x225.png
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
	 * @param array $item
	 *
	 * @return \Pressbooks\Entities\Cloner\Media
	 */
	protected function createMediaEntity( $item ) {
		$m = new \Pressbooks\Entities\Cloner\Media();

		$m->id = $item['post_id'];
		$m->title = $item['post_title'];
		$m->description = $item['post_content'];
		$m->caption = $item['post_excerpt'];

		if ( isset( $item['postmeta'] ) && is_array( $item['postmeta'] ) ) {
			foreach ( $item['postmeta'] as $meta ) {
				if ( str_starts_with( $meta['key'], '_' ) === false ) {
					$m->meta[ $meta['key'] ] = $meta['value'];
				}
				if ( $meta['key'] === '_wp_attachment_image_alt' ) {
					$m->altText = $meta['value'];
				}
			}
		}

		$m->sourceUrl = $item['attachment_url'];

		return $m;
	}

	/**
	 * When importing a book, the IDs change
	 * Use this method to add a transition, that we can do something with later, if needed
	 *
	 * @param string $type
	 * @param int $old_id
	 * @param int $new_id
	 */
	public function createTransition( $type, $old_id, $new_id ) {
		$transition = new \Pressbooks\Entities\Cloner\Transition();
		$transition->type = $type;
		$transition->oldId = $old_id;
		$transition->newId = $new_id;
		$this->transitions[] = $transition;
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
		// TODO
		//  H5P not supported in WXR imports
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
					$md5 = md5( $transition->oldId . $transition->newId . rand() );
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
		// TODO
		//  H5P not supported in WXR imports
	}

	/**
	 * For backwards-compatibility, some PB5 field names are pluralized so that any third-party code that looks for the old fields will still be able to retrieve them.
	 * That means both the old and new fields could still be in the XML. If we try to import both it causes buggy behaviour.
	 * This function helps us pick either/or.
	 *
	 * @param array $postmeta
	 *
	 * @return int
	 */
	protected function figureOutDataModel( $postmeta ) {

		foreach ( $this->contributors->valid as $contributor_type ) {
			if ( $this->searchForMetaValue( $contributor_type, $postmeta ) ) {
				return 5;
			};
		}

		foreach ( $this->contributors->deprecated as $contributor_type ) {
			if ( $this->searchForMetaValue( $contributor_type, $postmeta ) ) {
				return 4;
			};
		}

		// We found nothing? May as well use most recent version then...
		return 5;
	}

}
