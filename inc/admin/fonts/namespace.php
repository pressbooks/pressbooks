<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fonts;

use function Pressbooks\Editor\update_editor_style;
use Pressbooks\Container;

/**
 * Compile Sass for everything that has to do with dynamically generated font stacks
 */
function update_font_stacks() {
	// Try to stop a Cache Stampede, Dog-Pile, Cascading Failure...
	if ( ! get_transient( 'pressbooks_updating_font_stacks' ) ) {
		set_transient( 'pressbooks_updating_font_stacks', 1, 5 * MINUTE_IN_SECONDS );

		Container::get( 'GlobalTypography' )->updateGlobalTypographyMixin();
		Container::get( 'Styles' )->updateWebBookStyleSheet();
		update_editor_style();

		delete_transient( 'pressbooks_updating_font_stacks' );
	}
}

/**
 * Fix Sass for everything that has to do with dynamically generated font stacks
 */
function maybe_update_font_stacks() {
	// If this is ajax/cron/404, don't update right now
	if ( wp_doing_ajax() || wp_doing_cron() || is_404() ) {
		return;
	}

	// Try to stop a Cache Stampede, Dog-Pile, Cascading Failure...
	if ( ! get_transient( 'pressbooks_updating_font_stacks' ) ) {
		set_transient( 'pressbooks_updating_font_stacks', 1, 5 * MINUTE_IN_SECONDS );

		$sass = Container::get( 'Sass' );
		if ( ! is_file( $sass->pathToUserGeneratedSass() . '/_font-stack-web.scss' ) ) {
			Container::get( 'GlobalTypography' )->updateGlobalTypographyMixin();
		}
		if ( realpath( get_stylesheet_directory() . '/style.scss' ) && ! is_file( $sass->pathToUserGeneratedCss() . '/style.css' ) ) {
			Container::get( 'Styles' )->updateWebBookStyleSheet();
		}
		if ( ! is_file( $sass->pathToUserGeneratedCss() . '/editor.css' ) ) {
			update_editor_style();
		}

		delete_transient( 'pressbooks_updating_font_stacks' );
	}
}
