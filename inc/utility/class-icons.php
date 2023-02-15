<?php

namespace Pressbooks\Utility;

/**
 * Class Icons
 * @package Pressbooks\Utility
 * This class is used to get the path to the icons used in the admin menu (heroicons).
 */
class Icons {
	/**
	 * @var string
	 */
	private String $path;

	/**
	 * Icons constructor.
	 */
	public function __construct() {
		$this->path = PB_PLUGIN_URL . 'assets/dist/images/icons/heroicons/';
	}

	/**
	 * @param  String  $icon The name of the heroicon.
	 * @param  bool  $solid Whether the icon is a solid icon.
	 * @return String
	 */
	public function getIcon( String $icon, bool $solid = false ): String {
		$icon = $this->path . $icon . '.svg';
		if ( $solid ) {
			$icon = $this->path . 'solid/' . $icon . '.svg';
		}
		return $icon;
	}
}
