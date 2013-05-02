<?php

/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import\Epub;


use PressBooks\Import\Import;

require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
require_once( ABSPATH . "wp-admin" . '/includes/media.php' );
require_once( ABSPATH . "wp-admin" . '/includes/image.php' );

class Epub201 extends Import {

	/**
	 * Reference to the object that represents the epub zip folder
	 *
	 * @var \ZipArchive
	 */
	private $zip;

	/**
	 * relative path the where the content in is
	 *
	 * @var string
	 */
	private $basedir = '';

	private $tempdir;

	private $imagefiles = array();

	private $chapters = array();

	//private $post_types = array('front-matter' => 'front-matter', 'chapter' => 'chapter', 'back-matter' => 'back-matter');
	private $available_chapters = array();

	private $selected_chapters = array();

	/**
	 * Holds the data from OPF file
	 *
	 * @var \SimpleXMLElement
	 */
	private $content_xml;


	/**
	 * Static function to call the constructor and start the import
	 *
	 * @param string $path_and_file_name - must be /path/to/filename
	 * @param string $selective_import   - user wants to choose which chapters to bring in
	 */
	public static function import( $path_and_file_name, $selective_import ) {

		$importer = new self ( $path_and_file_name, $selective_import );

		if ( isset ( $selective_import ) ) {
			$step = get_option( 'pressbooks_selective_import' );
			if ( $step == 'step1' ) {
				$importer->setChapters();
			} elseif ( $step == 'step2' ) {
				// set instance variable
				$importer->selected_chapters = get_option( 'pressbooks_selective_import_chapters' );

				// find out where all the content is
				$importer->getOpf();
				// parse, import and save
				$importer->parse();
			}
		} else {
			// find out where all the content is
			$importer->getOpf();
			// parse, import and save
			$importer->parse();
		}
	}


