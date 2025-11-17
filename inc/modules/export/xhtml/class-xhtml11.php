<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * See templating function for reference: \Pressbooks\Modules\Export\Export loadTemplate()
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Modules\Export\Xhtml;

use Exception;
use function Pressbooks\Image\maybe_swap_with_bigger;
use function Pressbooks\L10n\romanize;
use function Pressbooks\Modules\Export\get_contributors_section;
use function Pressbooks\Sanitize\clean_filename;
use function Pressbooks\Sanitize\decode;
use function Pressbooks\Utility\check_xmllint_install;
use function Pressbooks\Utility\put_contents;
use function Pressbooks\Utility\str_starts_with;
use Generator;
use PressbooksMix\Assets;
use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\Contributors;
use Pressbooks\HtmLawed;
use Pressbooks\HtmlParser;
use Pressbooks\Interactive\Content;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Modules\Export\ExportHelpers;
use Pressbooks\Modules\Export\Traits\HandleContributors;
use Pressbooks\Sanitize;
use Pressbooks\Taxonomy;
use Pressbooks\Utility\PercentageYield;

class Xhtml11 extends Export {

	use ExportHelpers;
	use HandleContributors;

	const TRANSIENT = 'pressbooks_export_xhtml_buffer_inner_html';

	/**
	 * Prettify HTML
	 *
	 * @var bool
	 */
	public $tidy = false;

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * @var string
	 */
	public string $transformOutput;

	/**
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	protected array $endnotes = [];

	/**
	 * Footnotes storage container.
	 *
	 * @var array
	 */
	protected array $footnotes = [];

	/**
	 * We forcefully reorder some of the front-matter types to respect the Chicago Manual of Style.
	 * Keep track of where we are using this variable.
	 *
	 * @var int
	 */
	protected int $frontMatterPos = 1;

	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	protected bool $hasIntroduction = false;

	/**
	 * Should all header elements be wrapped in a container? Requires a theme based on Buckram.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected bool $wrapHeaderElements = false;

	/**
	 * Should the short title be output in a hidden element? Requires a theme based on Buckram 1.2.0 or greater.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected bool $outputShortTitle = true;

	/**
	 * Main language of document, two letter code
	 *
	 * @var string
	 */
	protected string $lang = 'en';

	/**
	 * @var string
	 */
	protected $generatorPrefix;

	/**
	 * @var Taxonomy
	 */
	protected $taxonomy;

	/**
	 * @var Contributors
	 */
	protected $contributors;

	/**
	 * @var bool
	 */
	protected $displayAboutTheAuthors;

	/**
	 * @var Blade
	 */
	protected $blade;

	/**
	 * Performance optimization caches
	 */
	private array $processingCache = [];
	private array $domCache = [];
	private array $imageCache = [];
	private array $metaCache = [];

	/**
	 * Cache for pre-export processing
	 */
	private static bool $preExportOptimized = false;
	private static array $preExportCache = [];

	/**
	 * @param array $args
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct( array $args ) {

		// Some defaults

		$this->taxonomy = Taxonomy::init();
		$this->contributors = new Contributors();
		$this->blade = Container::get( 'Blade' );

		if ( Container::get( 'Styles' )->hasBuckram( '0.3.0' ) ) {
			$this->wrapHeaderElements = true;
		}

		if ( Container::get( 'Styles' )->hasBuckram( '1.2.0' ) ) {
			$this->outputShortTitle = false;
		}

		if ( ! defined( 'PB_XMLLINT_COMMAND' ) ) {
			define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );
		}

		$defaults = [
			'endnotes' => false,
		];
		$r = wp_parse_args( $args, $defaults );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		// Append endnotes to URL?
		if ( $r['endnotes'] ) {
			$this->url .= '&endnotes=true';
			$_GET['endnotes'] = 'true';
		}

		// HtmLawed: id values not allowed in input
		foreach ( $this->reservedIds as $val ) {
			$fixme[ $val ] = 1;
		}
		if ( isset( $fixme ) ) {
			$GLOBALS['hl_Ids'] = $fixme;
		}

		$this->generatorPrefix = __( 'XHTML: ', 'pressbooks' );

		if ( isset( $r['no-export'] ) ) {
			$this->url = '';
		}

		// Pre-warm common caches
		$this->preWarmCaches();
	}

	/**
	 * Yields an estimated percentage slice of: 1 to 80
	 *
	 * @return Generator
	 * @throws Exception
	 */
	public function convert() : Generator {
		yield 1 => $this->generatorPrefix . __( 'Initializing', 'pressbooks' );

		// Optimize WordPress environment for export
		$this->optimizeWordPressForExport();

		try {
			yield from $this->transformGenerator();

			if ( ! $this->transformOutput ) {
				throw new Exception();
			}
			if ( $this->url !== '' ) {
				yield 75 => $this->generatorPrefix . __( 'Saving file to exports folder', 'pressbooks' );
				$filename = $this->timestampedFileName( '.html' );
				put_contents( $filename, $this->transformOutput );
				$this->outputPath = $filename;
				yield 80 => $this->generatorPrefix . __( 'Export successful', 'pressbooks' );
			}
		} finally {
			$this->restoreDatabaseOperations();
			$this->clearCaches();
		}

		return $this->outputPath;
	}

	/**
	 * Yields an estimated percentage slice of: 80 to 100
	 *
	 * @return Generator
	 * @throws Exception
	 */
	public function validate() : Generator {
		yield 90 => $this->generatorPrefix . __( 'Validating file', 'pressbooks' );

		// Xmllint params
		$command = PB_XMLLINT_COMMAND . ' --html --valid --noout ' . escapeshellcmd( $this->outputPath ) . ' 2>&1';

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );

