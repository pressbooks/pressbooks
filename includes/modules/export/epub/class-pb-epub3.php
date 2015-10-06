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
	 * @param array $args
	 */

	
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
		
		// @TODO Check for remote resources
		
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
	 * Parse CSS, copy assets, rewrite copy.
	 *
	 * @param string $path_to_original_stylesheet*
	 * @param string $path_to_copy_of_stylesheet
	 */
	protected function scrapeKneadAndSaveCss( $path_to_original_stylesheet, $path_to_copy_of_stylesheet ) {

		// html5 targeted css
		$css3 = 'css3.css';
		$path_to_css3_stylesheet = $this->dir . "/templates/epub3/css/$css3";

		$scss_dir = pathinfo( $path_to_original_stylesheet, PATHINFO_DIRNAME );
		$path_to_epub_assets = $this->tmpDir . '/OEBPS/assets';

		$scss = file_get_contents( $path_to_copy_of_stylesheet );
		
		// Append css3
		$scss .= $this->loadTemplate( $path_to_css3_stylesheet );
		
		// Append overrides
		$scss .=  $this->cssOverrides;
		
		if ( $this->isScss() ) {
			$css = \PressBooks\SASS\compile( $scss, array( 'load_paths' => array( $this->genericMixinsPath, $this->globalTypographyMixinPath, get_stylesheet_directory() ) ) );
		} else {
			$css = static::injectHouseStyles( $scss );
		}

		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) use ( $scss_dir, $path_to_epub_assets ) {

			$url = $matches[3];
			$filename = sanitize_file_name( basename( $url ) );

			if ( preg_match( '#^images/#', $url ) && substr_count( $url, '/' ) == 1 ) {

				// Look for "^images/"
				// Count 1 slash so that we don't touch stuff like "^images/out/of/bounds/"	or "^images/../../denied/"

				$my_image = realpath( "$scss_dir/$url" );
				if ( $my_image ) {
					copy( $my_image, "$path_to_epub_assets/$filename" );
					return "url(assets/$filename)";
				}

			} elseif ( preg_match( '#^https?://#i', $url ) && preg_match( '/(\.jpe?g|\.gif|\.png)$/i', $url ) ) {

				// Look for images via http(s), pull them in locally

				if ( $new_filename = $this->fetchAndSaveUniqueImage( $url, $path_to_epub_assets ) ) {
					return "url(assets/$new_filename)";
				}

			} elseif ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {

				// Look for themes-book/pressbooks-book/fonts/*.otf (or .woff, or .ttf), copy into our Epub

				$my_font = realpath( PB_PLUGIN_DIR . $url );
				if ( $my_font ) {
					copy( $my_font, "$path_to_epub_assets/$filename" );
					return "url(assets/$filename)";
				}

			}

			return $matches[0]; // No change

		}, $css );

		// Overwrite the new file with new info
		file_put_contents( $path_to_copy_of_stylesheet, $css );
		
		// Output compiled CSS for debugging.
		$wp_upload_dir = wp_upload_dir();
		$debug_dir = $wp_upload_dir['basedir'] . '/export-css';
		if ( ! is_dir( $debug_dir ) ) {
			mkdir( $debug_dir );
		}
		$debug_file = $debug_dir . '/epub3.css';
		file_put_contents( $debug_file, $css );
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
		$doc = $this->scrapeAndKneadMedia( $doc );

		// Deal with <a href="">, <a href=''>, and other mutations
		$doc = $this->kneadHref( $doc, $type, $pos );

		// Make sure empty tags (e.g. <b></b>) don't get turned into self-closing versions by adding an empty text node to them.
		$xpath = new \DOMXPath( $doc );
		while( ( $nodes = $xpath->query( '//*[not(text() or node() or self::br or self::hr or self::img)]' ) ) && $nodes->length > 0 ) {
		    foreach ( $nodes as $node ) {
		        $node->appendChild( new \DOMText('') );
		    }
		}

		// If you are storing multi-byte characters in XML, then saving the XML using saveXML() will create problems.
		// Ie. It will spit out the characters converted in encoded format. Instead do the following:
		$html = $doc->saveXML( $doc->documentElement );

		// Remove auto-created <html> <body> and <!DOCTYPE> tags.
		$html = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array ( '<html>', '</html>', '<body>', '</body>' ), array ( '', '', '', '' ), $html ) );

		// Mobi7 hacks
		$html = $this->transformXML( $utf8_hack . "<html>$html</html>", $this->dir . '/templates/epub201/mobi-hacks.xsl' );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
	}

	/**
	 * Fetch a url with wp_remote_get(), save it to $fullpath with a unique name.
	 * Will return an empty string if something went wrong.
	 * 
	 * @staticvar array $already_done
	 * @param string $url
	 * @param string $fullpath
	 * @return string|array
	 */
	protected function fetchAndSaveUniqueMedia( $url, $fullpath ) {
		// Cheap cache
		static $already_done = array();
		if ( isset( $already_done[$url] ) ) {
			return $already_done[$url];
		}

		$response = wp_remote_get( $url, array( 'timeout' => $this->timeout ) );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: handle $response->get_error_message();
			$already_done[$url] = '';
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
			$already_done[$url] = '';
			return ''; // Not a valid media type
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( file_get_contents( $tmp_file ) ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}

		$already_done[$url] = $filename;
		return $filename;
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
		static $already_done = array();
		if ( isset( $already_done[$url] ) ) {
			return $already_done[$url];
		}

		$response = wp_remote_get( $url, array( 'timeout' => $this->timeout ) );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: handle $response->get_error_message();
			$already_done[$url] = '';
			return '';
		}
		
		// Basename without query string
		$filename = explode( '?', basename( $url ) );

		// isolate latex image service from WP, add file extension
		if ( 's.wordpress.com' == parse_url( $url, PHP_URL_HOST ) && 'latex.php' == $filename[0] ) {
			$filename = md5( array_pop( $filename ) );
			// content-type = 'image/png'
			$type = explode( '/', $response['headers']['content-type'] );
			$type = array_pop( $type );
			$filename = $filename . "." . $type;
		} else {
			$filename = array_shift( $filename );
			$filename = sanitize_file_name( urldecode( $filename ) );
			$filename = Sanitize\force_ascii( $filename );
		}

		$tmp_file = \PressBooks\Utility\create_tmp_file();
		file_put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \PressBooks\Image\is_valid_image( $tmp_file, $filename ) ) {
			$already_done[$url] = '';
			return ''; // Not an image
		}

		if ( $this->compressImages ) {
			$format = explode( '.', $filename );
			$format = strtolower( end( $format ) ); // Extension
			\PressBooks\Image\resize_down( $format, $tmp_file );
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( file_get_contents( $tmp_file ) ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}

		$already_done[$url] = $filename;
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
		);

		// Find all the image files, insert them into the OPF file
		$html = '';
		$path_to_assets = $this->tmpDir . '/OEBPS/assets';
		$assets = scandir( $path_to_assets );
		$used_ids = array ();

		foreach ( $assets as $asset ) {
			if ( '.' == $asset || '..' == $asset ) continue;
			$mimetype = $this->mediaType( "$path_to_assets/$asset" );
			if ( $this->coverImage == $asset ) {
				$file_id = 'cover-image';
			} else {
				$file_id = 'media-' . pathinfo( "$path_to_assets/$asset", PATHINFO_FILENAME );
				$file_id = Sanitize\sanitize_xml_id( $file_id );
			}

			// Check if a media id has already been used, if so give it a new one
			$check_if_used = $file_id;
			for ( $i = 2; $i <= 999; $i ++  ) {
				if ( empty( $used_ids[$check_if_used] ) ) break;
				else $check_if_used = $file_id . "-$i";
			}
			$file_id = $check_if_used;

			$html .= sprintf( '<item id="%s" href="OEBPS/assets/%s" media-type="%s" />', $file_id, $asset, $mimetype ) . "\n\t\t";

			$used_ids[$file_id] = true;
		}
		$vars['manifest_assets'] = $html;

		//Clear the html buffer for reuse
		$html = '';

		//Loop through the html files for the manifest and assemble them. Assign properties based on their content.
		foreach ( $this->manifest as $k => $v ) {
			$properties = $this->getProperties( $this->tmpDir . "/OEBPS/" . $v['filename'] );
			(array_key_exists( 'scripted', $properties ) ? $scripted = 'properties="scripted" ' : $scripted = '');

			$html .= sprintf( '<item id="%s" href="OEBPS/%s" %smedia-type="application/xhtml+xml" />', $k, $v['filename'], $scripted ) . "\n\t\t";

		}
		$vars['manifest_filelist'] = $html;
		$vars['do_copyright_license'] = strip_tags( $this->doCopyrightLicense( $metadata ) );

		// Put contents
		file_put_contents(
			$this->tmpDir . "/book.opf", $this->loadTemplate( $this->dir . '/templates/epub3/opf.php', $vars )
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


		$vars = array (
		    'author' => @$metadata['pb_author'],
		    'manifest' => $this->manifest,
		    'dtd_uid' => ( ! empty( $metadata['pb_ebook_isbn'] ) ? $metadata['pb_ebook_isbn'] : get_bloginfo( 'url' ) ),
		);

		file_put_contents(
			$this->tmpDir . "/toc.xhtml", $this->loadTemplate( $this->dir . '/templates/epub3/toc.php', $vars ) );
		
		// for backwards compatibility
		file_put_contents(
			$this->tmpDir . "/toc.ncx",
			$this->loadTemplate( $this->dir . '/templates/epub3/ncx.php', $vars ) );


	}


	/**
	 * Override load template function, switch path from epub201 to epub3 when possible.
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