	/**
	 * Constructor requires a file to import, validates it, unzips contents of the epub
	 * and puts it in a temporary directory.
	 *
	 * @param string $filename
	 * @param string $selective_import- user wants to choose which chapters to bring in
	 *
	 * @throws \Exception
	 */
	function __construct( $file_name, $selective_import ) {

		if ( ! defined( 'PB_EPUBCHECK_COMMAND' ) )
			define ( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /opt/epubcheck/epubcheck.jar' );

		// Set the location of the file
		$this->import_path = $file_name;

		if ( ! file_exists( $this->import_path ) ) {
			throw new \Exception ( 'uploaded file does not exist.' );
		}

		// Validate the uploaded epub file
		$this->validate( $this->import_path );

		// instantiate a zipArchive object for the epub directory
		$this->zip = new \ZipArchive;
		$result = $this->zip->open( $this->import_path );
		if ( $result !== true ) {
			throw new \Exception ( 'opening epub file failed' );
		}

		//$this->tempdir = \PressBooks\Utility\get_media_prefix() . 'tmp/pb_import_' . \md5(\date('Y-m-d H:i:s') . \getmypid() . '') . '/';
		$this->tempdir = '/tmp/pb_import_' . \md5( \date( 'Y-m-d H:i:s' ) . \getmypid() . '' ) . '/';
	}


	/**
	 * Garbage collection, obliterate the mess
	 */
	function __destruct() {

		$dir = $this->tempdir;
		$it = new \RecursiveDirectoryIterator ( $dir );
		$files = new \RecursiveIteratorIterator ( $it,
			\RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $file ) {
			if ( $file->getFilename() === '.' || $file->getFilename() === '..' ) {
				continue;
			}
			if ( $file->isDir() ) {
				\rmdir( $file->getRealPath() );
			} else {
				\unlink( $file->getRealPath() );
			}
		}
		\rmdir( $dir );
	}


	/**
	 * Get/Find the OPF file and extract data from the
	 * structure of the epub/zip folder
	 *
	 */
	private function getOpf() {

		$mimetype = $this->getZipContent( 'mimetype', false );

		// safety check
		if ( $mimetype != 'application/epub+zip' ) {
			throw new \Exception ( 'wrong mimetype' );
		}


		$containerXml = $this->getZipContent( 'META-INF/container.xml' );
		$contentPath = $containerXml->rootfiles->rootfile['full-path'];

		// this is where the OPF file is; subsequently the map to where
		// all the content/XHTML/images/CSS files live
		$this->content_xml = $this->getZipContent( $contentPath );

		//    echo "<pre>";
		//    print_r($this->content_xml->metadata->children('dc', true));
		//    echo "</pre>";
		//    die();

		$this->basedir = dirname( $contentPath ) . '/';
	}


	/**
	 * Parse content
	 */
	private function parse() {
		// @todo set all previous chapters to status "inactive"

		$this->parseManifest( $this->content_xml->manifest );
		$this->parseMetadata( $this->content_xml->metadata );
	}


	/**
	 * Give it an array and set the instance variable
	 *
	 *
	 */
	private function setChapters() {
		$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_import';

		// find where the content files live
		$this->getOpf();

		// assign the value to an instance variable
		$this->available_chapters = $this->content_xml->spine;

		// process the simplexml object array, into an array that wp can handle
		$array_of_chapters['file'] = $this->import_path;
		$array_of_chapters['file_type'] = 'application/epub+zip';

		foreach ( $this->available_chapters->children() AS $item ) {

			foreach ( $item->attributes() as $key => $val ) {

				if ( $key == 'idref' ) {

					$array_of_chapters['chapters'][(string) $val] = 'chapter';
				}
			}
		}

		// update the option in wordpress, so we can access it later.
		update_option( 'pressbooks_selective_import_chapters', $array_of_chapters );

		\PressBooks\Redirect\location( $redirect_url . '&select_chapters=step1' );
	}


	/**
	 *
	 * @param \SimpleXMLElement $metadata
	 */
	private function parseMetadata( \SimpleXMLElement $metadata ) {
		//echo "metadata<br />";
		// @todo post_type: metadata
	}


	/**
	 * Iterate through each of the manifest items to extract the content files
	 * and associated metadata. Save each 'item' as a chapter in PB.
	 *
	 * @param \SimpleXMLElement $manifest
	 */
	private function parseManifest( \SimpleXMLElement $manifest ) {
		//echo "manifest<br />";
		$files = array();
		/* @var $item \SimpleXMLElement */

		// create an array with file 'id' as key, each with it's own array of attributes
		foreach ( $manifest->children() AS $item ) {
			$file = array();

			// create an array of attributes for each item, skipping 'id'
			foreach ( $item->attributes() AS $attribute ) {
				switch ( $attribute->getName() ) {
					case 'id':
						$id = (string) $attribute;
						break;
					default:
						$file[$attribute->getName()] = (string) $attribute;
						break;
				}
			}
			// add attributes array to the file 'id' as key array
			$files[$id] = $file;
		}

		//    echo "<pre>";
		//    print_r($files);
		//    echo "</pre>";
		//    die();
		// @todo must modify the $files array to subtract from it, the chapters
		// that were NOT selected.


		$i = 0;
		foreach ( $files AS $file_id => $file ) {
			++$i;
			$this->importFile( $file_id, $file );
			//if ($i >= 2) break;
		}

		$this->saveChapters();
	}


	/**
	 * Depending on what file/mime type is passed, call a different import function
	 *
	 * @param string $file_id - name of file (ch.01.html, x001.jpg, template.css)
	 * @param array $file
	 */
	private function importFile( $file_id, array $file ) {
		$href = $file['href'];
		$media_type = $file['media-type'];

		switch ( $media_type ) {
			case 'application/x-dtbncx+xml':
				$this->importNcx( $file_id, $href );
				break;
			case 'application/xhtml+xml':
				$this->importChapter( $file_id, $href );
				break;
			case 'text/css':
				$this->importStyle( $file_id, $href );
				break;
			case 'image/jpeg':
			case 'image/png':
				$this->importImage( $file_id, $href );
				break;
		}
	}


	/**
	 * Import css files
	 *
	 * @param string $file_id - name of file (template.css)
	 * @param string $href    - path to file (Styles/template.css)
	 */
	private function importStyle( $file_id, $href ) {
		$css = $this->getZipContent( $this->basedir . $href, false );
		$uploads = \wp_upload_dir();
		$filename = $uploads['basedir'] . '/' . \basename( $href );
		\file_put_contents( $filename, $css );
		$imported_css = get_option( 'pressbooks_imported_css' );
		if ( $imported_css !== false && ! empty ( $imported_css ) ) {
			$imported_css .= '|' . \basename( $href );
		} else {
			$imported_css = \basename( $href );
		}
		\update_option( 'pressbooks_imported_css', $imported_css );
	}


	/**
	 *
	 * @global type $user_ID
	 *
	 * @param type $file_id
	 * @param type $href
	 */
	private function importChapter( $file_id, $href ) {
		//echo "import " . $file_id . ': ' . $href . '<br />';
		global $user_ID;
		$pb_type = $this->selected_chapters[$file_id]['type'];

		// @todo: title, content, category, "incluce chapter in exports", part, "show title in epub/pdf export"
		// check if the user wants this chapter
		if ( isset ( $this->selected_chapters ) && array_key_exists( 'import', $this->selected_chapters[$file_id] ) ) {
			$this->chapters[$file_id] = $this->parseChapter( $file_id, $this->getZipContent( $this->basedir . $href ), $pb_type );

		} elseif ( isset ( $this->selected_chapters ) && ! array_key_exists( 'import', $this->selected_chapters[$file_id] ) ) {
			// don't import it
			return;

		} else {
			$this->chapters[$file_id] = $this->parseChapter( $file_id, $this->getZipContent( $this->basedir . $href ) );
		}
	}


	private function saveChapters() {

		foreach ( $this->chapters AS $file_id => $chapter ) {

			$chapter->setImageFiles( $this->imagefiles );

			$post_array = array(
				//'ID'             => [ <post id> ] //Are you updating an existing post?
				//'menu_order'     => [ <order> ] //If new post is a page, it sets the order in which it should appear in the tabs.
				'comment_status' => 'closed', // 'closed' means no comments.
				'ping_status' => 'closed', // | 'open' ] // 'closed' means pingbacks or trackbacks turned off
				'post_author' => $user_ID, //The user ID number of the author.
				'post_content' => $chapter->getContent(),
				'post_date' => date( 'Y-m-d H:i:s' ), //The time post was made.
				'post_date_gmt' => date( 'Y-m-d H:i:s' ), //The time post was made, in GMT.
				'post_excerpt' => $chapter->getExcerpt(), //[ <an excerpt> ] //For all your post excerpt needs.
				'post_name' => $chapter->getSlug(), // The name (slug) for your post
				//'post_parent' => $chapter->getParent(), //Sets the parent of the new post.
				'post_status' => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] //Set the status of the new post.
				'post_title' => $chapter->getTitle(), // [ <the title> ] //The title of your post.
				'post_type' => $chapter->getPbType(), //[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] //You may want to insert a regular post, page, link, a menu item or some custom post type
				//'tags_input'     => [ '<tag>, <tag>, <...>' ] //For tags.
				//'to_ping'        => [ ? ] //?
				//'tax_input'      => [ array( 'taxonomy_name' => array( 'term', 'term2', 'term3' ) ) ] // support for custom taxonomies.
			);
			//var_dump($post_array);
			//return;
			// pb_author, pb_language, pb_export, pb_show_title
			// Insert the post into the database
			$post_id = wp_insert_post( $post_array );

			// @todo get author value from opf, <dc:creator opf:role="auth">  and append it to existing book
			update_post_meta( $post_id, 'pb_author', $user_ID );

			// @todo add chapter author to the chapter, get the value from opf
			//update_post_meta ( $post_id, 'pb_section_author' );

			// @todo add chapter short title
			// @todo add chapter subtitle

			// @todo get value from opf, <dc:language> and update
			update_post_meta( $post_id, 'pb_language', 'en' );

			// $todo get publisher from opf, <dc:publisher> and append


			update_post_meta( $post_id, 'pb_export', 'on' );
			update_post_meta( $post_id, 'pb_show_title', 'on' );
		}
	}


