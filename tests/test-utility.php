<?php

class UtilityTest extends \WP_UnitTestCase {

	/**
	 * @covers \PressBooks\Utility\scandir_by_date
	 */
	public function test_scandir_by_date() {

		$files = \PressBooks\Utility\scandir_by_date( __DIR__ );

		$this->assertTrue( is_array( $files ) );
		$this->assertContains( basename( __FILE__ ), $files );
		$this->assertNotContains( '.htaccess', $files );
	}


	/**
	 * @covers \PressBooks\Utility\group_exports
	 */
	public function test_group_exports() {

		$files = \PressBooks\Utility\group_exports();
		$this->assertTrue( is_array( $files ) );

		$files = \PressBooks\Utility\group_exports( __DIR__ );
		$this->assertNotContains( '.htaccess', $files );
	}


//	/**
//	 * @covers \PressBooks\Utility\truncate_exports
//	 */
//	public function test_truncate_exports() {
//		// TODO: Testing this as-is would delete files. Need to refactor to allow mocking the file system.
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Utility\get_media_prefix
	 */
	public function test_get_media_prefix() {

		$prefix = \PressBooks\Utility\get_media_prefix();

		$this->assertTrue(
			false !== strpos( $prefix, '/blogs.dir/' ) || false !== strpos( $prefix, '/uploads/sites/' )
		);
	}


	/**
	 * @covers \PressBooks\Utility\get_media_path
	 */
	public function test_get_media_path() {

		$guid = 'http://pressbooks.dev/test/wp-content/uploads/sites/3/2015/11/foobar.jpg';

		$path = \PressBooks\Utility\get_media_path( $guid );

		$this->assertStringStartsWith( WP_CONTENT_DIR, $path );
		$this->assertStringEndsWith( 'foobar.jpg', $path );
		$this->assertTrue(
			false !== strpos( $path, '/blogs.dir/' ) || false !== strpos( $path, '/uploads/sites/' )
		);
	}


	/**
	 * @covers \PressBooks\Utility\multi_sort
	 */
	public function test_multi_sort() {

		$arr = [
			[ 'foo' => 1, 'bar' => 'A' ],
			[ 'foo' => 3, 'bar' => 'C' ],
			[ 'foo' => 2, 'bar' => 'B' ],
		];

		$res = \PressBooks\Utility\multi_sort( $arr, 'foo:desc' );

		$this->assertEquals( '3', $res[0]['foo'] );
		$this->assertEquals( '1', $res[2]['foo'] );

		$res = \PressBooks\Utility\multi_sort( $arr, 'bar:asc', 'foo:desc' );

		$this->assertEquals( 'A', $res[0]['bar'] );
		$this->assertEquals( 'C', $res[2]['bar'] );
		
		$res = \PressBooks\Utility\multi_sort( $arr );
		
		$this->assertFalse( $res );
		
	}


//	/**
//	 * @covers \PressBooks\Utility\wp_mail
//	 */
//	public function test_wp_mail() {
//		// TODO: Testing this as-is would send emails. Need to refactor to allow mocking of postmarkapp endpoint.
//		$this->markTestIncomplete();
//	}


//	/**
//	 * @covers \PressBooks\Utility\pm_send_mail
//	 */
//	public function test_pm_send_mail() {
//		// TODO: Testing this as-is would send emails. Need to refactor to allow mocking of postmarkapp endpoint.
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Utility\add_sitemap_to_robots_txt
	 */
	public function test_add_sitemap_to_robots_txt_0() {

		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/^\s*$/' ); // string is empty or has only whitespace
		\PressBooks\Utility\add_sitemap_to_robots_txt();
	}


	/**
	 * @covers \PressBooks\Utility\add_sitemap_to_robots_txt
	 */
	public function test_add_sitemap_to_robots_txt_1() {

		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/Sitemap:(.+)feed=sitemap.xml/' );
		\PressBooks\Utility\add_sitemap_to_robots_txt();
	}


	/**
	 * @covers \PressBooks\Utility\do_sitemap
	 */
	public function test_do_sitemap_0() {

		update_option( 'blog_public', 0 );
		$this->expectOutputRegex( '/404 Not Found/i' );
		\PressBooks\Utility\do_sitemap();
	}


	/**
	 * @covers \PressBooks\Utility\do_sitemap
	 */
	public function test_do_sitemap_1() {

		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/^<\?xml /' );
		\PressBooks\Utility\do_sitemap();
	}


