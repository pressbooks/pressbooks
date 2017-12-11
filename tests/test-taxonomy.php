<?php

use Pressbooks\Taxonomy;

class TaxonomyTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Taxonomy
	 */
	protected $taxonomy;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$stub = $this
			->getMockBuilder( '\Pressbooks\Licensing' )
			->getMock();
		$stub
			->method( 'getSupportedTypes' )
			->willReturn( [] );

		$this->taxonomy = new Taxonomy( $stub );
	}

	public function test_init() {
		$instance = Taxonomy::init();
		$this->assertTrue( $instance instanceof Taxonomy );
	}

	public function test_hooks() {
		$this->_book();
		$this->taxonomy->hooks( $this->taxonomy );
		$this->assertEquals( 1000, has_filter( 'init', [ $this->taxonomy, 'maybeUpgrade' ] ) );
	}


	public function test_registerTaxonomies() {
		global $wp_taxonomies;
		$wp_taxonomies_old = $wp_taxonomies;
		$wp_taxonomies = [];

		$pressbooks_taxonomies = [
			'front-matter-type',
			'back-matter-type',
			'chapter-type',
			'contributor',
			'license',
		];

		foreach ( $pressbooks_taxonomies as $t ) {
			$this->assertArrayNotHasKey( $t, $wp_taxonomies );
		}

		$this->taxonomy->registerTaxonomies();

		foreach ( $pressbooks_taxonomies as $t ) {
			$this->assertArrayHasKey( $t, $wp_taxonomies );
			$this->assertTrue( $wp_taxonomies[ $t ]->show_in_rest );
		}

		$wp_taxonomies = $wp_taxonomies_old;
	}

	public function test_insertTerms() {
		if ( $exists = get_term_by( 'slug', 'standard', 'chapter-type' ) ) {
			wp_delete_term( $exists->term_id, 'chapter-type' );
		}
		$this->assertFalse( get_term_by( 'slug', 'standard', 'chapter-type' ) );

		$this->taxonomy->insertTerms();
		$this->assertInstanceOf( '\WP_Term', get_term_by( 'slug', 'standard', 'chapter-type' ) );
	}

	public function test_getters() {
		$this->assertEquals( 'miscellaneous', $this->taxonomy->getFrontMatterType( 999 ) );
		$this->assertEquals( 'miscellaneous', $this->taxonomy->getBackMatterType( 999 ) );
		$this->assertEquals( 'standard', $this->taxonomy->getChapterType( 999 ) );
	}


}