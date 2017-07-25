<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\ThemeOptions;

class ThemeOptions {

	/**
	 * The registered theme options tabs.
	 *
	 * @see loadTabs()
	 * @var array
	 */
	private $tabs;

	/**
	 * Constructor.
	 *
	 * @param array $tabs
	 */
	function __construct( array $tabs ) {
		$this->tabs = $tabs;
	}

	/**
	 * Register the settings on each tab, run upgrade() if needed.
	 */
	function loadTabs() {
		foreach ( $this->tabs as $slug => $subclass ) {
			add_filter( "option_page_capability_pressbooks_theme_options_$slug", [ $this, 'setPermissions' ], 10, 1 );
			add_filter( 'pressbooks_theme_options_' . $slug . '_defaults', [ $subclass, 'filterDefaults' ], 10, 1 );
			$option = get_option( 'pressbooks_theme_options_' . $slug, $subclass::getDefaults() );
			/** @var \Pressbooks\Options $tab */
			$tab = new $subclass( $option );
			$tab->init();
			wp_cache_delete( 'pressbooks_theme_options_' . $slug . '_version', 'options' );
			$version = get_option( 'pressbooks_theme_options_' . $slug . '_version', 0 );
			if ( $version < $tab::VERSION ) {
				$tab->upgrade( $version );
				update_option( 'pressbooks_theme_options_' . $slug . '_version', $tab::VERSION, false );
				if ( WP_DEBUG ) {
					error_log( 'Upgraded ' . $slug . ' options from version ' . $version . ' --> ' . $tab::VERSION );
				}
			}
		}
	}

	/**
	 * Modifies the capability for theme options to allow Editors to manage them.
	 * @see https://developer.wordpress.org/reference/hooks/option_page_capability_option_page/
	 *
	 * @since 4.1.0
	 * @param string $capability
	 * @return string
	 */
	function setPermissions( $capability ) {
		return 'edit_others_posts';
	}

	/**
	 * Render the theme options page and load the appropriate tab.
	 */
	static function render() {
		?>
		<div class="wrap">
			<h1><?php echo wp_get_theme(); ?> <?php _e( 'Theme Options', 'pressbooks' ); ?></h1>
			<?php settings_errors(); ?>
			<?php $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'global'; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( \Pressbooks\Modules\ThemeOptions\ThemeOptions::getTabs() as $slug => $subclass ) { ?>
					<a href="<?php echo admin_url( '/themes.php' );
					?>?page=pressbooks_theme_options&tab=<?php echo $slug;
					?>" class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>"><?php echo $subclass::getTitle() ?></a>
				<?php } ?>
			</h2>
			<form method="post" action="options.php">
				<?php do_action( 'pb_before_themeoptions_settings_fields' );
				settings_fields( 'pressbooks_theme_options_' . $active_tab );
				do_settings_sections( 'pressbooks_theme_options_' . $active_tab );
				submit_button(); ?>
			</form>
		</div>
	<?php }

	/**
	 * Instantiate the class and add loadTabs() to the admin_init hook.
	 */
	static function init() {
		$self = new self( \Pressbooks\Modules\ThemeOptions\ThemeOptions::getTabs() );
		add_action( 'admin_init', [ $self, 'loadTabs' ] );
	}

	/**
	 * Returns a filtered array of tabs that we should be loading.
	 * @returns \Pressbooks\Options[]
	 */
	static function getTabs() {
		$tabs = [
			'global' => '\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'web' => '\Pressbooks\Modules\ThemeOptions\WebOptions',
			'pdf' => '\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'ebook' => '\Pressbooks\Modules\ThemeOptions\EbookOptions',
		];

		if ( false === get_site_transient( 'pb_pdf_compatible' ) && false === \Pressbooks\Modules\Export\Prince\Pdf::hasDependencies() ) {
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
		 */
		return apply_filters( 'pb_theme_options_tabs', $tabs );
	}

}
