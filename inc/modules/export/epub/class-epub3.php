<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Epub;

use function \Pressbooks\Sanitize\sanitize_xml_attribute;
use function \Pressbooks\Utility\debug_error_log;
use function \Pressbooks\Utility\oxford_comma;
use Pressbooks\HtmLawed;
use Pressbooks\HtmlParser;
use Pressbooks\Sanitize;

class Epub3 extends Epub201 {

	/**
	 * @var array
	 */
	protected $fetchedMediaCache = [];

	/**
	 * @var string
	 */
	protected $version = '3';

	/**
	 * @var string
	 */
	protected $filext = 'xhtml';

	/**
	 * $var string
	 */
	protected $dir = __DIR__;

	/**
	 * $var string
	 */
	protected $suffix = '_3.epub';

	/**
	 * Regular expression for supported fonts  (used in /($supportedFontExtensions)/i')
	 *
	 * @var string
	 */
	protected $supportedFontExtensions = '\.woff|\.otf|\.ttf';

	/**
	 * MathML Tags
	 *
	 * @var array
	 */
	protected $MathMLTags = [
		'math',
		'maction',
		'maligngroup',
		'malignmark',
		'menclose',
		'merror',
		'mfenced',
		'mfrac',
		'mglyph',
		'mi',
		'mlabeledtr',
		'mlongdiv',
		'mmultiscripts',
		'mn',
		'mo',
		'mover',
		'mpadded',
		'mphantom',
		'mroot',
		'mrow',
		'ms',
		'mscarries',
		'mscarry',
		'msgroup',
		'msline',
		'mspace',
		'msqrt',
		'msrow',
		'mstack',
		'mstyle',
		'msub',
		'msup',
		'msubsup',
		'mtable',
		'mtd',
		'mtext',
		'mtr',
		'munder',
		'munderover',
		'semantics',
		'annotation',
		'annotation-xml',
	];

	/**
	 * JavaScript Events
	 *
	 * @var array
	 */
	protected $javaScriptEvents = [
		'onabort',
		'onblur',
		'oncanplay',
		'oncanplaythrough',
		'onchange',
		'onclick',
		'oncontextmenu',
		'ondblclick',
		'ondrag',
		'ondragend',
		'ondragenter',
		'ondragleave',
		'ondragover',
		'ondragstart',
		'ondrop',
		'ondurationchange',
		'onemptied',
		'onended',
		'onerror',
		'onfocus',
		'oninput',
		'oninvalid',
		'onkeydown',
		'onkeypress',
		'onkeyup',
		'onload',
		'onloadeddata',
		'onloadedmetadata',
		'onloadstart',
		'onmousedown',
		'onmousemove',
		'onmouseout',
		'onmouseover',
		'onmouseup',
		'onmousewheel',
		'onpause',
		'onplay',
		'onplaying',
		'onprogress',
		'onratechange',
		'onreadystatechange',
		'onreset',
		'onscroll',
		'onseeked',
		'onseeking',
		'onselect',
		'onshow',
		'onstalled',
		'onsubmit',
		'onsuspend',
		'ontimeupdate',
		'onvolumechange',
		'onwaiting',
	];

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {
		return parent::convert();
	}

	/**
	 * @return \Generator
	 * @throws \Exception
	 */
	function convertGenerator() : \Generator {
		// HTML5 targeted css
		$this->extraCss = $this->dir . '/templates/epub3/css/css3.css';
		yield from parent::convertGenerator();
	}

	/**
	 * Check for existence of properties attributes
	 *
	 * @param string $html_file
	 *
	 * @return array $properties
	 * @throws \Exception
	 */
	protected function getProperties( $html_file ) {

		$html = \Pressbooks\Utility\get_contents( $html_file );
		$properties = [];

		if ( empty( $html ) ) {
			throw new \Exception( 'File contents empty for getProperties' );
		}

		if ( $this->isMathML( $html ) ) {
			$properties['mathml'] = 1;
		}

		if ( $this->isScripted( $html ) ) {
			$properties['scripted'] = 1;
		}

		// TODO: Check for remote resources

		return $properties;
	}