		// Is this a valid XHTML?
		if ( is_countable( $output ) && count( $output ) ) {
			$this->logError( implode( "\n", $output ) );
			throw new Exception();
		}

		yield 100 => $this->generatorPrefix . __( 'Validation successful', 'pressbooks' );
		return $return_var === 0;
	}

	/**
	 * Procedure for "format/xhtml" rewrite rule.
	 *
	 * Supported http (aka $_GET) params:
	 *
	 *   + timestamp: (int) combines with `hashkey` to allow a 3rd party service temporary access
	 *   + hashkey: (string) combines with `timestamp` to allow a 3rd party service temporary access
	 *   + endnotes: (bool) move all footnotes to end of the book
	 *   + style: (string) name of a user generated stylesheet you want included in the header
	 *   + script: (string) name of javascript file you you want included in the header
	 *   + preview: (bool) Use `Content-Disposition: inline` instead of `Content-Disposition: attachment` when passing through Export::formSubmit
	 *   + optimize-for-print: (bool) Replace images with originals when possible, add class="print" to <body>, and other print specific tweaks
	 *
	 * @see \Pressbooks\Redirect\do_format
	 *
	 * @param bool $return (optional) If you would like to capture the output of transform, use the return parameter. If this parameter is set
	 *                     to true, transform will return its output, instead of printing it.
	 *
	 * @return mixed
	 */
	public function transform( $return = false ) {

		// Check permissions

		if ( ! current_user_can( 'edit_posts' ) ) {
			$timestamp = ( isset( $_REQUEST['timestamp'] ) ) ? absint( $_REQUEST['timestamp'] ) : 0;
			$hashkey = ( isset( $_REQUEST['hashkey'] ) ) ? $_REQUEST['hashkey'] : '';
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

		try {
			$generator = $this->transformGenerator();
			while ( $generator->valid() ) {
				$generator->next();
			}
		} catch ( Exception $e ) {
			return null;
		}

		if ( $return ) {
			return $this->transformOutput;
		} else {
			echo $this->transformOutput;
			return null;
		}
	}

	/**
	 * Yields an estimated percentage slice of: 10 to 75
	 *
	 * @return Generator
	 * @throws Exception
	 */
	public function transformGenerator() : Generator {
		/**
		 * Let other plugins tweak things before exporting
		 * TODO: (bg) Check why is this required in theory is being called in class-backgroundjob.php probably because we have a different the_content when processing
		 * @since 4.4.0
		 */
		do_action( 'pb_pre_export' );

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', [ $this, 'endnoteShortcode' ] );
		} else {
			add_shortcode( 'footnote', [ $this, 'footnoteShortcode' ] );
		}
		// Use SVG for math
		add_filter( 'pb_mathjax_use_svg', '__return_true' );

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start!

		$metadata = Book::getBookInformation( null, false, false );
		$_unused = [];

		// Set two letter language code
		if ( isset( $metadata['pb_language'] ) ) {
			[ $this->lang ] = explode( '-', $metadata['pb_language'] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// Buffer for Outer XHTML

		ob_start();

		$this->echoDocType( $_unused, $_unused );

		//TODO: convert to blade
		echo "<head>\n";
		echo '<meta content="text/html; charset=UTF-8" http-equiv="content-type" />' . "\n";
		echo '<meta http-equiv="Content-Language" content="' . $this->lang . '" />' . "\n";
		echo '<meta name="generator" content="Pressbooks ' . PB_PLUGIN_VERSION . '" />' . "\n";

		$this->echoMetaData( $_unused, $metadata );

		echo '<title>' . get_bloginfo( 'name' ) . "</title>\n";

		if ( current_user_can( 'edit_posts' ) ) {
			if ( ! empty( $_GET['debug'] ) ) {
				$assets = new Assets( 'pressbooks', 'plugin' );
				$css = ( $_GET['debug'] === 'prince' ) ? $this->getLatestExportStyleUrl( 'prince' ) : false;
				$js = $assets->getPath( 'scripts/paged.polyfill.js' );
				if ( $css ) {
					echo "<link rel='stylesheet' href='$css' type='text/css' />\n";
				}
				echo "<script src='$js'></script>\n";
			}
		}

		if ( ! empty( $_GET['style'] ) ) {
			$url = ( $_GET['style'] === 'prince' ) ? $this->getLatestExportStyleUrl( 'prince' ) : false;
			if ( $url ) {
				echo "<link rel='stylesheet' href='$url' type='text/css' />\n";
			}
		}
		if ( ! empty( $_GET['script'] ) ) {
			$url = $this->getExportScriptUrl( clean_filename( $_GET['script'] ) );
			if ( $url ) {
				echo "<script src='$url' type='text/javascript'></script>\n";
			}
		}
		echo "<!-- PB_SCOPED_STYLES_PLACEHOLDER -->\n"; // Placeholder for the custom stylesheet link
		echo "</head>\n<body lang='{$this->lang}' ";
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			echo "class='print' ";
		}
		echo ">\n";
		$replace_token = uniqid( 'PB_REPLACE_INNER_HTML_', true );
		echo $replace_token;
		if ( ! empty( $_GET['movefootnotes'] ) ) {
			echo "\n <script>
					function moveFootnotes() {
						var footnotes = document.getElementsByClassName( 'footnote-indirect' );
						for ( var i = 0; i < footnotes.length; i++ ) {
							var ref = document.getElementById( footnotes[i].getAttribute( 'data-fnref' ) );
							if ( ref ) {
								footnotes[i].appendChild( ref );
							}
						}
					}
					if ( typeof Prince != 'undefined' ) {
						moveFootnotes();
					}
				</script> \n";
		}
		echo "\n</body>\n</html>";

		$buffer_outer_html = ob_get_clean();

		// ------------------------------------------------------------------------------------------------------------
		// Buffer for Inner XHTML

		$my_get = $_GET;
		unset( $my_get['timestamp'], $my_get['hashkey'] );
		$cache = get_transient( self::TRANSIENT );
		if ( is_array( $cache ) && isset( $cache[0] ) && $cache[0] === md5( wp_json_encode( $my_get ) ) ) {
			// The $_GET parameters haven't changed since the last request so the output will be the same
			$buffer_inner_html = $cache[1];
		} else {
			$book_contents = $this->preProcessBookContents( Book::getBookContents() );

			ob_start();

			$this->displayAboutTheAuthors = ! empty( get_option( 'pressbooks_theme_options_global', [] )['about_the_author'] );

			// Before Title Page
			yield 10 => $this->generatorPrefix . __( 'Creating before title page', 'pressbooks' );
			$this->renderBeforeTitle( $book_contents );

			// Half-title
			yield 15 => $this->generatorPrefix . __( 'Creating half title page', 'pressbooks' );
			$this->renderHalfTitle();

			// Cover
			yield 20 => $this->generatorPrefix . __( 'Creating cover', 'pressbooks' );
			$this->renderCover( $book_contents, $metadata );

			// Title
			yield 25 => $this->generatorPrefix . __( 'Creating title page', 'pressbooks' );
			$this->renderTitle( $book_contents, $metadata );

			// Copyright
			yield 30 => $this->generatorPrefix . __( 'Creating copyright page', 'pressbooks' );
			$this->renderCopyright( $metadata );

			// Dedication and Epigraph (In that order!)
			yield 35 => $this->generatorPrefix . __( 'Creating dedication and epigraph', 'pressbooks' );
			$this->renderDedicationAndEpigraph( $book_contents );

			// Table of contents
			yield 40 => $this->generatorPrefix . __( 'Creating table of contents', 'pressbooks' );
			$this->renderToc( $book_contents );

			// Front-matter
			yield 50 => $this->generatorPrefix . __( 'Exporting front matter', 'pressbooks' );
			yield from $this->renderFrontMatterGenerator( $book_contents, $metadata );

			// Promo
			$this->createPromo( $_unused, $_unused );

			// Parts, Chapters
			yield 60 => $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' );
			yield from $this->renderPartsAndChaptersGenerator( $book_contents, $metadata );

			// Back-matter
			yield 70 => $this->generatorPrefix . __( 'Exporting back matter', 'pressbooks' );
			yield from $this->renderBackMatterGenerator( $book_contents, $metadata );

			$buffer_inner_html = ob_get_clean();

			if ( $this->tidy ) {
				$buffer_inner_html = Sanitize\prettify( $buffer_inner_html );
			}

			// Put the $_GET parameters and the buffer in a transient
			set_transient( self::TRANSIENT, [ md5( wp_json_encode( $my_get ) ), $buffer_inner_html ] );
		}

		do_action( 'pb_xhtml_after_content_processed' );

		$custom_stylesheet_url = app( 'ScopedStyles' )->h5p_css_url;

		$pos = strpos( $buffer_outer_html, $replace_token );
		$buffer = substr_replace( $buffer_outer_html, $buffer_inner_html, $pos, strlen( $replace_token ) );

		if ( ! empty( $custom_stylesheet_url ) ) {
			$link_tag = sprintf( '<link rel="stylesheet" href="%s" type="text/css" />', esc_url( $custom_stylesheet_url ) );
			$buffer = str_replace( '<!-- PB_SCOPED_STYLES_PLACEHOLDER -->', $link_tag, $buffer );
		} else {
			$buffer = str_replace( "<!-- PB_SCOPED_STYLES_PLACEHOLDER -->\n", '', $buffer );
		}

		$this->transformOutput = $buffer;
	}

	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	public function logError( $message, array $more_info = [] ): void {

		$more_info['url'] = $this->url;

		parent::logError( $message, $more_info );
	}

	/**
	 * Wrap footnotes for Prince compatibility
	 *
	 * @see http://www.princexml.com/doc/8.1/footnotes/
	 *
	 * @param $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function footnoteShortcode( $atts, $content = null ): string {
		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, preProcessBookContents, ...]
		$this->footnotes[ $id ][] = trim( $content );
		$ref_id = count( $this->footnotes[ $id ] );
		$ref_id_dom = $id . '-' . $ref_id;
		return "<span class='footnote'><span class='footnote-indirect' data-fnref='$ref_id_dom'></span></span>";
	}

	/**
	 * Convert footnotes to endnotes by moving them to the end of the_content()
	 *
	 * @see doEndnotes
	 *
	 * @param array $atts
	 * @param null  $content
	 *
	 * @return string
	 */
	function endnoteShortcode( $atts, $content = null ): string {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, preProcessBookContents, ...]

		if ( ! $content ) {
			return '';
		}

		$this->endnotes[ $id ][] = trim( $content );

		return '<sup class="endnote">' . count( $this->endnotes[ $id ] ) . '</sup>';
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
	function doFootnotes( $id ): string {
		if ( ! isset( $this->footnotes[ $id ] ) || ! count( $this->footnotes[ $id ] ) ) {
			return '';
		}

		$cache_key = 'footnotes_' . $id . '_' . md5( serialize( $this->footnotes[ $id ] ) );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		if ( ! has_filter( 'the_content', 'do_shortcode' ) ) {
			add_filter( 'the_content', 'do_shortcode', 11 );
		}

		$footnotes_html = [];
		foreach ( $this->footnotes[ $id ] as $k => $footnote ) {
			$key = $k + 1;
			$id_attr = $id . '-' . $key;

			$footnote_content = apply_filters( 'the_content', $footnote );
			$footnotes_html[] = "<div id='$id_attr'>" . $this->fixInternalLinks( $footnote_content ) . '</div>';
		}

		$result = '<div class="footnotes">' . implode( '', $footnotes_html ) . '</div>';
		$this->processingCache[ $cache_key ] = $result;

		return $result;
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Performance Optimizations
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * Pre-warm commonly used caches
	 */
	private function preWarmCaches(): void {
		$this->processingCache['home_url'] = rtrim( home_url(), '/' );
		$this->processingCache['blog_name'] = get_bloginfo( 'name' );
	}

	/**
	 * Optimize WordPress environment for export
	 */
	protected function optimizeWordPressForExport(): void {

		wp_suspend_cache_invalidation( true );

		if ( function_exists( 'ini_set' ) ) {
			ini_set( 'memory_limit', '1024M' );
		}

		// Disable term counting for performance
		wp_defer_term_counting( true );

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 ); // No time limit
		}

		// Disable unnecessary hooks during export
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
	}

	/**
	 * Restore normal database operations
	 */
	protected function restoreDatabaseOperations(): void {
		wp_suspend_cache_invalidation( false );
		wp_defer_term_counting( false );
	}

	/**
	 * Clear all performance caches
	 */
	private function clearCaches(): void {
		$this->processingCache = [];
		$this->domCache = [];
		$this->imageCache = [];
		$this->metaCache = [];

		// Force garbage collection
		if ( function_exists( 'gc_collect_cycles' ) ) {
			gc_collect_cycles();
		}
	}

	/**
	 * Clear only processing caches, keep essential caches
	 */
	private function clearProcessingCaches(): void {
		$essential_keys = [ 'home_url', 'blog_name' ];
		$essential_cache = array_intersect_key( $this->processingCache, array_flip( $essential_keys ) );

		$this->processingCache = $essential_cache;
		$this->domCache = [];

		if ( function_exists( 'gc_collect_cycles' ) ) {
			gc_collect_cycles();
		}
	}

	/**
	 * Extract all post IDs for batch operations
	 */
	private function extractAllPostIds( $book_contents ): array {
		$post_ids = [];

		foreach ( $book_contents as $type => $struct ) {
			if ( str_starts_with( $type, '__' ) ) {
				continue;
			}

			foreach ( $struct as $item ) {
				if ( isset( $item['ID'] ) ) {
					$post_ids[] = $item['ID'];
				}

				// Handle chapters within parts
				if ( $type === 'part' && isset( $item['chapters'] ) ) {
					foreach ( $item['chapters'] as $chapter ) {
						if ( isset( $chapter['ID'] ) ) {
							$post_ids[] = $chapter['ID'];
						}
					}
				}
			}
		}

		return array_unique( $post_ids );
	}

	/**
	 * Preload post meta to reduce DB queries
	 */
	private function preloadPostMeta( array $post_ids ): void {
		if ( empty( $post_ids ) ) {
			return;
		}

		// Use WordPress's update_meta_cache to preload all meta
		update_meta_cache( 'post', $post_ids );
	}

	/**
	 * Cached XML attribute sanitization
	 */
	private function cachedSanitizeXmlAttribute( string $value ): string {
		$cache_key = 'xml_attr_' . md5( $value );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		$result = Sanitize\sanitize_xml_attribute( $value );
		$this->processingCache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Cached DOM parser to reuse parsed documents
	 */
	private function getCachedDomParser( string $content ): \DOMDocument {
		$cache_key = md5( $content );

		if ( isset( $this->domCache[ $cache_key ] ) ) {
			return clone $this->domCache[ $cache_key ];
		}

		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );

		// Cache the DOM (clone it to avoid reference issues)
		$this->domCache[ $cache_key ] = clone $dom;

		return $dom;
	}

	/**
	 * Optimized HTML saving from DOM
	 */
	private function saveHTMLFromDom( \DOMDocument $dom ): string {
		$html5 = new HtmlParser();
		return $html5->saveHTML( $dom );
	}

	/**
	 * Cached image swapping
	 */
	private function getCachedImageSwap( string $src ): string {
		if ( isset( $this->imageCache[ $src ] ) ) {
			return $this->imageCache[ $src ];
		}

		$new_src = maybe_swap_with_bigger( $src );
		$this->imageCache[ $src ] = $new_src;

		return $new_src;
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Sanitize book (Optimized)
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param $book_contents
	 *
	 * @return mixed
	 */
	protected function preProcessBookContents( $book_contents ) {

		global $id;
		$old_id = $id;

		// Pre-load all post meta in batch to reduce DB queries
		$all_post_ids = $this->extractAllPostIds( $book_contents );
		$this->preloadPostMeta( $all_post_ids );

		// Process content in batches
		foreach ( $book_contents as $type => $struct ) {
			if ( str_starts_with( $type, '__' ) ) {
				continue; // Skip __magic keys
			}

			$book_contents[ $type ] = $this->batchProcessStructure( $struct, $type );
		}

		$id = $old_id;
		return $book_contents;
	}

	/**
	 * Batch process structure with optimized content processing
	 */
	private function batchProcessStructure( array $struct, string $type ): array {
		global $id;

		foreach ( $struct as $i => $val ) {
			if ( isset( $val['post_content'] ) && $val['export'] ) {
				$id = $val['ID'];
				$struct[ $i ]['post_content'] = $this->optimizedPreProcessPostContent(
					$val['post_content'],
					$id
				);
			} else {
				$struct[ $i ]['post_content'] = '';
			}

			// Optimize title and name processing
			if ( isset( $val['post_title'] ) ) {
				$struct[ $i ]['post_title'] = $this->cachedSanitizeXmlAttribute( $val['post_title'] );
			}
			if ( isset( $val['post_name'] ) ) {
				$struct[ $i ]['post_name'] = $this->preProcessPostName( $val['post_name'] );
			}

			// Handle chapters in parts with batch processing
			if ( $type === 'part' && isset( $val['chapters'] ) ) {
				$struct[ $i ]['chapters'] = $this->batchProcessChapters( $val['chapters'] );
			}
		}

		return $struct;
	}

	/**
	 * Batch process chapters
	 */
	private function batchProcessChapters( array $chapters ): array {
		global $id;

		foreach ( $chapters as $j => $chapter ) {
			if ( isset( $chapter['post_content'] ) ) {
				$id = $chapter['ID'];
				$chapters[ $j ]['post_content'] = $this->optimizedPreProcessPostContent(
					$chapter['post_content'],
					$id
				);
			}

			if ( isset( $chapter['post_title'] ) ) {
				$chapters[ $j ]['post_title'] = $this->cachedSanitizeXmlAttribute( $chapter['post_title'] );
			}
			if ( isset( $chapter['post_name'] ) ) {
				$chapters[ $j ]['post_name'] = $this->preProcessPostName( $chapter['post_name'] );
			}
		}

		return $chapters;
	}

	/**
	 * Optimized content processing with caching
	 */
	protected function optimizedPreProcessPostContent( string $content, int $id = null ): string {
		$cache_key = 'content_' . md5( $content . $id );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		$content = apply_filters( 'the_export_content', $content );

		// Batch remove empty tags
		$content = str_ireplace(
			[ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ],
			'',
			$content
		);

		// Process content with optimized methods
		$content = $this->optimizedFixInternalLinks( $content, $id );
		$content = $this->switchLaTexFormat( $content );
		$content = $this->optimizedFixImageAttributes( $content );

		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			$content = $this->optimizedFixImages( $content );
		}

		$content = $this->tidy( $content );

		$this->processingCache[ $cache_key ] = $content;

		return $content;
	}

	/**
	 * Optimized image attributes processing
	 */
	protected function optimizedFixImageAttributes( $content ) {
		if ( stripos( $content, '<img' ) === false ) {
			return $content;
		}

		$cache_key = 'img_attrs_' . md5( $content );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		$dom = $this->getCachedDomParser( $content );
		$images = $dom->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			$alt = $image->getAttribute( 'alt' );
			if ( $alt ) {
				$image->setAttribute( 'alt', htmlspecialchars( $alt ) );
			}

			$title = $image->getAttribute( 'title' );
			if ( $title ) {
				$image->setAttribute( 'title', htmlspecialchars( $title ) );
			}
		}

		$result = $this->saveHTMLFromDom( $dom );
		$this->processingCache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Replace links to QuickLaTex PNG files with links to the corresponding SVG files.
	 *
	 * @param string $content The section content.
	 *
	 * @return string
	 */
	protected function switchLaTexFormat( $content ) {
		return preg_replace( '/(quicklatex.com-[a-f0-9]{32}_l3.)(png)/i', '$1svg', $content );
	}

	/**
	 * Optimized internal links processing
	 */
	protected function optimizedFixInternalLinks( $source_content, $id = null ) {
		// Quick check to avoid DOM parsing if no links
		if ( stripos( $source_content, '<a' ) === false ) {
			return $source_content;
		}

		$cache_key = 'links_' . md5( $source_content . $id );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		// Use cached DOM parser
		$dom = $this->getCachedDomParser( $source_content );
		$links = $dom->getElementsByTagName( 'a' );
		$home_url = $this->processingCache['home_url']; // Use cached home_url
		$has_changes = false;

		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );

			if ( str_starts_with( $href, '#' ) && ! empty( $id ) ) {
				$link->setAttribute( 'data-url', get_permalink( $id ) . $href );
			} else {
				$link->setAttribute( 'data-url', $href );
			}

			if ( str_starts_with( $href, '/' ) || str_starts_with( $href, $home_url ) ) {
				$pos = strpos( $href, '#' );
				if ( $pos !== false ) {
					$fragment = substr( $href, $pos + 1 );
				} elseif ( preg_match( '%(front\-matter|chapter|back\-matter|part)/([a-z0-9\-]*)([/]?)%', $href, $matches ) ) {
					$fragment = "{$matches[1]}-{$matches[2]}";
				} else {
					$fragment = false;
				}

				if ( $fragment ) {
					$external_anchors = [ Content::ANCHOR ];
					if ( ! in_array( "#{$fragment}", $external_anchors, true ) && ! str_starts_with( $fragment, 'h5p' ) ) {
						$link->setAttribute( 'href', "#{$fragment}" );
						$has_changes = true;
					}
				}
			}
		}

		$result = $has_changes ? $this->saveHTMLFromDom( $dom ) : $source_content;
		$this->processingCache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * @param string $source_content
	 * @param int    $id
	 *
	 * @return string
	 */
	protected function fixInternalLinks( $source_content, $id = null ) {
		return $this->optimizedFixInternalLinks( $source_content, $id );
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
			/**
			 * @var \DOMElement $url
			 */
			// Is this the attributionUrl?
			if ( $url->getAttribute( 'rel' ) === 'cc:attributionURL' ) {
				$url->parentNode->replaceChild(
					$dom->createTextNode( $url->nodeValue ),
					$url
				);
				$changed = true;
			}
		}

		if ( $changed ) {
			$content = $html5->saveHTML( $dom );
			$content = $this->html5ToXhtml( $content );
		}
		return $content;
	}

	/**
	 * Optimized image processing with caching
	 */
	protected function optimizedFixImages( $content ) {
		if ( stripos( $content, '<img' ) === false ) {
			return $content;
		}

		$cache_key = 'images_' . md5( $content );

		if ( isset( $this->processingCache[ $cache_key ] ) ) {
			return $this->processingCache[ $cache_key ];
		}

		$dom = $this->getCachedDomParser( $content );
		$images = $dom->getElementsByTagName( 'img' );
		$has_changes = false;

		foreach ( $images as $image ) {
			$old_src = $image->getAttribute( 'src' );

			// Use cached image swapping
			$new_src = $this->getCachedImageSwap( $old_src );

			if ( $old_src !== $new_src ) {
				$image->setAttribute( 'src', $new_src );
				$image->removeAttribute( 'srcset' );
				$has_changes = true;
			}
		}

		$result = $has_changes ? $this->saveHTMLFromDom( $dom ) : $content;
		$this->processingCache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Replace every image with the bigger original image
	 *
	 * @param $content
	 *
	 * @return string
	 */
	protected function fixImages( $content ) {
		return $this->optimizedFixImages( $content );
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

		$html = Content::init()->replaceInteractiveTags( $html );

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		];

		$spec = 'table=-border;';
		$spec .= 'div=title;';

		return HtmLawed::filter( $html, $config, $spec );
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
		return HtmLawed::filter( $html, $config );
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Echo Functions
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDocType( $book_contents, $metadata ) {

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $this->lang . '">' . "\n";
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoMetaData( $book_contents, $metadata ) {
		foreach ( $metadata as $name => $content ) {
			$name = Sanitize\sanitize_xml_id( str_replace( '_', '-', $name ) );
			if ( is_array( $content ) ) {
				foreach ( $content as $content_item ) {
					if ( isset( $content_item['name'] ) ) {
						$this->echoMetaDataItem( $name, $content_item['name'] );
					}
				}
				continue;
			}
			$this->echoMetaDataItem( $name, $content );
		}
	}

	protected function echoMetaDataItem( $name, $content_item ) {
		$content_item = trim( wp_strip_all_tags( html_entity_decode( $content_item ) ) ); // Plain text
		$content_item = preg_replace( '/\s+/', ' ', preg_replace( '/\n+/', ' ', $content_item ) ); // Normalize whitespaces
		$content_item = Sanitize\sanitize_xml_attribute( $content_item );
		printf( '<meta name="%s" content="%s" />', $name, $content_item );
		echo "\n";
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderCover( $book_contents, $metadata ) {
		// Does nothing.
		// Is here for child classes to override if ever needed.
	}

	/**
	 * @param array $book_contents
	 */
	protected function renderBeforeTitle( $book_contents ) {
		$i = $this->frontMatterPos;
		foreach ( [ 'before-title' ] as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) {
					continue; // Skip
				}

				$front_matter_id = $front_matter['ID'];
				$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

				if ( $compare !== $subclass ) {
					continue; //Skip
				}

				$slug = "front-matter-{$front_matter['post_name']}";
				$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				echo $this->blade->render(
					'export/generic-post-type',
					[
						'post_type_class' => 'front-matter',
						'subclass' => $subclass,
						'slug' => $slug,
						'post_number' => $i,
						'title' => decode( $title ),
						'content' => $content,
						'endnotes' => $this->doEndnotes( $front_matter_id ),
						'footnotes' => $this->doFootnotes( $front_matter_id ),
					]
				);

				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}

	protected function renderHalfTitle() {
		echo $this->blade->render(
			'export/half-title', [ 'title' => $this->processingCache['blog_name'] ]
		);
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderTitle( $book_contents, $metadata ) {
		// Look for custom title-page

		$content = '';
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$front_matter_id = $front_matter['ID'];
			$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

			if ( 'title-page' !== $subclass ) {
				continue; // Skip
			}

			$content = $front_matter['post_content'];
			break;
		}

		// HTML
		if ( $content ) {
			echo $this->blade->render(
				'export/title', [
					'content' => $content,
				]
			);
		} else {
			$contributors_data = $this->getFormattedContributors( $metadata );

			echo $this->blade->render(
				'export/title', [
					'title' => $this->processingCache['blog_name'],
					'subtitle' => $metadata['pb_subtitle'] ?? '',
					'authors' => $contributors_data['authors'],
					'editors' => $contributors_data['editors'],
					'logo' => current_theme_supports( 'pressbooks_publisher_logo' ) ? get_theme_support( 'pressbooks_publisher_logo' )[0]['logo_uri'] : null,
					'publisher' => $metadata['pb_publisher'] ?? '',
					'publisher_city' => $metadata['pb_publisher_city'] ?? '',
				]
			);
		}
	}

	/**
	 * @param array $metadata
	 */
	protected function renderCopyright( $metadata ) {

		if ( empty( $metadata['pb_book_license'] ) ) {
			$all_rights_reserved = true;
		} elseif ( $metadata['pb_book_license'] === 'all-rights-reserved' ) {
			$all_rights_reserved = true;
		} else {
			$all_rights_reserved = false;
		}
		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			$has_custom_copyright = true;
		} else {
			$has_custom_copyright = false;
		}

		// Custom Copyright must override All Rights Reserved
		if ( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) ) {
			$license = $this->doCopyrightLicense( $metadata );
			if ( $license ) {
				$license_copyright = $this->removeAttributionLink( $license );
			}
		}

		// Custom copyright
		if ( $has_custom_copyright ) {
			$custom_copyright = $this->tidy( $metadata['pb_custom_copyright'] );
		}

		// by default, so something is displayed
		$has_default = false;
		if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) ) {
			$has_default = true;
			$default_copyright_name = $this->processingCache['blog_name'] . ' ' . __( 'Copyright', 'pressbooks' ) . ' &copy; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$default_copyright_date = $meta['pb_copyright_year'] . ' ';
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				$default_copyright_date = date( 'Y', $meta['pb_publication_date'] );
			} else {
				$default_copyright_date = date( 'Y' );
			}
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				$default_copyright_holder = ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			}
		}
		$blade_vars = [];
		if ( isset( $license_copyright ) ) {
			$blade_vars['license_copyright'] = $license_copyright;
		}
		if ( isset( $custom_copyright ) ) {
			$blade_vars['custom_copyright'] = $custom_copyright;
		}
		if ( $has_default ) {
			$blade_vars['has_default'] = $has_default;
			$blade_vars['default_copyright_name'] = $default_copyright_name;
			$blade_vars['default_copyright_date'] = $default_copyright_date;
			if ( isset( $default_copyright_holder ) ) {
				$blade_vars['default_copyright_holder'] = $default_copyright_holder;
			}
		}
		echo $this->blade->render( 'export/copyright', $blade_vars );
	}

	/**
	 * @param array $book_contents
	 */
	protected function renderDedicationAndEpigraph( $book_contents ) {

		$index = $this->frontMatterPos;
		$parse_subsections = Export::shouldParseSubsections();

		foreach ( [ 'dedication', 'epigraph' ] as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) {
					continue; // Skip
				}

				$front_matter_id = $front_matter['ID'];
				$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

				if ( $compare !== $subclass ) {
					continue; // Skip
				}

				$slug = "front-matter-{$front_matter['post_name']}";
				$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				echo $this->blade->render(
					'export/dedication-epigraph', //TODO: Review if it could be consolidated in a single file
					[
						'subclass' => $subclass,
						'slug' => $slug,
						'front_matter_number' => $index,
						'title' => decode( $title ),
						'content' => $content,
						'endnotes' => $this->doEndnotes( $front_matter_id ),
						'footnotes' => $this->doFootnotes( $front_matter_id ),
						'subsection_class' => $parse_subsections ? 'with-subsections' : '',
					]
				);
				echo "\n";
				++$index;
			}
		}
		$this->frontMatterPos = $index;
	}

	/**
	 * @param array $book_contents
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

					$rendered_items[] = $this->blade->render(
						'export/bullet-toc-part', [
							'bullet_class' => 'part',
							'is_visible' => get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on',
							'has_content' => trim( $part_data['content'] ), // show in TOC
							'has_at_least_one_chapter' => $this->atLeastOneExport( $part['chapters'] ), // show in TOC
							'item' => [
								'is_epub' => false,
								'slug' => '#' . $part_data['href'],
								'title' => decode( $part_data['title'] ),
							],
						]
					);

					foreach ( $part['chapters'] as $chapter ) {

						if ( ! $chapter['export'] ) {
							continue;
						}

						$chapter_data = $this->getExtendedPostInformation( 'chapter', $chapter );

						$rendered_items[] = $this->renderTocItem( 'chapter', $chapter_data, true, true );
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

							$rendered_items[] = $this->renderTocItem( $post_type, $matter_data, true, true );

							break;

						case 'back-matter':
							$matter_data = $this->getExtendedPostInformation( $type, $val );

							$rendered_items[] = $this->renderTocItem( $type, $matter_data, true, true );

							break;
					}
				}
			}
		}
		echo $this->blade->render(
			'export/toc', [
				'title' => __( 'Contents', 'pressbooks' ),
				'toc' => $rendered_items,
			]
		);
	}

	/**
	 * Yields an estimated percentage slice of: 50 to 60
	 *
	 * @param  array $book_contents
	 * @param  array $metadata
	 * @return Generator
	 */
	protected function renderFrontMatterGenerator( $book_contents, $metadata ) : Generator {

		$y = new PercentageYield( 50, 60, count( $book_contents['front-matter'] ) );

		$index = $this->frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {
			yield from $y->tick( $this->generatorPrefix . __( 'Exporting front matter', 'pressbooks' ) );

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$data = $this->mapBookDataAndContent(
				$front_matter, $metadata, $index, [
					'type' => 'front_matter',
					'endnotes' => true,
					'footnotes' => true,
				]
			);

			$skip_classes = [
				'dedication',
				'epigraph',
				'title-page',
				'before-title',
			];

			if ( in_array( $data['subclass'], $skip_classes, true ) ) {
				continue;
			}

			if ( $this->hasIntroduction ) {
				$data['subclass'] .= ' post-introduction';
			}

			if ( 'introduction' === $data['subclass'] ) {
				$this->hasIntroduction = true;
			}

			echo $this->blade->render( 'export/generic-post-type', $data );

			++$index;
		}
		$this->frontMatterPos = $index;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createPromo( $book_contents, $metadata ) {

		$promo_html = apply_filters( 'pressbooks_pdf_promo', '' );
		if ( $promo_html ) {
			echo $promo_html;
		}
	}

	/**
	 * Yields an estimated percentage slice of: 60 to 70
	 *
	 * @param  array $book_contents
	 * @param  array $metadata
	 * @return Generator
	 */
	protected function renderPartsAndChaptersGenerator( $book_contents, $metadata ) : Generator {
		$yield = new PercentageYield( 60, 70, $this->countPartsAndChapters( $book_contents ) );

		$part_index = 1;
		$chapter_index = 1;
		$parts_amount = count( $book_contents['part'] );
		$parse_subsections = Export::shouldParseSubsections();

		foreach ( $book_contents['part'] as $part ) {
			yield from $yield->tick( $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' ) );

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
					'number' => romanize( $part_number ),
					'title' => decode( $part_title ),
					'content' => $part_content,
					'endnotes' => $this->doEndnotes( $part['ID'] ),
					'footnotes' => $this->doFootnotes( $part['ID'] ),
				]
			);

			$rendered_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {

				yield from $yield->tick( $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' ) );

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

				if ( $parse_subsections && Book::getSubsections( $chapter_id ) !== false ) {
					$chapter_content = Book::tagSubsections( $chapter_content, $chapter_id );
				}

				if ( ! $this->hasIntroduction ) {
					$this->hasIntroduction = true;
					$chapter_subclass .= ' introduction';
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$chapter_content .= $this->displayAboutTheAuthors
					? get_contributors_section( $chapter_id )
					: '';

				$chapter_number = ! str_contains( $chapter_subclass, 'numberless' ) ? $chapter_index : '';

				if ( preg_match_all( '/<style.*?scoped="scoped".*?>(.*?)<\/style>/is', $chapter_content, $matches ) ) {
					$scoped_styles = implode( "\n", $matches[1] ) . "\n";
					add_filter('pb_process_scoped_styles', function( $st ) use ( $scoped_styles ) {
						return $st . $this->cleanH5PCss( $scoped_styles );
					});
				}
				$chapter_content = preg_replace( '/<style.*?scoped="scoped".*?<\/style>/i', '', $chapter_content );

				$rendered_chapters .= $this->blade->render(
					'export/chapter',
					[
						'subclass' => $chapter_subclass,
						'slug' => $chapter_slug,
						'sanitized_title' => decode( $chapter_short_title ) ?: wp_strip_all_tags( decode( $chapter['post_title'] ) ),
						'number' => $chapter_number,
						'title' => decode( $chapter_title ),
						'is_new_buckram' => $this->wrapHeaderElements,
						'output_short_title' => $this->outputShortTitle,
						'author' => $chapter_author,
						'subtitle' => $chapter_subtitle,
						'short_title' => $chapter_short_title,
						'content' => $chapter_content,
						'append_content' => $append_chapter_content,
						'endnotes' => $this->doEndnotes( $chapter_id ),
						'footnotes' => $this->doFootnotes( $chapter_id ),
						'subsection_class' => $parse_subsections ? 'with-subsections' : '',
					]
				);

				if ( $chapter_number ) {
					++$chapter_index;
				}
			}

			if ( $invisible ) {
				$this->renderPart( $part_slug, $rendered_chapters );

				continue;
			}

			if ( $parts_amount === 1 ) {
				$content = $part_content
					? $rendered_part . $rendered_chapters
					: $rendered_chapters;

				$this->renderPart( $part_slug, $content );
			} else {
				if ( ! $rendered_chapters ) {
					if ( $part_content ) {
						$this->renderPart( $part_slug, $rendered_part );
					}

					continue;
				}

				$this->renderPart( $part_slug, $rendered_part . $rendered_chapters );
			}

			++$part_index;

			// Clear processing caches periodically to manage memory
			if ( $part_index % 3 === 0 ) {
				$this->clearProcessingCaches();
			}
		}
	}

	/**
	 * Yields an estimated percentage slice of: 70 to 80
	 *
	 * @param  array $book_contents
	 * @param  array $metadata
	 * @return Generator
	 */
	protected function renderBackMatterGenerator( $book_contents, $metadata ) : Generator {

		$y = new PercentageYield( 70, 80, count( $book_contents['back-matter'] ) );

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {
			yield from $y->tick( $this->generatorPrefix . __( 'Exporting back matter', 'pressbooks' ) );

			if ( ! $back_matter['export'] ) {
				continue;
			}

			$data = $this->mapBookDataAndContent(
				$back_matter, $metadata, $i, [
					'type' => 'back_matter',
					'endnotes' => true,
					'footnotes' => true,
				]
			);

			echo $this->blade->render( 'export/generic-post-type', $data );

			++$i;
		}

	}

	/**
	 * Renders the complete part wrapper
	 *
	 * @param  string $id
	 * @param  string $content
	 * @return void
	 */
	protected function renderPart( string $id, string $content ): void {
		echo $this->blade->render(
			'export/part-wrapper',
			[
				'id' => $id,
				'content' => $content,
			]
		);
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

	/**
	 * Dependency check.
	 *
	 * @return bool
	 */
	public static function hasDependencies() {
		if ( true === check_xmllint_install() ) {
			return true;
		}

		return false;
	}

}
