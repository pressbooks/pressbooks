<?php

class IntegrationsTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_cloneRemoteBook() {

		$source = 'https://pbtest.pressbooks.com';
		$target = uniqid( 'clone-' );

		$this->_setupBookApi();
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		$cloner = new \Pressbooks\Cloner( $source, $target );

		global $wpdb;
		$suppress = $wpdb->suppress_errors();
		$this->assertTrue( $cloner->cloneBook() );
		$wpdb->suppress_errors( $suppress );

		$this->assertEquals( $source, $cloner->getSourceBookUrl() );
		$this->assertInternalType( 'int', $cloner->getSourceBookId() );

		$structure = $cloner->getSourceBookStructure();
		$this->assertInternalType( 'array', $structure );
		$this->assertNotEmpty( $structure );

		$terms = $cloner->getSourceBookTerms();
		$this->assertInternalType( 'array', $terms );
		$this->assertNotEmpty( $terms );

		$meta = $cloner->getSourceBookMetadata();
		$this->assertInternalType( 'array', $meta );
		$this->assertNotEmpty( $meta );
		$this->assertEquals( 'Public Domain (No Rights Reserved)', $meta['license']['name'] );

		$cloned_items = $cloner->getClonedItems();

		$this->assertTrue( count( $cloned_items['metadata'] ) === 1 );
		$this->assertTrue( count( $cloned_items['terms'] ) === 47 );
		$this->assertTrue( count( $cloned_items['front-matter'] ) === 1 );
		$this->assertTrue( count( $cloned_items['parts'] ) === 2 );
		$this->assertTrue( count( $cloned_items['chapters'] ) === 5 );
		$this->assertTrue( count( $cloned_items['back-matter'] ) === 1 );
		$this->assertTrue( count( $cloned_items['media'] ) === 2 );
	}

	public function test_ImportUsingCloningApi() {

		$source = 'https://pbtest.pressbooks.com';

		$this->_setupBookApi();
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] ); // TODO: Why doesn't contributor work?
		wp_set_current_user( $user_id );

		// Import

		$importer = new \Pressbooks\Modules\Import\Api\Api();
		$this->assertTrue( $importer->setCurrentImportOption( [ 'file' => '', 'url' => $source, 'type' => '' ] ) );
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

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'Lorem Ipsum', $struct['part'][2]['chapters'][0]['post_title'] );
		$this->assertEquals( 'Hosted Video', $struct['part'][3]['chapters'][0]['post_title'] );
	}

	public function test_ImportPressbooksWxr() {
		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		// Import

		$importer = new \Pressbooks\Modules\Import\WordPress\Wxr();
		$file = $importer->createTmpFile();
		file_put_contents( $file, file_get_contents( __DIR__ . '/data/Pressbooks-Integration-Testing-1535463018.xml' ) );
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
		$this->assertEquals( 'Pressbooks Integration Testing', $info ['pb_title'] );
		$this->assertEquals( 'Nobody', $info ['pb_authors'] );

		$term = get_term_by( 'slug', 'nobody', \Pressbooks\Contributors::TAXONOMY );
		$this->assertInstanceOf( \WP_Term::class, $term );

		$struct = \Pressbooks\Book::getBookStructure();
		$this->assertEquals( 'Lorem Ipsum', $struct['part'][2]['chapters'][0]['post_title'] );
		$this->assertEquals( 'Hosted Video', $struct['part'][3]['chapters'][0]['post_title'] );

		$this->asserttrue( count( $_SESSION['pb_notices'] ) === 1 );
		$this->assertContains( 'Imported 2 front matter, 2 parts, 6 chapters, 2 back matter, and 2 media attachments.', $_SESSION['pb_notices'][0] );
		unset( $_SESSION['pb_notices'] );
	}

}