<?php

/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import\Epub;

use PressBooks\Import\Import;

require_once(ABSPATH . "wp-admin" . '/includes/file.php');
require_once(ABSPATH . "wp-admin" . '/includes/media.php');
require_once(ABSPATH . "wp-admin" . '/includes/image.php');

class Epub201 extends Import {

  /**
   *
   * @var \ZipArchive
   */
  private $zip;
  private $basedir = '';
  private $tempdir;
  private $imagefiles = array();
  private $chapters = array();

  static function import($filename) {
    
    $importer = new self($filename);
    $importer->run();
  }

  public function __construct($filename) {

    if (!file_exists($filename)) {
      throw new \Exception('uploaded file does not exist.');
    }

    $this->zip = new \ZipArchive;
    $result = $this->zip->open($filename);
    if ($result !== true) {
      throw new \Exception('opening epub file failed');
    }

    $this->tempdir = \PressBooks\Utility\get_media_prefix() . 'tmp/pb_import_' . \md5(\date('Y-m-d H:i:s') . \getmypid() . '') . '/';
  }

  public function __destruct() {

    //return;

    $dir = $this->tempdir;
    $it = new \RecursiveDirectoryIterator($dir);
    $files = new \RecursiveIteratorIterator($it,
                    \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
      if ($file->getFilename() === '.' || $file->getFilename() === '..') {
        continue;
      }
      if ($file->isDir()) {
        \rmdir($file->getRealPath());
      } else {
        \unlink($file->getRealPath());
      }
    }
    \rmdir($dir);
  }

  public function run() {

    $mimetype = $this->getZipContent('mimetype', false);

    if ($mimetype != 'application/epub+zip') {
      throw new \Exception('wrong mimetype');
    }

    $containerXml = $this->getZipContent('META-INF/container.xml');
    $contentPath = $containerXml->rootfiles->rootfile['full-path'];

    $contentXml = $this->getZipContent($contentPath);

    $this->basedir = dirname($contentPath) . '/';

    // @todo set all previous chapters to status "inactive"

    $this->parseManifest($contentXml->manifest);
    $this->parseMetadata($contentXml->metadata);
  }

  private function parseMetadata(\SimpleXMLElement $metadata) {
    //echo "metadata<br />";
    // @todo post_type: metadata
  }

  private function parseManifest(\SimpleXMLElement $manifest) {
    //echo "manifest<br />";
    $files = array();
    /* @var $item \SimpleXMLElement */
    foreach ($manifest->children() AS $item) {
      $file = array();
      foreach ($item->attributes() AS $attribute) {
        switch ($attribute->getName()) {
          case 'id':
            $id = (string) $attribute;
            break;
          default:
            $file[$attribute->getName()] = (string) $attribute;
            break;
        }
      }
      $files[$id] = $file;
    }
    $i = 0;
    foreach ($files AS $file_id => $file) {
      ++$i;
      $this->importFile($file_id, $file);
      //if ($i >= 2) break;
    }

    $this->saveChapters();
  }

  private function importFile($file_id, array $file) {
    $href = $file['href'];
    $media_type = $file['media-type'];

    switch ($media_type) {
      case 'application/x-dtbncx+xml':
        $this->importNcx($file_id, $href);
        break;
      case 'application/xhtml+xml':
        $this->importChapter($file_id, $href);
        break;
      case 'text/css':
        $this->importStyle($file_id, $href);
        break;
      case 'image/jpeg':
      case 'image/png':
        $this->importImage($file_id, $href);
        break;
    }
  }

  private function importStyle($file_id, $href) {
    $css = $this->getZipContent($this->basedir . $href, false);
    $uploads = \wp_upload_dir();
    $filename = $uploads['basedir'] . '/' . \basename($href);
    \file_put_contents($filename, $css);
    $imported_css = get_option('pressbooks_imported_css');
    if ($imported_css !== false && !empty($imported_css)) {
      $imported_css .= '|' . \basename($href);
    } else {
      $imported_css = \basename($href);
    }
    \update_option('pressbooks_imported_css', $imported_css);
  }

  private function importChapter($file_id, $href) {
    //echo "import " . $file_id . ': ' . $href . '<br />';

    global $user_ID;

    // @todo: title, content, category, "incluce chapter in exports", part, "show title in epub/pdf export"

    $this->chapters[$file_id] = $this->parseChapter($file_id, $this->getZipContent($this->basedir . $href));
  }

  private function saveChapters() {

    foreach ($this->chapters AS $file_id => $chapter) {

      $chapter->setImageFiles($this->imagefiles);

      $post_array = array(
          //'ID'             => [ <post id> ] //Are you updating an existing post?
          //'menu_order'     => [ <order> ] //If new post is a page, it sets the order in which it should appear in the tabs.
          'comment_status' => 'closed', // 'closed' means no comments.
          'ping_status' => 'closed', // | 'open' ] // 'closed' means pingbacks or trackbacks turned off
          'post_author' => $user_ID, //The user ID number of the author.
          'post_content' => $chapter->getContent(),
          'post_date' => date('Y-m-d H:i:s'), //The time post was made.
          'post_date_gmt' => date('Y-m-d H:i:s'), //The time post was made, in GMT.
          'post_excerpt' => $chapter->getExcerpt(), //[ <an excerpt> ] //For all your post excerpt needs.
          'post_name' => $chapter->getSlug(), // The name (slug) for your post
          'post_parent' => $chapter->getParent(), //Sets the parent of the new post.
          'post_status' => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] //Set the status of the new post.
          'post_title' => $chapter->getTitle(), // [ <the title> ] //The title of your post.
          'post_type' => 'chapter', //[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] //You may want to insert a regular post, page, link, a menu item or some custom post type
              //'tags_input'     => [ '<tag>, <tag>, <...>' ] //For tags.
              //'to_ping'        => [ ? ] //?
              //'tax_input'      => [ array( 'taxonomy_name' => array( 'term', 'term2', 'term3' ) ) ] // support for custom taxonomies.
      );
      //var_dump($post_array);
      //return;
      // pb_author, pb_language, pb_export, pb_show_title
      // Insert the post into the database
      $post_id = wp_insert_post($post_array);

      update_post_meta($post_id, 'pb_author', $user_ID);

      // @todo get value from opf
      update_post_meta($post_id, 'pb_language', 'de');
      update_post_meta($post_id, 'pb_export', 'on');
      update_post_meta($post_id, 'pb_show_title', 'on');
    }
  }

  private function parseChapter($file_id, \SimpleXMLElement $xml) {
    return new Chapter($file_id, $xml);
  }

  private function importNcx($file_id, $href) {
    
  }

  private function importImage($file_id, $href) {

    //$dst = \wp_tempnam($href);
    if (!\is_dir($this->tempdir . $this->basedir)) {
      $this->zip->extractTo($this->tempdir);
    }
    $dst = $this->tempdir . $this->basedir . $href;
    //echo $dst . ' ' . (\file_exists($dst)?'ja':'nein') . "<br />";
    $image_id = \media_handle_sideload(array('name' => \basename($dst), 'tmp_name' => $dst), 0, \basename($dst));

    $this->imagefiles[$href] = $image_id;
  }

  private function getZipContent($file, $as_xml = true) {
    $index = $this->zip->locateName($file);

    if ($index === false) {
      throw new \Exception('file [' . $file . '] not found');
    }

    $content = $this->zip->getFromIndex($index);
    if (!$as_xml) {
      return $content;
    }
    return new \SimpleXMLElement($content);
  }

  /**
   * must validate that it is an epub
   */
  function validate() {
    // @todo: validate the ebpub file, also as epub 2.01 
  }

}