	/**
	 * Check for existence of scripting MathML elements
	 *
	 * @param string $html
	 *
	 * @return bool
	 */
	protected function isMathML( $html ) {

		foreach ( $this->MathMLTags as $tag ) {
			if ( false !== stripos( $html, "<$tag>" ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Check for existence of scripting elements
	 *
	 * @param string $html
	 *
	 * @return bool
	 */
	protected function isScripted( $html ) {

		if ( preg_match( '/<script[^>]*>.*?<\/script>/is', $html ) ) {
			return true;
		}

		try {
			$html5 = new HtmlParser( true );
			$doc = $html5->loadHTML( $html );
			foreach ( $doc->getElementsByTagname( '*' ) as $element ) {
				foreach ( iterator_to_array( $element->attributes ) as $name => $attribute ) {
					if ( in_array( $name, $this->javaScriptEvents, true ) ) {
						return true;
					}
				}
			}
		} catch ( \Exception $e ) {
			debug_error_log( $e );
		}

		return false;
	}

	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Venn diagram join between XTHML + HTML5 Deprecated Attributes
		//
		// Our $spec is artisanally hand crafted based on squinting very hard while reading the following docs:
		//
		//  + 2.3 - Extra HTML specifications using the $spec parameter
		//  + 3.4.6 -  Transformation of deprecated attributes
		//  + 3.3.2  - Tag-transformation for better compliance with standards
		//  + HTML5 - Deprecated Tags & Attributes
		//
		// That is we do not remove deprecated attributes that are already transformed by htmLawed
		//
		// More info:
		//  + http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/beta/htmLawed_README.htm
		//  + http://www.tutorialspoint.com/html5/html5_deprecated_tags.htm

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_epub3',
			'tidy' => -1,
			'make_tag_strict' => 2,
			'comment' => 1,
		];

		$spec = '';
		$spec .= 'a=,-charset,-coords,-rev,-shape;';
		$spec .= 'area=-nohref;';
		$spec .= 'col=-align,-char,-charoff,-valign,-width;';
		$spec .= 'colgroup=-align,-char,-charoff,-valign,-width;';
		$spec .= 'div=-align;';
		$spec .= 'iframe=-align,-frameborder,-longdesc,-marginheight,-marginwidth,-scrolling;';
		$spec .= 'img=-longdesc,-srcset;';
		$spec .= 'link=-charset,-rev,-target;';
		$spec .= 'menu=-compact;';
		$spec .= 'object=-archive,-classid,-codebase,-codetype,-declare,-standby;';
		$spec .= 'param=-type,-valuetype;';
		$spec .= 't=-abbr,-axis;';
		$spec .= 'table=-border,-cellpadding,-frame,-rules;';
		$spec .= 'tbody=-align,-char,-charoff,-valign;';
		$spec .= 'td=-axis,-abbr,-align,-char,-charoff,-scope,-valign;';
		$spec .= 'tfoot=-align,-char,-charoff,-valign;';
		$spec .= 'th=-align,-char,-charoff,-valign;';
		$spec .= 'thead=-align,-char,-charoff,-valign;';
		$spec .= 'tr=-align,-char,-charoff,-valign;';
		$spec .= 'ul=-type;';

		// Reset on each htmLawed invocation
		unset( $GLOBALS['hl_Ids'] );
		if ( ! empty( $this->fixme ) ) {
			$GLOBALS['hl_Ids'] = $this->fixme;
		}

		$html = HtmLawed::filter( $html, $config, $spec );

		return $html;
	}

	/**
	 * Fetch a url with wp_remote_get(), save it to $fullpath with a unique name.
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $url
	 * @param string $fullpath
	 *
	 * @return string|array
	 */
	protected function fetchAndSaveUniqueMedia( $url, $fullpath ) {

		if ( isset( $this->fetchedMediaCache[ $url ] ) ) {
			return $this->fetchedMediaCache[ $url ];
		}

		$response = wp_remote_get(
			$url, [
				'timeout' => $this->timeout,
			]
		);

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			try {
				// protocol relative urls handed to wp_remote_get will fail
				// try adding a protocol
				$protocol_relative = wp_parse_url( $url );
				if ( ! isset( $protocol_relative['scheme'] ) ) {
					if ( true === is_ssl() ) {
						$url = 'https:' . $url;
					} else {
						$url = 'http:' . $url;
					}
				}
				$response = wp_remote_get(
					$url, [
						'timeout' => $this->timeout,
					]
				);
				if ( is_wp_error( $response ) ) {
					throw new \Exception( 'Bad URL: ' . $url );
				}
			} catch ( \Exception $exc ) {
				$this->fetchedImageCache[ $url ] = '';
				debug_error_log( '\PressBooks\Export\Epub3\fetchAndSaveUniqueMedia wp_error on wp_remote_get() - ' . $response->get_error_message() . ' - ' . $exc->getMessage() );
				return '';
			}
		}

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );
		$filename = explode( '#', $filename )[0]; // Remove trailing anchors
		$filename = sanitize_file_name( urldecode( $filename ) );
		$filename = Sanitize\force_ascii( $filename );

		// A book with a lot of media can trigger "Fatal Error Too many open files" because tmpfiles are not closed until PHP exits
		// Use a $resource_key so we can close the tmpfile ourselves
		$resource_key = uniqid( 'tmpfile-epub-', true );
		$tmp_file = \Pressbooks\Utility\create_tmp_file( $resource_key );
		\Pressbooks\Utility\put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \Pressbooks\Media\is_valid_media( $tmp_file, $filename ) ) {
			$this->fetchedMediaCache[ $url ] = '';
			fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine
			return ''; // Not a valid media type
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( \Pressbooks\Utility\get_contents( $tmp_file ) ) !== md5( \Pressbooks\Utility\get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}
		fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine

		$this->fetchedMediaCache[ $url ] = $filename;
		return $filename;
	}

