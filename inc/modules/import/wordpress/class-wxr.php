<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\WordPress;

use function Pressbooks\Image\attachment_id_from_url;
use function Pressbooks\Image\strip_baseurl as image_strip_baseurl;
use function Pressbooks\Media\strip_baseurl as media_strip_baseurl;
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
	 *
	 */
	function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
		$this->contributors = new Contributors();
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
			$term = term_exists( $t['term_name'], $t['term_taxonomy'] );
			if ( null === $term || 0 === $term ) {
				$results = wp_insert_term(
					$t['term_name'],
					$t['term_taxonomy'],
					[
						'description' => $t['term_description'],
						'slug' => $t['slug'],
					]
				);
				if ( ! empty( $t['termmeta'] ) && is_array( $results ) ) {
					foreach ( $t['termmeta'] as $termmeta ) {
						add_term_meta( $results['term_id'], $termmeta['key'], $termmeta['value'], true );
					}
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
			$media = $this->scrapeAndKneadImages( $dom );
			$dom = $media['dom'];
			$attachments = $media['attachments'];

			// Download media, change media paths
			$media = $this->scrapeAndKneadMedia( $dom, $html5->parser );
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
			$this->transitions[] = $this->createTransition( $post_type, $p['post_id'], $pid );
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

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		return $pid;
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
			$meta_val = $this->searchForMetaValue( 'pb_authors', $p['postmeta'] );
			if ( $meta_val ) {
				// PB5 contributors (slugs)
				add_post_meta( $pid, 'pb_authors', $meta_val );
				wp_set_object_terms( $pid, $meta_val, Contributors::TAXONOMY );
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
				add_post_meta( $pid, $meta['key'], $meta['value'] );
				wp_set_object_terms( $pid, $meta['value'], Contributors::TAXONOMY );
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

		foreach ( $postmeta as $meta ) {
			// prefer this value, if it's set
			if ( $meta_key === $meta['key'] ) {
				$meta_val = $meta['value'];
				if ( is_serialized( $meta_val ) ) {
					$meta_val = unserialize( $meta_val ); // @codingStandardsIgnoreLine
					if ( is_object( $meta_val ) ) {
						$meta_val = ''; // Hack attempt?
					}
				}
				return $meta_val;
			}
		}

		return '';
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
					$x = maybe_unserialize( $meta['value'] );
					break;
				}
			}
			if ( ! is_array( $x ) || empty( $x ) ) {
				continue; // Something went wrong, skip
			}

			$m = $this->createMediaEntity( $item );
			if ( preg_match( $this->pregSupportedImageExtensions, $m->sourceUrl ) ) {
				$prefix = str_replace( $this->basename( $m->sourceUrl ), '', $x['file'] ); // 2017/08
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
	 * @param string $type
	 * @param int $old_id
	 * @param int $new_id
	 *
	 * @return \Pressbooks\Entities\Cloner\Transition
	 */
	protected function createTransition( $type, $old_id, $new_id ) {
		$transition = new \Pressbooks\Entities\Cloner\Transition();
		$transition->type = $type;
		$transition->oldId = $old_id;
		$transition->newId = $new_id;
		return $transition;
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
						\Pressbooks\Shortcodes\Glossary\Glossary::SHORTCODE,
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
						\Pressbooks\Shortcodes\Attributions\Attachments::SHORTCODE,
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
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
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
	 * Will return -1 if image is not hosted on the source Pb network, or 0 if something went wrong.
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
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = '';
			return 0;
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = '';
			return 0;
		}

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, don't import
				$this->imageWasAlreadyDownloaded[ $remote_img_location ] = '';
				unlink( $tmp_name );
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
				$m = $this->knownMedia[ $attached_file ];
				wp_update_post(
					[
						'ID' => $pid,
						'post_title' => $m->title,
						'post_content' => $m->description,
						'post_excerpt' => $m->caption,
					]
				);
				update_post_meta( $pid, '_wp_attachment_image_alt', $m->altText );
				foreach ( $m->meta as $meta_key => $meta_value ) {
					update_post_meta( $pid, $meta_key, $meta_value );
				}
				// Store a transitional state
				$this->transitions[] = $this->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = $pid;
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
			$maybe_src_new = \Pressbooks\Utility\str_lreplace( $basename_new, $basename_old, $src_new );
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
				$m = $this->knownMedia[ $attached_file ];
				wp_update_post(
					[
						'ID' => $pid,
						'post_title' => $m->title,
						'post_content' => $m->description,
						'post_excerpt' => $m->caption,
					]
				);
				foreach ( $m->meta as $meta_key => $meta_value ) {
					update_post_meta( $pid, $meta_key, $meta_value );
				}
				// Store a transitional state
				$this->transitions[] = $this->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->mediaWasAlreadyDownloaded[ $remote_media_location ] = $pid;
		}
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $pid;
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
