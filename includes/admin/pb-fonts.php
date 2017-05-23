<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Fonts;


use Pressbooks\Container;

/**
 * Compile Sass for everything that has to do with dynamically generated font stacks
 */
function update_font_stacks() {

	Container::get( 'GlobalTypography' )->updateGlobalTypographyMixin();
	Container::get( 'GlobalTypography' )->updateWebBookStyleSheet();
	\Pressbooks\Editor\update_editor_style();
}


/**
 * Fix Sass for everything that has to do with dynamically generated font stacks
 */
function fix_missing_font_stacks() {

	$sass = Container::get( 'Sass' );

	if ( ! is_file( $sass->pathToUserGeneratedSass() . '/_font-stack-web.scss' ) ) {
		Container::get( 'GlobalTypography' )->updateGlobalTypographyMixin();
	}

	if ( realpath( get_stylesheet_directory() . '/style.scss' ) && ! is_file( $sass->pathToUserGeneratedCss() . '/style.css' ) ) {
		Container::get( 'GlobalTypography' )->updateWebBookStyleSheet();
	}

	if ( ! is_file( $sass->pathToUserGeneratedCss() . '/editor.css' ) ) {
		\Pressbooks\Editor\update_editor_style();
	}
}
