<?php

class HtmLawedTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\HtmLawed::filter
	 */
	public function test_filter() {
		$output = \Pressbooks\HtmLawed::filter('<h1>Hello world!');
		$this->assertEquals('<h1>Hello world!</h1>', $output);

		$output = \Pressbooks\HtmLawed::filter('<i>nothing to see</i><script>alert("xss")</script>');
		$this->assertEquals('<i>nothing to see</i>alert("xss")', $output);
}
