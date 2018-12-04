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
			'\Pressbooks\Admin\Delete\Book',
			'\Pressbooks\Covergenerator\Covergenerator',
			'\Pressbooks\EventStreams',
			'\Pressbooks\Interactive\Content',
			'\Pressbooks\Modules\Export\Prince\Filters',
			'\Pressbooks\Modules\SearchAndReplace\SearchAndReplace',
			'\Pressbooks\Modules\ThemeOptions\Admin',
			'\Pressbooks\Modules\ThemeOptions\ThemeOptions',
			'\Pressbooks\Privacy',
			'\Pressbooks\Shortcodes\Footnotes\Footnotes',
			'\Pressbooks\Shortcodes\Generics\Generics',
			'\Pressbooks\Shortcodes\Wikipublisher\Glyphs',
			'\Pressbooks\Taxonomy',
			'\Pressbooks\Theme\Lock',
			'\Pressbooks\Updates',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}

}