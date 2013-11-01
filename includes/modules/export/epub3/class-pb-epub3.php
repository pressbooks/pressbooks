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
	 * $var string
	 */
	protected $dir = __DIR__;

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
		mkdir( $this->tmpDir . '/OEBPS/assets' );
		mkdir( $this->tmpDir . '/OEBPS/audios' );
		mkdir( $this->tmpDir . '/OEBPS/videos' );

		file_put_contents(
			$this->tmpDir . '/META-INF/container.xml', $this->loadTemplate( $this->dir . '/templates/container.php' ) );
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
		$html = $this->transformXML( $utf8_hack . "<html>$html</html>", $this->dir . '/templates/mobi-hacks.xsl' );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
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

			$html .= sprintf( '<item id="%s" href="OEBPS/assets/%s" media-type="%s" />', $file_id, $asset, $mimetype ) . "\n";

			$used_ids[$file_id] = true;
		}
		$vars['manifest_assets'] = $html;

		// Put contents
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
			$this->tmpDir . "/book.opf", $this->loadTemplate( $this->dir . '/templates/opf.php', $vars ) );
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
			$this->tmpDir . "/toc.xhtml", $this->loadTemplate( $this->dir . '/templates/ncx.php', $vars ) );
	}

}
