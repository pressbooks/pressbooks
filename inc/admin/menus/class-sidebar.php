<?php

namespace Pressbooks\Admin\Menus;

class SideBar {

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		add_action( 'admin_menu', [ $this, 'addAppearance' ] );
	}

}
