<?php

namespace Pressbooks\Modules\Import\IMSCC;

use Pressbooks\Modules\Import\Import;
use Pressbooks\Book;

class IMSCC extends Import {
  /**
   * @
   */
  function setCurrentImportOption( array $upload ) {
    try {
      $imscc = new IMSCCParser( $upload['file'] );
    }
    catch (\Exception $e ) {
      return FALSE;
    }

    list($chapters, $posttypes) = $imscc->getImportableContent();

    $option = array(
      'file' => $upload['file'],
      'file_type' => $upload['type'],
      'type_of' => 'imscc',
      'chapters' => $chapters,
      'post_types' => $posttypes,
      'allow_parts' => true
    );
    $imscc->cleanUp();

    return update_option('pressbooks_current_import', $option);
  }

  /**
   * @param array $current_import
   */
  function import ( array $current_import ) {
    try {
      $imscc = new IMSCCParser( $current_import['file'] );
    }
    catch (\Exception $e ) {
      return FALSE;
    }

    $items = $imscc->manifestGetItems();
    $match_ids = array_flip( array_keys( $current_import['chapters'] ) );
    $total = 0;
    if (!empty($items)) {
      $current_post_parent = -1;
      foreach ($items as $id => $item) {
        // Skip
        if ( ! $this->flaggedForImport( $id ) ) continue;
        if ( ! isset( $match_ids[$id] ) ) continue;

        $post_type = $this->determinePostType( $id );
        $new_post = array(
          'post_title' => wp_strip_all_tags( $item['title'] ),
          'post_type' => $post_type,
          'post_status' => ( 'part' == $post_type )?'publish':'draft',
        );
        if ( 'part' != $post_type ) {
          $new_post['post_content'] = $imscc->getContent( $id );
        }

        if ( 'chapter' == $post_type ) {
          if ($current_post_parent==-1) {
            $new_post['post_parent'] = $this->getChapterParent();
          } else {
            $new_post['post_parent'] = $current_post_parent;
          }
        }

        $pid = wp_insert_post( add_magic_quotes( $new_post ) );

        //store part post ID to use as parent for subsequent chapters
        if ( 'part' == $post_type ) {
          $current_post_parent = $pid;
        }

        // @todo postmeta like author

        update_post_meta( $pid, 'pb_show_title', 'on' );
        update_post_meta( $pid, 'pb_export', 'on' );

        if ( 'part' == $post_type && $imscc->getContent( $id ) ) {
          update_post_meta( $pid, 'pb_part_content', $imscc->getContent( $id ) );
        }

        Book::consolidatePost( $pid, get_post( $pid ) );
        ++$total;
      }
    }

    // Done
    $_SESSION['pb_notices'][] = sprintf( __('Imported %d chapters.', 'pressbooks'), $total );
    $imscc->cleanUp();
    return $this->revokeCurrentImport();
  }


}


class IMSCCParser {
  // XML for manifest.
  private $xml;

  // Xpath handle to $xml.
  private $xpath;

  // Working directory for unzipped file.
  private $tempDir;

  // Store manifest items.
  private $items;

  // Store manifest resources.
  private $resources;

  // Cache discovered importable content.
  private $content;

  // Cache whether content appears to be a part or chapter
  private $content_posttype;

  function __construct($file) {
    try {
      $this->unzip($file);
      $this->setupManifest();
      $this->manifestGetItems();
      $this->manifestGetResources();
      $this->miscellaneousGetResources();
      $this->pruneInvalidItems();
    }
    catch (\Exception $e ) {
      return false;
    }
  }

  function unzip($file) {
    if (!is_file($file)) {
      throw new \Exception( __( 'Zip file not found', 'pressbooks' ) );
    }

    // Unfortunately tempnam() actually creates a file, we want a directory.
    $this->tempDir = tempnam(sys_get_temp_dir(), '');
    unlink($this->tempDir);
    mkdir($this->tempDir);
    if (!is_dir($this->tempDir)) {
      throw new\Exception( __( 'Unable to create temporary directory.', 'pressbooks' ) );
    }


    $zip = new \ZipArchive;
    if ($zip->open($file) === TRUE) {
      $zip->extractTo($this->tempDir);
      $zip->close();
    }
    else {
      throw new \Exception( __('Unable to open zipfile', 'pressbooks' ) );
    }
  }

