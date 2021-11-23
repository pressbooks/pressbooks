<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version))
 */
namespace Pressbooks\Modules\Export\Odt;

use Pressbooks\Container;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Modules\Export\ExportGenerator;
use Pressbooks\Modules\Export\ExportHelpers;
use Pressbooks\Utility\PercentageYield;
use function Pressbooks\Utility\str_starts_with;
use Pressbooks\Sanitize;

class PhpWord extends Export {

	use ExportHelpers;

	public $phpword;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var string
	 */
	private $tmpDir;

	private $timeout = 30;

	/**
	 * @var \Jenssegers\Blade\Blade|mixed
	 */
	private $blade;

	/**
	 * @var \Pressbooks\Taxonomy|null
	 */
	private $taxonomy;

	/**
	 * @var \Pressbooks\Contributors
	 */
	protected $contributors;

	/**
	 * @var bool
	 */
	protected $displayAboutTheAuthors;
	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	protected $hasIntroduction = false;

	/**
	 * Should all header elements be wrapped in a container? Requires a theme based on Buckram.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected $wrapHeaderElements = false;

	/**
	 * Should the short title be output in a hidden element? Requires a theme based on Buckram 1.2.0 or greater.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected $outputShortTitle = true;


	/**
	 * @param array $args
	 */
	public function __construct( array $args ) {
		$this->phpword = new \PhpOffice\PhpWord\PhpWord();
		$this->blade = Container::get( 'Blade' );
		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";
	}

	/**
	 * Delete temporary directories when done.
	 */
	function __destruct() {
		$this->deleteTmpDir();
	}

