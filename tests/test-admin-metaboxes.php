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
		global $post, $current_user;

		$default_user_id = $current_user->ID;

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
		$nonce = $doc->getElementById( "{$metabox->slug}_nonce" )->getAttribute( 'value' );

		$_POST[ "{$metabox->slug}_nonce" ] = $nonce;
		$_POST[ 'pb_subtitle' ] = 'Or, the Whale';
		$metabox->save( $post->ID );
		$this->assertEquals( 'Or, the Whale', get_post_meta( $post->ID, 'pb_subtitle', true ) );

		$_POST[ "{$metabox->slug}_nonce" ] = 'bad nonce';
		$_POST[ 'pb_subtitle' ] = 'An Arcane History';
		$metabox->save( $post->ID );

		$this->assertEquals( 'Or, the Whale', get_post_meta( $post->ID, 'pb_subtitle', true ) );

		wp_set_current_user( $default_user_id, '' );

		$_POST[ "{$metabox->slug}_nonce" ] = $nonce;
		$_POST[ 'pb_subtitle' ] = 'An Arcane History';
		$metabox->save( $post->ID );

		$this->assertEquals( 'Or, the Whale', get_post_meta( $post->ID, 'pb_subtitle', true ) );
	}
}
