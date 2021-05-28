<?php

// require_once( PB_PLUGIN_DIR . 'inc/class-book.php' );
use Pressbooks\DataCollector\Book as BookDataCollector;

class BookTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @group book
	 */
	public function test_getInstance() {

		$book = \Pressbooks\Book::getInstance();

		$this->assertInstanceOf( '\Pressbooks\Book', $book );
	}

	/**
	 * @group book
	 */
	public function test_isBook() {

		$book = \Pressbooks\Book::getInstance();

		switch_to_blog( get_network()->site_id );
		$this->assertFalse( $book::isBook() );

		$this->_book();
		$this->assertTrue( $book::isBook() );
	}

	/**
	 * @group book
	 */
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
		wp_update_post(
			[
				'ID' => $page['ID'],
				'post_status' => 'draft',
			]
		);
		$structure = $book::getBookStructure( $blog_id );
		$this->assertTrue( count( $structure['__orphans'] ) === 1 );
		$vals = array_values( $structure['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );

		// Returns latest export value no cache
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post(
			[
				'ID' => $page['ID'],
				'post_status' => 'draft',
			]
		);
		$book::deleteBookObjectCache();
		$structure = $book::getBookStructure();
		$this->assertTrue( count( $structure['__orphans'] ) === 1 );
		$vals = array_values( $structure['__orphans'] );
		$page = array_shift( $vals );
		$this->assertFalse( $page['export'] );
	}

	/**
	 * @group book
	 */
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
		wp_update_post(
			[
				'ID' => $page['ID'],
				'post_status' => 'draft',
			]
		);
		$contents = $book::getBookContents();
		$this->assertTrue( count( $contents['__orphans'] ) === 1 );
		$vals = array_values( $contents['__orphans'] );
		$page = array_shift( $vals );
		$this->assertTrue( $page['export'] );

		// Returns latest export value no cache
		delete_post_meta( $page['ID'], 'pb_export' );
		wp_update_post(
			[
				'ID' => $page['ID'],
				'post_status' => 'draft',
			]
		);
		$book::deleteBookObjectCache();
		$contents = $book::getBookContents();
		$this->assertTrue( count( $contents['__orphans'] ) === 1 );
		$vals = array_values( $contents['__orphans'] );
		$page = array_shift( $vals );
		$this->assertFalse( $page['export'] );
	}

	/**
	 * @group book
	 */
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

	/**
	 * @group book
	 */
	public function test_wordCount() {

		$book = \Pressbooks\Book::getInstance();

		$this->_book();
		$wc = $book::wordCount();
		$wc_selected_for_export = $book::wordCount( true );

		$this->assertEquals( 174, $wc );
		$this->assertEquals( 174, $wc_selected_for_export );
	}

	/**
	 * @group book
	 */
	public function test_getSubsections() {
		$this->_book();
		$book = \Pressbooks\Book::getInstance();

		$result = $book::getSubsections( 0 );
		$this->assertEquals( false, $result );

		$test = '<h1>Hi there!<b></b></h1><p>How are you?</p>';
		$id = $book::getBookStructure()['front-matter'][0]['ID'];
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertArrayHasKey( "front-matter-{$id}-section-1", $result );
		$this->assertEquals( 'Hi there!', $result[ "front-matter-{$id}-section-1" ] );

		$test = "<H1 style='font-size:small;'>Hi there! Hope you're doing good.<B></B></H1><P>How are you?</P>"; // ALL CAPS, texturized
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertArrayHasKey( "front-matter-{$id}-section-1", $result );
		$this->assertEquals( 'Hi there! Hope you&#8217;re doing good.', $result[ "front-matter-{$id}-section-1" ] );

		$test = "<h2>Hi there! Hope you're doing good.<b></b></h2><p>How are you?</p>"; // H2
		$this->factory()->post->update_object( $id, [ 'post_content' => $test ] );
		$result = $book::getSubsections( $id );
		$this->assertEquals( false, $result );
	}

	/**
	 * @group book
	 */
	public function test_getAllSubsections() {
		$this->_book();
		$book = \Pressbooks\Book::getInstance();
		update_option( 'pressbooks_theme_options_global', [ 'parse_subsections' => 1 ] );

		$id = $book::getBookStructure()['part'][0]['chapters'][0]['ID'];
		$result = $book::getAllSubsections( $book::getBookStructure() );
		$this->assertArrayHasKey( 'chapters', $result );
		$this->assertInternalType( 'array', $result['chapters'][ $id ] );
	}

	/**
	 * @group book
	 */
	public function test_tagSubsections() {

		$this->_book();
		$book = \Pressbooks\Book::getInstance();

		$test = '<h1>Hi there!<b></b></h1><p>How are you?.</p>';
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

		$test = '<h2>Hi there!<b></b></h2><p>How are you?</p>'; // H2
		$result = $book::tagSubsections( $test, $id );
		$this->assertEquals( false, $result );
	}

	/**
	 * @group book
	 */
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

		$url = $book::getFirst( false, true );
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

	/**
	 * @group book
	 */
	public function test_getChapterNumber() {
		$this->_book();
		update_option( 'pressbooks_theme_options_global', [ 'chapter_numbers' => 1 ] );
		$book = \Pressbooks\Book::getInstance();

		$struct = $book::getBookStructure();
		$one = $struct['part'][0]['chapters'][0];
		$two = $struct['part'][0]['chapters'][1];
		$this->assertEquals( 1, $book::getChapterNumber( $one['ID'] ) );
		$this->assertEquals( 2, $book::getChapterNumber( $two['ID'] ) );

		wp_update_post(
			[
				'ID' => $one['ID'],
				'post_status' => 'private',
			]
		);
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

	public function test_getSanitizedBookAboutInfo() {

		$this->_book();
		$mp = ( new \Pressbooks\Metadata() )->getMetaPost();

		$c = custom_metadata_manager::instance();

		$c->admin_init();
		$c->init_metadata();

		\Pressbooks\Admin\Metaboxes\add_meta_boxes();

		$xss_string = '<img src=# onerror=alert(document.cookie) /> hello xss';

		$about_field = 'pb_about_50';

		$field = $c->get_field( $about_field, 'about-the-book', 'metadata' );
		$_POST[ $about_field ] = $xss_string;
		$c->save_metadata_field( $about_field, $field, 'metadata', $mp->ID );
		$value = $c->get_metadata_field_value( $about_field, $field, 'metadata', $mp->ID );
		$this->assertEquals( '<img src="#" alt="image" /> hello xss', $value[0] );

		$about_extended_field = 'pb_about_unlimited';

		$field = $c->get_field( $about_extended_field, 'about-the-book', 'metadata' );
		$_POST[ $about_extended_field ] = $xss_string;
		$c->save_metadata_field( $about_extended_field, $field, 'metadata', $mp->ID );
		$value = $c->get_metadata_field_value( $about_extended_field, $field, 'metadata', $mp->ID );
		$this->assertEquals( '<img src="#" alt="image" /> hello xss', $value[0] );

		$copyright_field = 'pb_custom_copyright';

		$field = $c->get_field( $copyright_field, 'copyright', 'metadata' );
		$_POST[ $copyright_field ] = $xss_string;
		$c->save_metadata_field( $copyright_field, $field, 'metadata', $mp->ID );
		$value = $c->get_metadata_field_value( $copyright_field, $field, 'metadata', $mp->ID );
		$this->assertEquals( '<img src="#" alt="image" /> hello xss', $value[0] );

		$field = $c->get_field( $about_extended_field, 'about-the-book', 'metadata' );
		$_POST[ $about_extended_field ] = '<a href="https://pressbooks.org">Link</a>';
		$c->save_metadata_field( $about_extended_field, $field, 'metadata', $mp->ID );
		$value = $c->get_metadata_field_value( $about_extended_field, $field, 'metadata', $mp->ID );
		$this->assertEquals( '<a href="https://pressbooks.org">Link</a>', $value[0] );

	}

	/**
	 * @group book
	 */
	public function test_invalidatedBisacCodesNotice() {
		$this->_book();
		global $blog_id;
		$book_data_collector = BookDataCollector::init();
		$book_information_array = $book_data_collector->get( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$invalidated_bisac_codes = [ 'COM020010', 'COM020050' ];
		$validated_bisac_codes = [ 'CRA001000', 'CRA053000' ];
		$bisac_codes = array_merge( $validated_bisac_codes, $invalidated_bisac_codes );
		$book_information_array['pb_bisac_subject'] = join(', ', $bisac_codes );
		update_site_meta( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY, $book_information_array );
		$meta = new \Pressbooks\Metadata();
		$meta_post = $meta->getMetaPost();
		delete_post_meta( $meta_post->ID, 'pb_bisac_subject' );
		foreach ($bisac_codes as $bisac_code) {
			add_metadata( 'post', $meta_post->ID, 'pb_bisac_subject', $bisac_code );
		}

		add_filter( 'get_invalidated_codes_alternatives_mapped', function( $bisac_codes ) {
			return [ 'TEC071000', 'COM051010', 'CRA001000', 'CRA053000' ];
		}, 10, 1 );
		$this->assertTrue( \Pressbooks\Book::notifyBisacCodesRemoved() );

		$_SESSION = [];
		ob_start();
		\Pressbooks\Admin\Laf\admin_notices();
		$buffer = ob_get_clean();
		$notice_msg = "This book was using a <a href='https://bisg.org/page/InactivatedCodes' target='_blank'> retired BISAC subject term </a>, which has been replaced in your book with a recommended BISAC replacement. You may wish to check the BISAC subject terms manually to confirm that you are satisfied with these replacements.";
		$this->assertEquals(
			'<div class="error" role="alert"><p>' . $notice_msg . '</p></div>',
			$buffer
		);

		$metadata = get_post_meta( $meta_post->ID );
		$this->assertArrayHasKey( 'pb_bisac_subject', $metadata );
		$this->assertContains( $validated_bisac_codes[0], $metadata['pb_bisac_subject'] );
		$this->assertNotContains( $invalidated_bisac_codes[0], $metadata['pb_bisac_subject'] );

		$book_information_array_updated = $book_data_collector->get( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$this->assertArrayHasKey( 'pb_bisac_subject', $book_information_array_updated );
		$blog_bisac_codes_updated = explode(', ', $book_information_array_updated['pb_bisac_subject'] );
		$this->assertContains( $validated_bisac_codes[0], $blog_bisac_codes_updated );
		$this->assertContains( 'TEC071000', $blog_bisac_codes_updated );
		$this->assertNotContains( $invalidated_bisac_codes[0], $blog_bisac_codes_updated );
	}

	/**
	 * @group book
	 */
	public function test_invalidatedBisacCodesNotFound() {
		$this->_book();
		global $blog_id;
		$book_data_collector = BookDataCollector::init();
		$book_information_array = $book_data_collector->get( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$bisac_codes = [ 'CRA001000', 'CRA053000' ];
		$book_information_array['pb_bisac_subject'] = join(', ', $bisac_codes );
		update_site_meta( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY, $book_information_array );
		$meta = new \Pressbooks\Metadata();
		$meta_post = $meta->getMetaPost();
		delete_post_meta( $meta_post->ID, 'pb_bisac_subject' );
		foreach ($bisac_codes as $bisac_code) {
			add_metadata( 'post', $meta_post->ID, 'pb_bisac_subject', $bisac_code );
		}

		add_filter( 'get_invalidated_codes_alternatives_mapped', function( $bisac_codes ) {
			return [ 'CRA001000', 'CRA053000' ];
		}, 10, 1 );
		$this->assertFalse( \Pressbooks\Book::notifyBisacCodesRemoved() );

		$metadata = get_post_meta( $meta_post->ID );
		$this->assertArrayHasKey( 'pb_bisac_subject', $metadata );
		$this->assertEquals( $bisac_codes, $metadata['pb_bisac_subject'] );

		$book_information_array_updated = $book_data_collector->get( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$this->assertArrayHasKey( 'pb_bisac_subject', $book_information_array_updated );
		$blog_bisac_codes_updated = explode(', ', $book_information_array_updated['pb_bisac_subject'] );
		$this->assertEquals( $bisac_codes, $blog_bisac_codes_updated );
	}
}