	/**
	 * Removes the CC attribution link. Returns valid xhtml.
	 *
	 * @since 4.1
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function removeAttributionLink( $content ) {
		if ( stripos( $content, '<a' ) === false ) {
			// There are no <a> tags to look at, skip this
			return $content;
		}

		$changed = false;
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );

		$urls = $dom->getElementsByTagName( 'a' );
		foreach ( $urls as $url ) {
			/** @var \DOMElement $url */
			// Is this the the attributionUrl?
			if ( $url->getAttribute( 'rel' ) === 'cc:attributionURL' ) {
				$url->parentNode->replaceChild(
					$dom->createTextNode( $url->nodeValue ),
					$url
				);
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return $content;
		} else {
			$content = $html5->saveHTML( $dom );
			$content = $this->html5ToXhtml( $content );
			return $content;
		}
	}

	/**
	 * Clean up content processed by HTML5 Parser, change it back into XHTML
	 *
	 * @param $html
	 *
	 * @return string
	 */
	protected function html5ToXhtml( $html ) {
		$config = [
			'valid_xhtml' => 1,
			'unique_ids' => 0,
		];
		$html = \Pressbooks\HtmLawed::filter( $html, $config );
		return $html;
	}

	/**
	 * Style endnotes.
	 *
	 * @see endnoteShortcode
	 *
	 * @param $id
	 *
	 * @return string
	 */
	function doEndnotes( $id ) {
		// TODO: convert to blade
		if ( ! isset( $this->endnotes[ $id ] ) || ! count( $this->endnotes[ $id ] ) ) {
			return '';
		}

		$e = '<div class="endnotes">';
		$e .= '<hr />';
		$e .= '<h3>' . __( 'Notes', 'pressbooks' ) . '</h3>';
		$e .= '<ol>';
		foreach ( $this->endnotes[ $id ] as $endnote ) {
			$e .= "<li><span>$endnote</span></li>";
		}
		$e .= '</ol></div>';

		return $e;
	}

	/**
	 * Content for footnotes.
	 *
	 * @see footnoteShortCode
	 *
	 * @param $id
	 *
	 * @return string
	 */
	function doFootnotes( $id ) {
		// TODO: convert to blade
		if ( ! isset( $this->footnotes[ $id ] ) || ! count( $this->footnotes[ $id ] ) ) {
			return '';
		}

		$e = '<div class="footnotes">';
		foreach ( $this->footnotes[ $id ] as $k => $footnote ) {
			$key = $k + 1;
			$id_attr = $id . '-' . $key;
			$e .= "<div id='$id_attr'>" . $this->fixInternalLinks( $footnote ) . '</div>';
		}
		$e .= '</div>';

		return $e;
	}

	/**
	 * Does array of chapters have at least one export? Recursive.
	 *
	 * @param array $chapters
	 *
	 * @return bool
	 */
	protected function atLeastOneExport( array $chapters ) {

		foreach ( $chapters as $key => $val ) {
			if ( is_array( $val ) ) {
				$found = $this->atLeastOneExport( $val );
				if ( $found ) {
					return true;
				} else {
					continue;
				}
			} elseif ( 'export' === (string) $key && $val ) {
				return true;
			}
		}

		return false;
	}

	protected function fixImageAttributes( $content ) {
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );
		$images = $dom->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$alt = $image->getAttribute( 'alt' );
			$alt = htmlspecialchars( $alt );
			$image->setAttribute( 'alt', $alt );
			$title = $image->getAttribute( 'title' );
			$title = htmlspecialchars( $title );
			$image->setAttribute( 'title', $title );
		}
		$content = $html5->saveHTML( $dom );
		return $content;
	}

	/**
	 * Replace links to QuickLaTex PNG files with links to the corresponding SVG files.
	 *
	 * @param string $content The section content.
	 *
	 * @return string
	 */
	protected function switchLaTexFormat( $content ) {
		$content = preg_replace( '/(quicklatex.com-[a-f0-9]{32}_l3.)(png)/i', '$1svg', $content );

		return $content;
	}

	/**
	 * @param string $source_content
	 * @param int    $id
	 *
	 * @return string
	 */
	protected function fixInternalLinks( $source_content, $id = null ) {

		if ( stripos( $source_content, '<a' ) === false ) {
			// There are no <a> tags to look at, skip this
			return $source_content;
		}

		$home_url = rtrim( home_url(), '/' );
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $source_content );
		$links = $dom->getElementsByTagName( 'a' );

		foreach ( $links as $link ) {
			/** @var \DOMElement $link */
			$href = $link->getAttribute( 'href' );

			if ( str_starts_with( $href, '#' ) && ! empty( $id ) ) {
				$link->setAttribute( 'data-url', get_permalink( $id ) . $href );
			} else {
				$link->setAttribute( 'data-url', $href );
			}

			if ( str_starts_with( $href, '/' ) || str_starts_with( $href, $home_url ) ) {
				$pos = strpos( $href, '#' );
				if ( $pos !== false ) {
					// Use the #fragment
					$fragment = substr( $href, strpos( $href, '#' ) + 1 );
				} elseif ( preg_match( '%(front\-matter|chapter|back\-matter|part)/([a-z0-9\-]*)([/]?)%', $href, $matches ) ) {
					// Convert type + slug to #fragment
					$fragment = "{$matches[1]}-{$matches[2]}";
				} else {
					$fragment = false;
				}
				if ( $fragment ) {
					// Check if a fragment is considered external, don't change the URL if we find a match
					$external_anchors = [ \Pressbooks\Interactive\Content::ANCHOR ];
					if ( in_array( "#{$fragment}", $external_anchors, true ) || str_starts_with( $fragment, 'h5p' ) ) {
						continue;
					} else {
						$link->setAttribute( 'href', "#{$fragment}" );
					}
				}
			}
		}

		$content = $html5->saveHTML( $dom );
		return $content;
	}

	/**
	 * Replace every image with the bigger original image
	 *
	 * @param $content
	 *
	 * @return string
	 */
	protected function fixImages( $content ) {

		// Cheap cache
		static $already_done = [];

		$changed = false;
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );

		$images = $dom->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			$old_src = $image->getAttribute( 'src' );
			if ( isset( $already_done[ $old_src ] ) ) {
				$new_src = $already_done[ $old_src ];
			} else {
				$new_src = \Pressbooks\Image\maybe_swap_with_bigger( $old_src );
			}
			if ( $old_src !== $new_src ) {
				$image->setAttribute( 'src', $new_src );
				$image->removeAttribute( 'srcset' );
				$changed = true;
			}
			$already_done[ $old_src ] = $new_src;
		}

		if ( $changed ) {
			$content = $html5->saveHTML( $dom );
		}

		return $content;
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

		$html = \Pressbooks\Interactive\Content::init()->replaceInteractiveTags( $html );

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		];

		$spec = '';
		$spec .= 'table=-border;';
		$spec .= 'div=title;';

		return \Pressbooks\HtmLawed::filter( $html, $config, $spec );
	}

	/**
	 * @param string $content
	 * @param int    $id
	 *
	 * @return string
	 */
	protected function preProcessPostContent( $content, $id = null ) {
		$content = apply_filters( 'the_export_content', $content );
		$content = str_ireplace( [ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ], '', $content );
		$content = $this->fixInternalLinks( $content, $id );
		$content = $this->switchLaTexFormat( $content );
		$content = $this->fixImageAttributes( $content );
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			$content = $this->fixImages( $content );
		}
		$content = $this->tidy( $content );

		return $content;
	}

	/**
	 * @param $book_contents
	 *
	 * @return mixed
	 */
	protected function preProcessBookContents( $book_contents ) {

		// We need to change global $id for shortcodes, the_content, ...
		global $id;
		$old_id = $id;

		// Do root level structures first.
		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			foreach ( $struct as $i => $val ) {

				if ( isset( $val['post_content'] ) ) {
					$id = $val['ID'];
					if ( $val['export'] ) {
						$book_contents[ $type ][ $i ]['post_content'] = $this->preProcessPostContent( $val['post_content'], $id );
					} else {
						$book_contents[ $type ][ $i ]['post_content'] = '';
					}
				}
				if ( isset( $val['post_title'] ) ) {
					$book_contents[ $type ][ $i ]['post_title'] = Sanitize\sanitize_xml_attribute( $val['post_title'] );
				}
				if ( isset( $val['post_name'] ) ) {
					$book_contents[ $type ][ $i ]['post_name'] = $this->preProcessPostName( $val['post_name'] );
				}

				if ( 'part' === $type ) {

					// Do chapters, which are embedded in part structure
					foreach ( $book_contents[ $type ][ $i ]['chapters'] as $j => $val2 ) {

						if ( isset( $val2['post_content'] ) ) {
							$id = $val2['ID'];
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_content'] = $this->preProcessPostContent( $val2['post_content'], $id );
						}
						if ( isset( $val2['post_title'] ) ) {
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_title'] = Sanitize\sanitize_xml_attribute( $val2['post_title'] );
						}
						if ( isset( $val2['post_name'] ) ) {
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_name'] = $this->preProcessPostName( $val2['post_name'] );
						}
					}
				}
			}
		}

		$id = $old_id;
		return $book_contents;
	}

	/**
	 * @param  array  $book_contents
	 */
	protected function renderToc( $book_contents ) {

		$rendered_items = [];
		$skipped_items = [
			'dedication',
			'epigraph',
			'title-page',
			'before-title',
		];

		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			if ( 'part' === $type ) {

				foreach ( $struct as $part ) {

					$part_data = $this->getPostInformation( 'chapter', $part, 'part' );

					$rendered_items[] = $this->blade->render('export/bullet-toc-part', [
						'bullet_class' => 'part',
						'is_visible' => get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on',
						'has_content' => trim( $part_data['content'] ), // show in TOC
						'has_at_least_one_chapter' => $this->atLeastOneExport( $part['chapters'] ), // show in TOC
						'item' => [
							'is_epub' => false,
							'slug' => '#' . $part_data['href'],
							'title' => Sanitize\decode( $part_data['title'] ),
						],
					]);

					foreach ( $part['chapters'] as $chapter ) {

						if ( ! $chapter['export'] ) {
							continue;
						}

						$chapter_data = $this->getExtendedPostInformation( 'chapter', $chapter );

						$rendered_items[] = $this->renderTocItem( 'chapter', $chapter_data );

					}
				}
			} else {
				$has_intro = false;

				foreach ( $struct as $val ) {

					if ( ! $val['export'] ) {
						continue;
					}

					switch ( $type ) {

						case 'front-matter':
							$matter_data = $this->getExtendedPostInformation( $type, $val );

							$post_type = $type;

							if ( in_array( $matter_data['subclass'], $skipped_items, true ) ) {
								continue 2; // break foreach loop iteration
							}

							$post_type = $has_intro ? $post_type . ' post-introduction' : $post_type;
							$has_intro = $matter_data['subclass'] === 'introduction';

							$rendered_items[] = $this->renderTocItem( $post_type, $matter_data );

							break;

						case 'back-matter':
							$matter_data = $this->getExtendedPostInformation( $type, $val );

							$rendered_items[] = $this->renderTocItem( $type, $matter_data );

							break;
					}
				}
			}
		}
		return $this->blade->render('export/toc', [
			'title' => __( 'Contents', 'pressbooks' ),
			'toc' => $rendered_items,
		]);
	}

	protected function renderPartsAndChaptersGenerator( $book_contents, $metadata, $section ){

		$part_index = 1;
		$chapter_index = 1;
		$parts_amount = count( $book_contents['part'] );

		foreach ( $book_contents['part'] as $part ) {

			$invisible = get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on';

			$part_is_introduction = false;
			$part_slug = "part-{$part['post_name']}";
			$part_title = $part['post_title'];
			$part_content = trim( $part['post_content'] );

			// Should we inject the introduction class?
			if ( ! $invisible ) {
				// if it's single part and has content
				if ( $part_content && ! $this->hasIntroduction && $parts_amount === 1 ) {
					$part_is_introduction = true;
					$this->hasIntroduction = true;
				} elseif ( ! $this->hasIntroduction && $parts_amount > 1 ) {
					$part_is_introduction = true;
					$this->hasIntroduction = true;
				}
			}

			$part_number = $invisible ? '' : $part_index;

			$rendered_part = $this->blade->render(
				'export/part',
				[
					'invisibility' => $invisible ? 'invisible' : '',
					'introduction' => $part_is_introduction ? 'introduction' : '',
					'slug' => $part_slug,
					'number' => \Pressbooks\L10n\romanize( $part_number ),
					'title' => \Pressbooks\Sanitize\decode( $part_title ),
					'content' => $part_content,
					'endnotes' => $this->doEndnotes( $part['ID'] ),
					'footnotes' => $this->doFootnotes( $part['ID'] ),
				]
			);

			$rendered_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] ) {
					continue; // Skip
				}

				$chapter_id = $chapter['ID'];
				$chapter_subclass = $this->taxonomy->getChapterType( $chapter_id );
				$chapter_slug = "chapter-{$chapter['post_name']}";
				$chapter_title = get_post_meta( $chapter_id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>'; // Preserve auto-indexing in Prince using hidden span
				$chapter_content = $chapter['post_content'];
				$append_chapter_content = apply_filters( 'pb_append_chapter_content', '', $chapter_id );
				$chapter_short_title = trim( get_post_meta( $chapter_id, 'pb_short_title', true ) );
				$chapter_subtitle = trim( get_post_meta( $chapter_id, 'pb_subtitle', true ) );
				$chapter_author = $this->contributors->get( $chapter_id, 'pb_authors' );

				if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() && \Pressbooks\Book::getSubsections( $chapter_id ) !== false ) {
					$chapter_content = \Pressbooks\Book::tagSubsections( $chapter_content, $chapter_id );
				}

				if ( ! $this->hasIntroduction ) {
					$this->hasIntroduction = true;
					$chapter_subclass .= ' introduction';
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$chapter_content .= $this->displayAboutTheAuthors
					? \Pressbooks\Modules\Export\get_contributors_section( $chapter_id )
					: '';

				$chapter_number = strpos( $chapter_subclass, 'numberless' ) === false ? $chapter_index : '';

				$rendered_chapters .= $this->blade->render(
					'export/chapter',
					[
						'subclass' => $chapter_subclass,
						'slug' => $chapter_slug,
						'sanitized_title' => $chapter_short_title ?: wp_strip_all_tags( \Pressbooks\Sanitize\decode( $chapter['post_title'] ) ),
						'number' => $chapter_number,
						'title' => \Pressbooks\Sanitize\decode( $chapter_title ),
						'is_new_buckram' => $this->wrapHeaderElements,
						'output_short_title' => $this->outputShortTitle,
						'author' => $chapter_author,
						'subtitle' => $chapter_subtitle,
						'short_title' => $chapter_short_title,
						'content' => $chapter_content,
						'append_content' => $append_chapter_content,
						'endnotes' => $this->doEndnotes( $chapter_id ),
						'footnotes' => $this->doFootnotes( $chapter_id ),
					]
				);

				if ( $chapter_number ) {
					++$chapter_index;
				}
			}

			if ( $invisible ) {
				\PhpOffice\PhpWord\Shared\Html::addHtml($section, $rendered_chapters, false, false);
				continue;
			}

			if ( $parts_amount === 1 ) {
				$toRender = $part_content
					? $rendered_part . $rendered_chapters
					: $rendered_chapters;
				\PhpOffice\PhpWord\Shared\Html::addHtml($section, $toRender, false, false);
			} else {
				if ( ! $rendered_chapters ) {
					$toRender = $part_content ? $rendered_part : '';
					if($toRender) {
						\PhpOffice\PhpWord\Shared\Html::addHtml($section, $toRender, false, false);
					}
					continue;
				}

				\PhpOffice\PhpWord\Shared\Html::addHtml($section, $rendered_part . $rendered_chapters, false, false);
			}

			++$part_index;
		}
	}

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 * @throws \PhpOffice\PhpWord\Exception\Exception
	 */
	function convert() {

		$pageTitle = $this->phpword->addSection();
		$pageTitle->addTitle(get_bloginfo( 'name' ),1);
		$pageTitle->addPageBreak();

		// Inline font style
		$fontStyle['name'] = 'Times New Roman';
		$fontStyle['size'] = 20;
		$title = $this->phpword->addSection();
		$title->addText(get_bloginfo( 'name' ),$fontStyle);
		$title->addPageBreak();

		$book_contents = $this->preProcessBookContents( \Pressbooks\Book::getBookContents() );
		$metadata = \Pressbooks\Book::getBookInformation( null, false, false );

		$tocHtml = $this->renderToc($book_contents);
		$toc = $this->phpword->addSection();
		\PhpOffice\PhpWord\Shared\Html::addHtml($toc, $tocHtml, false, false);
		$toc->addPageBreak();

		$section = $this->phpword->addSection();
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
		/**
		 * Here is where an XLST template it could be useful to map HTML inside each part and chapter into an ODT valid XML.
		 */
		$this->renderPartsAndChaptersGenerator($book_contents, $metadata, $section);

		$word = $this->generateDocFileName();
		$odt = $this->generateOdtFileName();


		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->phpword, 'Word2007');
		$objWriter->save($word);
		$objWriter2 = \PhpOffice\PhpWord\IOFactory::createWriter($this->phpword, 'ODText');
		$objWriter2->save($odt);
		return $odt;
	}

	/**
	 * Query the access protected "format/xhtml" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryXhtml() {

	}

	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {
		return true;
	}

	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = [] ) {

		$more_info['url'] = $this->url;

		parent::logError( $message, $more_info );
	}

	/**
	 * Verify if body is actual ODT
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function isOdt( $file ) {

		$mime = static::mimeType( $file );

		return ( strpos( $mime, 'application/vnd.oasis.opendocument.text' ) !== false );
	}

	/**
	 * Fetch an image with wp_remote_get(), save it to $fullpath with a unique name.
	 * Will return an empty string if something went wrong.
	 *
	 * @param $url string
	 * @param $fullpath string
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $url, $fullpath ) {

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $url ] ) ) {
			return $already_done[ $url ];
		}

		$response = wp_remote_get(
			$url, [
				'timeout' => $this->timeout,
			]
		);

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: Better handling in the event of $response->get_error_message();
			$already_done[ $url ] = '';
			return '';
		}

		// Basename without query string
		$filename = explode( '?', basename( $url ) );

		// isolate latex image service, add file extension
		if ( PB_MATHJAX_URL && \Pressbooks\Utility\str_starts_with( $url, PB_MATHJAX_URL ) ) {
			$filename = md5( array_pop( $filename ) );
			// content-type = 'image/png'
			$type = explode( '/', $response['headers']['content-type'] );
			$type = array_pop( $type );
			$filename = $filename . '.' . $type;
		} else {
			$filename = array_shift( $filename );
			$filename = explode( '#', $filename )[0]; // Remove trailing anchors
			$filename = sanitize_file_name( urldecode( $filename ) );
			$filename = \Pressbooks\Sanitize\force_ascii( $filename );
		}

		// A book with a lot of images can trigger "Fatal Error Too many open files" because tmpfiles are not closed until PHP exits
		// Use a $resource_key so we can close the tmpfile ourselves
		$resource_key = uniqid( 'tmpfile-odt-', true );
		$tmp_file = \Pressbooks\Utility\create_tmp_file( $resource_key );
		\Pressbooks\Utility\put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_file, $filename ) ) {
			$already_done[ $url ] = '';
			fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine
			return ''; // Not an image
		}

		if ( $this->compressImages ) {
			$format = explode( '.', $filename );
			$format = strtolower( end( $format ) ); // Extension
			try {
				\Pressbooks\Image\resize_down( $format, $tmp_file );
			} catch ( \Exception $e ) {
				return '';
			}
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( \Pressbooks\Utility\get_contents( $tmp_file ) ) !== md5( \Pressbooks\Utility\get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}
		fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine

		$already_done[ $url ] = $filename;
		return $filename;
	}

	/**
	 * Delete temporary directories
	 */
	protected function deleteTmpDir() {
		// Cleanup temporary directory, if any
		if ( ! empty( $this->tmpDir ) ) {
			\Pressbooks\Utility\rmrdir( $this->tmpDir );
		}
		// Cleanup deprecated junk, if any
		$exports_folder = untrailingslashit( pathinfo( $this->outputPath, PATHINFO_DIRNAME ) );
		if ( ! empty( $exports_folder ) ) {
			\Pressbooks\Utility\rmrdir( "{$exports_folder}/META-INF" );
			\Pressbooks\Utility\rmrdir( "{$exports_folder}/media" );
		}
	}

	protected function generateDocFileName() {
		return $this->timestampedFileName( '.docx' );
	}

	protected function generateOdtFileName() {
		return $this->timestampedFileName( '.odt' );
	}

	/**
	 * Dependency check.
	 *
	 * @return bool
	 */
	static function hasDependencies() {
		if ( false !== \Pressbooks\Utility\check_saxonhe_install() ) {
			return true;
		}

		return false;
	}

}
