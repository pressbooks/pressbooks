<?php


/**
 * @group metaboxes
 */
class Admin_Metaboxes extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up() {
		parent::set_up();
		$this->book = \Pressbooks\Book::getInstance();
		$this->_book();
		$this->structure = $this->book::getBookStructure();
		$this->user_id = wp_insert_user( [
			'user_login' => 'administrator',
			'role' => 'administrator',
			'user_pass' => 'password',
		] );
		add_user_to_blog( get_current_blog_id(), $this->user_id, 'administrator' );
		wp_set_current_user( $this->user_id, '' );
		$this->metadata = new \Pressbooks\Metadata();
		$GLOBALS['post'] = $this->metadata->getMetaPost();
		$_POST = [];
	}

	public function tear_down(): void {
		parent::tear_down();

		wp_delete_user( $this->user_id );
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

	public function provideSaveData(): array {
		return [
			[
				'classname' => 'Pressbooks\\Admin\\Metaboxes\\About',
				'field' => 'pb_about_140',
				'value' => 'It was a dark and stormy night.',
			],
		];
	}

	/**
	 * @dataProvider provideSaveData
	 */
	public function test_save_metabox( string $classname, string $field, string $value ): void {
		global $post;

		$metabox = new $classname();
		$doc = new DOMDocument();
		$doc->loadHTML( $metabox->nonce );
		$_POST[ "{$metabox->slug}_nonce" ] = $doc->getElementById( "{$metabox->slug}_nonce" )->getAttribute( 'value' );
		$_POST[ $field ] = $value;
		$metabox->save( $post->ID );
		$this->assertEquals( $value, get_post_meta( $post->ID, $field, true ) );
	}
}
