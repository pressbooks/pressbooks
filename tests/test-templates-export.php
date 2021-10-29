<?php

use Pressbooks\Container;

class TemplateExportTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function setUp()
	{
		$this->blade = Container::get( 'Blade' );
		parent::setUp();
	}

	public function test_genericPostType() {

	}

}
