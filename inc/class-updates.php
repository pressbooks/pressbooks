<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;


class Updates {

	/**
	 * @var Updates
	 */
	private static $instance = null;

	/**
	 * @return Updates
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Updates $obj
	 */
	static public function hooks( Updates $obj ) {
		if ( false === Book::isBook() ) {
			add_action( 'plugins_loaded', [ $obj, 'gitHubUpdater' ] );
		}
	}

	/**
	 */
	public function __construct() {
	}

	/**
	 * @see https://github.com/YahnisElsts/plugin-update-checker
	 */
	public function gitHubUpdater() {
		$updater = \Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/pressbooks/pressbooks/',
			untrailingslashit( PB_PLUGIN_DIR ) . '/pressbooks.php', // Fully qualified path to the main plugin file
			'pressbooks',
			24
		);
		$updater->setBranch( 'master' );
		$updater->getVcsApi()->enableReleaseAssets();
	}

}