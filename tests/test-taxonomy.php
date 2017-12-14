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

	public function test_addContributor() {
		$this->taxonomy->registerTaxonomies();

		$this->assertFalse( $this->taxonomy->addContributor( 999 ) );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor', 'first_name' => 'Joey', 'last_name' => 'Joe Joe' ] );
		$user = get_userdata( $user_id );
		$results = $this->taxonomy->addContributor( $user_id );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $user->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $user->user_nicename );
		$this->assertEquals( $term->name, 'Joey Joe Joe' );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor'  ] );
		$user = get_userdata( $user_id );
		$results = $this->taxonomy->addContributor( $user_id );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $user->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $user->user_nicename );
		$this->assertEquals( $term->name, $user->user_nicename );
	}

	public function test_updateContributor() {
		$this->taxonomy->registerTaxonomies();

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor', 'first_name' => 'Joey', 'last_name' => 'Joe Joe' ] );
		$old_user_data = get_userdata( $user_id );
		$this->assertFalse( $this->taxonomy->updateContributor( 999, $old_user_data ) );

		$results = $this->taxonomy->updateContributor( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, 'Joey Joe Joe' );

		$update_user_data = get_userdata( $user_id );
		$update_user_data->last_name = 'Shabadoo';
		wp_update_user( $update_user_data );
		$results = $this->taxonomy->updateContributor( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, 'Joey Shabadoo' );

		$update_user_data->first_name = '';
		$update_user_data->last_name = '';
		wp_update_user( $update_user_data );
		$results = $this->taxonomy->updateContributor( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, $old_user_data->user_nicename );
	}

}
