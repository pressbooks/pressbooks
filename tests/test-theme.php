<?php

class ThemeTest extends \WP_UnitTestCase {

	public function test_update_template_root() {

		$old = get_option( 'template_root' );

		update_option( 'template_root', '/plugins/pressbooks/themes-book' );
		\Pressbooks\Theme\update_template_root();
		$this->assertEquals( '/themes', get_option( 'template_root' ) );

		update_option( 'template_root', $old ); // Put back to normal
	}
}