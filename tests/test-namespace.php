<?php

class NamespaceTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		$this->_book();
		global $wp_filter;
		$classes = [
			'\Pressbooks\Activation',
			'\Pressbooks\Taxonomy',
			'\Pressbooks\Updates',
			'\Pressbooks\Admin\Delete\Book',
			'\Pressbooks\Covergenerator\Covergenerator',
			'\Pressbooks\Interactive\Content',
			'\Pressbooks\Modules\Export\Prince\Filters',
			'\Pressbooks\Modules\SearchAndReplace\SearchAndReplace',
			'\Pressbooks\Modules\ThemeOptions\Admin',
			'\Pressbooks\Privacy',
			'\Pressbooks\Shortcodes\Footnotes\Footnotes',
			'\Pressbooks\Shortcodes\Generics\Generics',
			'\Pressbooks\Shortcodes\Wikipublisher\Glyphs',
			'\Pressbooks\Theme\Lock',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}

}