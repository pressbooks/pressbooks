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
	function __construct(array $tabs) {
		$this->tabs = $tabs;
	}

	/**
	 * Register the settings on each tab, run upgrade() if needed.
	 */
	function loadTabs() {
		foreach ( $this->tabs as $slug => $subclass ) {
			$option = get_option( 'pressbooks_theme_options_' . $slug, $subclass::getDefaults() );
			$tab = new $subclass( $option );
			$tab->init();
			wp_cache_delete( 'pressbooks_theme_options_' . $slug . '_version', 'options' );
			$version = get_option( 'pressbooks_theme_options_' . $slug . '_version', 0 );
			if ( $version < $tab::$currentVersion ) {
				$tab->upgrade( $version );
				update_option( 'pressbooks_theme_options_' . $slug . '_version', $tab::$currentVersion, false );
				if ( WP_DEBUG ) {
					error_log( 'Upgraded ' . $slug . ' options from version ' . $version .' --> ' . $tab::$currentVersion );
				}
			}
		}
	}

	/**
	 * Render the theme options page and load the appropriate tab.
	 */
	static function render() { ?>
		<div class="wrap">
			<h1><?php echo wp_get_theme(); ?> <?php _e('Theme Options', 'pressbooks'); ?></h1>
			<?php settings_errors(); ?>
			<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'global'; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( \Pressbooks\Modules\ThemeOptions\ThemeOptions::getTabs() as $slug => $subclass ) { ?>
					<a href="<?= admin_url('/themes.php'); ?>?page=pressbooks_theme_options&tab=<?= $slug; ?>" class="nav-tab <?= $active_tab == $slug ? 'nav-tab-active' : ''; ?>"><?= $subclass::getTitle() ?></a>
				<?php } ?>
			</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'pressbooks_theme_options_' . $active_tab );
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
		add_action('admin_init', array($self, 'loadTabs'));
	}

	/**
	 * Returns a filtered array of tabs that we should be loading.
	 * @returns array
	 */
	static function getTabs() {
		$tabs = array(
			'global' => '\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'web' => '\Pressbooks\Modules\ThemeOptions\WebOptions',
			'pdf' => '\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'mpdf' => '\Pressbooks\Modules\ThemeOptions\mPDFOptions',
			'ebook' => '\Pressbooks\Modules\ThemeOptions\EbookOptions'
		);

		if ( ! \Pressbooks\Utility\check_prince_install() ) {
			unset( $tabs['mpdf'] );
		}

		if ( ! \Pressbooks\Modules\Export\Mpdf\Pdf::isInstalled() ) {
			unset( $tabs['mpdf'] );
		}

		return apply_filters( 'pressbooks_theme_options_tabs', $tabs );
	}
}
