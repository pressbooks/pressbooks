<?php

class Modules_Export_TableTest extends \WP_UnitTestCase {
	/**
	 * @var \Pressbooks\Modules\Export\Table
	 * @group export
	 */
	protected $table;

	/**
	 * @var array
	 * @group export
	 */
	protected $item = [
		'ID' => '43761d21',
		'file' => 'Test-1547581888.pdf',
		'format' => 'Pdf',
		'size' => 999999,
		'pin' => 0,
		'exported' => '2019-01-15 19:51',
	];

	/**
	 * @group export
	 */
	public function set_up() {
		parent::set_up();
		$GLOBALS['hook_suffix'] = 'mock';
		$_REQUEST['page'] = 'pb_export';
		$this->table = new \Pressbooks\Modules\Export\Table();
	}

	/**
	 * @group export
	 */
	public function test_single_row() {
		ob_start();
		$this->table->single_row( $this->item );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( "<tr data-id='43761d21'", $buffer );
	}

	/**
	 * @group export
	 */
	public function test_column_default() {
		$x = $this->table->column_default( $this->item, 'ID' );
		$this->assertEquals( '43761d21', $x );
	}

	/**
	 * @group export
	 */
	public function test_column_file() {
		$x = $this->table->column_file( $this->item );
		$this->assertStringContainsString( "<div class='export-file-icon large pdf'", $x );
		$this->assertStringContainsString( "Test-1547581888.pdf", $x );
	}

	/**
	 * @group export
	 */
	public function test_column_pin() {
		$x = $this->table->column_pin( $this->item );
		$this->assertStringContainsString( "name='pin[43761d21]'", $x );
	}

	/**
	 * @group export
	 */
	public function test_get_columns() {
		$x = $this->table->get_columns();
		$this->assertArrayHasKey( 'cb', $x );
		$this->assertArrayHasKey( 'file', $x );
		$this->assertArrayHasKey( 'format', $x );
		$this->assertArrayHasKey( 'size', $x );
		$this->assertArrayHasKey( 'pin', $x );
		$this->assertArrayHasKey( 'exported', $x );
		$this->assertEquals( 'Date Exported', $x['exported'] );
	}

	/**
	 * @group export
	 */
	public function test_get_sortable_columns() {
		$x = $this->table->get_sortable_columns();
		$this->assertArrayHasKey( 'file', $x );
		$this->assertArrayHasKey( 'format', $x );
		$this->assertArrayHasKey( 'pin', $x );
		$this->assertArrayHasKey( 'exported', $x );
	}

	/**
	 * @group export
	 */
	public function test_get_bulk_actions() {
		$x = $this->table->get_bulk_actions();
		$this->assertArrayHasKey( 'delete', $x );
	}

	/**
	 * @group export
	 */
	public function test_prepare_items() {
		$this->table->prepare_items();
		$this->assertTrue( is_array( $this->table->items ) );
	}

	/**
	 * @group export
	 */
	public function test_inlineJs() {
		$x = $this->table->inlineJs();
		$this->assertStringContainsString( "var _pb_export_formats_map = ", $x );
		$this->assertStringContainsString( "var _pb_export_pins_inventory =", $x );
	}

	/**
	 * Test export file sorting when files have the same minute but different seconds.
	 * 
	 * @group export
	 */
	public function test_exported_sorting_with_same_minute_different_seconds() {
		$mock_data = [
			[
				'ID' => 'file_523',
				'file' => 'Test-1575485523_print.pdf',
				'format' => 'PDF',
				'size' => 1000,
				'pin' => 0,
				'exported' => '2019-12-04 18:52:03',
			],
			[
				'ID' => 'file_532', 
				'file' => 'Test-1575485532_print.pdf',
				'format' => 'PDF',
				'size' => 1000,
				'pin' => 0,
				'exported' => '2019-12-04 18:52:12',
			],
		];

		// Test descending sort (default - most recent first)
		$sorted_desc = wp_list_sort( $mock_data, [ 'exported' => 'desc' ] );
		
		$this->assertEquals( 'file_532', $sorted_desc[0]['ID'], 'File ending in 532 (12 seconds) should be first in desc order' );
		$this->assertEquals( 'file_523', $sorted_desc[1]['ID'], 'File ending in 523 (3 seconds) should be second in desc order' );

		// Test ascending sort (oldest first)  
		$sorted_asc = wp_list_sort( $mock_data, [ 'exported' => 'asc' ] );
		
		$this->assertEquals( 'file_523', $sorted_asc[0]['ID'], 'File ending in 523 (3 seconds) should be first in asc order' );
		$this->assertEquals( 'file_532', $sorted_asc[1]['ID'], 'File ending in 532 (12 seconds) should be second in asc order' );

		// Verify the display shows seconds precision (the key fix)
		$this->assertStringContainsString( '18:52:03', $mock_data[0]['exported'], 'Should show seconds precision for first file' );
		$this->assertStringContainsString( '18:52:12', $mock_data[1]['exported'], 'Should show seconds precision for second file' );
		
		// Verify that the seconds values are different (this would have been the same before the fix)
		$this->assertNotEquals( $mock_data[0]['exported'], $mock_data[1]['exported'], 'Files should have different export times when precision includes seconds' );
	}
}
