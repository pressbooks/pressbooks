<?php

namespace Pressbooks\HTMLBook;

/**
 * Based on HTMLBook
 *
 * To add comments to an HTMLBook document, either use standard HTML/XML comment syntax:
 *
 *     <!-- This is a comment -->
 *
 * Or add a `data-type="comment"` attribute to any HTML element, e.g.:
 *
 *     <div data-type="comment">This is a comment preceding a paragraph of text</div>
 *     <p>This is a paragraph of text <span data-type="comment">Inline comment in paragraph</span></p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_comments
 */
class Comments {

}