  function setupManifest() {
    if (!is_file($this->tempDir . '/imsmanifest.xml') ) {
      throw new \Exception( __('Unable to location IMS CC manifest file.', 'pressbooks' ) );
    }
    $this->xml = new \DOMDocument('1.0', 'UTF-8');
    $this->xml->load($this->tempDir . '/imsmanifest.xml');

    // xpath requires us to register a namespace we use 'fake'
    $this->xpath = new \DOMXPath($this->xml);
    $this->xpath->registerNameSpace('fake','http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1');
  }

  /**
   * Include in document order all items of type item with an identifier attribute
   */
  function manifestGetItems() {
    if (empty($this->items)) {
      $elements = $this->xpath->query(".//fake:item[@identifier]");

      $this->items = array();
      foreach($elements as $element) {
        $result = array();

        if (method_exists($element, 'hasAttributes') && $element->hasAttributes()) {
          foreach ($element->attributes as $attr) {
            $result[$attr->nodeName] = $attr->nodeValue;
          }

          // get the title
          $title = $this->xpath->query("./fake:title", $element);
          if ($title->length != 0) {
            $result['title'] = '';
            foreach ($title as $tag) {
              $result['title'] .= $tag->nodeValue;
            }
          }
        }

        if (!empty($result['identifier'])) {
          $this->items[$result['identifier']] = $result;
        }
      }

      // @todo update xpath selector to exclude 'LearningModules
      unset($this->items['LearningModules']);
    }
    return $this->items;
  }

  /**
   * Currently this only grabs resources of type = 'webcontent'
   */
  function manifestGetResources() {
    if ( empty( $this->resources ) ) {
      $this->resources = array();
      $elements = $this->xpath->query(".//fake:resource[@type='webcontent']");

      $this->resources = array();
      foreach( $elements as $element ) {
        $result = array();

        if ( method_exists( $element, 'hasAttributes') && $element->hasAttributes() ) {
          $result['type'] = 'webcontent';
          foreach ( $element->attributes as $attr ) {
            $result[$attr->nodeName] = $attr->nodeValue;
          }
        }

        $this->resources[$result['identifier']] = $result;

        // Assumption: webcontent resources have exactly one file child and the
        // href of the file matches the href of parent resource. So skipping
        // child file processing.
      }
    }
    return $this->resources;
  }

  /**
   * Adds file resources in the filesystem, to our resources.
   */
  function miscellaneousGetResources() {

    foreach ( $this->items as $id => $item ) {
      // make sure identifierref is in resources
      if ( ! empty( $item['identifierref'] ) ) {
        if ( !array_key_exists($item['identifierref'], $this->resources) ) {
          // Item does not exist in resources. Try to find it in filesystem.
          $globbed = glob( $this->tempDir . '/*' . $item['identifierref'] . '*' );
          if ( ! empty( $globbed ) ) {
            $path = $globbed[0];
            if ( is_dir($path) ) {
              // Assumption: These are non-content assignment and app items that
              // do not make sense to import.
            }
            else if ( is_file( $path ) ) {
              $ext = pathinfo($path, PATHINFO_EXTENSION);
              switch($ext) {
                case 'xml':
                  $info = $this->processXML($path);
                  if ( ! empty( $info ) ) {
                    $this->resources[$item['identifierref']] = $info;
                  }
                  break;
                default:
                  // Assumption: All non-xml files are non-content or
                  // assignments.
              }
            }
          }
        }
      }
      else {
        // Assumption: elements with no identifierref are LearningModules
        // and only contain a title.
        $this->resources[$id] = $item;
      }

    }
  }

