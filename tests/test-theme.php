<?php

class ThemeTest extends \WP_UnitTestCase
{
    use utilsTrait;

    public function test_migrate_book_themes() {
        $this->_book();
        delete_option( 'pressbooks_theme_migration' );
        \Pressbooks\Theme\migrate_book_themes();
        $this->assertEquals( 4, get_option( 'pressbooks_theme_migration' ) );
    }

    public function test_update_template_root() {
        $old = get_option( 'template_root' );

        update_option( 'template_root', '/plugins/pressbooks/themes-book' );
        \Pressbooks\Theme\update_template_root();
        $this->assertEquals( '/themes', get_option( 'template_root' ) );

        update_option( 'template_root', $old ); // Put back to normal
    }
}
