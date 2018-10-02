<?php

class UtilityTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_getset() {

		$array = [ 'hello' => 'world' ];
		$this->assertEquals( \Pressbooks\Utility\getset( $array, 'hello' ), 'world' );
		$this->assertEquals( \Pressbooks\Utility\getset( $array, 'nothing' ), null );
		$this->assertEquals( \Pressbooks\Utility\getset( $array, 'nothing', 'something' ), 'something' );

		global $fake_out;
		$fake_out['hello'] = 'world';
		$this->assertEquals( \Pressbooks\Utility\getset( 'fake_out', 'hello' ), 'world' );
		$this->assertEquals( \Pressbooks\Utility\getset( 'fake_out', 'nothing' ), null );
		$this->assertEquals( \Pressbooks\Utility\getset( 'fake_out', 'nothing', 'something' ), 'something' );

		$_POST['hello'] = 'world';
		$this->assertEquals( \Pressbooks\Utility\getset( '_POST', 'hello' ), 'world' );
		$this->assertEquals( \Pressbooks\Utility\getset( '_POST', 'nothing' ), null );
		$this->assertEquals( \Pressbooks\Utility\getset( '_POST', 'nothing', 'something' ), 'something' );
	}


	public function test_scandir_by_date() {

		$files = \Pressbooks\Utility\scandir_by_date( __DIR__ );

		$this->assertTrue( is_array( $files ) );
		$this->assertContains( basename( __FILE__ ), $files );
		$this->assertNotContains( '.htaccess', $files );
		$this->assertNotContains( 'data', $files );
	}


	public function test_group_exports() {

		$files = \Pressbooks\Utility\group_exports();
		$this->assertTrue( is_array( $files ) );

		$files = \Pressbooks\Utility\group_exports( __DIR__ );
		$this->assertNotContains( '.htaccess', $files );
		$this->assertNotContains( 'data', $files );
	}


	//  public function test_truncate_exports() {
	//      // TODO: Testing this as-is would delete files. Need to refactor to allow mocking the file system.
	//      $this->markTestIncomplete();
	//  }

	public function test_get_media_prefix() {
		switch_to_blog( $this->factory()->blog->create() );
		$prefix = \Pressbooks\Utility\get_media_prefix();
		$this->assertTrue(
			false !== strpos( $prefix, '/blogs.dir/' ) || false !== strpos( $prefix, '/uploads/sites/' )
		);
	}

	public function test_get_media_path() {

		$guid = 'http://pressbooks.dev/test/wp-content/uploads/sites/3/2015/11/foobar.jpg';

		$path = \Pressbooks\Utility\get_media_path( $guid );

		$this->assertStringStartsWith( WP_CONTENT_DIR, $path );
		$this->assertStringEndsWith( 'foobar.jpg', $path );
		$this->assertTrue(
			false !== strpos( $path, '/blogs.dir/' ) || false !== strpos( $path, '/uploads/sites/' )
		);
	}

	public function test_latest_exports() {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		foreach ( [
			'\Pressbooks\Modules\Export\HTMLBook\HTMLBook',
			'\Pressbooks\Modules\Export\WordPress\Wxr',
		] as $module ) {
			/** @var \Pressbooks\Modules\Export\Export $exporter */
			$exporter = new $module( [] );
			$exporter->convert();
		}
		$latest = \Pressbooks\Utility\latest_exports();
		$this->assertArrayHasKey( 'htmlbook', $latest );
		$this->assertArrayHasKey( 'wxr', $latest );
	}

	public function test_add_sitemap_to_robots_txt_0() {

		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/^\s*$/' ); // string is empty or has only whitespace
		\Pressbooks\Utility\add_sitemap_to_robots_txt();
	}

	public function test_add_sitemap_to_robots_txt_1() {

		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/Sitemap:(.+)feed=sitemap.xml/' );
		\Pressbooks\Utility\add_sitemap_to_robots_txt();
	}

	public function test_do_sitemap_0() {

		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/404 Not Found/i' );
		\Pressbooks\Utility\do_sitemap();
	}

	public function test_do_sitemap_1() {

		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/^<\?xml /' );
		\Pressbooks\Utility\do_sitemap();
	}

	public function test_create_tmp_file() {

		$file = \Pressbooks\Utility\create_tmp_file();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );

		$file = \Pressbooks\Utility\create_tmp_file( 'my-very-own-resource-key' );
		$this->assertNotEmpty( $GLOBALS['my-very-own-resource-key'] );
		fclose( $GLOBALS['my-very-own-resource-key'] );
	}

	public function test_check_prince_install() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\check_prince_install() );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}

	public function test_check_epubcheck_install() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\check_epubcheck_install() );
		$this->assertTrue( defined( 'PB_EPUBCHECK_COMMAND' ) );
	}

	public function test_check_kindlegen_install() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\check_kindlegen_install() );
		$this->assertTrue( defined( 'PB_KINDLEGEN_COMMAND' ) );
	}

	public function test_check_xmllint_install() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\check_xmllint_install() );
		$this->assertTrue( defined( 'PB_XMLLINT_COMMAND' ) );
	}

	public function test_check_saxonhe_install() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\check_saxonhe_install() );
		$this->assertTrue( defined( 'PB_SAXON_COMMAND' ) );
	}

	public function test_show_experimental_features() {

		$this->assertInternalType( 'bool', \Pressbooks\Utility\show_experimental_features() );
		$this->assertInternalType( 'bool', \Pressbooks\Utility\show_experimental_features( 'http://pressbooks.com' ) );

	}

	public function test_include_plugins() {

		\Pressbooks\Utility\include_plugins();
		$this->assertTrue( class_exists( 'custom_metadata_manager' ) );
	}

	public function test_filter_plugins() {

		$symbionts = [ 'a-plugin-that-does-not-exist/foobar.php' => 1 ];

		$filtered = \Pressbooks\Utility\filter_plugins( $symbionts );

		$this->assertTrue( is_array( $filtered ) );
		$this->assertArrayHasKey( 'a-plugin-that-does-not-exist/foobar.php', $filtered );
	}

	public function test_install_plugins_tabs() {
		$tabs = \Pressbooks\Utility\install_plugins_tabs( [] );
		$this->assertArrayNotHasKey( 'featured', $tabs );
	}

	public function test_file_upload_max_size() {

		$maxSize = \Pressbooks\Utility\file_upload_max_size();

		$this->assertTrue(
			ini_get( 'post_max_size' ) == $maxSize || ini_get( 'upload_max_filesize' ) == $maxSize
		);

	}

	public function test_parse_size() {

		$this->assertTrue( is_float( \Pressbooks\Utility\parse_size( '1' ) ) );

		$this->assertEquals( 65536, \Pressbooks\Utility\parse_size( '64K' ) );
		$this->assertEquals( 2097152, \Pressbooks\Utility\parse_size( '2M' ) );
		$this->assertEquals( 8388608, \Pressbooks\Utility\parse_size( '8M' ) );
	}

	public function test_format_bytes() {

		$this->assertEquals( '200 B', \Pressbooks\Utility\format_bytes( 200 ) );
		$this->assertEquals( '200 B', \Pressbooks\Utility\format_bytes( 200, 4 ) );

		$this->assertEquals( '1.95 KB', \Pressbooks\Utility\format_bytes( 2000 ) );
		$this->assertEquals( '1.9531 KB', \Pressbooks\Utility\format_bytes( 2000, 4 ) );

		$this->assertEquals( '1.91 MB', \Pressbooks\Utility\format_bytes( 2000000 ) );
		$this->assertEquals( '1.9073 MB', \Pressbooks\Utility\format_bytes( 2000000, 4 ) );

		$this->assertEquals( '1.86 GB', \Pressbooks\Utility\format_bytes( 2000000000 ) );
		$this->assertEquals( '1.8626 GB', \Pressbooks\Utility\format_bytes( 2000000000, 4 ) );

		$this->assertEquals( '1.82 TB', \Pressbooks\Utility\format_bytes( 2000000000000 ) );
		$this->assertEquals( '1.819 TB', \Pressbooks\Utility\format_bytes( 2000000000000, 4 ) );
	}


	//  public function test_email_error_log() {
	//      // TODO: Testing this as-is would send emails, write to error_log... Need to refactor
	//      $this->markTestIncomplete();
	//  }


	public function test_template() {

		$template = \Pressbooks\Utility\template(
			__DIR__ . '/data/template.php',
			[ 'title' => 'Foobar', 'body' => 'Hello World!' ]
		);

		$this->assertContains( '<title>Foobar</title>', $template );
		$this->assertNotContains( '<title></title>', $template );

		$this->assertContains( '<body>Hello World!</body>', $template );
		$this->assertNotContains( '<body></body>', $template );

		try {
			\Pressbooks\Utility\template( '/tmp/file/does/not/exist' );
		} catch ( \Exception $e ) {
			$this->assertTrue( true ); // Expected exception was thrown
			return;
		}
		$this->fail();
	}

	public function test_mail_from() {
		$this->assertEquals( 'pressbooks@example.org', \Pressbooks\Utility\mail_from( '' ) );
		define( 'WP_MAIL_FROM', 'hi@pressbooks.org' );
		$this->assertEquals( 'hi@pressbooks.org', \Pressbooks\Utility\mail_from( '' ) );
	}

	public function test_mail_from_name() {
		$this->assertEquals( 'Pressbooks', \Pressbooks\Utility\mail_from_name( '' ) );
		define( 'WP_MAIL_FROM_NAME', 'Ned' );
		$this->assertEquals( 'Ned', \Pressbooks\Utility\mail_from_name( '' ) );
	}

	public function test_rmrdir() {
		$file = \Pressbooks\Utility\create_tmp_file();
		$dirname = dirname( $file );
		if ( ! is_dir( "$dirname/one/two/three/four" ) ) {
			mkdir( "$dirname/one/two/three/four", 0777, true );
		}
		if ( ! is_file( "$dirname/one/two/three/four/delete-me.txt" ) ) {
			file_put_contents( "$dirname/one/two/three/four/delete-me.txt", 'TODO' );
		}
		if ( ! is_dir( "$dirname/one/a/b/c" ) ) {
			mkdir( "$dirname/one/a/b/c", 0777, true );
		}
		if ( ! is_file( "$dirname/one/a/b/c/delete-me.txt" ) ) {
			file_put_contents( "$dirname/one/a/b/c/delete-me.txt", 'TODO' );
		}
		\Pressbooks\Utility\rmrdir( "$dirname/one" );
		$this->assertFalse( is_dir( "$dirname/one" ) );
	}

	public function test_rcopy() {
		$uploads = wp_upload_dir();

		$src = trailingslashit( $uploads['path'] ) . 'src';
		if ( file_exists( $src ) ) {
			\Pressbooks\Utility\rmrdir( $src );
		}

		$dest = trailingslashit( $uploads['path'] ) . 'dest';
		if ( file_exists( $dest ) ) {
			\Pressbooks\Utility\rmrdir( $dest );
		}

		@mkdir( $src );
		file_put_contents( $src . '/test.txt', 'test' );

		$return = \Pressbooks\Utility\rcopy( $src, $dest );
		$contents = file_get_contents( $dest . '/test.txt' );
		$this->assertTrue( $return );
		$this->assertEquals( 'test', $contents );

		$return = \Pressbooks\Utility\rcopy( trailingslashit( $uploads['path'] ) . 'missing', $dest );
		$this->assertEquals( $return, false );
	}

	public function test_rcopy_excludes() {
		$uploads = wp_upload_dir();

		$src = trailingslashit( $uploads['path'] ) . 'src';
		if ( file_exists( $src ) ) {
			\Pressbooks\Utility\rmrdir( $src );
		}

		$dest = trailingslashit( $uploads['path'] ) . 'dest';
		if ( file_exists( $dest ) ) {
			\Pressbooks\Utility\rmrdir( $dest );
		}

		@mkdir( $src );
		@mkdir( "$src/subdir" );
		file_put_contents( $src . '/test.txt', 'test' );
		file_put_contents( $src . '/subdir/test.txt', 'test' );
		file_put_contents( $src . '/readme.txt', 'test' );

		$return = \Pressbooks\Utility\rcopy( $src, $dest, [ 'readme.*' ] );
		$this->assertTrue( $return );
		$this->assertFalse( file_exists( $dest . '/readme.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/test.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/subdir/test.txt' ) );

		\Pressbooks\Utility\rmrdir( $dest );
		$return = \Pressbooks\Utility\rcopy( $src, $dest, [ 'test.txt' ] );
		$this->assertTrue( $return );
		$this->assertFalse( file_exists( $dest . '/test.txt' ) );
		$this->assertFalse( file_exists( $dest . '/subdir/test.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/readme.txt' ) );

		\Pressbooks\Utility\rmrdir( $dest );
		$return = \Pressbooks\Utility\rcopy( $src, $dest, [ 'subdir/' ] );
		$this->assertTrue( $return );
		$this->assertFalse( file_exists( $dest . '/subdir' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/test.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/readme.txt' ) );
	}

	public function test_rcopy_includes() {
		$uploads = wp_upload_dir();

		$src = trailingslashit( $uploads['path'] ) . 'src';
		if ( file_exists( $src ) ) {
			\Pressbooks\Utility\rmrdir( $src );
		}

		$dest = trailingslashit( $uploads['path'] ) . 'dest';
		if ( file_exists( $dest ) ) {
			\Pressbooks\Utility\rmrdir( $dest );
		}

		@mkdir( $src );
		@mkdir( "$src/subdir" );
		file_put_contents( $src . '/test.txt', 'test' );
		file_put_contents( $src . '/subdir/test.txt', 'test' );
		file_put_contents( $src . '/readme.txt', 'test' );

		$return = \Pressbooks\Utility\rcopy( $src, $dest, [ 'readme.*' ], [ 'readme.txt' ] ); // Because exclude takes precedence, together these cancel everything
		$this->assertTrue( $return );
		$this->assertFalse( file_exists( $dest . '/readme.txt' ) );
		$this->assertFalse( file_exists( $dest . '/test.txt' ) );
		$this->assertFalse( file_exists( $dest . '/subdir/test.txt' ) );

		\Pressbooks\Utility\rmrdir( $dest );
		$return = \Pressbooks\Utility\rcopy( $src, $dest, [ 'test.*' ], [ 'readme.txt' ] );
		$this->assertTrue( $return );
		$this->assertTrue( file_exists( $dest . '/readme.txt' ) );
		$this->assertFalse( file_exists( $dest . '/test.txt' ) );
		$this->assertFalse( file_exists( $dest . '/subdir/test.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/readme.txt' ) );

		\Pressbooks\Utility\rmrdir( $dest );
		$return = \Pressbooks\Utility\rcopy( $src, $dest, [], [ 'readme.*' ] );
		$this->assertTrue( $return );
		$this->assertTrue( file_exists( $dest . '/readme.txt' ) );
		$this->assertFalse( file_exists( $dest . '/test.txt' ) );
		$this->assertFalse( file_exists( $dest . '/subdir/test.txt' ) );
		$this->assertEquals( 'test', file_get_contents( $dest . '/readme.txt' ) );
	}

	public function test_str_starts_with() {
		$this->assertTrue( \Pressbooks\Utility\str_starts_with( 's0.wp.com', 's0.wp' ) );
		$this->assertFalse( \Pressbooks\Utility\str_starts_with( 's0.wp.com', 'wp.com' ) );
	}

	public function test_str_ends_with() {
		$this->assertFalse( \Pressbooks\Utility\str_ends_with( 's0.wp.com', 's0.wp' ) );
		$this->assertTrue( \Pressbooks\Utility\str_ends_with( 's0.wp.com', 'wp.com' ) );
	}

	public function test_str_remove_prefix() {

		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'foo'  );
		$this->assertEquals( ' foo foo bar', $result );

		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'foo ' );
		$this->assertEquals( 'foo foo bar', $result );

		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'FOO ' );
		$this->assertEquals( 'foo foo foo bar', $result );
	}

	public function test_str_lreplace() {

		$result = \Pressbooks\Utility\str_lreplace( 'foo', 'bar', 'foo foo foo bar' );
		$this->assertEquals( 'foo foo bar bar', $result );

		$result = \Pressbooks\Utility\str_lreplace( 'FOO', 'BAR', 'foo foo foo bar' );
		$this->assertEquals( 'foo foo foo bar', $result );
	}

	public function test_commaDelimitedStringSearch() {
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'foo', 'foo' ) );
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'foo,', 'foo' ) );
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'foo ', 'foo' ) );
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo', 'foo' ) );
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo,', 'foo' ) );
		$this->assertTrue( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo ', 'foo' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'foo', 'bar' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'foo,', 'bar' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'foo ', 'bar' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo', 'bar' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo,', 'bar' ) );
		$this->assertFalse( \Pressbooks\Utility\comma_delimited_string_search( 'one,two,three,foo ', 'bar' ) );
	}

	public function test_word_count() {

		$content = 'This is four words.';
		$count = \Pressbooks\Utility\word_count( $content );
		$this->assertEquals( 4, $count );

		$content = "<p>This</p> <strong>is</strong> \r\n\r\n\r\n four! <script>five?</script> words.";
		$count = \Pressbooks\Utility\word_count( $content );
		$this->assertEquals( 4, $count );

		$content = 'One Two Three 4 Five'; // WordPress' built-in JS wordcount thinks this is 4 words, we think this is 5
		$count = \Pressbooks\Utility\word_count( $content );
		$this->assertEquals( 5, $count );

		$content = '电脑坏了。';
		$count = \Pressbooks\Utility\word_count( $content );
		$this->assertEquals( 4, $count );
	}


	public function test_absolute_path() {

		$path = '/simple/path';
		$this->assertEquals( '/simple/path', \Pressbooks\Utility\absolute_path( $path ) );

		$path = 'weird-path';
		$this->assertEquals( '/weird-path', \Pressbooks\Utility\absolute_path( $path ) );

		$path = '/path/to/test/.././..//..///..///../one/two/../three/filename';
		$this->assertEquals( '/one/three/filename', \Pressbooks\Utility\absolute_path( $path ) );

		$path = '\path\to\test\..\.\..\\..\\\..\\\..\one\two\..\three\filename';
		$this->assertEquals( '/one/three/filename', \Pressbooks\Utility\absolute_path( $path ) );

		$path = 'http://www.pressbooks.dev/simple/path';
		$this->assertEquals( 'http://www.pressbooks.dev/simple/path', \Pressbooks\Utility\absolute_path( $path ) );

		$path = 'http://www.pressbooks.dev/path/to/test/.././..//..///..///../one/two/../three/filename';
		$this->assertEquals( 'http://www.pressbooks.dev/one/three/filename', \Pressbooks\Utility\absolute_path( $path ) );

		$path = 'https://localhost/path/to/test/.././..//..///..///../one/two/../three/filename';
		$this->assertEquals( 'https://localhost/one/three/filename', \Pressbooks\Utility\absolute_path( $path ) );

		$path = 'ftp://127.0.0.1//path/to/test/.././..//..///..///../one/two/../three/filename';
		$this->assertEquals( 'ftp://127.0.0.1/one/three/filename', \Pressbooks\Utility\absolute_path( $path ) );
	}

	public function test_urls_have_same_host() {
		$this->assertTrue( \Pressbooks\Utility\urls_have_same_host( 'https://pressbooks.com', 'https://pressbooks.com' ) );
		$this->assertTrue( \Pressbooks\Utility\urls_have_same_host( 'https://book.pressbooks.com', 'https://pressbooks.com' ) );
		$this->assertTrue( \Pressbooks\Utility\urls_have_same_host( 'gopher://book.pressbooks.com', 'https://pressbooks.com/foo/bar?hello=world' ) );
		$this->assertTrue( \Pressbooks\Utility\urls_have_same_host( 'https://book.book.book.pressbooks.com', 'https://pressbooks.com' ) );
		$this->assertTrue( \Pressbooks\Utility\urls_have_same_host( 'https://co.uk', 'https://co.uk' ) );

		$this->assertFalse( \Pressbooks\Utility\urls_have_same_host( 'https://book.pressbooks.com', 'https://book.pressbooks.dev' ) );
		$this->assertFalse( \Pressbooks\Utility\urls_have_same_host( 'x', 'x' ) );
		$this->assertFalse( \Pressbooks\Utility\urls_have_same_host( 'pressbooks.com', 'pressbooks.com' ) ); // Not a fully qualified URL
	}

	public function get_generated_content_path() {
		$path = \Pressbooks\Utility\get_generated_content_path();
		$this->assertStringStartsWith( '/', $path );
		$this->assertContains( '/pressbooks/', $path );
	}

	public function get_generated_content_url() {
		$url = \Pressbooks\Utility\get_generated_content_url();
		$this->assertStringStartsWith( 'http', $url );
		$this->assertContains( '/pressbooks/', $url );
	}

	public function test_get_cache_path() {
		$this->assertNotEmpty( \Pressbooks\Utility\get_cache_path() );
	}

	public function test_oxford_comma() {
		$this->assertEquals( '', \Pressbooks\Utility\oxford_comma( [] ) );
		$vars[] = 'One Person';
		$this->assertEquals( 'One Person', \Pressbooks\Utility\oxford_comma( $vars ) );
		$vars[] = 'Two People';
		$this->assertEquals( 'One Person and Two People', \Pressbooks\Utility\oxford_comma( $vars ) );
		$vars[] = 'Three People';
		$this->assertEquals( 'One Person, Two People, and Three People', \Pressbooks\Utility\oxford_comma( $vars ) );
		$vars[] = 'Four People';
		$this->assertEquals( 'One Person, Two People, Three People, and Four People', \Pressbooks\Utility\oxford_comma( $vars ) );
	}

	public function test_oxford_comma_explode() {
		$this->assertEmpty( \Pressbooks\Utility\oxford_comma_explode( '' ) );
		$this->assertEquals( [ 'One Person', 'Two People' ], \Pressbooks\Utility\oxford_comma_explode( 'One Person and Two People' ) );
		$this->assertEquals( [ 'One Person', 'Two People', 'Three People', 'Four People' ], \Pressbooks\Utility\oxford_comma_explode( 'One Person, Two People, Three People, and Four People' ) );
		$this->assertEquals( [ 'andy', 'andrew', 'andrea', 'android' ], \Pressbooks\Utility\oxford_comma_explode( 'andy,andrew, andrea,  android' ) );
	}

	public function test_is_assoc() {
		$this->assertFalse( \Pressbooks\Utility\is_assoc( 'Doing it wrong' ) );
		$this->assertFalse( \Pressbooks\Utility\is_assoc( [ 'a', 'b', 'c' ] ) );
		$this->assertFalse( \Pressbooks\Utility\is_assoc( [ "0" => 'a', "1" => 'b', "2" => 'c' ] ) );
		$this->assertTrue( \Pressbooks\Utility\is_assoc( [ "1" => 'a', "0" => 'b', "2" => 'c' ] ) );
		$this->assertTrue( \Pressbooks\Utility\is_assoc( [ "a" => 'a', "b" => 'b', "c" => 'c' ] ) );
	}

	public function test_empty_space() {
		$this->assertFalse( \Pressbooks\Utility\empty_space( 'Hi' ) );
		$this->assertFalse( \Pressbooks\Utility\empty_space( true ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( '' ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( '  ' ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( "\n\r\t" ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( false ) );
	}

	public function test_main_contact_email() {
		$email = \Pressbooks\Utility\main_contact_email();
		$this->assertContains( '@', $email );
	}

	public function test_str_lowercase_dash() {
		$this->assertEquals( 'neural-networks', \Pressbooks\Utility\str_lowercase_dash( 'Neural Networks' ) );
		$this->assertEmpty( \Pressbooks\Utility\str_lowercase_dash( '') );
		$this->assertEquals( 'support--vector--machines', \Pressbooks\Utility\str_lowercase_dash( ' Support  Vector  MachINEs    ') );
	}

	public function test_shortcode_att_replace() {

		$c = '<h1>Test</h1><p>[pb_glossary hello=world id=111 foo=bar]Skatboards[/pb_glossary], not [pb_glossary hello=world id=222 foo=bar]death[/pb_glossary].</p><p>[some id=222]other shortcode[/some]</p>';
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 222, 999 );
		$this->assertEquals( "<h1>Test</h1><p>[pb_glossary hello=world id=111 foo=bar]Skatboards[/pb_glossary], not [pb_glossary hello=world id=999 foo=bar]death[/pb_glossary].</p><p>[some id=222]other shortcode[/some]</p>", $x );

		$c = '<h1>Test</h1><p>[pb_glossary hello="world" id="111" foo="bar"]Skatboards[/pb_glossary], not [pb_glossary hello="world" id="222" foo="bar"]death[/pb_glossary].</p><p>[some id="222"]other shortcode[/some]</p>';
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 222, 999 );
		$this->assertEquals( '<h1>Test</h1><p>[pb_glossary hello="world" id="111" foo="bar"]Skatboards[/pb_glossary], not [pb_glossary hello="world" id="999" foo="bar"]death[/pb_glossary].</p><p>[some id="222"]other shortcode[/some]</p>', $x );

		$c = "<h1>Test</h1><p>[pb_glossary hello='world' id='111' foo='bar']Skatboards[/pb_glossary], not [pb_glossary hello='world' id='222' foo='bar']death[/pb_glossary].</p><p>[some id='222']other shortcode[/some]</p>";
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 222, 999 );
		$this->assertEquals( "<h1>Test</h1><p>[pb_glossary hello='world' id='111' foo='bar']Skatboards[/pb_glossary], not [pb_glossary hello='world' id='999' foo='bar']death[/pb_glossary].</p><p>[some id='222']other shortcode[/some]</p>", $x );

		// Don't be greedy
		$c = '[pb_glossary id=222 foo=111]Zig[/pb_glossary]';
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 111, 999 );
		$this->assertEquals( '[pb_glossary id=222 foo=111]Zig[/pb_glossary]', $x );

		// Complete junk? No problem!
		$c = '[pb_glossary hello=world id=&quot;111&quot; foo="111"]Skatboards[/pb_glossary][pb_glossary broken=\'pebkac\']Yes[/pb_glossary]';
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 111, 999 );
		$this->assertEquals( '[pb_glossary hello=world id=&quot;999&quot; foo="111"]Skatboards[/pb_glossary][pb_glossary broken=\'pebkac\']Yes[/pb_glossary]', $x );
	}

}
