<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\WordPress;

use function Pressbooks\Image\attachment_id_from_url;
use function Pressbooks\Image\strip_baseurl;
use Masterminds\HTML5;
use Pressbooks\Book;
use Pressbooks\Contributors;
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
	 * Array of known images, format: [ 2017/08/foo-bar-300x225.png ] => [ Fullsize URL ], ...
	 *
	 * @var array
	 */
	protected $knownImages = [];

	/**
	 * @var \Pressbooks\Contributors;
	 */
	protected $contributors;

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
		$supported_post_types = apply_filters( 'pb_import_custom_post_types', [ 'post', 'page', 'front-matter', 'chapter', 'part', 'back-matter', 'metadata' ] );

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
		$this->knownImages = $this->buildListOfKnownImages( $xml );

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
		];

		/**
		 * Allow custom post taxonomies to be imported.
		 *
		 * @since 3.6.0
		 *
		 * @param array $value
		 */
		$taxonomies = apply_filters( 'pb_import_custom_taxonomies', [ 'front-matter-type', 'chapter-type', 'back-matter-type' ] );

		$custom_post_types = apply_filters( 'pb_import_custom_post_types', [ 'post', 'page', 'front-matter', 'chapter', 'part', 'back-matter', 'metadata' ] );

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

			$doc = new HTML5();
			$html = $this->tidy( wpautop( $p['post_content'] ) );
			$dom = $doc->loadHtml( $html );
			$dom = $this->scrapeAndKneadImages( $dom );
			$html = $doc->saveHTML( $dom );

			$html = \Pressbooks\Sanitize\strip_container_tags( $html ); // Remove auto-created <html> <body> and <!DOCTYPE> tags.
			$html = shortcode_unautop( $html ); // Ensures that shortcodes are not wrapped in `<p>...</p>`.

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
		}

		wp_defer_term_counting( false ); // Flush

		// Done
		$_SESSION['pb_notices'][] =

			sprintf(
				_x( 'Imported %1$s, %2$s, %3$s, and %4$s.', 'String which tells user how many front matter, parts, chapters and back matter were imported.', 'pressbooks' ),
				$totals['front-matter'] . ' ' . __( 'front matter', 'pressbooks' ),
				( 1 === $totals['part'] ) ? $totals['part'] . ' ' . __( 'part', 'pressbooks' ) : $totals['part'] . ' ' . __( 'parts', 'pressbooks' ),
				( 1 === $totals['chapter'] ) ? $totals['chapter'] . ' ' . __( 'chapter', 'pressbooks' ) : $totals['chapter'] . ' ' . __( 'chapters', 'pressbooks' ),
				$totals['back-matter'] . ' ' . __( 'back matter', 'pressbooks' )
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
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ) {

		$images = $doc->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			// Fetch image, change src
			$src_old = $image->getAttribute( 'src' );

			$attachment_id = $this->fetchAndSaveUniqueImage( $src_old );

			if ( $attachment_id ) {
				$image->setAttribute( 'src', $this->replaceImage( $attachment_id, $src_old, $image ) );
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$src_old}#fixme" );
			}
		}

		return $doc;
	}


	/**
	 * Parse XML to build an array of known images
	 *
	 * @param array $xml
	 *
	 * @return array
	 */
	public function buildListOfKnownImages( $xml ) {

		$known_images = [];

		foreach ( $xml['posts'] as $item ) {

			if ( $item['post_type'] !== 'attachment' ) {
				continue; // Not an attachment, skip
			}
			if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $item['attachment_url'] ) ) {
				continue; // Not a supported image, skip
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

			$fullsize = $item['attachment_url'];
			$prefix = str_replace( $this->basename( $fullsize ), '', $x['file'] );
			foreach ( $x['sizes'] as $size => $info ) {
				$attached_file = $prefix . $info['file'];
				$known_images[ $attached_file ] = $fullsize;
			}
		}

		return $known_images;
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
	 * Load remote url of image into WP using media_handle_sideload()
	 * Will return an empty string if something went wrong.
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
			$already_done[ $remote_img_location ] = '';
			return 0;
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$already_done[ $remote_img_location ] = '';
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
				$already_done[ $remote_img_location ] = '';
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
		}
		$already_done[ $remote_img_location ] = $pid;
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
