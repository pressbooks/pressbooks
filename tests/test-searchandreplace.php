<?php

class SearchResultTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Modules\SearchAndReplace\Result
	 */
	protected $result;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->result = new \Pressbooks\Modules\SearchAndReplace\Result();
	}


	/**
	 * @covers \Pressbooks\Modules\SearchAndReplace\Result::singleLine
	 */
	public function test_singleLine() {
		$this->result->search_plain = "line\rbreak";
		$this->assertEquals( $this->result->singleLine(), false );
		$this->result->search_plain = "line\nbreak";
		$this->assertEquals( $this->result->singleLine(), false );
		$this->result->search_plain = "no line break";
		$this->assertEquals( $this->result->singleLine(), true );
	}

}
