<?php


class ImportMock extends \Pressbooks\Modules\Import\Import {

	function setCurrentImportOption( array $upload ) {
		return true;
	}

	function import( array $current_import ) {
		return true;
	}
}


class Modules_ImportTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \ImportMock
	 */
	protected $import;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->import = new \ImportMock();
	}

	public function test_revokeCurrentImport() {
		$this->assertTrue( is_bool( $this->import->revokeCurrentImport() ) );
	}

	public function test_createTmpFile() {

		$file = $this->import->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}

	public function test_WordPress_Wxr_SanityCheck() {

		// Setup new book

		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		// Import

		$importer = new \Pressbooks\Modules\Import\WordPress\Wxr();
		$file = $importer->createTmpFile();
		file_put_contents( $file, file_get_contents( __DIR__ . '/data/Metamorphosis-1516388571.xml' ) );
		$this->assertTrue( $importer->setCurrentImportOption( [ 'file' => $file, 'type' => '' ] ) );

		$options = get_option( 'pressbooks_current_import' );
		$options['default_post_status'] = 'publish';
		$post = [];
		foreach ( $options['chapters'] as $k => $v ) {
			$post[ $k ] = [
				'import' => 1,
				'type' => $options['post_types'][ $k ],
			];
		}
		$_POST['chapters'] = $post;
		$this->assertTrue( $importer->import( $options ) );

		$info = \Pressbooks\Book::getBookInformation();
		$this->assertEquals( 'Metamorphosis', $info ['pb_title'] );
		$this->assertEquals( 'Franz Kafka', $info ['pb_authors'] );

		$term = get_term_by( 'slug', 'franz-kafka', \Pressbooks\Contributors::TAXONOMY );
		$this->assertInstanceOf( \WP_Term::class, $term );

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'CHAPTER I', $struct['part'][1]['chapters'][0]['post_title'] );
		$this->assertEquals( 'CHAPTER II', $struct['part'][1]['chapters'][1]['post_title'] );
		$this->assertEquals( 'CHAPTER III', $struct['part'][1]['chapters'][2]['post_title'] );
	}


}