	/**
	 *
	 * @param type $file_id
	 * @param \SimpleXMLElement $xml
	 * @param string $pb_type - pressbooks one of chapter, front-matter, back-matter
	 *
	 * @return \PressBooks\Import\Epub\Chapter
	 */
	private function parseChapter( $file_id, \SimpleXMLElement $xml, $pb_type = 'chapter' ) {
		return new Chapter ( $file_id, $xml, $pb_type );
	}


	/**
	 * Does nothing.
	 *
	 * @param type $file_id
	 * @param type $href
	 */
	protected function importNcx( $file_id, $href ) {
		// Is here for child classes to override if ever needed.
	}


	/**
	 *
	 * @param type $file_id
	 * @param type $href
	 */
	private function importImage( $file_id, $href ) {

		//$dst = \wp_tempnam($href);
		if ( ! \is_dir( $this->tempdir . $this->basedir ) ) {
			$this->zip->extractTo( $this->tempdir );
		}
		$dst = $this->tempdir . $this->basedir . $href;
		//echo $dst . ' ' . (\file_exists($dst)?'ja':'nein') . "<br />";
		$image_id = \media_handle_sideload( array( 'name' => \basename( $dst ), 'tmp_name' => $dst ), 0, \basename( $dst ) );

		$this->imagefiles[$href] = $image_id;
	}


