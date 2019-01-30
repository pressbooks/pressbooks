<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class Updates {

	/**
	 * @var string
	 */
	const VERSION_TESTED_HEADER = 'Pressbooks tested up to';

	/**
	 * @var Updates
	 */
	private static $instance = null;

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

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
		if ( Book::isBook() === false ) {
			add_action( 'plugins_loaded', [ $obj, 'gitHubUpdater' ] );
			add_action( 'in_plugin_update_message-pressbooks/pressbooks.php', [ $obj, 'inPluginUpdateMessage' ] );
			add_action( 'core_upgrade_preamble', [ $obj, 'coreUpgradePreamble' ] );
		}
		add_filter( 'extra_plugin_headers', [ $obj, 'extraPluginHeaders' ] );
		add_action( 'admin_init', [ $obj, 'translationsUpdater' ] );
	}

	/**
	 */
	public function __construct() {
		$this->blade = Container::get( 'Blade' );
	}

	/**
	 * GitHub Plugin Update Checker
	 * Hooked into action `plugins_loaded`
	 *
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

	/**
	 * Check for incompatible plugins.
	 * Displayed when user is looking at wp-admin/plugins.php
	 * Hooked into action `in_plugin_update_message-{$file}`
	 * @see \wp_plugin_update_row
	 *
	 * @param array $plugin_data An array of plugin metadata
	 */
	public function inPluginUpdateMessage( $plugin_data ) {
		$untested_plugins = $this->getUntestedPlugins( $plugin_data['new_version'] );
		if ( ! empty( $untested_plugins ) ) {
			echo $this->blade->render(
				'admin.incompatible-plugins', [
					'version' => $plugin_data['new_version'],
					'plugins' => $untested_plugins,
					'div_class' => 'incompatible-plugin-upgrade-notice',
					'table_class' => 'incompatible-plugin-details-table',
				]
			);
		}
	}

	/**
	 * Check for incompatible plugins.
	 * Displayed when user is looking at wp-admin/update-core.php
	 * Hooked into action `core_upgrade_preamble`
	 */
	public function coreUpgradePreamble() {
		$basename = $this->getBaseName();
		$updateable_plugins = get_plugin_updates();
		if ( $updateable_plugins[ $basename ]->update->new_version ?? null ) {
			$plugin_data = $updateable_plugins[ $basename ]; // $plugin_data is in \stdClass format
		} else {
			return; // Bail
		}

		$untested_plugins = $this->getUntestedPlugins( $plugin_data->update->new_version );
		if ( ! empty( $untested_plugins ) ) {
			echo $this->blade->render(
				'admin.incompatible-plugins', [
					'h2' => __( 'Plugins For Pressbooks', 'pressbooks' ),
					'version' => $plugin_data->update->new_version,
					'plugins' => $untested_plugins,
					'table_class' => 'wp-list-table widefat striped',
				]
			);
		}
	}

	/**
	 * @return string
	 */
	public function getBaseName() {
		$path = untrailingslashit( PB_PLUGIN_DIR ) . '/pressbooks.php';
		return plugin_basename( $path );
	}

	/**
	 * Include Pressbooks headers when reading plugin headers
	 * Hooked into filter `extra_plugin_headers`
	 * @see \get_file_data
	 *
	 * @param array $headers Headers.
	 *
	 * @return array
	 */
	public function extraPluginHeaders( $headers ) {
		$headers['PBTested'] = self::VERSION_TESTED_HEADER;
		return $headers;
	}

	/**
	 * Get a list of untested Pressbooks plugins
	 *
	 * @param string $pb_version
	 *
	 * @return array
	 */
	public function getUntestedPlugins( $pb_version ) {
		$pressbooks_plugins = array_merge(
			$this->getPluginsWithHeader( self::VERSION_TESTED_HEADER ),
			$this->getPluginsWithPressbooksInDescription()
		);

		$incompatible = [];
		foreach ( $pressbooks_plugins as $basename => $headers ) {
			$version = '0.0.0';
			if ( ! empty( $headers[ self::VERSION_TESTED_HEADER ] ) ) {
				$version = $headers[ self::VERSION_TESTED_HEADER ];
				if ( substr_count( $version, '.' ) === 1 ) {
					$version .= '.0'; // Semantic versioning fail?
				}
			}
			if ( ! version_compare( $version, $pb_version, '>=' ) ) {
				$incompatible[ $basename ] = $headers;
			}
		}

		return $incompatible;
	}

	/**
	 * Get plugins that have a specific header
	 *
	 * @param string $header
	 *
	 * @return array of plugin info arrays
	 */
	public function getPluginsWithHeader( $header ) {
		$plugins = get_plugins();
		$matches = [];
		foreach ( $plugins as $file => $plugin ) {
			if ( $plugin['Name'] === 'Pressbooks' ) {
				continue; // Fix Pluginception...
			}
			if ( ! empty( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}
		return $matches;
	}

	/**
	 * Get plugins with "Pressbooks" in the description
	 *
	 * @return array of plugin info arrays
	 */
	public function getPluginsWithPressbooksInDescription() {
		$plugins = get_plugins();
		$matches = [];
		foreach ( $plugins as $file => $plugin ) {
			if ( $plugin['Name'] === 'Pressbooks' ) {
				continue; // Fix Pluginception...
			}
			if ( stristr( $plugin['Name'], 'pressbooks' ) || stristr( $plugin['Description'], 'pressbooks' ) || stristr( $file, 'pressbooks' ) ) {
				$matches[ $file ] = $plugin;
			}
		}
		return $matches;
	}

	/**
	 * Get Pressbooks translations from separate repository.
	 * Hooked into action `admin_init`.
	 *
	 * @see https://github.com/afragen/translations-updater
	 *
	 * @return void
	 */
	public function translationsUpdater() {
		$config = [
			'git'       => 'github',
			'type'      => 'plugin',
			'slug'      => 'pressbooks',
			'version'   => PB_PLUGIN_VERSION,
			'languages' => 'https://my-path-to/language-packs',
		];
		( new \Fragen\Translations_Updater\Init() )->run( $config );
	}
}
