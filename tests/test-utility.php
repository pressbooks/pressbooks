<?php

use Pressbooks\Modules\Export\Export;
use function Pressbooks\Utility\add_sitemap_to_robots_txt;
use function Pressbooks\Utility\do_shortcode_by_tags;
use function Pressbooks\Utility\latest_exports;
use function Pressbooks\Utility\length_to_inches;
use function Pressbooks\Utility\objects_to_csv;

class UtilityTest extends \WP_UnitTestCase {
	use utilsTrait;

	public static function tear_down_after_class() {
		$_SERVER['SERVER_PORT'] = '';
	}

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
	public function test_scandir_by_date() {
		$files = \Pressbooks\Utility\scandir_by_date( __DIR__ );

		$this->assertTrue( is_array( $files ) );
		$this->assertContains( basename( __FILE__ ), $files );
		$this->assertNotContains( '.htaccess', $files );
		$this->assertNotContains( 'data', $files );

		$files = \Pressbooks\Utility\scandir_by_date( '/fake/junk' );

		$this->assertTrue( is_array( $files ) );
		$this->assertEmpty( $files );
	}

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
	public function test_get_media_prefix() {
		switch_to_blog( $this->factory()->blog->create() );
		$prefix = \Pressbooks\Utility\get_media_prefix();
		$this->assertTrue(
			false !== strpos( $prefix, '/blogs.dir/' ) || false !== strpos( $prefix, '/uploads/sites/' )
		);
	}

	/**
	 * @group utility
	 */
	public function test_get_media_path() {
		$guid = 'http://pressbooks.dev/test/wp-content/uploads/sites/3/2015/11/foobar.jpg';

		$path = \Pressbooks\Utility\get_media_path( $guid );

		$this->assertStringStartsWith( WP_CONTENT_DIR, $path );
		$this->assertStringEndsWith( 'foobar.jpg', $path );
		$this->assertTrue(
			false !== strpos( $path, '/blogs.dir/' ) || false !== strpos( $path, '/uploads/sites/' )
		);
	}

	/**
	 * @group utility
	 */
	public function test_latest_exports() {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		foreach ( [
			'\Pressbooks\Modules\Export\WordPress\Wxr',
		] as $module ) {
			/** @var Export $exporter */
			$exporter = new $module( [] );
			$generator = $exporter->convert();
			$this->runGenerator( $generator );
		}
		$latest = latest_exports();
		$this->assertArrayHasKey( 'wxr', $latest );
	}

	/**
	 * @group utility
	 */
	public function test_add_sitemap_to_robots_txt_0() {
		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/^\s*$/' ); // string is empty or has only whitespace
		add_sitemap_to_robots_txt();
	}

	/**
	 * @group utility
	 */
	public function test_add_sitemap_to_robots_txt_1() {
		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/Sitemap:(.+)feed=sitemap.xml/' );
		add_sitemap_to_robots_txt();
	}

	/**
	 * @group utility
	 */
	public function test_do_sitemap_0() {
		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/404 Not Found/i' );
		\Pressbooks\Utility\do_sitemap();
	}

	/**
	 * @group utility
	 */
	public function test_do_sitemap_1() {
		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/^<\?xml /' );
		\Pressbooks\Utility\do_sitemap();
	}

	/**
	 * @group utility
	 */
	public function test_create_tmp_file() {
		$file = \Pressbooks\Utility\create_tmp_file();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );

