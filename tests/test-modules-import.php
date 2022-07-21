<?php


use Pressbooks\Modules\Import\WordPress\Downloads;
use Pressbooks\Modules\Import\WordPress\Wxr;

class ImportMock extends \Pressbooks\Modules\Import\Import {
	/**
	 * @group import
	 */
	function setCurrentImportOption( array $upload ) {
		return true;
	}

	/**
	 * @group import
	 */
	function import( array $current_import ) {
		return true;
	}
}

class Modules_ImportTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \ImportMock
	 * @group import
	 */
	protected $import;

	/**
	 * @group import
	 */
	public function set_up() {
		parent::set_up();
		$this->import = new \ImportMock();
	}

	/**
	 * @group import
	 */
	public function test_revokeCurrentImport() {
		$this->assertTrue( is_bool( $this->import->revokeCurrentImport() ) );
	}

	/**
	 * @group import
	 */
	public function test_createTmpFile() {

		$file = $this->import->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}

	/**
	 * @group import
	 */
	public function test_isFormSubmission() {

		$this->assertFalse( $this->import::isFormSubmission() );

		$_REQUEST['page'] = 'pb_import';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue( $this->import::isFormSubmission() );
		unset( $_REQUEST['page'], $_SERVER['REQUEST_METHOD'] );

		// Assert that EventSource (Progress bar) returns false, import code works differently than export code
		$reporting = $this->_fakeAjax();
		$_REQUEST['action'] = 'import-book';
		$this->assertFalse( $this->import::isFormSubmission() );
		$this->_fakeAjaxDone( $reporting );
		unset( $_REQUEST['action'] );
	}

	/**
	 * @group import
	 */
	public function test_scrapeAndKneadImages() {

		$html = '<img src="pathtoremoteImage/image.jpg" /> <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4QAqRXhpZgA" />';

		$doc = new DOMDocument();
		$doc->loadHTML( $html );

		$wordpress_importer = new Downloads( null );

		$result = $wordpress_importer->scrapeAndKneadImages( $doc );
		$images = $result['dom']->getElementsByTagName( 'img' );
		$this->assertStringContainsString( '#fixme', $images[0]->getAttribute( 'src' ) );
		$this->assertStringNotContainsString( '#fixme', $images[1]->getAttribute( 'src' ) );

	}

	/**
	 * @group import
	 */
	public function test_wxrInsertAndFindTerm() {
		$wxr = new Wxr();
		$wxr->setSourceBookUrl( 'https://pressbooks.com/' );

		$imported_term = [
			'term_name' => 'Jane Doe',
			'term_taxonomy' => 'contributor',
			'term_description' => 'Some description',
			'slug' => 'jane-doe',
			'termmeta' => [
				[
					'key' => 'contributor_first_name',
					'value' => 'Jane',
				],
				[
					'key' => 'contributor_last_name',
					'value' => 'Doe',
				],
				[
					'key' => 'contributor_prefix',
					'value' => 'Dr.',
				],
				[
					'key' => 'contributor_suffix',
					'value' => 'VI',
				],
				[
					'key' => 'contributor_picture',
					'value' => 'https://pressbooks.com/app/uploads/sites/109504/2018/08/4tatoos.jpg',
				],
			],
		];

		$term = $wxr->insertTerm( $imported_term );
		$term_2 = $wxr->insertTerm( $imported_term );

		$last_term = get_terms(
			[
				'taxonomy' => 'contributor',
				'hide_empty' => false,
				'orderby' => 'id',
				'order' => 'DESC',
			]
		);

		$this->assertEquals( $last_term[1]->term_id, $term['term_id'] );

		$meta = get_term_meta( $term['term_id'] );
		$term = get_term( $term['term_id'] );

		$this->assertEquals( 'contributor', $term->taxonomy );
		$this->assertEquals( 'Jane Doe', $term->name );
		$this->assertStringContainsString( '4tatoos.jpg', $meta['contributor_picture'][0] );

		$term_2 = get_term( $term_2['term_id'] );

		$this->assertStringContainsString( '-', $term_2->slug );

		// Clean attachments after test
		$date = date( 'Y/m' );
		array_map( static function( $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}, array_filter( (array) glob( "/tmp/wordpress/wp-content/uploads/{$date}/*" ) ) );
	}

	public function test_searchMultipleContributorValues() {
		$contributors = new \Pressbooks\Contributors();
		$contributors->insert( 'Leo Schopenhauer', 1 );
		$contributors->insert( 'Leo Simon', 1 );
		$contributors->insert( 'Mary User', 1, 'pb_editors' );

		$post_meta = [
			[
				'key' => 'pb_authors',
				'value' => 'Leo',
			],
			[
				'key' => 'pb_editors',
				'value' => 'Leo',
			],
			[
				'key' => 'pb_editors',
				'value' => 'Os',
			],
			[
				'key' => 'pb_editors',
				'value' => 'Sarah',
			],
		];

		$import = new Wxr();
		$values = $import->searchMultipleContributorValues( 'pb_authors', $post_meta );
		$this->assertCount( 1, $values );

		$values = $import->searchMultipleContributorValues( 'pb_editors', $post_meta );
		$this->assertCount( 3, $values );

		$values = $import->searchMultipleContributorValues( 'pb_fail', $post_meta );
		$this->assertCount( 0, $values );
	}

}
