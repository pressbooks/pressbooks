<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */


/**
 * Clarke features we inject ourselves, (not user options)
 *
 * @param $css
 *
 * @return string
 */
function clarke_theme_pdf_css_override( $css ) {

	// Translate "Part" to whatever language this book is in
	$css .= '#toc .part a::before { content: "' . __( 'part', 'pressbooks' ) . ' "counter(part) ". "; }' . "\n";

	return $css;
}
add_filter( 'pb_pdf_css_override', 'clarke_theme_pdf_css_override' );


/**
 * Clarke features we inject ourselves, (not user options)
 *
 * @param $css
 *
 * @return string
 */
function clarke_theme_ebook_css_override( $css ) {

	return $css;
}
add_filter( 'pb_epub_css_override', 'clarke_theme_ebook_css_override' );