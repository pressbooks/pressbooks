<?php

/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Export\Epub3;

use PressBooks\Export\Epub;
use PressBooks\Sanitize;

require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

class Epub3 extends Epub\Epub201 {

	/**
	 * @var string
	 */
	protected $filext = 'xhtml';

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_EPUBCHECK_COMMAND' ) )
				define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /opt/epubcheck/epubcheck.jar -v 3.0' );

		$this->tmpDir = $this->createTmpDir();
		$this->exportStylePath = $this->getExportStylePath( 'epub' );

		$this->themeOptionsOverrides();

		// HtmLawed: id values not allowed in input
		foreach ( $this->reservedIds as $val ) {
			$this->fixme[$val] = 1;
		}
	}

	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Make XHTML 1.1 strict using htmlLawed

		$config = array (
		    'valid_xhtml' => 1,
		    'no_deprecated_attr' => 2,
		    'unique_ids' => 'fixme-',
		    'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		    'tidy' => -1,
		);

		// Reset on each htmLawed invocation
		unset( $GLOBALS['hl_Ids'] );
		if ( ! empty( $this->fixme ) ) $GLOBALS['hl_Ids'] = $this->fixme;


		$spec = 'audio = src, preload, autoplay, mediagroup, loop, muted, controls; video = src, poster, preload, autoplay, mediagroup, loop, muted, controls, width, height; source = src, type, media; track = kind, src, srclang, label, default'; // all standards-permitted attributes are allowed in the elements

		return htmLawed( $html, $config, $spec );
	}

	/**
	 * Create Open Publication Structure 2.0.1 container.
	 */
	protected function createContainer() {

		file_put_contents(
			$this->tmpDir . '/mimetype', utf8_decode( 'application/epub+zip' ) );

		mkdir( $this->tmpDir . '/META-INF' );
		mkdir( $this->tmpDir . '/OEBPS' );
		mkdir( $this->tmpDir . '/OEBPS/images' );
		mkdir( $this->tmpDir . '/OEBPS/audios' );
		mkdir( $this->tmpDir . '/OEBPS/videos' );

		file_put_contents(
			$this->tmpDir . '/META-INF/container.xml', $this->loadTemplate( __DIR__ . '/templates/container.php' ) );
	}

	/**
	 * Parse CSS, copy assets, rewrite copy.
	 *
	 * @param string $path_to_original_stylesheet*
	 * @param string $path_to_copy_of_stylesheet
	 */
	protected function scrapeKneadAndSaveCss( $path_to_original_stylesheet, $path_to_copy_of_stylesheet ) {

		$css_dir = pathinfo( $path_to_original_stylesheet, PATHINFO_DIRNAME );
		$css = file_get_contents( $path_to_copy_of_stylesheet );
		$fullpath = $this->tmpDir . '/OEBPS/images';

		// Search for url("*"), url('*'), and url(*)
		preg_match_all( '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $css, $matches, PREG_PATTERN_ORDER );

		// Remove duplicates, sort by biggest to smallest to prevent substring replacements
		$matches = array_unique( $matches[3] );
		usort( $matches, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		foreach ( $matches as $url ) {
			$filename = sanitize_file_name( basename( $url ) );

			if ( preg_match( '#^images/#', $url ) && substr_count( $url, '/' ) == 1 ) {

				// Look for "^images/"
				// Count 1 slash so that we don't touch stuff like "^images/out/of/bounds/"	or "^images/../../denied/"

				$my_image = realpath( "$css_dir/$url" );
				if ( $my_image ) {
					copy( $my_image, "$fullpath/$filename" );
				}
			} elseif ( preg_match( '#^https?://#i', $url ) && preg_match( '/(\.jpe?g|\.gif|\.png)$/i', $url ) ) {

				// Look for images via http(s), pull them in locally

				if ( $new_filename = $this->fetchAndSaveUniqueImage( $url, $fullpath ) ) {
					$css = str_replace( $url, "images/$new_filename", $css );
				}
			}
		}

		// Overwrite the new file with new info
		file_put_contents( $path_to_copy_of_stylesheet, $css );
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createCover( $book_contents, $metadata ) {

		// Resize Image

		if ( ! empty( $metadata['pb_cover_image'] ) && ! \PressBooks\Image\is_default_cover( $metadata['pb_cover_image'] ) ) {
			$source_path = \PressBooks\Utility\get_media_path( $metadata['pb_cover_image'] );
		} else {
			$source_path = \PressBooks\Image\default_cover_path();
		}
		$dest_image = sanitize_file_name( basename( $source_path ) );
		$dest_image = Sanitize\force_ascii( $dest_image );
		$dest_path = $this->tmpDir . "/OEBPS/images/" . $dest_image;

		$img = wp_get_image_editor( $source_path );
		if ( ! is_wp_error( $img ) ) {
			// Take the longest dimension of the image and resize.
			// Cropping is turned off. The aspect ratio is maintained.
			$img->resize( 1563, 2500, false );
			$img->save( $dest_path );
			$this->coverImage = $dest_image;
		}


		// HTML

		$html = '<div id="cover-image">';
		if ( $this->coverImage ) {
			$html .= sprintf( '<img src="images/%s" alt="%s" />', $this->coverImage, get_bloginfo( 'name' ) );
		}
		$html .= "</div>\n";

		// Create file, insert into manifest

		$vars = array (
		    'post_title' => __( 'Cover', 'pressbooks' ),
		    'stylesheet' => $this->stylesheet,
		    'post_content' => $html,
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$file_id = 'front-cover';
		$filename = "{$file_id}.{$this->filext}";

		file_put_contents(
			$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

		$this->manifest[$file_id] = array (
		    'ID' => -1,
		    'post_title' => $vars['post_title'],
		    'filename' => $filename,
		);
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createBeforeTitle( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$i = $this->frontMatterPos;
		foreach ( array ( 'before-title' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass ) continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '' );
				$content = $this->kneadHtml( $front_matter['post_content'], 'front-matter', $i );

				$vars['post_title'] = $front_matter['post_title'];
				$vars['post_content'] = sprintf( $front_matter_printf, $subclass, $slug, $i, Sanitize\decode( $title ), $content, '' );

				$file_id = 'front-matter-' . sprintf( "%03s", $i );
				$filename = "{$file_id}-{$slug}.{$this->filext}";

				file_put_contents(
					$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

				$this->manifest[$file_id] = array (
				    'ID' => $front_matter['ID'],
				    'post_title' => $front_matter['post_title'],
				    'filename' => $filename,
				);

				++ $i;
			}
		}
		$this->frontMatterPos = $i;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createTitle( $book_contents, $metadata ) {

		// Look for custom title-page

		$content = '';
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'title-page' != $subclass ) continue; // Skip

			$content = $this->kneadHtml( $front_matter['post_content'], 'front-matter' );
			break;
		}

		// HTML

		$html = '<div id="title-page">';
		if ( $content ) {
			$html .= $content;
		} else {
			$html .= sprintf( '<h1 class="title">%s</h1>', get_bloginfo( 'name' ) );
			$html .= sprintf( '<h2 class="subtitle">%s</h2>', @$metadata['pb_subtitle'] );
			$html .= sprintf( '<div class="logo"></div>' );
			$html .= sprintf( '<h3 class="author">%s</h3>', @$metadata['pb_author'] );
			$html .= sprintf( '<h4 class="publisher">%s</h4>', @$metadata['pb_publisher'] );
			$html .= sprintf( '<h5 class="publisher-city">%s</h5>', @$metadata['pb_publisher_city'] );
		}
		$html .= "</div>\n";

		// Create file, insert into manifest

		$vars = array (
		    'post_title' => __( 'Title Page', 'pressbooks' ),
		    'stylesheet' => $this->stylesheet,
		    'post_content' => $html,
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$file_id = 'title-page';
		$filename = "{$file_id}.{$this->filext}";

		file_put_contents(
			$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

		$this->manifest[$file_id] = array (
		    'ID' => -1,
		    'post_title' => $vars['post_title'],
		    'filename' => $filename,
		);
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createCopyright( $book_contents, $metadata ) {

		// HTML

		$html = '<div id="copyright-page"><div class="ugc">';

		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			$html .= $this->kneadHtml( $this->tidy( $metadata['pb_custom_copyright'] ), 'custom' );
		} else {
			$html .= '<p>';
			$html .= get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			$html .= ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
			if ( ! empty( $metadata['pb_copyright_holder'] ) )
					$html .= ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			$html .= '</p>';
		}

		// Copyright
		// Please be kind, help PressBooks grow by leaving this on!
		if ( empty( $GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_EPUB'] ) ) {
			$freebie_notice = 'This book was produced using <a href="http://pressbooks.com/">PressBooks.com</a>.';
			$html .= "<p>$freebie_notice</p>";
		}

		$html .= "</div></div>\n";

		// Create file, insert into manifest

		$vars = array (
		    'post_title' => __( 'Copyright', 'pressbooks' ),
		    'stylesheet' => $this->stylesheet,
		    'post_content' => $html,
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$file_id = 'copyright';
		$filename = "{$file_id}.{$this->filext}";

		file_put_contents(
			$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

		$this->manifest[$file_id] = array (
		    'ID' => - 1,
		    'post_title' => $vars['post_title'],
		    'filename' => $filename,
		);
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createDedicationAndEpigraph( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$i = $this->frontMatterPos;
		$last_pos = false;
		foreach ( array ( 'dedication', 'epigraph' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass ) continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '' );
				$content = $this->kneadHtml( $front_matter['post_content'], 'front-matter', $i );

				$vars['post_title'] = $front_matter['post_title'];
				$vars['post_content'] = sprintf( $front_matter_printf, $subclass, $slug, $i, Sanitize\decode( $title ), $content, '' );

				$file_id = 'front-matter-' . sprintf( "%03s", $i );
				$filename = "{$file_id}-{$slug}.{$this->filext}";

				file_put_contents(
					$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

				$this->manifest[$file_id] = array (
				    'ID' => $front_matter['ID'],
				    'post_title' => $front_matter['post_title'],
				    'filename' => $filename,
				);

				++ $i;
				$last_pos = $i;
			}
		}
		$this->frontMatterPos = $i;
		if ( $last_pos ) $this->frontMatterLastPos = $last_pos - 1;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createFrontMatter( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$i = $this->frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass )
					continue; // Skip

			if ( 'introduction' == $subclass ) $this->hasIntroduction = true;

			$slug = $front_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '' );
			$content = $this->kneadHtml( $front_matter['post_content'], 'front-matter', $i );

			$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			$vars['post_title'] = $front_matter['post_title'];
			$vars['post_content'] = sprintf( $front_matter_printf, $subclass, $slug, $i, Sanitize\decode( $title ), $content, '' );

			$file_id = 'front-matter-' . sprintf( "%03s", $i );
			$filename = "{$file_id}-{$slug}.{$this->filext}";

			file_put_contents(
				$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

			$this->manifest[$file_id] = array (
			    'ID' => $front_matter['ID'],
			    'post_title' => $front_matter['post_title'],
			    'filename' => $filename,
			);

			++ $i;
		}

		$this->frontMatterPos = $i;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createPromo( $book_contents, $metadata ) {

		$promo_html = apply_filters( 'pressbooks_epub_promo', '' );
		if ( $promo_html ) {

			$file_id = 'pressbooks-promo';
			$filename = "{$file_id}.{$this->filext}";

			$vars = array (
			    'post_title' => __( 'Make your own books using PressBooks.com', 'pressbooks' ),
			    'stylesheet' => $this->stylesheet,
			    'post_content' => $this->kneadHtml( $promo_html, 'custom' ),
			    'isbn' => @$metadata['pb_ebook_isbn'],
			);

			file_put_contents(
				$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

			$this->manifest[$file_id] = array (
			    'ID' => -1,
			    'post_title' => $vars['post_title'],
			    'filename' => $filename,
			);
		}
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createPartsAndChapters( $book_contents, $metadata ) {

		$part_printf = '<div class="part" id="%s">';
		$part_printf .= '<div class="part-title-wrap"><h3 class="part-number">%s</h3><h1 class="part-title">%s</h1></div>';
		$part_printf .= '</div>';

		$chapter_printf = '<div class="chapter" id="%s">';
		$chapter_printf .= '<div class="chapter-title-wrap"><h3 class="chapter-number">%s</h3><h2 class="chapter-title">%s</h2></div>';
		$chapter_printf .= '<div class="ugc chapter-ugc">%s</div>%s';
		$chapter_printf .= '</div>';

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		// Parts, Chapters
		$i = $j = 1;
		foreach ( $book_contents['part'] as $part ) {

			$part_printf_changed = '';
			$array_pos = count( $this->manifest );
			$has_chapters = false;

			// Inject introduction class?
			if ( ! $this->hasIntroduction && count( $book_contents['part'] ) > 1 ) {
				$part_printf_changed = str_replace( '<div class="part" id=', '<div class="part introduction" id=', $part_printf );
				$this->hasIntroduction = true;
			}

			// Inject part content?
			$part_content = trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
			if ( $part_content ) {
				$part_content = $this->kneadHtml( $this->preProcessPostContent( $part_content ), 'custom' );
				$part_printf_changed = str_replace( '</h1></div></div>', "</h1></div><div class=\"ugc part-ugc\">{$part_content}</div></div>", $part_printf );
			}

			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] ) continue; // Skip

				$chapter_printf_changed = '';
				$id = $chapter['ID'];
				$slug = $chapter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $chapter['post_title'] : '' );
				$content = $this->kneadHtml( $chapter['post_content'], 'chapter', $j );

				$short_title = false; // Ie. running header title is not used in EPUB
				$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

				if ( $author ) {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}

				if ( $subtitle ) {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}

				if ( $short_title ) {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$chapter_printf_changed = str_replace( '<div class="chapter" id=', '<div class="chapter introduction" id=', $chapter_printf );
					$this->hasIntroduction = true;
				}

				$vars['post_title'] = $chapter['post_title'];
				$vars['post_content'] = sprintf(
					( $chapter_printf_changed ? $chapter_printf_changed : $chapter_printf ), $slug, ( $this->numbered ? $j : '' ), Sanitize\decode( $title ), $content, '' );

				$file_id = 'chapter-' . sprintf( "%03s", $j );
				$filename = "{$file_id}-{$slug}.{$this->filext}";

				file_put_contents(
					$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

				$this->manifest[$file_id] = array (
				    'ID' => $chapter['ID'],
				    'post_title' => $chapter['post_title'],
				    'filename' => $filename,
				);

				$has_chapters = true;

				++ $j;
			}

			if ( $has_chapters && count( $book_contents['part'] ) > 1 ) {

				$slug = $part['post_name'];

				$vars['post_title'] = $part['post_title'];
				$vars['post_content'] = sprintf(
					( $part_printf_changed ? $part_printf_changed : $part_printf ), $slug, ( $this->numbered ? ( $this->romanizePartNumbers ? \PressBooks\L10n\romanize( $i ) : $i ) : '' ), Sanitize\decode( $part['post_title'] ) );

				$file_id = 'part-' . sprintf( "%03s", $i );
				$filename = "{$file_id}-{$slug}.{$this->filext}";

				file_put_contents(
					$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

				// Insert into correct pos
				$this->manifest = array_slice( $this->manifest, 0, $array_pos, true ) + array (
				    $file_id => array (
					'ID' => $part['ID'],
					'post_title' => $part['post_title'],
					'filename' => $filename,
				    ) ) + array_slice( $this->manifest, $array_pos, count( $this->manifest ) - 1, true );

				++ $i;
			}

			// Did we actually inject the introduction class?
			if ( $part_printf_changed && ! $has_chapters ) {
				$this->hasIntroduction = false;
			}
		}
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createBackMatter( $book_contents, $metadata ) {

		$back_matter_printf = '<div class="back-matter %s" id="%s">';
		$back_matter_printf .= '<div class="back-matter-title-wrap"><h3 class="back-matter-number">%s</h3><h1 class="back-matter-title">%s</h1></div>';
		$back_matter_printf .= '<div class="ugc back-matter-ugc">%s</div>%s';
		$back_matter_printf .= '</div>';

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) continue; // Skip

			$id = $back_matter['ID'];
			$subclass = \PressBooks\Taxonomy\back_matter_type( $id );
			$slug = $back_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $back_matter['post_title'] : '' );
			$content = $this->kneadHtml( $back_matter['post_content'], 'back-matter', $i );

			$vars['post_title'] = $back_matter['post_title'];
			$vars['post_content'] = sprintf( $back_matter_printf, $subclass, $slug, $i, Sanitize\decode( $title ), $content, '' );

			$file_id = 'back-matter-' . sprintf( "%03s", $i );
			$filename = "{$file_id}-{$slug}.{$this->filext}";

			file_put_contents(
				$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );

			$this->manifest[$file_id] = array (
			    'ID' => $back_matter['ID'],
			    'post_title' => $back_matter['post_title'],
			    'filename' => $filename,
			);

			++ $i;
		}
	}

	/**
	 * Uses $this->manifest to generate itself.
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createToc( $book_contents, $metadata ) {

		$vars = array (
		    'post_title' => '',
		    'stylesheet' => $this->stylesheet,
		    'post_content' => '',
		    'isbn' => @$metadata['pb_ebook_isbn'],
		);

		// Start by inserting self into correct manifest position
		$array_pos = $this->positionOfToc();

		$file_id = 'table-of-contents';
		$filename = "{$file_id}.{$this->filext}";
		$vars['post_title'] = __( 'Table Of Contents', 'pressbooks' );

		$this->manifest = array_slice( $this->manifest, 0, $array_pos + 1, true ) + array (
		    $file_id => array (
			'ID' => - 1,
			'post_title' => $vars['post_title'],
			'filename' => $filename,
		    ) ) + array_slice( $this->manifest, $array_pos + 1, count( $this->manifest ) - 1, true );

		// HTML

		$li_count = 0;
		$i = 1;
		$html = '<div id="toc"><h1>' . __( 'Contents', 'pressbooks' ) . '</h1><ul>';
		foreach ( $this->manifest as $k => $v ) {

			// We only care about front-matter, part, chapter, back-matter
			// Skip the rest

			$subtitle = '';
			$author = '';
			if ( preg_match( '/^front-matter-/', $k ) ) {
				$class = 'front-matter ';
				$class .= \PressBooks\Taxonomy\front_matter_type( $v['ID'] );
				$subtitle = trim( get_post_meta( $v['ID'], 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $v['ID'], 'pb_section_author', true ) );
			} elseif ( preg_match( '/^part-/', $k ) ) {
				$class = 'part';
			} elseif ( preg_match( '/^chapter-/', $k ) ) {
				$class = 'chapter';
				$subtitle = trim( get_post_meta( $v['ID'], 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $v['ID'], 'pb_section_author', true ) );
				if ( $this->numbered ) {
					$v['post_title'] = " $i. " . $v['post_title'];
				}
				++ $i;
			} elseif ( preg_match( '/^back-matter-/', $k ) ) {
				$class = 'back-matter ';
				$class .= \PressBooks\Taxonomy\back_matter_type( $v['ID'] );
			} else {
				continue;
			}

			$html .= sprintf( '<li class="%s"><a href="%s"><span class="toc-chapter-title">%s</span>', $class, $v['filename'], Sanitize\decode( $v['post_title'] ) );

			if ( $subtitle )
					$html .= ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';

			if ( $author )
					$html .= ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';

			$html .= "</a></li>\n";
			++ $li_count;
		}
		if ( 0 == $li_count ) $html .= '<li></li>';
		$html .= "</ul></div>\n";

		// Create file

		$vars['post_content'] = $html;

		file_put_contents(
			$this->tmpDir . "/OEBPS/$filename", $this->loadTemplate( __DIR__ . '/templates/xhtml.php', $vars ) );
	}

	/**
	 * Pummel the HTML into EPUB compatible dough.
	 *
	 * @param string $html
	 * @param string $type front-matter, part, chapter, back-matter, ...
	 * @param int $pos (optional) position of content, used when creating filenames like: chapter-001, chapter-002, ...
	 *
	 * @return string
	 */
	protected function kneadHtml( $html, $type, $pos = 0 ) {

		libxml_use_internal_errors( true );

		// Load HTMl snippet into DOMDocument using UTF-8 hack
		$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
		$doc = new \DOMDocument();
		$doc->loadHTML( $utf8_hack . $html );

		// Download images, change to relative paths
		$doc = $this->scrapeAndKneadImages( $doc );
		// Download audio files, change to relative paths
		$doc = $this->scrapeAndKneadAudios( $doc );
		// Download video files, change to relative paths
		$doc = $this->scrapeAndKneadVideos( $doc );

		// Deal with <a href="">, <a href=''>, and other mutations
		$doc = $this->kneadHref( $doc, $type, $pos );

		// If you are storing multi-byte characters in XML, then saving the XML using saveXML() will create problems.
		// Ie. It will spit out the characters converted in encoded format. Instead do the following:
		$html = $doc->saveXML( $doc->documentElement );

		// Remove auto-created <html> <body> and <!DOCTYPE> tags.
		$html = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array ( '<html>', '</html>', '<body>', '</body>' ), array ( '', '', '', '' ), $html ) );

		// Mobi7 hacks
		$html = $this->transformXML( $utf8_hack . "<html>$html</html>", __DIR__ . '/templates/mobi-hacks.xsl' );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
	}

	/**
	 * Parse HTML snippet, download all found <img> tags into /OEBPS/images/, return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ) {

		$fullpath = $this->tmpDir . '/OEBPS/images';

		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			// Fetch image, change src
			$url = $image->getAttribute( 'src' );
			$filename = $this->fetchAndSaveUniqueImage( $url, $fullpath );
			if ( $filename ) {
				// Replace with new image
				$image->setAttribute( 'src', 'images/' . $filename );
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$url}#fixme" );
			}
		}

		return $doc;
	}

	protected function scrapeAndKneadAudios( \DOMDocument $doc ) {

		$fullpath = $this->tmpDir . '/OEBPS/audios';

		$audios = $doc->getElementsByTagName( 'audio' );
		foreach ( $audios as $audio ) {

			//If there is a src attribute with a value, let's deal with that first
			if ( $audio->hasAttribute( 'src' ) && ( $audio->getAttribute( 'src' ) != "" ) ) {

				// Fetch the audio file
				$url = $audio->getAttribute( 'src' );
				$response = wp_remote_get( $url, array ( 'timeout' => $this->timeout ) );

				// WordPress error?
				if ( is_wp_error( $response ) ) {
					// TODO: handle $response->get_error_message();
				} else {
					$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
					$filename = sanitize_file_name( urldecode( $filename ) );
					$filename = Sanitize\force_ascii( $filename );

					$file_contents = wp_remote_retrieve_body( $response );

					// Check for duplicates, save accordingly
					if ( ! file_exists( "$fullpath/$filename" ) ) {
						file_put_contents( "$fullpath/$filename", $file_contents );
					} elseif ( md5( $file_contents ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
						$filename = wp_unique_filename( $fullpath, $filename );
						file_put_contents( "$fullpath/$filename", $file_contents );
					}

					// Change src to new relative path
					$audio->setAttribute( 'src', 'audios/' . $filename );
				}
			}

			//Now, we'll scan each audio file for source tags and deal with them
			$sources = $audio->getElementsByTagName( 'source' );
			foreach ( $sources as $source ) {

				// Fetch the audio file
				$url = $source->getAttribute( 'src' );
				$response = wp_remote_get( $url, array ( 'timeout' => $this->timeout ) );

				// WordPress error?
				if ( is_wp_error( $response ) ) {
					// TODO: handle $response->get_error_message();
				} else {
					$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
					$filename = sanitize_file_name( urldecode( $filename ) );
					$filename = Sanitize\force_ascii( $filename );

					$file_contents = wp_remote_retrieve_body( $response );

					// Check for duplicates, save accordingly
					if ( ! file_exists( "$fullpath/$filename" ) ) {
						file_put_contents( "$fullpath/$filename", $file_contents );
					} elseif ( md5( $file_contents ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
						$filename = wp_unique_filename( $fullpath, $filename );
						file_put_contents( "$fullpath/$filename", $file_contents );
					}

					// Change src to new relative path
					$source->setAttribute( 'src', 'audios/' . $filename );
					//$source->nodeValue = str_replace('</source>', '', $source->nodeValue);
				}
			}
		}

		return $doc;
	}

	protected function scrapeAndKneadVideos( \DOMDocument $doc ) {

		$fullpath = $this->tmpDir . '/OEBPS/videos';

		$videos = $doc->getElementsByTagName( 'video' );
		foreach ( $videos as $video ) {

			//If there is a src attribute with a value, let's deal with that first
			if ( $video->hasAttribute( 'src' ) && ( $video->getAttribute( 'src' ) != "" ) ) {

				// Fetch the video file
				$url = $video->getAttribute( 'src' );
				$response = wp_remote_get( $url, array ( 'timeout' => $this->timeout ) );

				// WordPress error?
				if ( is_wp_error( $response ) ) {
					// TODO: handle $response->get_error_message();
				} else {
					$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
					$filename = sanitize_file_name( urldecode( $filename ) );
					$filename = Sanitize\force_ascii( $filename );

					$file_contents = wp_remote_retrieve_body( $response );

					// Check for duplicates, save accordingly
					if ( ! file_exists( "$fullpath/$filename" ) ) {
						file_put_contents( "$fullpath/$filename", $file_contents );
					} elseif ( md5( $file_contents ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
						$filename = wp_unique_filename( $fullpath, $filename );
						file_put_contents( "$fullpath/$filename", $file_contents );
					}

					// Change src to new relative path
					$video->setAttribute( 'src', 'videos/' . $filename );
				}
			}

			//Now, we'll scan each video tag for source tags and deal with them
			$sources = $video->getElementsByTagName( 'source' );
			foreach ( $sources as $source ) {

				// Fetch the video file
				$url = $source->getAttribute( 'src' );
				$response = wp_remote_get( $url, array ( 'timeout' => $this->timeout ) );

				// WordPress error?
				if ( is_wp_error( $response ) ) {
					// TODO: handle $response->get_error_message();
				} else {
					$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
					$filename = sanitize_file_name( urldecode( $filename ) );
					$filename = Sanitize\force_ascii( $filename );

					$file_contents = wp_remote_retrieve_body( $response );

					// Check for duplicates, save accordingly
					if ( ! file_exists( "$fullpath/$filename" ) ) {
						file_put_contents( "$fullpath/$filename", $file_contents );
					} elseif ( md5( $file_contents ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
						$filename = wp_unique_filename( $fullpath, $filename );
						file_put_contents( "$fullpath/$filename", $file_contents );
					}

					// Change src to new relative path
					$source->setAttribute( 'src', 'videos/' . $filename );
					//$source->nodeValue = str_replace('</source>', '', $source->nodeValue);
				}
			}
		}

		return $doc;
	}

	/**
	 * Create OPF File.
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 *
	 * @throws \Exception
	 */
	protected function createOPF( $book_contents, $metadata ) {

		if ( empty( $this->manifest ) ) {
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEPBS() ?' );
		}

		// Vars

		$vars = array (
		    'meta' => $metadata,
		    'manifest' => $this->manifest,
		    'stylesheet' => $this->stylesheet,
		);

		// Find all the image files, insert them into the OPF file

		$html = '';
		$path_to_images = $this->tmpDir . '/OEBPS/images';
		$images = scandir( $path_to_images );
		$used_ids = array ();

		foreach ( $images as $image ) {
			if ( '.' == $image || '..' == $image ) continue;
			$mimetype = $this->mediaType( "$path_to_images/$image" );
			if ( $this->coverImage == $image ) {
				$file_id = 'cover-image';
			} else {
				$file_id = 'media-' . pathinfo( "$path_to_images/$image", PATHINFO_FILENAME );
				$file_id = Sanitize\sanitize_xml_id( $file_id );
			}

			// Check if a media id has already been used, if so give it a new one
			$check_if_used = $file_id;
			for ( $i = 2; $i <= 999; $i ++  ) {
				if ( empty( $used_ids[$check_if_used] ) ) break;
				else $check_if_used = $file_id . "-$i";
			}
			$file_id = $check_if_used;

			$html .= sprintf( '<item id="%s" href="OEBPS/images/%s" media-type="%s" />', $file_id, $image, $mimetype ) . "\n";

			$used_ids[$file_id] = true;
		}
		$vars['manifest_images'] = $html;


		// Find all the audio files, insert them into the OPF file

		$html = '';
		$path_to_audios = $this->tmpDir . '/OEBPS/audios';
		$audios = scandir( $path_to_audios );

		foreach ( $audios as $audio ) {
			if ( '.' == $audio || '..' == $audio ) continue;
			$mimetype = $this->mediaType( "$path_to_audios/$audio" );
			if ( $this->coverImage == $audio ) {
				$file_id = 'cover-audio';
			} else {
				$file_id = 'media-' . pathinfo( "$path_to_audios/$audio", PATHINFO_FILENAME );
				$file_id = Sanitize\sanitize_xml_id( $file_id );
			}

			// Check if a media id has already been used, if so give it a new one
			$check_if_used = $file_id;
			for ( $i = 2; $i <= 999; $i ++  ) {
				if ( empty( $used_ids[$check_if_used] ) ) break;
				else $check_if_used = $file_id . "-$i";
			}
			$file_id = $check_if_used;

			$html .= sprintf( '<item id="%s" href="OEBPS/audios/%s" media-type="%s" />', $file_id, $audio, $mimetype ) . "\n";

			$used_ids[$file_id] = true;
		}
		$vars['manifest_audios'] = $html;

		// Find all the video files, insert them into the OPF file

		$html = '';
		$path_to_videos = $this->tmpDir . '/OEBPS/videos';
		$videos = scandir( $path_to_videos );

		foreach ( $videos as $video ) {
			if ( '.' == $video || '..' == $video ) continue;
			$mimetype = $this->mediaType( "$path_to_videos/$video" );
			if ( $this->coverImage == $video ) {
				$file_id = 'cover-video';
			} else {
				$file_id = 'media-' . pathinfo( "$path_to_videos/$video", PATHINFO_FILENAME );
				$file_id = Sanitize\sanitize_xml_id( $file_id );
			}

			// Check if a media id has already been used, if so give it a new one
			$check_if_used = $file_id;
			for ( $i = 2; $i <= 999; $i ++  ) {
				if ( empty( $used_ids[$check_if_used] ) ) break;
				else $check_if_used = $file_id . "-$i";
			}
			$file_id = $check_if_used;

			$html .= sprintf( '<item id="%s" href="OEBPS/videos/%s" media-type="%s" />', $file_id, $video, $mimetype ) . "\n";

			$used_ids[$file_id] = true;
		}
		$vars['manifest_videos'] = $html;
		// Put contents

		file_put_contents(
			$this->tmpDir . "/book.opf", $this->loadTemplate( __DIR__ . '/templates/opf.php', $vars ) );
	}

	/**
	 * Create NCX file.
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 *
	 * @throws \Exception
	 */
	protected function createNCX( $book_contents, $metadata ) {

		if ( empty( $this->manifest ) ) {
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEPBS() ?' );
		}


		$vars = array (
		    'author' => @$metadata['pb_author'],
		    'manifest' => $this->manifest,
		    'dtd_uid' => ( ! empty( $metadata['pb_ebook_isbn'] ) ? $metadata['pb_ebook_isbn'] : get_bloginfo( 'url' ) ),
		);

		file_put_contents(
			$this->tmpDir . "/toc.xhtml", $this->loadTemplate( __DIR__ . '/templates/ncx.php', $vars ) );
	}

}