		$file = \Pressbooks\Utility\create_tmp_file( 'my-very-own-resource-key' );
		$this->assertNotEmpty( $GLOBALS['my-very-own-resource-key'] );
		fclose( $GLOBALS['my-very-own-resource-key'] );
	}

	/**
	 * @group utility
	 */
	public function test_check_prince_install() {
		$this->assertIsBool( \Pressbooks\Utility\check_prince_install() );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}

	/**
	 * @group utility
	 */
	public function test_check_epubcheck_install() {
		$this->assertIsBool( \Pressbooks\Utility\check_epubcheck_install() );
		$this->assertTrue( defined( 'PB_EPUBCHECK_COMMAND' ) );
	}

	/**
	 * @group utility
	 */
	public function test_check_xmllint_install() {
		$this->assertIsBool( \Pressbooks\Utility\check_xmllint_install() );
		$this->assertTrue( defined( 'PB_XMLLINT_COMMAND' ) );
	}

	/**
	 * @group utility
	 */
	public function test_check_saxonhe_install() {
		$this->assertIsBool( \Pressbooks\Utility\check_saxonhe_install() );
		$this->assertTrue( defined( 'PB_SAXON_COMMAND' ) );
	}

	/**
	 * @group utility
	 */
	public function test_show_experimental_features() {
		$this->assertIsBool( \Pressbooks\Utility\show_experimental_features() );
		$this->assertIsBool( \Pressbooks\Utility\show_experimental_features( 'http://pressbooks.com' ) );

	}

	/**
	 * @group utility
	 */
	public function test_filter_plugins() {
		$symbionts = [ 'a-plugin-that-does-not-exist/foobar.php' => 1 ];

		$filtered = \Pressbooks\Utility\filter_plugins( $symbionts );

		$this->assertTrue( is_array( $filtered ) );
		$this->assertArrayHasKey( 'a-plugin-that-does-not-exist/foobar.php', $filtered );
	}

	/**
	 * @group utility
	 */
	public function test_install_plugins_tabs() {
		$tabs = \Pressbooks\Utility\install_plugins_tabs( [] );
		$this->assertArrayNotHasKey( 'featured', $tabs );
	}

	/**
	 * @group utility
	 */
	public function test_file_upload_max_size() {
		$maxSize = \Pressbooks\Utility\file_upload_max_size();

		$this->assertTrue(
			ini_get( 'post_max_size' ) == $maxSize || ini_get( 'upload_max_filesize' ) == $maxSize
		);
	}

	/**
	 * @group utility
	 */
	public function test_parse_size() {
		$this->assertTrue( is_float( \Pressbooks\Utility\parse_size( '1' ) ) );

		$this->assertEquals( 65536, \Pressbooks\Utility\parse_size( '64K' ) );
		$this->assertEquals( 2097152, \Pressbooks\Utility\parse_size( '2M' ) );
		$this->assertEquals( 8388608, \Pressbooks\Utility\parse_size( '8M' ) );
	}

	/**
	 * @group utility
	 */
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
	//      // TODO: Testing this as-is would send emails, write to trigger_error... Need to refactor
	//      $this->markTestIncomplete();
	//  }

	/**
	 * @group utility
	 */
	public function test_template() {
		$template = \Pressbooks\Utility\template(
			__DIR__ . '/data/template.php',
			[
				'title' => 'Foobar',
				'body' => 'Hello World!',
			]
		);

		$this->assertStringContainsString( '<title>Foobar</title>', $template );
		$this->assertStringNotContainsString( '<title></title>', $template );

		$this->assertStringContainsString( '<body>Hello World!</body>', $template );
		$this->assertStringNotContainsString( '<body></body>', $template );

		try {
			\Pressbooks\Utility\template( '/tmp/file/does/not/exist' );
		} catch ( \Exception $e ) {
			$this->assertTrue( true ); // Expected exception was thrown
			return;
		}
		$this->fail();
	}

	/**
	 * @group utility
	 */
	public function test_mail_from() {
		$this->assertEquals( 'pressbooks@example.org', \Pressbooks\Utility\mail_from( '' ) );
		define( 'WP_MAIL_FROM', 'hi@pressbooks.org' );
		$this->assertEquals( 'hi@pressbooks.org', \Pressbooks\Utility\mail_from( '' ) );
	}

	/**
	 * @group utility
	 */
	public function test_mail_from_name() {
		$this->assertEquals( 'Pressbooks', \Pressbooks\Utility\mail_from_name( '' ) );
		define( 'WP_MAIL_FROM_NAME', 'Ned' );
		$this->assertEquals( 'Ned', \Pressbooks\Utility\mail_from_name( '' ) );
	}

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
	public function test_str_starts_with() {
		$this->assertTrue( \Pressbooks\Utility\str_starts_with( 's0.wp.com', 's0.wp' ) );
		$this->assertFalse( \Pressbooks\Utility\str_starts_with( 's0.wp.com', 'wp.com' ) );
	}

	/**
	 * @group utility
	 */
	public function test_str_ends_with() {
		$this->assertFalse( \Pressbooks\Utility\str_ends_with( 's0.wp.com', 's0.wp' ) );
		$this->assertTrue( \Pressbooks\Utility\str_ends_with( 's0.wp.com', 'wp.com' ) );
	}

	/**
	 * @group utility
	 */
	public function test_str_remove_prefix() {
		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'foo' );
		$this->assertEquals( ' foo foo bar', $result );

		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'foo ' );
		$this->assertEquals( 'foo foo bar', $result );

		$result = \Pressbooks\Utility\str_remove_prefix( 'foo foo foo bar', 'FOO ' );
		$this->assertEquals( 'foo foo foo bar', $result );
	}

	/**
	 * @group utility
	 */
	public function test_str_lreplace() {
		$result = \Pressbooks\Utility\str_lreplace( 'foo', 'bar', 'foo foo foo bar' );
		$this->assertEquals( 'foo foo bar bar', $result );

		$result = \Pressbooks\Utility\str_lreplace( 'FOO', 'BAR', 'foo foo foo bar' );
		$this->assertEquals( 'foo foo foo bar', $result );
	}

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
	public function get_generated_content_path() {
		$path = \Pressbooks\Utility\get_generated_content_path();
		$this->assertStringStartsWith( '/', $path );
		$this->assertStringContainsString( '/pressbooks/', $path );
	}

	/**
	 * @group utility
	 */
	public function get_generated_content_url() {
		$url = \Pressbooks\Utility\get_generated_content_url();
		$this->assertStringStartsWith( 'http', $url );
		$this->assertStringContainsString( '/pressbooks/', $url );
	}

	/**
	 * @group utility
	 */
	public function test_get_cache_path() {
		$this->assertNotEmpty( \Pressbooks\Utility\get_cache_path() );
	}

	/**
	 * @group utility
	 */
	public function test_implode_add_and() {
		$this->assertEmpty( \Pressbooks\Utility\explode_remove_and( ';', '' ) );
		$this->assertEquals( [ 'One Person', 'Two People' ], \Pressbooks\Utility\explode_remove_and( ';', 'One Person and Two People' ) );
		$this->assertEquals( [ 'One Person', 'Two People', 'Three People', 'Four People' ], \Pressbooks\Utility\explode_remove_and( ';', 'One Person; Two People; Three People; and Four People' ) );
		$this->assertEquals( [ 'andy, suff', 'andrew', 'andrea', 'android' ], \Pressbooks\Utility\explode_remove_and( ';', 'andy, suff; andrew; andrea; android' ) );
	}

	/**
	 * @group utility
	 */
	public function test_explode_remove_and() {
		$this->assertEquals( '', \Pressbooks\Utility\implode_add_and( ';', [] ) );
		$vars[] = 'One Person';
		$this->assertEquals( 'One Person', \Pressbooks\Utility\implode_add_and( ';', $vars ) );
		$vars[] = 'Two People';
		$this->assertEquals( 'One Person and Two People', \Pressbooks\Utility\implode_add_and( ';', $vars ) );
		$vars[] = 'Three People';
		$this->assertEquals( 'One Person; Two People; and Three People', \Pressbooks\Utility\implode_add_and( ';', $vars ) );
		$vars[] = 'Four People';
		$this->assertEquals( 'One Person; Two People; Three People; and Four People', \Pressbooks\Utility\implode_add_and( ';', $vars ) );
	}

	/**
	 * @group utility
	 */
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

	/**
	 * @group utility
	 */
	public function test_oxford_comma_explode() {
		$this->assertEmpty( \Pressbooks\Utility\oxford_comma_explode( '' ) );
		$this->assertEquals( [ 'One Person', 'Two People' ], \Pressbooks\Utility\oxford_comma_explode( 'One Person and Two People' ) );
		$this->assertEquals( [ 'One Person', 'Two People', 'Three People', 'Four People' ], \Pressbooks\Utility\oxford_comma_explode( 'One Person, Two People, Three People, and Four People' ) );
		$this->assertEquals( [ 'andy', 'andrew', 'andrea', 'android' ], \Pressbooks\Utility\oxford_comma_explode( 'andy, andrew, andrea, android' ) );
	}

	/**
	 * @group utility
	 */
	public function test_is_assoc() {
		$this->assertFalse( \Pressbooks\Utility\is_assoc( 'Doing it wrong' ) );
		$this->assertFalse( \Pressbooks\Utility\is_assoc( [ 'a', 'b', 'c' ] ) );
		$this->assertFalse(
			\Pressbooks\Utility\is_assoc(
				[
					'0' => 'a',
					'1' => 'b',
					'2' => 'c',
				]
			)
		);
		$this->assertTrue(
			\Pressbooks\Utility\is_assoc(
				[
					'1' => 'a',
					'0' => 'b',
					'2' => 'c',
				]
			)
		);
		$this->assertTrue(
			\Pressbooks\Utility\is_assoc(
				[
					'a' => 'a',
					'b' => 'b',
					'c' => 'c',
				]
			)
		);
	}

	/**
	 * @group utility
	 */
	public function test_empty_space() {
		$this->assertFalse( \Pressbooks\Utility\empty_space( 'Hi' ) );
		$this->assertFalse( \Pressbooks\Utility\empty_space( true ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( '' ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( '  ' ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( "\n\r\t" ) );
		$this->assertTrue( \Pressbooks\Utility\empty_space( false ) );
	}

	/**
	 * @group utility
	 */
	public function test_main_contact_email() {
		$email = \Pressbooks\Utility\main_contact_email();
		$this->assertStringContainsString( '@', $email );
	}

	/**
	 * @group utility
	 */
	public function test_str_lowercase_dash() {
		$this->assertEquals( 'neural-networks', \Pressbooks\Utility\str_lowercase_dash( 'Neural Networks' ) );
		$this->assertEmpty( \Pressbooks\Utility\str_lowercase_dash( '' ) );
		$this->assertEquals( 'support--vector--machines', \Pressbooks\Utility\str_lowercase_dash( ' Support  Vector  MachINEs    ' ) );
	}

	/**
	 * @group utility
	 */
	public function test_shortcode_att_replace() {
		$c = '<h1>Test</h1><p>[pb_glossary hello=world id=111 foo=bar]Skatboards[/pb_glossary], not [pb_glossary hello=world id=222 foo=bar]death[/pb_glossary].</p><p>[some id=222]other shortcode[/some]</p>';
		$x = \Pressbooks\Utility\shortcode_att_replace( $c, 'pb_glossary', 'id', 222, 999 );
		$this->assertEquals( '<h1>Test</h1><p>[pb_glossary hello=world id=111 foo=bar]Skatboards[/pb_glossary], not [pb_glossary hello=world id=999 foo=bar]death[/pb_glossary].</p><p>[some id=222]other shortcode[/some]</p>', $x );

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

	/**
	 * @group utility
	 */
	public function test_do_shortcode_by_tags() {
		add_filter( 'pb_mathjax_use', '__return_true' );
		$content = '[latex]e^{\i \pi} + 1 = 0[/latex][embed]https://image.png[/embed]';

		$expected = '<img src="http://localhost:3000/latex?latex=ZV57XGkgXHBpfSArIDEgPSAw&fg=000000&isBase64=1" alt="e^{&#92;i &#92;pi} + 1 = 0" title="e^{&#92;i &#92;pi} + 1 = 0" class="latex mathjax" />[embed]https://image.png[/embed]';
		$this->assertEquals( $expected, do_shortcode_by_tags( $content, [ 'latex' ] ) );

		$expected = '[latex]e^{\i \pi} + 1 = 0[/latex]';
		$this->assertEquals( $expected, do_shortcode_by_tags( $content, [ 'embed' ] ) );

		$expected = '<img src="http://localhost:3000/latex?latex=ZV57XGkgXHBpfSArIDEgPSAw&fg=000000&isBase64=1" alt="e^{&#92;i &#92;pi} + 1 = 0" title="e^{&#92;i &#92;pi} + 1 = 0" class="latex mathjax" />';
		$this->assertEquals( $expected, do_shortcode_by_tags( $content, [ 'latex', 'embed' ] ) );
	}

	public function test_https_swap() {
		$_SERVER['SERVER_PORT'] = '443';
		$url = 'http://pressbooks.test/book';
		$this->assertEquals( \Pressbooks\Utility\apply_https_if_available( $url ), 'https://pressbooks.test/book' );

		$_SERVER['SERVER_PORT'] = '';
		$url = 'http://network-no-ssl.pressbooks.test/book';
		$this->assertEquals( \Pressbooks\Utility\apply_https_if_available( $url ), 'http://network-no-ssl.pressbooks.test/book' );
	}

	/**
	 * @group utility
	 */
	public function test_contracts_and_traits() {
		$contributors = new \Pressbooks\Contributors();
		$glossary = new Pressbooks\Shortcodes\Glossary\Glossary();

		$this->assertTrue( is_a( $contributors, \Pressbooks\PostType\BackMatter::class ) );
		$this->assertTrue( is_a( $glossary, \Pressbooks\PostType\BackMatter::class ) );

		$class1 = new \ReflectionClass( \Pressbooks\Contributors::class );
		$class2 = new \ReflectionClass( Pressbooks\Shortcodes\Glossary\Glossary::class );

		$this->assertTrue( is_a( $class1->getMethod( 'display' ), '\ReflectionMethod' ) );
		$this->assertTrue( is_a( $class2->getMethod( 'display' ), '\ReflectionMethod' ) );
	}

	/**
	 * @group utility
	 */
	public function test_objects_to_csv() {

		$data = [
			(object) [
				'title' => 'foo',
				'author' => 'bar',
				'isbn' => 'baz',
			],
			(object) [
				'title' => 'foo2',
				'author' => 'bar2',
				'isbn' => 'baz2',
			],
		];


		$csv = objects_to_csv( $data );

		$this->assertStringContainsString( 'title,author,isbn', $csv );
		$this->assertStringContainsString( 'foo,bar,baz', $csv );
	}

	public function test_handle_book_indexing(): void
	{
		$this->_book();

		$this->assertEquals(
			[ 'noindex' => 0, 'nofollow' => 0, 'noai' => 0, 'noimageai' => 0 ],
			\Pressbooks\Utility\handle_book_indexing( [] )
		);

		update_option( 'pressbooks_robots', [
			'discourage-ai' => 1,
			'discourage-index' => 0,
		] );

		$this->assertEquals(
			[ 'noindex' => 0, 'nofollow' => 0, 'noai' => 1, 'noimageai' => 1 ],
			\Pressbooks\Utility\handle_book_indexing( [] )
		);

		update_option( 'pressbooks_robots', [
			'discourage-ai' => 0,
			'discourage-index' => 1,
		] );

		$this->assertEquals(
			[ 'noindex' => 1, 'nofollow' => 1, 'noai' => 0, 'noimageai' => 0 ],
			\Pressbooks\Utility\handle_book_indexing( [] )
		);

		update_option( 'pressbooks_robots', [
			'discourage-ai' => 1,
			'discourage-index' => 1,
		] );

		$this->assertEquals(
			[ 'max-image-preview' => 'large', 'noindex' => 1, 'nofollow' => 1, 'noai' => 1, 'noimageai' => 1 ],
			\Pressbooks\Utility\handle_book_indexing( [ 'max-image-preview' => 'large'] )
		);
	}

	/**
	 * @group utility
	 */
	public function test_length_to_inches() {
		$css = [
			'pdf_page_width' => '10cm',
			'pdf_page_margin_inside' => '20cm',
			'pdf_page_margin_outside' => '2in',
		];

		$inches_pdf_w = length_to_inches( $css['pdf_page_width'] );
		$inches_pdf_mi = length_to_inches( $css['pdf_page_margin_inside'] );
		$inches_pdf_mo = length_to_inches( $css['pdf_page_margin_outside'] );

		$this->assertEquals( 3.93701, round( $inches_pdf_w, 5 ) );
		$this->assertEquals( 7.87402, round( $inches_pdf_mi, 5 ) );
		$this->assertEquals( 2, $inches_pdf_mo );

		$inches_pdf_w = length_to_inches( null );
		$this->assertFalse( $inches_pdf_w );
	}

	/**
	 * @group utility
	 * @test
	 */
	public function it_unqueues_editoria11y_assets(): void {
		switch_to_blog( get_main_site_id() );

		wp_register_script( 'editoria11y-js', '/test-editoria11y.js', [], null, false );
		wp_register_script( 'editoria11y-js-shim', '/test-editoria11y-shim.js', [], null, false );
		wp_register_style( 'editoria11y-lib-css', '/test-editoria11y.css', [], null );

		$old_active = get_option( 'active_plugins', [] );

		$this->activate_plugins( [
			'pressbooks-results-for-lms/pressbooks-results-for-lms.php',
			'pressbooks-network-catalog/pressbooks-network-catalog.php',
		] );

		try {
			// main site
			$this->go_to( home_url( '/' ) );
			$this->assertTrue( is_main_site() );
			$this->assertTrue( is_home() || is_front_page() );

			$this->enqueue_test_ed11y_assets();
			
			do_action( 'wp_enqueue_scripts' );
			
			$this->assert_ed11y_dequeued();

			if ( function_exists( 'ed11y_init' ) ) {
				add_action( 'wp_footer', 'ed11y_init' );
				$this->assertNotFalse( has_action( 'wp_footer', 'ed11y_init' ) );
				
				do_action( 'wp_enqueue_scripts' );
				$this->assertFalse( has_action( 'wp_footer', 'ed11y_init' ) );
			}

			// results viewer page
			$results_page_id = wp_insert_post( [
				'post_title'  => 'Pressbooks Results',
				'post_name'   => 'pressbooks-results',
				'post_type'   => 'page',
				'post_status' => 'publish',
			] );
			$this->assertIsInt( $results_page_id );
			$this->go_to( home_url( '/pressbooks-results' ) );
			
			$this->enqueue_test_ed11y_assets();

			do_action( 'wp_enqueue_scripts' );
			
			$this->assert_ed11y_dequeued();

			// catalog page
			$catalog_page_id = wp_insert_post( [
				'post_title'  => 'Catalog',
				'post_name'   => 'catalog',
				'post_type'   => 'page',
				'post_status' => 'publish',
			] );
			$this->assertIsInt( $catalog_page_id );
			update_post_meta( $catalog_page_id, '_wp_page_template', 'page-catalog.php' );
			$this->go_to( get_permalink( $catalog_page_id ) );
			
			$this->enqueue_test_ed11y_assets();
			
			do_action( 'wp_enqueue_scripts' );
			
			$this->assert_ed11y_dequeued();
		} finally {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins', [] ) );
			update_option( 'active_plugins', $old_active );
		}
	}

	private function activate_plugins( array $plugins ): void {
		$sitewide = get_site_option( 'active_sitewide_plugins', [] );
		foreach( $plugins as $plugin ) {
			$sitewide[ $plugin ] = time();
		}
		update_site_option( 'active_sitewide_plugins', $sitewide );
	}

	private function enqueue_test_ed11y_assets(): void {
		$this->assertFalse( wp_script_is( 'editoria11y-js', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'editoria11y-js-shim', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'editoria11y-lib-css', 'enqueued' ) );
	}

	private function assert_ed11y_dequeued(): void {
		$this->assertFalse( wp_script_is( 'editoria11y-js', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'editoria11y-js-shim', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'editoria11y-lib-css', 'enqueued' ) );
	}
}
