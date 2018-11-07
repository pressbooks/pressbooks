<?php

// require_once( PB_PLUGIN_DIR . 'inc/class-book.php' );

class BookTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_getInstance() {

		$book = \Pressbooks\Book::getInstance();

		$this->assertInstanceOf( '\Pressbooks\Book', $book );
	}


	public function test_isBook() {

		$book = \Pressbooks\Book::getInstance();

		switch_to_blog( get_network()->site_id );
		$this->assertFalse( $book::isBook() );

		$this->_book();
		$this->assertTrue( $book::isBook() );
	}

	public function test_getBookStructure() {

		$book = \Pressbooks\Book::getInstance();

		// Returns export value
		$this->_book();
		$this->_createChapter(); // Create orphan
		$structure = $book::getBookStructure();

		$this->assertTrue( count( $structure['__orphans'] ) === 1 ); // In __orphans because doesn't belong to a part
		$vals = array_values( $structure['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );
		$this->assertEquals( $structure['front-matter'][0]['post_title'], __( 'Introduction', 'pressbooks' ) );
		$this->assertArrayHasKey( 'part', $structure );
		$this->assertArrayHasKey( 'chapters', $structure['part'][0] );
		$this->assertArrayHasKey( 'back-matter', $structure );

		// Returns cached export value, with $blog_id as param
		global $blog_id;
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post( [ 'ID' => $page['ID'], 'post_status' => 'draft' ] );
		$structure = $book::getBookStructure( $blog_id );
		$this->assertTrue( count( $structure['__orphans'] ) === 1 );
		$vals = array_values( $structure['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );

		// Returns latest export value no cache
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post( [ 'ID' => $page['ID'], 'post_status' => 'draft' ] );
		$book::deleteBookObjectCache();
		$structure = $book::getBookStructure();
		$this->assertTrue( count( $structure['__orphans'] ) === 1 );
		$vals = array_values( $structure['__orphans'] );
		$page = array_shift( $vals );
		$this->assertFalse( $page['export'] );
	}

	public function test_getBookContents() {

		$book = \Pressbooks\Book::getInstance();

		// Returns export value
		$this->_book();
		$this->_createChapter(); // Create orphan
		$contents = $book::getBookContents();
		$this->assertTrue( count( $contents['__orphans'] ) === 1 ); // In __orphans because doesn't belong to a part
		$vals = array_values( $contents['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );
		$this->assertEquals( $contents['front-matter'][0]['post_content'], __( 'This is where you can write your introduction.', 'pressbooks' ) );
		$this->assertArrayHasKey( 'part', $contents );
		$this->assertArrayHasKey( 'chapters', $contents['part'][0] );
		$this->assertArrayHasKey( 'back-matter', $contents );

		// Returns cached export value
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post( [ 'ID' => $page['ID'], 'post_status' => 'draft' ] );
		$contents = $book::getBookContents();
		$this->assertTrue( count( $contents['__orphans'] ) === 1 );
		$vals = array_values( $contents['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );

		// Returns latest export value no cache
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post( [ 'ID' => $page['ID'], 'post_status' => 'draft' ] );
		$book::deleteBookObjectCache();
		$contents = $book::getBookContents();
		$this->assertTrue( count( $contents['__orphans'] ) === 1 );
		$vals = array_values( $contents['__orphans'] );
		$page = array_shift( $vals );
		$this->assertFalse( $page['export'] );
	}

	public function test_getBookInformation() {

		$book = \Pressbooks\Book::getInstance();

		$this->_book();
		$mp = ( new \Pressbooks\Metadata() )->getMetaPost();
		add_post_meta( $mp->ID, 'pb_about_unlimited', 'Hello world!', true );

		// Returns texturized pb_about_unlimited value
		$info = $book::getBookInformation();
		$this->assertArrayHasKey( 'pb_about_unlimited', $info );
		$this->assertEquals( $info['pb_about_unlimited'], '<p>Hello world!</p>' );
		foreach ( $info as $key => $val ) {
			$this->assertStringStartsWith( 'pb_', $key );
		}

		// Returns cached pb_about_unlimited value, with $blog_id as param
		global $blog_id;
		delete_post_meta( $mp->ID, 'pb_about_unlimited' );
		$info = $book::getBookInformation( $blog_id );
		$this->assertArrayHasKey( 'pb_about_unlimited', $info );
		$this->assertEquals( $info['pb_about_unlimited'], '<p>Hello world!</p>' );

		// Returns latest pb_about_unlimited value no cache
		delete_post_meta( $mp->ID, 'pb_about_unlimited' );
		$book::deleteBookObjectCache();
		$info = $book::getBookInformation();
		$this->assertArrayNotHasKey( 'pb_about_unlimited', $info );
	}

	public function test_wordCount() {

		$book = \Pressbooks\Book::getInstance();

		$this->_book();
		$wc = $book::wordCount();
		$wc_selected_for_export = $book::wordCount( true );

		$this->assertEquals( 164, $wc );
		$this->assertEquals( 164, $wc_selected_for_export );
	}

	public function test_getSubsections() {
		$this->_book();
		$book = \Pressbooks\Book::getInstance();

		$result = $book::getSubsections( 0 );
		$this->assertEquals( false, $result );

		$test = "<h1>Hi there!<b></b></h1><p>How are you?</p>";
		$id = $book::getBookStructure()['front-matter'][0]['ID'];
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertArrayHasKey( "front-matter-{$id}-section-1", $result );
		$this->assertEquals( 'Hi there!', $result["front-matter-{$id}-section-1"] );

		$test = "<H1 style='font-size:small;'>Hi there! Hope you're doing good.<B></B></H1><P>How are you?</P>"; // ALL CAPS, texturized
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertArrayHasKey( "front-matter-{$id}-section-1", $result );
		$this->assertEquals( 'Hi there! Hope you&#8217;re doing good.', $result["front-matter-{$id}-section-1"] );

		$test = "<h2>Hi there! Hope you're doing good.<b></b></h2><p>How are you?</p>"; // H2
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertEquals( false, $result );
	}

	public function test_getAllSubsections() {
		$this->_book();
		$book = \Pressbooks\Book::getInstance();
		update_option( 'pressbooks_theme_options_global', [ 'parse_subsections' => 1 ] );

		$id = $book::getBookStructure()['part'][0]['chapters'][0]['ID'];
		$result = $book::getAllSubsections( $book::getBookStructure() );
		$this->assertArrayHasKey( 'chapters', $result );
		$this->assertInternalType( 'array', $result['chapters'][ $id ] );
	}

	public function test_tagSubsections() {

		$this->_book();
		$book = \Pressbooks\Book::getInstance();

		$test = "<h1>Hi there!<b></b></h1><p>How are you?.</p>";
		$result = $book::tagSubsections( $test, 0 );
		$this->assertEquals( false, $result );

		$id = $book::getBookStructure()['front-matter'][0]['ID'];
		$result = $book::tagSubsections( $test, $id );
		$this->assertContains( "<h1 id=\"front-matter-{$id}-section-1", $result );
		$this->assertContains( '<b></b>', $result );

		$test = "<H1 style='font-size:small;'>Hi there!<B></B></H1><P>How are you?.</P>"; // ALL CAPS
		$result = $book::tagSubsections( $test, $id );
		$this->assertContains( "<h1 style=\"font-size:small;\" id=\"front-matter-{$id}-section-1\" class=\"section-header\"", $result );
		$this->assertContains( '<b></b>', $result );

		$test = "<h1 class='foo' id='bar'>Hi there!<b></b></h1><p>How are you?.</p>"; // existing class and id
		$result = $book::tagSubsections( $test, $id );
		$this->assertContains( "<h1 class=\"section-header foo bar\" id=\"front-matter-{$id}-section-1\"", $result );

		$test = "<h2>Hi there!<b></b></h2><p>How are you?</p>"; // H2
		$result = $book::tagSubsections( $test, $id );
		$this->assertEquals( false, $result );
	}

	public function test_get_position() {
		$this->_book();
		$book = \Pressbooks\Book::getInstance();

		// Front End Mode

		$url_1 = $book::getFirst();
		$this->assertContains( 'example.org/', $url_1 );
		$post_id = $book::getFirst( true );
		$this->assertTrue( is_integer( $post_id ) );
		$this->assertTrue( $post_id > 0 );

		$url_2 = $book::get( 'first' );
		$this->assertEquals( $url_2, $url_1 );
		$post_id = $book::get( 'first', true );
		$this->assertTrue( is_integer( $post_id ) );
		$this->assertTrue( $post_id > 0 );

		// Set pos to first post
		global $blog_id, $post;
		$blog_id = get_current_blog_id();
		$post = get_post( $post_id );

		$url = $book::get( 'next' );
		$this->assertContains( 'example.org/', $url );
		$post_id = $book::get( 'next', true );
		$this->assertTrue( is_integer( $post_id ) );
		$this->assertTrue( $post_id > 0 );

		// Admin Mode

		$user_id = $this->factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );

		$post_id = $book::getFirst( true, true );
		$this->assertEquals( 0, $post_id );
		$post_id = $book::get( 'next', true, true );
		$this->assertEquals( 0, $post_id );

		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$url = $book::getFirst( false, true);
		$this->assertContains( 'example.org/', $url );
		$post_id = $book::getFirst( true, true );
		$this->assertTrue( is_integer( $post_id ) );
		$this->assertTrue( $post_id > 0 );

		global $blog_id, $post;
		$blog_id = get_current_blog_id();
		$post = get_post( $post_id );

		$url = $book::get( 'next', false, true );
		$this->assertContains( 'example.org/', $url );
		$post_id = $book::get( 'next', true, true );
		$this->assertTrue( is_integer( $post_id ) );
		$this->assertTrue( $post_id > 0 );
	}

	public function test_getChapterNumber() {

		$this->_book();
		update_option( 'pressbooks_theme_options_global', [ 'chapter_numbers' => 1 ] );
		$book = \Pressbooks\Book::getInstance();

		$struct = $book::getBookStructure();
		$one = $struct['part'][0]['chapters'][0];
		$two = $struct['part'][0]['chapters'][1];
		$this->assertEquals( 1, $book::getChapterNumber( $one['ID'] ) );
		$this->assertEquals( 2, $book::getChapterNumber( $two['ID'] ) );

		wp_update_post( [ 'ID' => $one['ID'], 'post_status' => 'private' ] );
		$book::deleteBookObjectCache();

		$this->assertEquals( 0, $book::getChapterNumber( $one['ID'], 'webbook' ) );
		$this->assertEquals( 1, $book::getChapterNumber( $two['ID'], 'webbook' ) );

		$this->assertEquals( 1, $book::getChapterNumber( $one['ID'], 'exports' ) );
		$this->assertEquals( 2, $book::getChapterNumber( $two['ID'], 'exports' ) );

		update_option( 'pressbooks_theme_options_global', [ 'chapter_numbers' => 0 ] );
		$this->assertEquals( 0, $book::getChapterNumber( $one['ID'] ) );
		$this->assertEquals( 0, $book::getChapterNumber( $two['ID'] ) );
		$this->assertEquals( 0, $book::getChapterNumber( $one['ID'], 'exports' ) );
		$this->assertEquals( 0, $book::getChapterNumber( $two['ID'] ), 'exports' );
	}

}