	/**
	 * Parse HTML snippet, download all found <audio>, <video> and <source> tags
	 * into /OEBPS/assets/, return the HTML with changed 'src' paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadMedia( \DOMDocument $doc ) {

		$fullpath = $this->tmpDir . '/OEBPS/assets';
		$tags = [ 'source', 'audio', 'video' ];

		foreach ( $tags as $tag ) {

			$sources = $doc->getElementsByTagName( $tag );
			foreach ( $sources as $source ) {
				/** @var $source \DOMElement */
				if ( ! empty( $source->getAttribute( 'src' ) ) ) {
					// Fetch the audio file
					$url = $source->getAttribute( 'src' );
					$filename = $this->fetchAndSaveUniqueMedia( $url, $fullpath );

					if ( $filename ) {
						// Change src to new relative path
						$source->setAttribute( 'src', 'assets/' . $filename );
					} else {
						// Tag broken media
						$source->setAttribute( 'src', "{$url}#fixme" );
					}
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
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEBPS() ?' );
		}

		$vars = [
			'manifest' => $this->manifest,
			'stylesheet' => $this->stylesheet,
			'lang' => $this->lang,
		];

		$vars['manifest_assets'] = $this->buildManifestAssetsHtml();

		$vars['do_copyright_license'] = sanitize_xml_attribute(
			wp_strip_all_tags( $this->doCopyrightLicense( $metadata ), true )
		);

		// Loop through the html files for the manifest and assemble them. Assign properties based on their content.
		$html = '';
		foreach ( $this->manifest as $k => $v ) {
			$properties = $this->getProperties( $this->tmpDir . '/OEBPS/' . $v['filename'] );

			array_key_exists( 'mathml', $properties ) ? $mathml = 'properties="mathml" ' : $mathml = '';
			array_key_exists( 'scripted', $properties ) ? $scripted = 'properties="scripted" ' : $scripted = '';

			$html .= sprintf( '<item id="%s" href="OEBPS/%s" %s%smedia-type="application/xhtml+xml" />', $k, $v['filename'], $mathml, $scripted ) . "\n\t\t";
		}
		$vars['manifest_filelist'] = $html;

		// Sanitize metadata for usage in XML template
		foreach ( $metadata as $key => $val ) {
			if ( is_array( $val ) ) {
				$items = [];
				foreach ( $val as $item ) {
					if ( isset( $item['name'] ) ) {
						$items[] = sanitize_xml_attribute( $item['name'] );
					}
				}
				if ( ! empty( $items ) ) {
					$metadata[ $key ] = oxford_comma( $items );
				}
			} else {
				$metadata[ $key ] = sanitize_xml_attribute( $val );
			}
		}
		$vars['meta'] = $metadata;

		// Put contents
		\Pressbooks\Utility\put_contents(
			$this->tmpDir . '/book.opf',
			$this->loadTemplate( $this->dir . '/templates/epub3/opf.php', $vars )
		);
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
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEBPS() ?' );
		}

		$authors = '';
		if ( isset( $metadata['pb_authors'] ) && is_array( $metadata['pb_authors'] ) && ! empty( $metadata['pb_authors'] ) ) {
			$authors = [];
			foreach ( $metadata['pb_authors'] as $author ) {
				$authors[] = $author['name'];
			}
			$authors = oxford_comma( $authors );
		}

		// Sanitize variables for usage in XML template
		$vars = [
			'author' => sanitize_xml_attribute( $authors ),
			'manifest' => $this->manifest,
			'dtd_uid' => ! empty( $metadata['pb_ebook_isbn'] ) ? sanitize_xml_attribute( $metadata['pb_ebook_isbn'] ) : sanitize_xml_attribute( get_bloginfo( 'url' ) ),
			'enable_external_identifier' => false,
			'lang' => $this->lang,
		];

		\Pressbooks\Utility\put_contents(
			$this->tmpDir . '/toc.xhtml',
			$this->loadTemplate( $this->dir . '/templates/epub3/toc.php', $vars )
		);

		// For backwards compatibility
		\Pressbooks\Utility\put_contents(
			$this->tmpDir . '/toc.ncx',
			$this->loadTemplate( $this->dir . '/templates/epub201/ncx.php', $vars )
		);
	}


	/**
	 * Override load template function
	 * Switch path from /epub201 to /epub3 when possible.
	 *
	 * @param string $path
	 * @param array $vars (optional)
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function loadTemplate( $path, array $vars = [] ) {

		$search = '/templates/epub201/';
		$replace = '/templates/epub3/';

		$pos = strpos( $path, $search );
		if ( false !== $pos ) {
			$new_path = substr_replace( $path, $replace, $pos, strlen( $search ) );
			if ( file_exists( $new_path ) ) {
				$path = $new_path;
			}
		}

		return parent::loadTemplate( $path, $vars );
	}

}
