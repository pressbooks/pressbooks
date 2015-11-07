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
		// TODO
		$this->markTestIncomplete();
	}


	/**
	 * @covers \PressBooks\Utility\truncate_exports
	 */
	public function test_truncate_exports() {
		// TODO
		$this->markTestIncomplete();
	}


	/**
	 * @covers \PressBooks\Utility\get_media_prefix
	 */
	public function test_get_media_prefix() {
		// TODO
		$this->markTestIncomplete();
	}


	/**
	 * @covers \PressBooks\Utility\get_media_path
	 */
	public function test_get_media_path() {

		$guid = 'http://pressbooks.dev/test/wp-content/uploads/sites/3/2015/11/the-shaman.jpg';

		// TODO
		$todo = \PressBooks\Utility\get_media_path( $guid );
		$this->markTestIncomplete();
	}


	/**
	 * @covers \PressBooks\Utility\multi_sort
	 */
	public function test_multi_sort() {
		// TODO
		$this->markTestIncomplete();
	}


//	/**
//	 * @covers \PressBooks\Utility\wp_mail
//	 */
//	public function test_wp_mail() {
//		// TODO
//		$this->markTestIncomplete();
//	}
//
//
//	/**
//	 * @covers \PressBooks\Utility\pm_send_mail
//	 */
//	public function test_pm_send_mail() {
//		// TODO
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Utility\add_sitemap_to_robots_txt
	 */
	public function test_add_sitemap_to_robots_txt() {

		$old = get_option( 'blog_public' );

		update_option( 'blog_public', 1 );
		$this->expectOutputRegex( '/Sitemap:(.+)feed=sitemap.xml/' );
		\PressBooks\Utility\add_sitemap_to_robots_txt();

		update_option( 'blog_public', 0 );
		ob_start();
		\PressBooks\Utility\add_sitemap_to_robots_txt();
		$out = ob_get_contents();
		ob_end_clean();
		$this->assertEquals( null, $out );

		update_option( 'blog_public', $old );
	}


//	/**
//	 * @covers \PressBooks\Utility\do_sitemap
//	 */
//	public function test_do_sitemap() {
//		// TODO
//		$this->markTestIncomplete();
//	}


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

		$this->assertTrue( is_bool( \PressBooks\Utility\check_prince_install() ) );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}


	/**
	 * @covers \PressBooks\Utility\show_experimental_features
	 */
	public function test_show_experimental_features() {

		$this->assertTrue( is_bool( \PressBooks\Utility\show_experimental_features() ) );

	}


//	/**
//	 * @covers \PressBooks\Utility\include_plugins
//	 */
//	public function test_include_plugins() {
//		// TODO
//		$this->markTestIncomplete();
//	}


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
//		// TODO
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