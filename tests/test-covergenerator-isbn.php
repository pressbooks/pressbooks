<?php

class CoverGenerator_IsbnTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Covergenerator\Isbn
	 */
	public $isbn;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();
		$this->isbn = new \Pressbooks\Covergenerator\Isbn();
	}


	public function test_createBarcode() {
		$isbn = "978-1-873671-00-9 54499";
		$url = $this->isbn->createBarcode( $isbn );
		$this->assertContains( "978-1-873671-00-9-54499", $url );
		$this->assertEquals( $url, get_option( 'pressbooks_cg_isbn' ) );
		$this->assertNotEmpty( \Pressbooks\Image\attachment_id_from_url( $url ) );
	}


	/**
	 *  [ $isbnNumber, $expected ]
	 *
	 * @return array
	 */
	public function validateIsbnNumberProvider() {

		return [
			[ '978-1-873671-00-9 54499', true ],
			[ '978-1-873671-00-9 59', true ],
			[ '978-1-873671-00-9', true ],
			[ '9781873671009', true ],
			[ '1-86074-271-8', true ],
			[ '   1-86074-271-8   ', true ],
			[ '   1-86074-271-8    88888', true ],
			[ '   1-86074-271-8    88888   ', true ],
			[ '978-1-873671-00-9 111', false ],
			[ '111-1-873671-00-9', false ],
			[ '1111111111111', false ],
			[ 'abc', false ],
			[ '', false ],
			[ '1-86074-271-2', false ], // We do not support automatically upgrading ISBN-10 to ISBN-13
			[ '978-1-873671-00', false ], // We do not support ISBN without a trailing check digit
		];
	}

	/**
	 * @dataProvider validateIsbnNumberProvider
	 *
	 * @param string $isbnNumber
	 * @param bool $expected
	 */
	public function test_validateIsbnNumber( $isbnNumber, $expected ) {

		$this->assertEquals( $expected, $this->isbn->validateIsbnNumber( $isbnNumber ) );
	}


	/**
	 *  [ $isbnNumber, $expected ]
	 *
	 * @return array
	 */
	public function fixIsbnNumberProvider() {

		return [
			[ '978-1-873671-00-9 54499', '978-1-873671-00-9 54499' ],
			[ '9992158-10-7', '99921-58-10-7' ],
			[ '9781873671009', '978-1-873671-00-9' ],
			[ '   1-8-6-0-7-4-2-7-1-8    88888   ', '1-86074-271-8 88888' ],
		];
	}

	/**
	 * @dataProvider fixIsbnNumberProvider
	 *
	 * @param string $isbnNumber
	 * @param string $expected
	 */
	public function test_fixIsbnNumber( $isbnNumber, $expected ) {

		$this->assertEquals( $expected, $this->isbn->fixIsbnNumber( $isbnNumber ) );

	}


}
