<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Import;


class Import {

    static public function formSubmit() {

        // Uploading and importing an epub file
        if ( isset( $_GET['upload_epub'] ) && $_GET['upload_epub'] == 'yes' ) {

            $filename = $_FILES['epub_file']['name'];
            $tempname = $_FILES['epub_file']['tmp_name'];

            echo "<pre>";
            var_dump($_FILES);
            echo "</pre>";

            $filesize = filesize( $tempname );

            if ( substr_compare( $filename, 'epub', - 4, 4 ) !== 0 ) {
                header( 'Location: ' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_import&import_error=filetype' );
            } else {

                $path = \PressBooks\Utility\get_media_prefix() . "import_epub/";

                if ( ! file_exists( $path ) ) {
                    mkdir( $path, 0775, true );
                }

                move_uploaded_file($tempname, $path . $filename);

                \PressBooks\Import\Epub\Epub::import($path . $filename);
            }

        }
    }
}
