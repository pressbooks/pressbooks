<?php

use Pressbooks\Admin\Network_Managers_List_Table;

/**
 * @group network-managers-list
 */

class NetworkManagersListTableTest extends \WP_UnitTestCase
{
	protected Network_Managers_List_Table $table;

	/**
	 * @group plugin
	 */
	public function setUp(): void
	{
		parent::setUp();
		$this->table = new Network_Managers_List_Table();
	}

	/**
	 * @test
	 */
	public function it_renders_headers_as_buttons(): void {
		$this->table->set_columns( [
			'column1' => 'Column 1',
			'column2' => 'Column 2',
		] );

		ob_start();
		$this->table->print_column_headers();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<button', $output );
	}

	/**
	 * @test
	 */
	public function it_contains_screen_reader_in_column_headers(): void
	{
		$this->table->set_columns( [
			'column1' => 'Column 1',
			'column2' => 'Column 2',
		] );

		ob_start();
		$this->table->print_column_headers();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'screen-reader-text', $output );
	}
}
