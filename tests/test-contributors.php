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
	public function test_init() {
		global $wp_filter;

		\Pressbooks\Contributors::init();

		$this->assertNotEmpty( $wp_filter['the_content' ]);
		$this->assertNotEmpty( $wp_filter['handle_bulk_actions-edit-contributor' ]);
		$this->assertNotEmpty( $wp_filter['bulk_actions-edit-contributor' ]);
		$this->assertNotEmpty( $wp_filter['upload_mimes'] );
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

	/**
	 * @group contributors
	 */
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

		$this->assertContains( '<button name="dispatch-media-picture" id="btn-media">Upload Picture</button>', $buffer );

	}

	/**
	 * @group contributors
	 */
	public function test_getContributorsMeta() {

		$this->taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		$this->contributor->insert( 'Zig Zag', $post_id, 'contributors' );
		$this->contributor->insert( 'Zig Zog', $post_id, 'contributors' );

		$contributors = $this->contributor->getContributorsWithMeta( $post_id, 'contributors' );

		$this->assertCount( 2, $contributors );
		$this->assertArrayHasKey( 'contributor_twitter', $contributors[0] );
		$this->assertArrayHasKey( 'contributor_picture', $contributors[0] );

		$contributors = $this->contributor->getContributorsWithMeta( $post_id, 'fake_reviewer' );

		$this->assertCount( 0, $contributors );

	}

	/**
	 * @group contributors
	 */
	public function test_personalName() {

		$this->taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		$person1 = $this->contributor->insert( 'Steel Wagstaff', $post_id, 'contributors' );
		$person2 = $this->contributor->insert( 'Apurva Ashook', $post_id, 'contributors' );
		$person3 = $this->contributor->insert( 'Mario Bros', $post_id, 'contributors' );
		$person4 = $this->contributor->insert( 'Isaac Asimov', $post_id, 'contributors' );

		$term1 = get_term_by( 'term_id', $person1['term_id'], 'contributor' );
		$term2 = get_term_by( 'term_id', $person2['term_id'], 'contributor' );
		$term3 = get_term_by( 'term_id', $person3['term_id'], 'contributor' );
		$term4 = get_term_by( 'term_id', $person4['term_id'], 'contributor' );

		add_term_meta( $term1->term_id, 'contributor_first_name', 'Steel' );
		add_term_meta( $term1->term_id, 'contributor_last_name', 'Wagstaff' );
		add_term_meta( $term1->term_id, 'contributor_prefix', 'Dr.' );
		add_term_meta( $term1->term_id, 'contributor_suffix', 'PhD' );

		add_term_meta( $term2->term_id, 'contributor_first_name', 'Apurva' );
		add_term_meta( $term2->term_id, 'contributor_last_name', 'Ashook' );
		add_term_meta( $term2->term_id, 'contributor_prefix', 'Prof.' );
		add_term_meta( $term2->term_id, 'contributor_suffix', 'IV' );

		add_term_meta( $term3->term_id, 'contributor_first_name', 'Mario' );
		add_term_meta( $term3->term_id, 'contributor_last_name', 'Bros' );

		add_term_meta( $term4->term_id, 'contributor_first_name', 'Isaac' );
		add_term_meta( $term4->term_id, 'contributor_last_name', 'Asimov' );
		add_term_meta( $term4->term_id, 'contributor_prefix', 'Sir.' );

		$name1 = $this->contributor->personalName( $term1->slug );
		$name2 = $this->contributor->personalName( $term2->slug );
		$name3 = $this->contributor->personalName( $term3->slug );
		$name4 = $this->contributor->personalName( $term4->slug );

		$this->assertEquals( 'Dr. Steel Wagstaff, PhD', $name1 );
		$this->assertEquals( 'Prof. Apurva Ashook IV', $name2 );
		$this->assertEquals( 'Mario Bros', $name3 );
		$this->assertEquals( 'Sir. Isaac Asimov', $name4 );

	}

	/**
	 * @group contributors
	 */
	public function test_getFields() {
		$fields = Contributors::getContributorFields( 'picture' );
		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'label', $fields );
		$this->assertEquals( 'Picture', $fields['label'] );
		$this->assertArrayHasKey( 'sanitization_method', $fields );

		$fields = Contributors::getContributorFields();
		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'contributor_first_name', $fields );
		$this->assertIsArray( $fields['contributor_first_name'] );
		$this->assertArrayHasKey( 'contributor_description', $fields );
		$this->assertArrayHasKey( 'contributor_twitter', $fields );
	}

	/**
	 * @group contributors
	 */
	public function test_contributorsBackMatterAutoDisplay() {

		$this->taxonomy->registerTaxonomies();
		$this->_book();

		$book = \Pressbooks\Book::getInstance();
		$mp = ( new \Pressbooks\Metadata() )->getMetaPost();

		$person1 = $this->contributor->insert( 'Ricardo ScrumDev', $mp->ID, 'pb_authors' );
		$person2 = $this->contributor->insert( 'Dalcin Dev', $mp->ID, 'pb_authors' );
		$person3 = $this->contributor->insert( 'Os Editor', $mp->ID, 'pb_editors' );

		// No change
		$content = 'Hello';
		$this->assertEquals( 'Hello', $this->contributor->overrideDisplay( $content ) );

		// No change
		global $post;
		$args = [
			'post_title' => 'Test Contributors Page: ' . rand(),
			'post_type' => 'back-matter',
			'post_status' => 'publish',
			'post_content' => 'Not empty',
		];
		$pid = $this->factory()->post->create_object( $args );
		wp_set_object_terms( $pid, 'contributors', 'back-matter-type' );
		$post = get_post( $pid );
		$this->assertEquals( 'Not empty', $this->contributor->overrideDisplay( $post->post_content ) );

		// Yes, changed
		$pid = $this->factory()->post->update_object( $pid, [ 'post_content' => ' &nbsp;    ' ] );
		$post = get_post( $pid );
		$content = $this->contributor->overrideDisplay( $post->post_content );
		$this->assertContains( '<div class="contributors page">', $content );
		$this->assertNotContains( '<h2>Reviewers</h2>', $content ); // if no reviewers that should not be printed
		$this->assertContains( '<h2>Authors</h2>', $content ); // two authors should be plural
		$this->assertContains( '<h2>Editor</h2>', $content ); // one editor should be singular
	}

	/**
	 * @group contributors
	 */
	public function test_getAllContributors() {

		$this->taxonomy->registerTaxonomies();
		$this->_book();

		$book = \Pressbooks\Book::getInstance();
		$mp = ( new \Pressbooks\Metadata() )->getMetaPost();

		$person1 = $this->contributor->insert( 'Ricardo ScrumDev', $mp->ID, 'pb_authors' );
		$person2 = $this->contributor->insert( 'Dalcin Dev', $mp->ID, 'pb_authors' );
		$person3 = $this->contributor->insert( 'Os Editor', $mp->ID, 'pb_editors' );

		$contributors = $this->contributor->getAllContributors();

		$this->assertCount( 2, $contributors );
		$this->assertCount( 1, $contributors['pb_editors']['records'] );
		$this->assertCount( 2, $contributors['pb_authors']['records'] );

		$this->assertEquals( 'Editor', $contributors['pb_editors']['title'] ); // Singular for one element
		$this->assertEquals( 'Authors', $contributors['pb_authors']['title'] ); // Plural for two or more

	}

	/**
	 * @group contributors
	 */
	public function test_addExtraMimeTypes() {
		$mimes = $this->contributor->addExtraMimeTypes( [] );

		$this->assertNotEmpty( $mimes );
		$this->assertArrayHasKey( 'csv', $mimes );
	}

	/**
	 * @group contributors
	 */
	public function test_addBulkAction() {
		$actions = $this->contributor->addBulkAction( [] );

		$this->assertNotEmpty( $actions );
		$this->assertArrayHasKey( 'contributor-download', $actions );
	}

	/**
	 * @group contributors
	 */
	public function test_handleBulkAction() {
		$this->taxonomy->registerTaxonomies();

		$user_one = $this->factory()->user->create([
			'role' => 'contributor',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'slug' => 'johndoe',
		] );

		$user_two = $this->factory()->user->create([
			'role' => 'contributor',
			'first_name' => 'Jane',
			'last_name' => 'Doe',
			'slug' => 'jane',
		]);

		$contributor_one = $this->contributor->addBlogUser( $user_one );
		$contributor_two = $this->contributor->addBlogUser( $user_two );

		$contributors = $this->getMockBuilder( Contributors::class )
			->setMethods(['exportCsv', 'importCsv'])
			->getMock();

		$contributors->expects( $this->once() )->method( 'exportCsv' )->with([
			$contributor_one['term_id'], $contributor_two['term_id']
		]);

		$contributors->handleBulkAction( false, 'contributor-download', [
			$contributor_one['term_id'], $contributor_two['term_id']
		]);

		$contributors->expects( $this->once() )->method( 'importCsv' );

		$contributors->handleBulkAction( false, 'contributor-import', [] );
	}

	/**
	 * @group contributors
	 */
	public function test_exportCsv() {
		$this->taxonomy->registerTaxonomies();

		$user_id = $this->factory()->user->create([
			'role' => 'contributor',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'slug' => 'johndoe',
		] );

		$contributor = $this->contributor->addBlogUser( $user_id );

		add_term_meta( $contributor['term_id'], 'contributor_prefix', 'Dr.' );
		add_term_meta( $contributor['term_id'], 'contributor_first_name', 'John' );
		add_term_meta( $contributor['term_id'], 'contributor_last_name', 'Doe' );
		add_term_meta( $contributor['term_id'], 'contributor_description', 'John\'s biographical info' );
		add_term_meta( $contributor['term_id'], 'contributor_institution', 'Rebus Foundation' );
		add_term_meta( $contributor['term_id'], 'contributor_user_url', 'https://someurl.com' );
		add_term_meta( $contributor['term_id'], 'contributor_twitter', 'https://twitter.com/johndoe' );
		add_term_meta( $contributor['term_id'], 'contributor_linkedin', 'https://linkedin.com/in/johndoe' );
		add_term_meta( $contributor['term_id'], 'contributor_github', 'https://github.com/johndoe' );

		$content = $this->contributor->generateCsvContent(
			$this->contributor->getExportableItems( [ $contributor['term_id'] ] )
		);

		$contributors = $this->getMockBuilder( Contributors::class )
			->setMethods(['downloadCsv'])
			->getMock();

		$contributors->expects( $this->once() )
			->method( 'downloadCsv' )
			->with( $content );

		$contributors->handleBulkAction( false, 'contributor-download', [
			$contributor['term_id']
		]);
	}

	/**
	 * @group contributors
	 */
	public function test_renderImportForm() {
		$this->taxonomy->registerTaxonomies();

		ob_start();
		$this->contributor->renderImportForm();
		$content = ob_get_clean();

		$this->assertContains( '<h2>Import Contributors</h2>', $content );
		$this->assertContains( '<input type="hidden" name="action" value="contributor-import">', $content );
		$this->assertContains( '<input type="file" name="import_file" />', $content );
	}

	/**
	 * @group contributors
	 */
	public function test_getFormMessages() {
		$messages = $this->contributor->getFormMessages();

		$this->assertEquals( '<h2>Import Contributors</h2>', $messages['title'] );
		$this->assertNotEmpty( $messages['hint'] );
	}

	/**
	 * @group contributors
	 */
	public function test_getTransferableFields() {
		$this->assertEquals(
			array_keys( \Pressbooks\Contributors::getContributorFields() ),
			$this->contributor->getTransferableFields()
		);
	}

	/**
	 * @group contributors
	 */
	public function test_downloadContributors() {
		$this->taxonomy->registerTaxonomies();

		$taxonomy = \Pressbooks\Contributors::TAXONOMY;

		$user_id = $this->factory()->user->create([
			'role' => 'contributor',
			'first_name' => 'John',
			'last_name' => 'Doe',
			'slug' => 'johndoe',
		] );

		$contributor = $this->contributor->addBlogUser( $user_id );

		add_term_meta( $contributor['term_id'], $taxonomy . '_prefix', 'Dr.' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_first_name', 'John' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_last_name', 'Doe' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_description', 'John\'s biographical info' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_institution', 'Rebus Foundation' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_user_url', 'https://someurl.com' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_twitter', 'https://twitter.com/johndoe' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_linkedin', 'https://linkedin.com/in/johndoe' );
		add_term_meta( $contributor['term_id'], $taxonomy . '_github', 'https://github.com/johndoe' );

		$items = $this->contributor->getExportableItems( [ $contributor['term_id'] ] );

		$this->assertIsArray( $items );

		$this->assertEquals( 'Dr.', $items[0]['contributor_prefix'] );
		$this->assertEquals( 'John', $items[0]['contributor_first_name'] );
		$this->assertEquals( 'Doe', $items[0]['contributor_last_name'] );
		$this->assertEquals( '', $items[0]['contributor_suffix'] );
		$this->assertEquals( "John\'s biographical info", $items[0]['contributor_description'] );
		$this->assertEquals( 'Rebus Foundation', $items[0]['contributor_institution'] );
		$this->assertEquals( 'https://someurl.com', $items[0]['contributor_user_url'] );
		$this->assertEquals( 'https://twitter.com/johndoe', $items[0]['contributor_twitter'] );
		$this->assertEquals( 'https://linkedin.com/in/johndoe', $items[0]['contributor_linkedin'] );
		$this->assertEquals( 'https://github.com/johndoe', $items[0]['contributor_github'] );

		$csv = $this->contributor->generateCsvContent( $items );

		$this->assertContains(
			'Dr.,John,Doe,,,"John\\\'s biographical info","Rebus Foundation",https://someurl.com,https://twitter.com/johndoe,https://linkedin.com/in/johndoe,https://github.com/johndoe',
			$csv
		);
	}

	/**
	 * @group contributors
	 */
	public function test_importCsv() {
		$contributors = $this->getMockBuilder( Contributors::class )
			->setMethods(['handleUpload'])
			->getMock();

		copy( __DIR__ . '/data/test-contributor-list.csv', __DIR__ . '/data/upload/test-contributor-list.csv' );

		$contributors->expects( $this->once() )
			->method( 'handleUpload' )
			->willReturn( [
				'file' => __DIR__ . '/data/upload/test-contributor-list.csv',
			] );

		$contributors->importCsv();

		$term = get_term_by( 'slug', 'johndoe', 'contributor' );

		$this->assertEquals( 'John Doe', $term->name );
		$this->assertEquals( 'johndoe', $term->slug );
		$this->assertArrayHasKey( 'pb_notices', $_SESSION );
		$this->assertArraySubset(
			[ 'pb_notices' => ['Successfully imported.'] ],
			$_SESSION
		);

		unset( $_SESSION['pb_notices'] );
	}

	/**
	 * @group contributors
	 */
	public function test_skipsImportCsv() {
		$contributors = $this->getMockBuilder( Contributors::class )
			->setMethods(['handleUpload'])
			->getMock();

		$contributors->expects( $this->once() )
			->method( 'handleUpload' )
			->willReturn( false );

		$contributors->importCsv();

		$term = get_term_by( 'slug', 'johndoe', 'contributor' );

		$this->assertFalse( $term );
	}

	/**
	 * @group contributors
	 */
	public function test_contributorRoleNameChange() {
		$current_roles = new WP_Roles();

		$this->assertEquals( 'Contributor', $current_roles->roles['contributor']['name'] );

		Contributors::changeContributorName( $current_roles );

		$this->assertEquals( 'Collaborator', $current_roles->roles['contributor']['name'] );
	}

	/**
	 * @group contributors
	 */
	public function test_handleImage() {
		$src = $this->contributor->handleImage( 'https://pressbooks.com/app/plugins/pressbooks/assets/dist/images/default-book-cover.jpg' );

		$this->assertContains( 'default-book-cover', $src );

		$src = $this->contributor->handleImage( 'not-a-valid-url' );

		$this->assertFalse( $src );
	}
}
