<?php

namespace Pressbooks\Utility;

/**
 * Class Icons
 *
 * @package Pressbooks\Utility
 * This class is used to get the path to the icons used in the admin menu (heroicons).
 */
class Icons {
	private string $base_url;

	private string $base_path;

	public function __construct() {
		$path = 'assets/dist/images/icons/heroicons';

		$this->base_url = PB_PLUGIN_URL . $path;
		$this->base_path = PB_PLUGIN_DIR . $path;
	}

	/**
	 * @param  String  $icon The name of the heroicon.
	 * @param  bool  $solid Whether the icon is a solid icon.
	 * @return String
	 */
	public function getIcon( string $icon, bool $solid = false ): string {
		return $this->base_url . $this->path( $icon, $solid );
	}

	/**
	 * @param string $icon The name of the heroicon.
	 * @param bool $solid Whether the icon is solid or outline.
	 * @return string The svg content of the icon.
	 */
	public function render( string $icon, bool $solid = false ): string {
		$file = $this->base_path . $this->path( $icon, $solid );

		if ( ! file_exists( $file ) ) {
			return '';
		}

		return file_get_contents( $file );
	}

	private function path( string $icon, bool $solid = false ): string {
		return $solid ? "/solid/{$icon}.svg" : "/{$icon}.svg";
	}
}
