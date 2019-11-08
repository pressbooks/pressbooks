<?php

// Legacy "My Catalog" code, WP_List_Table

class Admin_CatalogListTableTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Admin\Catalog_List_Table
	 * @group my_catalog
	 */
	protected $table;

	/**
	 * @group my_catalog
	 */
	public function setUp() {
		parent::setUp();
		$GLOBALS['hook_suffix'] = 'mock';
		$_REQUEST['page'] = 'pb_catalog';
		$this->table = new \Pressbooks\Admin\Catalog_List_Table();
	}

	/**
	 * @group my_catalog
	 */
	public function test_get_columns() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$cols = $this->table->get_columns();
		$this->assertEquals( 'Tag 1', $cols['tag_1'] );
		$this->assertEquals( 'Tag 2', $cols['tag_2'] );

		update_user_meta( $user_id, 'pb_catalog_tag_1_name', '<script>alert(1);</script>' );
		update_user_meta( $user_id, 'pb_catalog_tag_2_name', '<script>alert(2);</script>' );
		$cols = $this->table->get_columns();
		$this->assertEquals( 'alert(1);', $cols['tag_1'] );
		$this->assertEquals( 'alert(2);', $cols['tag_2'] );
	}

}