	/**
	 * Finds a file in the zip directory, returns the contents of the file
	 *
	 * @param type $file
	 * @param type $as_xml
	 *
	 * @return \SimpleXMLElement
	 * @throws \Exception
	 */
	private function getZipContent( $file, $as_xml = true ) {
		// locates an entry using its name
		$index = $this->zip->locateName( $file );

		if ( $index === false ) {
			throw new \Exception ( 'file [' . $file . '] not found' );
		}

		// returns the contents using its index
		$content = $this->zip->getFromIndex( $index );

		// if it's not xml, return
		if ( ! $as_xml ) {
			return $content;
		}

		// if it is xml, then instantiate and return a simplexml object
		return new \SimpleXMLElement ( $content );
	}


	/**
	 * Check the version of the EPUB file, (we want it to be version 2)
	 * and produce a warning if errors are discovered.
	 *
	 * @return boolean
	 */
	function validate() {

		// Epubcheck command
		$command = PB_EPUBCHECK_COMMAND . ' ' . escapeshellcmd( $this->import_path ) . ' 2>&1';

		// Execute command
		$output = array();
		$return_var = 0;
		exec( $command, $output, $return_var );

		// What version of Epub is this?
		$version_two = 'Validating against EPUB version 2.0';
		if ( ! in_array( $version_two, $output ) ) {
			return false;
		}

		// Any errors?
		$last_line = strtolower( end( array_filter( $output ) ) );
		if ( false !== strpos( $last_line, 'check finished with warnings or errors' ) ) {
			$this->logError( implode( "\n", $output ) );

			echo '<p><strong>' . __( 'Some errors were detected when validating the uploaded epub file. Depending on the severity of the errors
        it may affect the import process', 'pressbooks' ) . '</strong></p>';
		}

		//    echo "<pre>";
		//    print_r($output);
		//    echo "</pre>";
		//    die();

		return true;
	}

}
