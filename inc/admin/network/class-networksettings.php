<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.NonceVerification.Missing
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash

namespace Pressbooks\Admin\Network;

class NetworkSettings {

	const DEFAULT_THEME_OPTION = 'pressbooks_default_book_theme';

	const DEFAULT_THEME = 'pressbooks-malala';

	const DISPLAY_CTA_BANNER_OPTION = 'pressbooks_display_cta_banner';

	/**
	 * @var array
	 */
	private $customOptions = [];

	/**
	 * @var NetworkSettings
	 */
	private static $instance = null;

	public function __construct() {
		$this->customOptions = [
			'default_theme' => self::DEFAULT_THEME_OPTION,
			'display_cta_banner' => self::DISPLAY_CTA_BANNER_OPTION,
		];
	}

	/**
	 * @return NetworkSettings
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param NetworkSettings $obj
	 */
	public static function hooks( NetworkSettings $obj ) {
		add_filter( 'wpmu_options', [ $obj, 'renderCustomOptions' ] );
		add_action( 'update_wpmu_options', [ $obj, 'saveNetworkSettings' ] );
	}

	/**
	 * Render custom network settings options
	 *
	 * @return void
	 */
	public function renderCustomOptions() : void {
		echo '<h3>' . __( 'Theme Settings', 'pressbooks' ) . '</h3>';
		echo ' <table id="menu" class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row">' . __( 'Default Theme', 'pressbooks' ) . '</th><td>';
		$select_options = '';
		$themes = $GLOBALS['pressbooks']->allowedBookThemes( \WP_Theme::get_allowed_on_network() );
		$default_theme = get_site_option( $this->customOptions['default_theme'], self::getDefaultTheme() );
		$display_banner_option = get_site_option( $this->customOptions['display_cta_banner'], '1' );

		foreach ( $themes as $theme => $_ ) {
			$select_options .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				$theme,
				selected( $default_theme, $theme, false ),
				wp_get_theme( $theme )->get( 'Name' )
			);
		}
		printf(
			'<select id="%1$s" name="%1$s">%2$s</select></td></tr></tbody></table>',
			$this->customOptions['default_theme'],
			$select_options
		);
		echo '</td></tr></tbody></table>';
		echo '<h3>' . __( 'CTA banner settings', 'pressbooks' ) . '</h3>';
		echo ' <table id="menu" class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row">' . __( 'CTA Banner', 'pressbooks' ) . '</th><td>';
		printf(
			'<input type="checkbox" id="%1$s" name="%1$s" value="%4$s" %2$s /> <label for="%1$s">%3$s</label>',
			$this->customOptions['display_cta_banner'],
			checked( $display_banner_option, '1', false ),
			__( 'Display the CTA banner', 'pressbooks' ),
			$display_banner_option
		);
		echo '</td></tr></tbody></table>';
	}

	/**
	 * Save network setting options
	 *
	 * @return bool
	 */
	public function saveNetworkSettings() : bool {
		if ( isset( $_POST[ $this->customOptions['default_theme'] ] ) ) {
			$default_theme = sanitize_text_field( $_POST[ $this->customOptions['default_theme'] ] );
			$themes = $GLOBALS['pressbooks']->allowedBookThemes( \WP_Theme::get_allowed_on_network() );
			if ( array_key_exists( $default_theme, $themes ) ) {
				update_site_option( $this->customOptions['default_theme'], $default_theme );
			}
		}

		$display_cta_banner = isset( $_POST[ $this->customOptions['display_cta_banner'] ] ) ? '1' : '0';
		update_site_option( $this->customOptions['display_cta_banner'], $display_cta_banner );

		return true;
	}

	/**
	 * Get default theme defined by default if available, otherwise it returns 'pressbooks-book'
	 *
	 * @return string
	 */
	public static function getDefaultTheme() {
		$themes = $GLOBALS['pressbooks']->allowedBookThemes( \WP_Theme::get_allowed_on_network() );
		return array_key_exists( self::DEFAULT_THEME, $themes ) ?
			self::DEFAULT_THEME : 'pressbooks-book';
	}
}
