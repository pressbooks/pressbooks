<?php

use Pressbooks\Contributors;

class ContributorsTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Taxonomy
	 * @group contributors
	 */
	protected $taxonomy;

	/**
	 * @var \Pressbooks\Contributors
	 * @group contributors
	 */
	protected $contributor;

	/**
	 * @group contributors
	 */
	public function setUp() {
		parent::setUp();
		$this->contributor = new Contributors();
		$this->taxonomy = new \Pressbooks\Taxonomy(
			$this->getMockBuilder( '\Pressbooks\Licensing' )->getMock(),
			$this->contributor
		);
	}

	/**
	 * @group contributors
	 */
	public function test_insert() {
		$this->taxonomy->registerTaxonomies();

		$tom = 'Töm O\'Reilly';
		$results = $this->contributor->insert( $tom );
		$term = get_term_by( 'term_id', $results['term_id'], 'contributor' );
		$this->assertEquals( 'tom-oreilly', $term->slug );

		$results2 = $this->contributor->insert( $tom ); // No dupes.
		$this->assertEquals( $results, $results2 );
	}

	/**
	 * @group contributors
	 */
	public function test_getContributors() {
		$this->taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		// Old data model
		add_post_meta( $post_id, 'pb_contributing_authors', 'Monsieur Fake' );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Monsieur Fake', $s );

		// New Data Model
		$this->contributor->insert( 'Zig Zag', $post_id, 'contributors' );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Monsieur Fake and Zig Zag', $s );

		$this->contributor->insert( 'Miss Real', $post_id, 'contributors' );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Monsieur Fake, Zig Zag, and Miss Real', $s );

		// Test that if we try to add more contributors using the old data model it's ignored because we already upgraded
		add_post_meta( $post_id, 'pb_contributing_authors', 'Me Too' );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Monsieur Fake, Zig Zag, and Miss Real', $s );

		// Different contributor type
		$this->contributor->insert( 'Me Too', $post_id, 'authors' );
		$s = $this->contributor->get( $post_id, 'pb_authors' );
		$this->assertEquals( 'Me Too', $s );

		// Invalid contributor type
		$this->contributor->insert( 'Me Three', $post_id, 'pb_fake' );
		$s = $this->contributor->get( $post_id, 'pb_fake' );
		$this->assertEquals( '', $s );

		// All of them
		$all = $this->contributor->getAll( $post_id );
		$this->assertEquals( 'Me Too', $all['pb_authors'] );
		$this->assertEquals( 'Monsieur Fake, Zig Zag, and Miss Real', $all['pb_contributors'] );
		$this->assertEmpty( $all['pb_reviewers'] );
	}

	/**
	 * @group contributors
	 */
	public function test_add() {
		$this->taxonomy->registerTaxonomies();

		$this->assertFalse( $this->contributor->addBlogUser( 999 ) );

		$user_id = $this->factory()->user->create(
			[
				'role' => 'contributor',
				'first_name' => 'Joey',
				'last_name' => 'Joe Joe',
			]
		);
		$user = get_userdata( $user_id );
		$results = $this->contributor->addBlogUser( $user_id );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $user->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $user->user_nicename );
		$this->assertEquals( $term->name, 'Joey Joe Joe' );
		$this->assertEquals( 'Joey', get_term_meta( $term->term_id, 'contributor_first_name', true ) );
		$this->assertEquals( 'Joe Joe', get_term_meta( $term->term_id, 'contributor_last_name', true ) );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		$user = get_userdata( $user_id );
		$results = $this->contributor->addBlogUser( $user_id );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $user->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $user->user_nicename );
		$this->assertEquals( $term->name, $user->display_name );
		$this->assertEquals( '', get_term_meta( $term->term_id, 'contributor_first_name', true ) );
		$this->assertEquals( '', get_term_meta( $term->term_id, 'contributor_last_name', true ) );

		$user_id = $this->factory()->user->create(
			[
				'role' => 'subscriber',
				'first_name' => 'Fanny',
				'last_name' => 'Fan Fan',
			]
		);
		$this->assertFalse( $this->contributor->addBlogUser( $user_id ) );
	}

	/**
	 * @group contributors
	 */
	public function test_update() {
		$this->taxonomy->registerTaxonomies();

		$user_id = $this->factory()->user->create(
			[
				'role' => 'contributor',
				'first_name' => 'Joey',
				'last_name' => 'Joe Joe',
			]
		);
		$old_user_data = get_userdata( $user_id );
		$this->assertFalse( $this->contributor->updateBlogUser( 999, $old_user_data ) );

		$results = $this->contributor->updateBlogUser( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, 'Joey Joe Joe' );

		$update_user_data = get_userdata( $user_id );
		$update_user_data->last_name = 'Shabadoo';
		wp_update_user( $update_user_data );
		$results = $this->contributor->updateBlogUser( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, 'Joey Shabadoo' );

		$update_user_data->first_name = '';
		$update_user_data->last_name = '';
		wp_update_user( $update_user_data );
		$results = $this->contributor->updateBlogUser( $user_id, $old_user_data );
		$this->assertTrue( is_array( $results ) );
		$term = get_term_by( 'slug', $old_user_data->user_nicename, 'contributor' );
		$this->assertEquals( $term->term_id, $results['term_id'] );
		$this->assertEquals( $term->slug, $old_user_data->user_nicename );
		$this->assertEquals( $term->name, $old_user_data->display_name );
	}

	/**
	 * @group contributors
	 */
	public function test_maybeUpgradeSlug() {
		$this->assertEquals( 'pb_authors', $this->contributor->maybeUpgradeSlug( 'pb_section_author' ) );
		$this->assertEquals( 'garbage', $this->contributor->maybeUpgradeSlug( 'garbage' ) );
	}

	/**
	 * @group contributors
	 */
	public function test_upgradeMetaToTerm() {
		$this->taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		$this->assertFalse(
			$this->contributor->convert( 'garbage', 'Rando1', $post_id )
		);

		$this->contributor->convert( 'pb_contributing_authors', 'Rando1', $post_id );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Rando1', $s );

		$this->contributor->convert( 'pb_contributing_authors', [ 'Rando2', 'Rando3' ], $post_id );
		$s = $this->contributor->get( $post_id, 'pb_contributors' );
		$this->assertEquals( 'Rando1, Rando2, and Rando3', $s );
	}

	public function test_editContributorForm() {

		$this->taxonomy->registerTaxonomies();

		$user_id = $this->factory()->user->create(
			[
				'role' => 'contributor',
				'first_name' => 'Joey',
				'last_name' => 'Joe Joe',
			]
		);

		$user = get_userdata( $user_id );

		$results = $this->contributor->addBlogUser( $user_id );

		$term = get_term_by( 'slug', $user->user_nicename, 'contributor' );

		ob_start();
		\Pressbooks\Admin\Metaboxes\contributor_edit_form( $term );
		$buffer = ob_get_clean();

		$this->assertContains( "jQuery('.term-description-wrap').remove()", $buffer );

	}

	public function test_getFullContributors() {

		$this->taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		$this->contributor->insert( 'Zig Zag', $post_id, 'contributors' );
		$this->contributor->insert( 'Zig Zog', $post_id, 'contributors' );

		$contributors = $this->contributor->getFullContributors( $post_id, 'contributors' );

		$this->assertCount( 2, $contributors );
		$this->assertArrayHasKey( 'contributor_twitter', $contributors[0] );

		$contributors = $this->contributor->getFullContributors( $post_id, 'fake_reviewer' );

		$this->assertCount( 0 , $contributors);

	}

}
