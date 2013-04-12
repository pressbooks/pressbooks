<?php

/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import;

use PressBooks\Import\Epub\Epub201;

abstract class Import {

  /**
   * Email addresses to send log errors.
   *
   * @var array
   */
  public $errors_email = array(
      'bpayne@bccampus.ca',
      'michael@4horsemen.de',
      'errors@pressbooks.com'
  );

  /**
   * Location of imported data file
   *
   * @var string fullpath
   */
  protected $import_path;

  /**
   * determines what type of file is being uploaded and calls the necessary
   * class to deal with that
   * 
   */
  static public function formSubmit() {


    if (false == static::isFormSubmission() || false == current_user_can('edit_posts')) {
      // Don't do anything in this function, bail.
      return;
    }

    $selective_import = $_POST['pressbooks_selective_import'];
    update_option('pressbooks_selective_import', $selective_import);

    // if user has selected chapters to import
    if (isset($_GET['select_chapters']) && $_GET['select_chapters'] == 'step2' && $_POST['pressbooks_select_chapters'] == 'Import') {

      // need to grab the file name, file type and path to file before overwriting the data
      $available_chapters = get_option('pressbooks_selective_import_chapters');

      $file = $available_chapters['file'];
      $file_type = $available_chapters['file_type'];

      // just get the chapters the user selected
      $selected_chapters = $_POST['chapters'];

      // save the option with the new array posted
      // @see admin/templates/import.php
      update_option('pressbooks_selective_import_chapters', $selected_chapters);


      // find out what type of file is being uploaded
      switch ($file_type) {

        case 'application/epub+zip':
          Epub201::import($file, $selective_import);
          break;

        // @todo: case 'application/msword'
        // @todo: case 'text/html'
        // @todo: case 'application/xhtml+xml'

        default:
          header('Location: ' . get_bloginfo('url') . '/wp-admin/admin.php?page=pb_import&import_error=step2');
          break;
      }
    }


    // Uploading and importing a file
    if (isset($_GET['upload_file']) && $_GET['upload_file'] == 'yes' && $_POST['Submit'] == 'Upload') {

      $file_name = $_FILES['import_file']['name'];   // title of the file including .epub suffix
      $temp_name = $_FILES['import_file']['tmp_name'];  // string that is the directory path of the file 
      $file_type = $_FILES['import_file']['type']; // the type of file being uploaded
      $file_size = $_FILES['import_file']['size']; // the size of the file
      $file_error = $_FILES['import_file']['error']; // any errors of the file
//      echo "<pre>";
//      var_dump($_FILES);
//      echo "</pre>";
      // if there are any errors associated with the file
      // @todo: evaluate any errors associated with $file_error and send appropriate message
      // http://www.php.net/manual/en/features.file-upload.errors.php
      // if the file size is 0 
      // @todo: evaluate if nothing was uploaded with $file_size and send appropriate error message
      // create a directory to hold all imports
      $path = \PressBooks\Utility\get_media_prefix() . "imports/";

      if (!file_exists($path)) {
        mkdir($path, 0775, true);
      }

      move_uploaded_file($temp_name, $path . $file_name);




      // find out what type of file is being uploaded
      switch ($file_type) {

        case 'application/epub+zip':
          Epub201::import($path . $file_name, $selective_import);
          break;

        // @todo: case 'application/msword'
        // @todo: case 'text/html'
        // @todo: case 'application/xhtml+xml'

        default:
          header('Location: ' . get_bloginfo('url') . '/wp-admin/admin.php?page=pb_import&import_error=filetype');
          break;
      }
    }
  }

  /**
   * Returns the location of the import files
   * @return string
   */
  function getImportPath() {
    return $this->import_path;
  }

  /**
   * Check if a user submitted something to admin.php?page=pb_import
   *
   * @return bool
   */
  static function isFormSubmission() {

    if ('pb_import' != @$_REQUEST['page']) {
      return false;
    }

    if (!empty($_POST)) {
      return true;
    }

    if (count($_GET) > 1) {
      return true;
    }

    return false;
  }

  /**
   * Log errors using wp_mail() and error_log(), include useful WordPress info.
   *
   * @param string $message
   * @param array  $more_info
   */
  function logError($message, array $more_info = array()) {

    /** $var \WP_User $current_user */
    global $current_user;

    $subject = get_class($this);

    $info = array(
        'time' => strftime('%c'),
        'user' => ( isset($current_user) ? $current_user->user_login : '__UNKNOWN__' ),
        'site_url' => site_url(),
        'blog_id' => get_current_blog_id(),
        'theme' => '' . wp_get_theme(), // Stringify by appending to empty string
    );

    $message = print_r(array_merge($info, $more_info), true) . $message;

    // ------------------------------------------------------------------------------------------------------------
    // Write to error log

    error_log($subject . "\n" . $message);

    // ------------------------------------------------------------------------------------------------------------
    // Email logs

    if (@$current_user->user_email && get_option('pressbooks_email_validation_logs')) {
      $this->errors_email[] = $current_user->user_email;
    }

    add_filter('wp_mail_from', function ( $from_email ) {
              return str_replace('wordpress@', 'pressbooks@', $from_email);
            });
    add_filter('wp_mail_from_name', function ( $from_name ) {
              return 'PressBooks';
            });

    foreach ($this->errors_email as $email) {
      wp_mail($email, $subject, $message);
    }
  }

  /**
   * Mandatory validate function 
   * 
   * @return bool
   */
  abstract function validate();
}
