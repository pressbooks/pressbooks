<?php

namespace Pressbooks\Utility;

use PressbooksMix\Assets;

/**
 * Class Icons
 *
 * @package Pressbooks\Utility
 * This class is used to get the path to the icons used in the admin menu (heroicons).
 */
class Icons {
	/**
	 * @var string
	 */
	private string $path;

	/**
	 * Icons constructor.
	 */
	public function __construct() {
		$assets = new Assets( 'pressbooks', 'plugin' );
		$this->path = $assets->getPath( 'images/icons/heroicons/' );
	}

	/**
	 * @param  String  $icon The name of the heroicon.
	 * @param  bool  $solid Whether the icon is a solid icon.
	 * @return String
	 */
	public function getIcon( string $icon, bool $solid = false ): string {
		if ( $solid ) {
			return "{$this->path}solid/{$icon}.svg";
		}

		return "{$this->path}{$icon}.svg";
	}

	/**
	 * @param string $icon The name of the heroicon.
	 * @param bool $solid Whether the icon is solid or outline.
	 * @return string The svg content of the icon.
	 */
	public function getIconContents( string $icon, bool $solid = false ): string {
		return file_get_contents(
			$this->getIcon( $icon, $solid )
		);
	}
}
