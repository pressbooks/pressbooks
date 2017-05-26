<?php

class CustomCssTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\CustomCss()
	 */
	protected $cc;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->cc = new \Pressbooks\CustomCss();
	}

	public function test_getCustomCssFolder() {

		$path = $this->cc->getCustomCssFolder();
		$this->assertStringEndsWith( '/custom-css/', $path );

	}

	public function test_getBaseTheme() {

		$input = file_get_contents( PB_PLUGIN_DIR . 'themes-book/pressbooks-book/style.css' );
		$output = $this->cc->getCustomCssFolder() . sanitize_file_name( 'web.css' );

		file_put_contents( $output, $input );

		$web = $this->cc->getBaseTheme( 'web' );

		$this->assertTrue( 'pressbooks-book' == $web );

		$prince = $this->cc->getBaseTheme( 'prince' );

		$this->assertFalse( 'pressbooks-book' == $prince );

	}

}
