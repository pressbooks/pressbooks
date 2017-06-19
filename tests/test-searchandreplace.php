<?php

class SearchResultTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Modules\SearchAndReplace\Result
	 */
	protected $result;

	/**
	 * @var \Pressbooks\Modules\SearchAndReplace\Types\Content
	 */
	protected $content;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->result = new \Pressbooks\Modules\SearchAndReplace\Result();
		$this->content = new \Pressbooks\Modules\SearchAndReplace\Types\Content();
	}

	public function test_singleLine() {
		$this->result->search_plain = "line\rbreak";
		$this->assertEquals( $this->result->singleLine(), false );
		$this->result->search_plain = "line\nbreak";
		$this->assertEquals( $this->result->singleLine(), false );
		$this->result->search_plain = 'no line break';
		$this->assertEquals( $this->result->singleLine(), true );
	}

	public function test_regexValidate() {

		$expr = "/known/i";
		$result = $this->content->regexValidate( $expr );
		$this->assertEquals( null, $result );

		$expr = '/known/e';
		$result = $this->content->regexValidate( $expr );
		$this->assertContains( 'Unknown modifier', $result );

		$expr = "/known/e\0";
		$result = $this->content->regexValidate( $expr );
		$this->assertContains( 'Null byte', $result );

		$expr = '~not a regex/';
		$result = $this->content->regexValidate( $expr );
		$this->assertNotEquals( null, $result );
	}

	public function test_searchAndReplace() {

		$this->_book();

		$this->content->regex = false;
		$results = $this->content->searchAndReplace( 'chapter', 'laughter', 0, 0, 'asc', true );
		foreach ( $results as $result ) {
			$this->assertContains( 'laughter', $result->content );
		}

		$this->content->regex = true;
		$results = $this->content->searchAndReplace( '/LAUGHTER/i', 'sadness', 0, 0, 'asc', true );
		foreach ( $results as $result ) {
			$this->assertContains( 'sadness', $result->content );
		}
	}

}
