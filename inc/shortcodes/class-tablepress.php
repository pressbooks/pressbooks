<?php

namespace Pressbooks\Shortcodes;

/**
 * This class wedges itself in between Pressbooks and the TablePress WordPress Plugin
 *
 * @see https://github.com/TobiasBg/TablePress
 *
 * By default, TablePress only registers its shortcodes outside of the admin context.
 * We need them registered there too so that EPUB exports will include the
 * rendered tables.
 *
 * @see https://github.com/TobiasBg/TablePress/blob/master/classes/class-tablepress.php#L148-L155
 */
class TablePress {
	/**
	 * @var TablePress
	 */
	private static $instance = null;

	/**
	 * @return TablePress
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param TablePress $obj
	 */
	static public function hooks( TablePress $obj ) {
		if ( is_plugin_active( 'tablepress/tablepress.php' ) || is_plugin_active_for_network( 'tablepress/tablepress.php' ) ) {
			// Load shortcodes
			$obj->loadShortcodes();
		}
	}

	/**
	 * Add actions and filters to TablePress to load shortcodes in the admin context.
	 */
	public function loadShortcodes() {
		add_action(
			'tablepress_run', function () {
				if ( \Pressbooks\Modules\Export\Export::isFormSubmission() ) {
					\TablePress::$model_options = \TablePress::load_model( 'options' );
					\TablePress::$model_table = \TablePress::load_model( 'table' );
					$GLOBALS['tablepress_frontend_controller'] = \TablePress::load_controller( 'frontend' );
				}
			}
		);
		add_filter(
			'tablepress_edit_link_below_table', function ( $show ) {
				if ( \Pressbooks\Modules\Export\Export::isFormSubmission() ) {
					return false;
				}
				return $show;
			}
		);
	}
}
