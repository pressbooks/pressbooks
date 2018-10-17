<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/organize/namespace.php' );

class Admin_OrganizeTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_update_post_visibility() {

		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'publish', $struct['front-matter'][0]['post_status'] );
		$this->assertEquals( 'publish', $struct['back-matter'][0]['post_status'] );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-organize-visibility' );
		$_POST['post_ids'] = $struct['front-matter'][0]['ID'] . ',' . $struct['back-matter'][0]['ID'];
		$_POST['export'] = 0;
		\Pressbooks\Admin\Organize\update_post_visibility();

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'web-only', $struct['front-matter'][0]['post_status'] );
		$this->assertEquals( 'web-only', $struct['back-matter'][0]['post_status'] );
	}

	public function test_update_post_title_visibility() {

		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( '', get_post_meta( $struct['front-matter'][0]['ID'], 'pb_show_title', true ) );
		$this->assertEquals( '', get_post_meta( $struct['back-matter'][0]['ID'], 'pb_show_title', true ) );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-organize-showtitle' );
		$_POST['post_ids'] = $struct['front-matter'][0]['ID'] . ',' . $struct['back-matter'][0]['ID'];
		$_POST['show_title'] = 'on';
		\Pressbooks\Admin\Organize\update_post_title_visibility();

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'on', get_post_meta( $struct['front-matter'][0]['ID'], 'pb_show_title', true ) );
		$this->assertEquals( 'on', get_post_meta( $struct['back-matter'][0]['ID'], 'pb_show_title', true ) );
	}

	public function test_reorder() {

		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$struct = \Pressbooks\Book::getBookStructure();
		$one = $struct['part'][0]['chapters'][0];
		$two = $struct['part'][0]['chapters'][1];
		$this->assertNotEquals( 'Chapter 1', $one['post_title'] );
		$this->assertEquals( 'Chapter 1', $two['post_title'] );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-organize-reorder' );
		$_POST['id'] = $two['ID'];
		$_POST['old_order'] = "chapter[]={$two['ID']}&chapter[]={$one['ID']}";
		$_POST['new_order'] = "chapter[]={$two['ID']}&chapter[]={$one['ID']}";
		$_POST['old_parent'] = $struct['part'][0]['ID'];
		$_POST['new_parent'] = $struct['part'][0]['ID'];

		\Pressbooks\Admin\Organize\reorder();

		$struct = \Pressbooks\Book::getBookStructure();
		$one = $struct['part'][0]['chapters'][0];
		$two = $struct['part'][0]['chapters'][1];
		$this->assertEquals( 'Chapter 1', $one['post_title'] );
		$this->assertNotEquals( 'Chapter 1', $two['post_title'] );
	}

}
