<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\ThemeOptions;

use function \Pressbooks\Utility\debug_error_log;

/**
 * Not a subclass of \Pressbooks\Options!
 * Handles initialization of Theme Options admin menu
 */
class ThemeOptions {

	/**
	 * @var ThemeOptions
	 */
	private static $instance = null;

	/**
	 * @return ThemeOptions
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param ThemeOptions $obj
	 */
	static public function hooks( ThemeOptions $obj ) {
		add_action( 'admin_init', [ $obj, 'loadTabs' ] );
		add_filter( 'admin_menu', [ $obj, 'adminMenu' ] );
		add_action( 'after_switch_theme', [ $obj, 'afterSwitchTheme' ] );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Returns a filtered array of tabs that we should be loading.
	 *
	 * @returns array
	 */
	public function getTabs() {
		$tabs = [
			'global' => '\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'web' => '\Pressbooks\Modules\ThemeOptions\WebOptions',
			'pdf' => '\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'ebook' => '\Pressbooks\Modules\ThemeOptions\EbookOptions',
		];

		if ( false === get_site_transient( 'pb_pdf_compatible' ) && false === \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
			unset( $tabs['pdf'] );
		} else {
			set_site_transient( 'pb_pdf_compatible', true );
		}

		if ( false === get_site_transient( 'pb_epub_compatible' ) && false === \Pressbooks\Modules\Export\Epub\Epub201::hasDependencies() ) {
			unset( $tabs['ebook'] );
		} else {
			set_site_transient( 'pb_epub_compatible', true );
		}

		/**
		 * Add a custom tab to the theme options page.
		 *
		 * @since 3.9.7
		 *
		 * @param array $tabs
		 */
		return apply_filters( 'pb_theme_options_tabs', $tabs );
	}

	/**
	 * Add admin page
	 */
	public function adminMenu() {
		add_theme_page(
			__( 'Theme Options', 'pressbooks' ),
			__( 'Theme Options', 'pressbooks' ),
			'edit_others_posts',
			'pressbooks_theme_options',
			[ $this, 'render' ]
		);
	}

	/**
	 * Register the settings on each tab, run upgrade() if needed.
	 */
	public function loadTabs() {
		foreach ( $this->getTabs() as $slug => $subclass ) {
			/** @var \Pressbooks\Options $subclass (not instantiated, just a string) */
			add_filter( "option_page_capability_pressbooks_theme_options_$slug", [ $this, 'setPermissions' ], 10, 1 );
			add_filter( "pb_theme_options_{$slug}_defaults", [ $subclass, 'filterDefaults' ], 10, 1 );
			$options = get_option( "pressbooks_theme_options_{$slug}", [] );
			/** @var \Pressbooks\Options $tab */
			$tab = new $subclass( $options );
			$tab->init();
			wp_cache_delete( "pressbooks_theme_options_{$slug}_version", 'options' ); // WordPress Core caches this key in the "options" group
			$version = get_option( "pressbooks_theme_options_{$slug}_version", 0 );
			if ( $tab::VERSION !== null && $version < $tab::VERSION ) {
				$tab->upgrade( $version );
				update_option( "pressbooks_theme_options_{$slug}_version", $tab::VERSION, false );
				debug_error_log( 'Upgraded ' . $slug . ' options from version ' . $version . ' --> ' . $tab::VERSION );
			}
		}
	}

	/**
	 * Modifies the capability for theme options to allow Editors to manage them.
	 * @see https://developer.wordpress.org/reference/hooks/option_page_capability_option_page/
	 *
	 * @since 4.1.0
	 *
	 * @param string $capability
	 *
	 * @return string
	 */
	public function setPermissions( $capability ) {
		return 'edit_others_posts';
	}

	/**
	 * Render the theme options page and load the appropriate tab.
	 */
	public function render() {
		?>
		<div class="wrap">
			<h1><?php echo wp_get_theme(); ?> <?php _e( 'Theme Options', 'pressbooks' ); ?></h1>
			<?php settings_errors(); ?>
			<?php $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'global'; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->getTabs() as $slug => $subclass ) { ?>
					<a href="
					<?php
					echo admin_url( '/themes.php' );
					?>
					?page=pressbooks_theme_options&tab=
					<?php
					echo $slug;
					?>
					" class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>"><?php echo $subclass::getTitle(); ?></a>
				<?php } ?>
			</h2>
			<form method="post" action="options.php">
				<?php
				do_action( 'pb_before_themeoptions_settings_fields' );
				settings_fields( 'pressbooks_theme_options_' . $active_tab );
				do_settings_sections( 'pressbooks_theme_options_' . $active_tab );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Override saved options with filtered defaults when switching theme
	 */
	public function afterSwitchTheme() {
		$this->clearCache();
		foreach ( $this->getTabs() as $slug => $subclass ) {
			/** @var \Pressbooks\Options $subclass (not instantiated, just a string) */
			$current_options = get_option( "pressbooks_theme_options_{$slug}", [] );
			if ( ! empty( $current_options ) ) {
				update_option( "pressbooks_theme_options_{$slug}", $subclass::filterDefaults( $current_options ) );
			}
		}
	}

	/**
	 * Clear caches in one fell swoop
	 */
	public function clearCache() {
		foreach ( $this->getTabs() as $slug => $subclass ) {
			wp_cache_delete( "pressbooks_theme_options_{$slug}_version", 'options' );  // WordPress Core caches this key in the "options" group
			delete_transient( "pressbooks_theme_options_{$slug}_parsed_sass_variables" );
		}
	}

}
