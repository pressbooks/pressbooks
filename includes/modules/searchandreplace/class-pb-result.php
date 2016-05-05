<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2+
 */

namespace Pressbooks\Modules\SearchAndReplace;

class Result {
    var $search;
    var $replace;
    var $content;
    var $id;
    var $title;
    var $offset;
    var $length;
    var $replace_string;

    function single_line() {
        if ( strpos ( $this->search_plain, "\r" ) !== false || strpos ( $this->search_plain, "\n" ) !== false ) {
            return false;
        }
        return true;
    }
}
