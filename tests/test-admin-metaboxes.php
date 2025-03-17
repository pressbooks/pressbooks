<?php

use Pressbooks\Contributors;
use function Pressbooks\Admin\Metaboxes\save_contributor_meta;


/**
 * @group metaboxes
 */
class Admin_Metaboxes extends \WP_UnitTestCase {
	use utilsTrait;

	private $book;
	private $structure;
	private $metadata;

	public function set_up() {
		parent::set_up();
		$this->book = \Pressbooks\Book::getInstance();
		$this->_book();
		$this->structure = $this->book::getBookStructure();
		$this->metadata = new \Pressbooks\Metadata();
		$GLOBALS['post'] = $this->metadata->getMetaPost();
		$_POST = [];
	}

	public function test_render_metabox() {
		 global $post;

		add_post_meta( $post->ID, 'pb_is_based_on', 'https://book.pressbooks.com/' );

		foreach ( [
			'Pressbooks\\Admin\\Metaboxes\\About',
			'Pressbooks\\Admin\\Metaboxes\\AdditionalCatalogInformation',
			'Pressbooks\\Admin\\Metaboxes\\Copyright',
			'Pressbooks\\Admin\\Metaboxes\\GeneralInformation',
			'Pressbooks\\Admin\\Metaboxes\\Institutions',
			'Pressbooks\\Admin\\Metaboxes\\Subjects',
		] as $classname ) {
			$metabox = in_array( $classname, [
				'Pressbooks\\Admin\\Metaboxes\\Copyright',
				'Pressbooks\\Admin\\Metaboxes\\GeneralInformation',
			] ) ?
				new $classname( expanded: true ) :
				new $classname();
			ob_start();
			$metabox->render();
			$output = ob_get_clean();

			$this->assertStringContainsString( $metabox->slug . '_nonce', $output );

			foreach ( $metabox->fields as $field ) {
				$this->assertStringContainsString( $field->name, $output );
			}
		}

		$post = get_post( $this->structure['part'][0]['ID'] );

		$metabox = new Pressbooks\Admin\Metaboxes\PartVisibility();
		ob_start();
		$metabox->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( $metabox->slug . '_nonce', $output );

		foreach ( $metabox->fields as $field ) {
			$this->assertStringContainsString( $field->name, $output );
		}

		$post = get_post( $this->structure['part'][0]['chapters'][0]['ID'] );

		$metabox = new Pressbooks\Admin\Metaboxes\SectionMetadata();
		ob_start();
		$metabox->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( $metabox->slug . '_nonce', $output );

		foreach ( $metabox->fields as $field ) {
			$this->assertStringContainsString( $field->name, $output );
		}
	}

	public function test_save_metabox(): void {
		global $post;

		// Nonce not set, user lacks permissions
		$metabox = new Pressbooks\Admin\Metaboxes\GeneralInformation();
		$_POST['pb_subtitle'] = 'Or, the Whale';
		$metabox->save( $post->ID, 'Or, the Whale' );

		$this->assertEquals( '', get_post_meta( $post->ID, 'pb_subtitle', true ) );

		// Nonce set, user lacks permissions
		$metabox = new Pressbooks\Admin\Metaboxes\GeneralInformation();
		$doc = new DOMDocument();
		$doc->loadHTML( $metabox->nonce );

		$_POST[ "{$metabox->slug}_nonce" ] = $doc->getElementById( "{$metabox->slug}_nonce" )->getAttribute( 'value' );

		$_POST['pb_subtitle'] = 'Or, the Whale';
		$metabox->save( $post->ID, 'Or, the Whale' );

		$this->assertEquals( '', get_post_meta( $post->ID, 'pb_subtitle', true ) );

		// Nonce set, user has permissions
		$user_id = wp_insert_user( [
			'user_login' => 'administrator',
			'role' => 'administrator',
			'user_pass' => 'password',
		] );
		add_user_to_blog( get_current_blog_id(), $user_id, 'administrator' );
		wp_set_current_user( $user_id, '' );

		$metabox = new Pressbooks\Admin\Metaboxes\GeneralInformation();

		$doc = new DOMDocument();
		$doc->loadHTML( $metabox->nonce );

		$_POST[ "{$metabox->slug}_nonce" ] = $doc->getElementById( "{$metabox->slug}_nonce" )->getAttribute( 'value' );
		$_POST['pb_subtitle'] = 'Or, the Whale';

		$metabox->save( $post->ID, 'Or, the Whale' );

		$this->assertEquals( 'Or, the Whale', get_post_meta( $post->ID, 'pb_subtitle', true ) );

	}

	/**
	 * @test
	 * @group metaboxes
	 */
	public function it_saves_contributor_meta(): void {
		$term_id = wp_insert_term( 'Test Contributor', Contributors::TAXONOMY )['term_id'];

		$_POST = [
			'contributor_meta_nonce' => wp_create_nonce( 'contributor-meta' ),
			'contributor_institution' => 'Test Institution',
			'contributor_description' => 'A test description',
			'contributor_picture' => 'http://example.com/test-picture.jpg',
		];

		save_contributor_meta( $term_id, null, Contributors::TAXONOMY );

		$this->assertSame(
			'Test Institution',
			get_term_meta( $term_id, 'contributor_institution', true )
		);
		$this->assertSame(
			'A test description',
			get_term_meta( $term_id, 'contributor_description', true )
		);
		$this->assertSame(
			'http://example.com/test-picture.jpg',
			get_term_meta( $term_id, 'contributor_picture', true )
		);
	}

	/**
	 * @test
	 * @group metaboxes
	 */
	public function it_save_contributor_meta_wrong_taxonomy(): void {
		$term_id = wp_insert_term( 'Test Contributor', Contributors::TAXONOMY )['term_id'];

		$_POST = [
			'contributor_meta_nonce' => wp_create_nonce( 'contributor-meta' ),
			'contributor_institution' => 'Institution should not save',
		];

		save_contributor_meta( $term_id, null, 'wrong_taxonomy' );

		$this->assertEmpty(
			get_term_meta( $term_id, 'contributor_institution', true )
		);
	}

	/**
	 * @test
	 * @group metaboxes
	 */
	public function it_removes_contributor_picture(): void {
		$term_id = wp_insert_term( 'Test Contributor', Contributors::TAXONOMY )['term_id'];

		update_term_meta( $term_id, 'contributor_picture', 'http://example.com/test-picture.jpg' );

		$_POST = [
			'contributor_meta_nonce' => wp_create_nonce( 'contributor-meta' ),
			'remove_picture' => '1',
		];

		save_contributor_meta( $term_id, null, Contributors::TAXONOMY );

		$this->assertEmpty(
			get_term_meta( $term_id, 'contributor_picture', true )
		);
	}
}