  /**
   * Remove items that have identifierrefs to non-added resources.
   *
   * @see manifestGetResources()
   * @see miscellaneousGetResources()
   */
  function pruneInvalidItems() {
    foreach($this->items as $id => $item) {
      if (!empty($item['identifierref']) && empty($this->resources[$item['identifierref']])) {
        unset($this->items[$item['identifierref']]);
        unset($this->items[$id]);
      }
    }
  }

  function getContent( $id ) {
    $content = '';

    $item = $this->getItem( $id );
    if (!empty($item['payload'])) {
      $content = $item['payload'];
    }
    else {
      // Figure out what to do.
      //seeing this isn't useful
      //$content = '<pre>' . var_export($item, 1) . '</pre>';
    }

    return $content;
  }

  /**
   * Parses an XML file for known IMSCC content that makes sense for import.
   */
  function processXML( $path ) {
    $xml = new \DOMDocument('1.0', 'utf-8');
    $xml->load($path);

    $info = array();

    $xpath = new \DOMXPath($xml);
    $xpath->registerNameSpace('fake','http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1');

    $elements = $xpath->query("//*");
    foreach ($elements as $element) {
      $tag = $this->getTagName($element);
      switch ( $tag ) {
        case 'title':
          $info['title'] = $element->nodeValue;
          break;
        case 'url':
          $info['href'] = $element->getAttribute('href');
          break;
        case '#text':
        case 'text':
          $text = $element->nodeValue;
          if ( trim( $text ) ) {
            $info['text'] = $text;
          }
          break;
        default:
          if ( empty($info['type'])) {
            $info['type'] = $tag;
          }
      }
    }
    return $info;
  }

  function getTagName($element) {
    $tag = '';
    if ( is_object($element) ) {
      if (property_exists($element, 'tagName')) {
        $tag = $element->tagName;
      }
      elseif (property_exists($element, 'node_name')) {
        $tag = $element->node_name;
      }
      elseif (property_exists($element, 'nodeName')) {
        $tag = $element->nodeName;
      }
    }
    return $tag;
  }

  /**
   * Get the importable content. We should omit content such
   * as assessments which may not make sense to import.
   *
   */
  function getImportableContent() {
    if ( empty( $this->content ) ) {
      $this->content = array();
      $this->content_posttype = array();
      foreach ( $this->items as $id => $item ) {
        $this->content[$item['identifier']] = (!empty($item['title']))?$item['title']:'';
        $this->content_posttype[$item['identifier']] = (isset($item['identifierref']))?'chapter':'part';
      }
    }

    return array($this->content, $this->content_posttype);
  }

    /**
   * Given an item identifier return its corresponding content.
   *
   * This will do the necessary dereferencing of identifierref and
   * traverse the file directory structure where necessary.
   */
  function getItem( $id ) {
    $item = array();

    // dereference the id
    if ( ! empty( $this->items[$id]['identifierref'] ) ) {
      $id = $this->items[$id]['identifierref'];
    }

    // look up id in the resources array
    if ( ! empty( $this->resources[$id] ) ) {
      // get the content may include digging through the filesystem
      $item = $this->resources[$id];
    }

    $item['payload'] = $this->getPayload($item);

    return $item;
  }

  function getPayload($item) {
    if ( empty($item['type']) ) {
    	    return '';
    }
    switch($item['type']) {
      case 'webLink':
        return '<a href="' . $item['href'] . '">' . $item['title'] . '</a>';
        break;
      case 'topic':
        return $item['topic'];
        break;
      case 'webcontent':
        $filepath = $this->tempDir . '/' . $item['href'];
        if (file_exists($filepath)) {
          $ext = pathinfo($filepath, PATHINFO_EXTENSION);
          switch ($ext) {
            case 'html':
              $utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
              $doc = new \DOMDocument();
              $doc->loadHTML( $utf8_hack . file_get_contents($filepath));
              $doc = $this->scrapeAndKneadImages( $doc );
              $xpath = new \DOMXPath($doc);
              $body = $xpath->query('/html/body');
              $html = $doc->saveHTML($body->item(0));
              // Prefix element IDs with special meaning to prevent conflicts
              $html = preg_replace('/id="(wrap|content|sidebar|booknav|toc)"/','id="page_$1"',$html);
              return $html;
              break;
            default:
              $file = array(
                'name' => basename($filepath),
                'tmp_name' => $filepath,
              );
              $id = media_handle_sideload($file, 0);
              $src = wp_get_attachment_url($id);
              if (empty($item['title'])) {
                $title = 'link';
              }
              else {
                $title = $item['title'];
              }
              return '<a href="' . $src . '">' . $title . '</a>';
              break;
          }
        }
      default:
        //  error_log('Unhandled type(' . $item['type'] . ')');
        //  error_log(var_export($item,1));
        break;
    }

    return '';
  }


