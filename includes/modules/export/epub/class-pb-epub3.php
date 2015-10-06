<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Export\Epub;

use PressBooks\Export\Epub;
use PressBooks\Sanitize;

require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

class Epub3 extends Epub\Epub201 {

	/**
	 * @var array
	 */
	protected $fetchedMediaCache = array();

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
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// HTML5 targeted css
		$this->extraCss = $this->dir . '/templates/epub3/css/css3.css';

		return parent::convert();
	}

	/**
	 * Check for existence of properties attributes
	 *
	 * @param string $html_file
	 * @return array $properties
	 * @throws \Exception
	*/
	protected function getProperties( $html_file ) {

		$html = file_get_contents( $html_file );
		$properties = array();
	
		if ( empty( $html ) ) {
			throw new \Exception( 'File contents empty for getProperties' );
		}

		// Check for script elements
		if ( preg_match_all( '/<script[^>]*>.*?<\/script>/is', $html ) >= 1 ) {
			$properties['scripted'] = 1;
		}
		
		// TODO: Check for remote resources
		
		return $properties;
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

		$config = array(
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			// 'hook' => '\PressBooks\Sanitize\html5_to_epub3', // TODO
			'tidy' => - 1,
			'make_tag_strict' => 2,

		);

		$spec = '';
		$spec .= 'a=,-charset,-coords,-rev,-shape;';
		$spec .= 'area=-nohref;';
		$spec .= 'col=-align,-char,-charoff,-valign,-width;';
		$spec .= 'colgroup=-align,-char,-charoff,-valign,-width;';
		$spec .= 'div=-align;';
		$spec .= 'iframe=-align,-frameborder,-longdesc,-marginheight,-marginwidth,-scrolling;';
		$spec .= 'img=-longdesc;';
		$spec .= 'li=-type;';
		$spec .= 'link=-charset,-rev,-target;';
		$spec .= 'menu=-compact;';
		$spec .= 'object=-archive,-classid,-codebase,-codetype,-declare,-standby;';
		$spec .= 'ol=-type;';
		$spec .= 'param=-type,-valuetype;';
		$spec .= 't=-abbr,-axis;';
		$spec .= 'table=-border,-cellspacing,-cellpadding,-frame,-rules,-width;';
		$spec .= 'tbody=-align,-char,-charoff,-valign;';
		$spec .= 'td=-axis,-abbr,-align,-char,-charoff,-scope,-valign;';
		$spec .= 'tfoot=-align,-char,-charoff,-valign;';
		$spec .= 'th=-align,-char,-charoff,-valign;';
		$spec .= 'thead=-align,-char,-charoff,-valign;';
		$spec .= 'tr=-align,-char,-charoff,-valign;';
		$spec .= 'ul=-type;';

		// Reset on each htmLawed invocation
		unset( $GLOBALS['hl_Ids'] );
		if ( ! empty( $this->fixme ) )
			$GLOBALS['hl_Ids'] = $this->fixme;

		$html = htmLawed( $html, $config, $spec );

		return $html;
	}

	/**
	 * Fetch a url with wp_remote_get(), save it to $fullpath with a unique name.
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $url
	 * @param string $fullpath
	 * @return string|array
	 */
	protected function fetchAndSaveUniqueMedia( $url, $fullpath ) {

		if ( isset( $this->fetchedMediaCache[$url] ) ) {
			return $this->fetchedMediaCache[$url];
		}

		$response = wp_remote_get( $url, array( 'timeout' => $this->timeout ) );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: handle $response->get_error_message();
			$this->fetchedMediaCache[$url] = '';
			return '';
		}

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );
		$filename = Sanitize\force_ascii( $filename );

		$tmp_file = \PressBooks\Utility\create_tmp_file();
		file_put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \PressBooks\Media\is_valid_media( $tmp_file, $filename ) ) {
			$this->fetchedMediaCache[$url] = '';
			return ''; // Not a valid media type
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		}
		elseif ( md5( file_get_contents( $tmp_file ) ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}

		$this->fetchedMediaCache[$url] = $filename;
		return $filename;
	}

	/**
	 * Parse HTML snippet, download all found <audio>, <video> and <source> tags 
	 * into /OEBPS/assets/, return the HTML with changed 'src' paths.
	 *
	 * @param \DOMDocument $doc
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadMedia( \DOMDocument $doc ) {

		$fullpath = $this->tmpDir . '/OEBPS/assets';
		$tags = array( 'source', 'audio', 'video' );

		foreach ( $tags as $tag ) {

			$sources = $doc->getElementsByTagName( $tag );
			foreach ( $sources as $source ) {

				if ( $source->getAttribute( 'src' ) != '' ) {
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
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEPBS() ?' );
		}

		$vars = array (
		    'meta' => $metadata,
		    'manifest' => $this->manifest,
		    'stylesheet' => $this->stylesheet,
			'lang' => $this->lang,
		);

		$vars['manifest_assets'] = $this->buildManifestAssetsHtml();

		$vars['do_copyright_license'] = strip_tags( $this->doCopyrightLicense( $metadata ) );

		// Loop through the html files for the manifest and assemble them. Assign properties based on their content.
		//
		$html = '';
		foreach ( $this->manifest as $k => $v ) {
			$properties = $this->getProperties( $this->tmpDir . "/OEBPS/" . $v['filename'] );
			( array_key_exists( 'scripted', $properties ) ? $scripted = 'properties="scripted" ' : $scripted = '' );
			$html .= sprintf( '<item id="%s" href="OEBPS/%s" %smedia-type="application/xhtml+xml" />', $k, $v['filename'], $scripted ) . "\n\t\t";
		}
		$vars['manifest_filelist'] = $html;

		// Put contents
		file_put_contents(
			$this->tmpDir . "/book.opf",
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
			throw new \Exception( '$this->manifest cannot be empty. Did you forget to call $this->createOEPBS() ?' );
		}

		$vars = array(
			'author' => @$metadata['pb_author'],
			'manifest' => $this->manifest,
			'dtd_uid' => ( ! empty( $metadata['pb_ebook_isbn'] ) ? $metadata['pb_ebook_isbn'] : get_bloginfo( 'url' ) ),
			'enable_external_identifier' => false,
			'lang' => $this->lang,
		);

		file_put_contents(
			$this->tmpDir . "/toc.xhtml",
			$this->loadTemplate( $this->dir . '/templates/epub3/toc.php', $vars )
		);

		// For backwards compatibility
		file_put_contents(
			$this->tmpDir . "/toc.ncx",
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
	protected function loadTemplate( $path, array $vars = array() ) {

		$search = '/templates/epub201/';
		$replace = '/templates/epub3/';

		$pos = strpos( $path, $search );
		if ( $pos !== false ) {
			$newPath = substr_replace( $path, $replace, $pos, strlen( $search ) );
			if ( file_exists( $newPath ) ) {
				$path = $newPath;
			}
		}

		return parent::loadTemplate( $path, $vars );
	}

}
