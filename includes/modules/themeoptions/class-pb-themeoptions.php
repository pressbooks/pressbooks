<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeOptions;

class ThemeOptions {

	private $tabs;

	function __construct(array $tabs) {
		$this->tabs = $tabs;
	}

	function loadTabs() {
		foreach ( glob( PB_PLUGIN_DIR . 'includes/modules/themeoptions/sections/*.php') as $file ) {
			include_once( $file );
		}

		foreach ( $this->tabs as $slug => $tab ) {
			$subclass = '\Pressbooks\Modules\ThemeOptions\\' . ucfirst( $slug ) . 'Options';
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
			} else {
				if ( WP_DEBUG ) {
					error_log( 'No upgrade needed for ' . $slug . ' options (already at version ' . $tab::$currentVersion . ').' );
				}
			}
		}
	}

	static function display() { ?>
		<div class="wrap">
			<h1><?php echo wp_get_theme(); ?> <?php _e('Theme Options', 'pressbooks'); ?></h1>
			<?php settings_errors(); ?>
			<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'global'; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( \Pressbooks\Modules\ThemeOptions\ThemeOptions::getTabs() as $slug => $tab ) { ?>
					<a href="<?= admin_url('/themes.php'); ?>?page=pressbooks_theme_options&tab=<?= $slug; ?>" class="nav-tab <?= $active_tab == $slug ? 'nav-tab-active' : ''; ?>"><?= $tab ?></a>
				<?php } ?>
			</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'pressbooks_theme_options_' . $active_tab );
				do_settings_sections( 'pressbooks_theme_options_' . $active_tab );
				submit_button(); ?>
			</form>
		</div>
	<?php }

	static function init() {
		$self = new self( \Pressbooks\Modules\ThemeOptions\ThemeOptions::getTabs() );
		add_action('admin_init', array($self, 'loadTabs'));
	}

	static function getTabs() {
		$tabs = array(
			'global' => __( 'Global Options', 'pressbooks' ),
			'web' => __( 'Web Options', 'pressbooks' ),
			'pdf' => __( 'PDF Options', 'pressbooks' ),
			'mpdf' => __( 'mPDF Options', 'pressbooks' ),
			'ebook' => __( 'Ebook Options', 'pressbooks' )
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