  /**
   * Cleanup function to recursively remove temporary working directory.
   */
  function cleanUp() {
    if ( is_dir($this->tempDir ) ) {
      foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tempDir, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $path ) {
        $path->isDir() ? rmdir( $path->getPathname() ) : unlink( $path->getPathname() );
      }
      rmdir( $this->tempDir );
    }
  }

  protected function scrapeAndKneadImages( \DOMDocument $doc ) {

    $images = $doc->getElementsByTagName( 'img' );

    foreach ( $images as $image ) {
      // Fetch image, change src
      $old_src = $image->getAttribute( 'src' );

      $new_src = $this->fetchAndSaveUniqueImage( $old_src );

      if ( $new_src ) {
        // Replace with new image
        $image->setAttribute( 'src', $new_src );
      } else {
        // Tag broken image
        $image->setAttribute( 'src', "{$old_src}#fixme" );
      }
    }

    return $doc;
  }


  /**
   * Load remote url of image into WP using media_handle_sideload()
   * Will return an empty string if something went wrong.
   *
   * @param string $url
   *
   * @see media_handle_sideload
   *
   * @return string filename
   */
  protected function fetchAndSaveUniqueImage( $url ) {

    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
      return '';
    }

    $remote_img_location = $url;

    // Cheap cache
    static $already_done = array ( );
    if ( isset( $already_done[$remote_img_location] ) ) {
      return $already_done[$remote_img_location];
    }

    /* Process */

    // Basename without query string
    $filename = explode( '?', basename( $url ) );
    $filename = array_shift( $filename );

    $filename = sanitize_file_name( urldecode( $filename ) );

    if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
      // Unsupported image type
      $already_done[$remote_img_location] = '';
      return '';
    }

    $tmp_name = download_url( $remote_img_location );
    if ( is_wp_error( $tmp_name ) ) {
      // Download failed
      $already_done[$remote_img_location] = '';
      return '';
    }

    if ( ! \PressBooks\Image\is_valid_image( $tmp_name, $filename ) ) {

      try { // changing the file name so that extension matches the mime type
        $filename = $this->properImageExtension( $tmp_name, $filename );

        if ( ! \PressBooks\Image\is_valid_image( $tmp_name, $filename ) ) {
          throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
        }
      } catch ( \Exception $exc ) {
        // Garbage, don't import
        $already_done[$remote_img_location] = '';
        unlink( $tmp_name );
        return '';
      }
    }

    $pid = media_handle_sideload( array ( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
    $src = wp_get_attachment_url( $pid );
    if ( ! $src ) $src = ''; // Change false to empty string
    $already_done[$remote_img_location] = $src;
    @unlink( $tmp_name );

    return $src;
  }

  /**
   * Checks if the file extension matches its mimetype, returns a modified
   * filename if they don't match.
   *
   * @param string $path_to_file
   * @param string $filename
   *
   * @return string - modified filename if the extension did not match the mimetype,
   * otherwise returns the filename that was passed to it
   */
  protected function properImageExtension( $path_to_file, $filename ) {
    $mimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
    );

    // Attempt to determine the real file type of a file.
    $validate = wp_check_filetype_and_ext( $path_to_file, $filename, $mimes );

    // change filename to the extension that matches its mimetype
    if ( $validate['proper_filename'] !== false ) {
      return $validate['proper_filename'];
    } else {
      return $filename;
    }
  }

}