	/**
	 * @covers \PressBooks\Utility\create_tmp_file
	 */
	public function test_create_tmp_file() {

		$file = \PressBooks\Utility\create_tmp_file();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}


	/**
	 * @covers \PressBooks\Utility\check_prince_install
	 */
	public function test_check_prince_install() {

		$this->assertInternalType( 'bool', \PressBooks\Utility\check_prince_install() );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}


	/**
	 * @covers \PressBooks\Utility\show_experimental_features
	 */
	public function test_show_experimental_features() {

		$this->assertInternalType( 'bool', \PressBooks\Utility\show_experimental_features() );
		$this->assertInternalType( 'bool', \PressBooks\Utility\show_experimental_features( 'http://pressbooks.com' ) );
		
	}


	/**
	 * @covers \PressBooks\Utility\include_plugins
	 */
	public function test_include_plugins() {

		\PressBooks\Utility\include_plugins();
		$this->assertTrue( class_exists( 'custom_metadata_manager' ) );
	}


	/**
	 * @covers \PressBooks\Utility\filter_plugins
	 */
	public function test_filter_plugins() {

		$symbionts = [ 'a-plugin-that-does-not-exist/foobar.php' => 1 ];

		$filtered = \PressBooks\Utility\filter_plugins( $symbionts );

		$this->assertTrue( is_array( $filtered ) );
		$this->assertArrayHasKey( 'a-plugin-that-does-not-exist/foobar.php', $filtered );
	}


	/**
	 * @covers \PressBooks\Utility\file_upload_max_size
	 */
	public function test_file_upload_max_size() {

		$maxSize = \PressBooks\Utility\file_upload_max_size();

		$this->assertTrue(
			ini_get( 'post_max_size' ) == $maxSize || ini_get( 'upload_max_filesize' ) == $maxSize
		);

	}


	/**
	 * @covers \PressBooks\Utility\parse_size
	 */
	public function test_parse_size() {

		$this->assertTrue( is_float( \PressBooks\Utility\parse_size( '1' ) ) );

		$this->assertEquals( 65536, \PressBooks\Utility\parse_size( '64K' ) );
		$this->assertEquals( 2097152, \PressBooks\Utility\parse_size( '2M' ) );
		$this->assertEquals( 8388608, \PressBooks\Utility\parse_size( '8M' ) );
	}


	/**
	 * @covers \PressBooks\Utility\format_bytes
	 */
	public function test_format_bytes() {

		$this->assertEquals( '200 B', \PressBooks\Utility\format_bytes( 200 ) );
		$this->assertEquals( '200 B', \PressBooks\Utility\format_bytes( 200, 4 ) );

		$this->assertEquals( '1.95 KB', \PressBooks\Utility\format_bytes( 2000 ) );
		$this->assertEquals( '1.9531 KB', \PressBooks\Utility\format_bytes( 2000, 4 ) );

		$this->assertEquals( '1.91 MB', \PressBooks\Utility\format_bytes( 2000000 ) );
		$this->assertEquals( '1.9073 MB', \PressBooks\Utility\format_bytes( 2000000, 4 ) );

		$this->assertEquals( '1.86 GB', \PressBooks\Utility\format_bytes( 2000000000 ) );
		$this->assertEquals( '1.8626 GB', \PressBooks\Utility\format_bytes( 2000000000, 4 ) );

		$this->assertEquals( '1.82 TB', \PressBooks\Utility\format_bytes( 2000000000000 ) );
		$this->assertEquals( '1.819 TB', \PressBooks\Utility\format_bytes( 2000000000000, 4 ) );
	}


//	/**
//	 * @covers \PressBooks\Utility\email_error_log
//	 */
//	public function test_email_error_log() {
//		// TODO: Testing this as-is would send emails, write to error_log... Need to refactor
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Utility\template
	 */
	public function test_template() {

		$template = \PressBooks\Utility\template(
			__DIR__ . '/data/template.php',
			[ 'title' => 'Foobar', 'body' => 'Hello World!' ]
		);

		$this->assertContains( '<title>Foobar</title>', $template );
		$this->assertNotContains( '<title></title>', $template );

		$this->assertContains( "<body>Hello World!</body>", $template );
		$this->assertNotContains( '<body></body>', $template );

		try {
			\PressBooks\Utility\template( '/tmp/file/does/not/exist' );
		}
		catch ( \Exception $e ) {
			$this->assertTrue( true );
			return;
		}
		$this->fail();
	}

}