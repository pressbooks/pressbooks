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

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\Licensing' )
			->getMock();
		$stub1
			->method( 'getSupportedTypes' )
			->willReturn( [] );

		$stub2 = $this
			->getMockBuilder( '\Pressbooks\Contributors' )
			->getMock();
		$stub2
			->method( 'convert' )
			->willReturn( [ 'term_id' => 999, 'term_taxonomy_id' => 999 ] );

		$this->taxonomy = new Taxonomy( $stub1, $stub2 );
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
			'glossary-type',
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
		$this->assertEquals( 'miscellaneous', $this->taxonomy->getGlossaryType( 999 ) );
		$this->assertEquals( 'standard', $this->taxonomy->getChapterType( 999 ) );
	}

	public function test_convertMetaToTerm() {
		$this->taxonomy->registerTaxonomies();

		// Pointless fake tests, stubbed.

		$results = $this->taxonomy->upgradeToContributorTaxonomy( null, null, 'pb_contributing_authors', [ 'Joe Joe', 'Jim Jim', 'Jay Jay' ] );
		$this->assertEquals( 999, $results['term_id'] );
		$this->assertEquals( 999, $results['term_taxonomy_id'] );

		$results = $this->taxonomy->upgradeToContributorTaxonomy( null, null, 'pb_contributing_authors', 'Töm O\'Reilly' );
		$this->assertEquals( 999, $results['term_id'] );
		$this->assertEquals( 999, $results['term_taxonomy_id'] );
	}

	public function test_removeTaxonomyViewLinks() {
		$arr = [ 'view' => 1, 'something_else' => 2 ];
		$res = $this->taxonomy->removeTaxonomyViewLinks( $arr, null );
		$this->assertArrayNotHasKey( 'view', $res );
		$this->assertEquals( 2, $res['something_else'] );
	}

}
