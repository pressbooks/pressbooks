<?php
// Test-only shims for Pressbooks\Admin\CustomFonts namespace.
// This file is required from tests/bootstrap.php and only used during tests.

namespace Pressbooks\Admin\CustomFonts;

// Provide test-friendly is_uploaded_file and move_uploaded_file implementations
// only inside this namespace. PHPUnit will load this bootstrap before tests run.

if ( ! function_exists( __NAMESPACE__ . '\\is_uploaded_file' ) ) {
    function is_uploaded_file( $filename ) {
        // In tests treat any existing file as "uploaded" so move_uploaded_file will be used.
        return file_exists( $filename );
    }
}

if ( ! function_exists( __NAMESPACE__ . '\\move_uploaded_file' ) ) {
    function move_uploaded_file( $from, $to ) {
        // Attempt rename first, then copy. Suppress warnings.
        if ( @rename( $from, $to ) ) {
            return true;
        }
        if ( @copy( $from, $to ) ) {
            @unlink( $from );
            return true;
        }
        return false;
    }
}
