<?php

namespace Pressbooks\HTMLBook\Heading;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML elements: <h1>, <h2>, <h3>, <h4>, <h5>, or <h6>
 *
 * Content Model: text and/or zero or more Inline Elements
 *
 * Notes: Many main book components (e.g., chapters, parts, appendixes) require headings. The appropriate
 * element from <h1>–<h6> is outlined below, as well as in the corresponding documentation for these components:
 *
 *     book title -> h1
 *     part title -> h1
 *     chapter title -> h1
 *     preface title -> h1
 *     appendix title -> h1
 *     colophon title -> h1
 *     dedication title -> h1
 *     glossary title -> h1
 *     bibliography title -> h1
 *     sect1 title -> h1
 *     sect2 title -> h2
 *     sect3 title -> h3
 *     sect4 title -> h4
 *     sect5 title -> h5
 *     sidebar title -> h5
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_headings
 */
abstract class Headings extends Element {

}
