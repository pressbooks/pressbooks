<?php

require_once( PB_PLUGIN_DIR . 'includes/admin/pb-metaboxes.php' );


class MetaboxesTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @covers \Pressbooks\Admin\Metaboxes\add_meta_boxes
	 */
	public function test_update_font_stacks() {

		global $wp_meta_boxes;
		$c = custom_metadata_manager::instance();

		\Pressbooks\Admin\Metaboxes\add_meta_boxes();

		$this->assertArrayHasKey( 'chapter', $wp_meta_boxes );
		$this->assertArrayHasKey( 'part', $wp_meta_boxes );
		$this->assertArrayHasKey( 'metadata', $c->metadata );
		$this->assertArrayHasKey( 'general-book-information', $c->metadata['metadata'] );
		$this->assertArrayHasKey( 'additional-catalogue-information', $c->metadata['metadata'] );
	}

}
