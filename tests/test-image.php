<?php

use function \Pressbooks\Utility\str_ends_with;

class ImageTest extends \WP_UnitTestCase {

	/**
	 * @group media
	 */
	public function test_default_cover_url() {
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url( 'thumbnail' ), 'default-book-cover-100x100.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url( 'small' ), 'default-book-cover-65x0.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url( 'medium' ), 'default-book-cover-225x0.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url(), 'default-book-cover.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url( 'large' ), 'default-book-cover.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_url( 'full' ), 'default-book-cover.jpg' ) );
	}

	/**
	 * @group media
	 */
	public function test_default_cover_path() {
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path( 'thumbnail' ), 'default-book-cover-100x100.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path( 'small' ), 'default-book-cover-65x0.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path( 'medium' ), 'default-book-cover-225x0.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path(), 'default-book-cover.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path( 'large' ), 'default-book-cover.jpg' ) );
		$this->assertTrue( str_ends_with( \Pressbooks\Image\default_cover_path( 'full' ), 'default-book-cover.jpg' ) );
	}

	/**
	 * @group media
	 */
	public function test_is_default_cover() {
		$url = \Pressbooks\Image\default_cover_url();
		$path = \Pressbooks\Image\default_cover_path();
		$this->assertTrue( \Pressbooks\Image\is_default_cover( $url ) );
		$this->assertTrue( \Pressbooks\Image\is_default_cover( $path ) );
		$this->assertFalse( \Pressbooks\Image\is_default_cover( 'https://pressbooks.com/wp-content/uploads/2015/04/hero-image-4.png' ) );
	}

	/**
	 * @group media
	 */
	public function test_is_valid_image() {
		$this->assertTrue( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/pb.png', 'pb.png' ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( 'binary', 'pb.png', true ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/pb.png', 'pb.unknown' ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/template.php', 'pb.png' ) );
	}

	/**
	 * @group media
	 */
	public function test_thumbify() {
		$thumb = '_zigzag';
		$path = '/2017/08/foo-bar.jpeg';
		$result = \Pressbooks\Image\thumbify( $thumb, $path );
		$this->assertEquals( '/2017/08/foo-bar_zigzag.jpeg', $result );

		$thumb = '_zigzag';
		$path = '/2017/08/foo-bar.unknown';  // Not an image name
		$result = \Pressbooks\Image\thumbify( $thumb, $path );
		$this->assertEquals( '/2017/08/foo-bar.unknown', $result );
	}

	/**
	 * @group media
	 */
	public function test_strip_baseurl() {
		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar.png';
		$result = \Pressbooks\Image\strip_baseurl( $test );
		$this->assertEquals( '2017/08/foo-bar.png', $result );

		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar-300x225.png';
		$result = \Pressbooks\Image\strip_baseurl( $test );
		$this->assertEquals( '2017/08/foo-bar-300x225.png', $result );

		$test = 'https://pressbooks.dev/upload/zig/zag/foo-bar.png';
		$result = \Pressbooks\Image\strip_baseurl( $test );
		$this->assertEquals( 'https://pressbooks.dev/upload/zig/zag/foo-bar.png', $result );
	}

	/**
	 * @group media
	 */
	public function test_fudge_factor() {
		$before = (int) ini_get( 'memory_limit' );
		$format = 'png';
		$file = __DIR__ . '/data/pb.png';
		@\Pressbooks\Image\fudge_factor( $format, $file );
		$after = (int) ini_get( 'memory_limit' );
		$this->assertTrue( $before < $after );
		ini_set( 'memory_limit', $before );
	}

	/**
	 * @group media
	 */
	public function test_proper_image_extension() {
		$file = __DIR__ . '/data/pb.png';
		$result = \Pressbooks\Image\proper_image_extension( $file, 'pb.jpg' );
		$this->assertEquals( 'pb.png', $result );

		$result = \Pressbooks\Image\proper_image_extension( $file, 'pb.unknown' ); // Not an image name
		$this->assertEquals( 'pb.unknown', $result );
	}

	/**
	 * @group media
	 */
	public function test_get_dpi() {

		$file = __DIR__ . '/data/template.php';
		$dpi = \Pressbooks\Image\get_dpi( $file );
		$this->assertTrue( false === $dpi );

		$file = __DIR__ . '/data/pb.png';
		$dpi = \Pressbooks\Image\get_dpi( $file );
		$this->assertTrue( false === $dpi );

		$file = __DIR__ . '/data/mountains.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file );
		$this->assertEquals( 300, $dpi );

		$file = __DIR__ . '/data/mountains.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file, true );
		$this->assertEquals( 300, $dpi );

		$file = __DIR__ . '/data/DosenmoorBirken1.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file, true );
		$this->assertEquals( 300, $dpi );

		$file = __DIR__ . '/data/skates.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file );
		$this->assertEquals( 72, $dpi );
	}

	/**
	 * @group media
	 */
	public function test_get_aspect_ratio() {

		$file = __DIR__ . '/data/template.php';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertTrue( false === $aspect_ratio );

		$file = __DIR__ . '/data/pb.png';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '1:1', $aspect_ratio );

		$file = __DIR__ . '/data/mountains.jpg';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '4:3', $aspect_ratio );

		$file = __DIR__ . '/data/mountains-300x225.jpg';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '4:3', $aspect_ratio );

		$file = __DIR__ . '/data/skates.jpg';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '3:4', $aspect_ratio );

		$file = __DIR__ . '/data/DosenmoorBirken1.jpg';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '1024:685', $aspect_ratio );
	}

	/**
	 * @group media
	 */
	public function test_differences() {

		$file1 = __DIR__ . '/data/template.php';
		$file2 = __DIR__ . '/data/pb.png';
		$file3 = __DIR__ . '/data/mountains.jpg';
		$file4 = __DIR__ . '/data/skates.jpg';

		$distance = \Pressbooks\Image\differences( $file1, $file2 );
		$this->assertTrue( false === $distance );

		$distance = \Pressbooks\Image\differences( $file3, $file3 );
		$this->assertTrue( 0 === $distance );

		$distance = \Pressbooks\Image\differences( $file3, $file4 );
		$this->assertTrue( $distance > 0 );
	}

	/**
	 * @group media
	 */
	public function test_is_bigger_version() {

		$mountains = __DIR__ . '/data/mountains.jpg';
		$file1 = __DIR__ . '/data/template.php';
		$file2 = __DIR__ . '/data/pb.png';
		$file3 = __DIR__ . '/data/mountains-300x225.jpg';

		$this->assertFalse( \Pressbooks\Image\is_bigger_version( $file1, $mountains ) );
		$this->assertFalse( \Pressbooks\Image\is_bigger_version( $file2, $mountains ) );
		$this->assertFalse( \Pressbooks\Image\is_bigger_version( $mountains, $file3 ) );
		$this->assertTrue( \Pressbooks\Image\is_bigger_version( $file3, $mountains ) );
	}

	/**
	 * @group media
	 */
	public function test_maybe_swap_with_bigger() {
		$id = $this->factory()->attachment->create_upload_object( __DIR__ . '/data/mountains.jpg' );

		$old = wp_get_attachment_image_src( $id, 'medium' )[0];
		$new = \Pressbooks\Image\maybe_swap_with_bigger( $old );
		$this->assertFalse( $old == $new );

		$old = wp_get_attachment_image_src( $id, 'thumbnail' )[0]; // Not the same aspect ratio, should stay the same
		$new = \Pressbooks\Image\maybe_swap_with_bigger( $old );
		$this->assertTrue( $old == $new );

		$new = \Pressbooks\Image\maybe_swap_with_bigger( 'blah-blah-blah' );
		$this->assertEquals( 'blah-blah-blah', $new );
	}

	/**
	 * @group media
	 */
	public function test_same_aspect_ratio() {

		$file1 = __DIR__ . '/data/DosenmoorBirken1.jpg';
		$file2 = __DIR__ . '/data/DosenmoorBirken1-300x201.jpg';
		$file3 = __DIR__ . '/data/mountains.jpg';
		$file4 = __DIR__ . '/data/mountains-300x225.jpg';
		$file5 = __DIR__ . '/data/template.php';

		$this->assertTrue( \Pressbooks\Image\same_aspect_ratio( $file1, $file2 ) );
		$this->assertTrue( \Pressbooks\Image\same_aspect_ratio( $file2, $file1 ) );
		$this->assertTrue( \Pressbooks\Image\same_aspect_ratio( $file3, $file4 ) );
		$this->assertTrue( \Pressbooks\Image\same_aspect_ratio( $file4, $file3 ) );

		$this->assertFalse( \Pressbooks\Image\same_aspect_ratio( $file1, $file4 ) );
		$this->assertFalse( \Pressbooks\Image\same_aspect_ratio( $file4, $file1 ) );
		$this->assertFalse( \Pressbooks\Image\same_aspect_ratio( $file3, $file2 ) );
		$this->assertFalse( \Pressbooks\Image\same_aspect_ratio( $file2, $file3 ) );

		$this->assertFalse( \Pressbooks\Image\same_aspect_ratio( $file5, $file5 ) );
	}

	/**
	 * @group media
	 * @throws Exception
	 */
	public function test_imageResize() {

		ini_set( 'memory_limit', '100M' ); //Needed to resize and open the image for testing

		/*
		 * Test PNGs with alpha channel
		 */
		$path = __DIR__ . '/data/';
		$original = "${path}alpha.png";
		$resized = "${path}alpha_resized.png";
		copy( $original, $resized );

		\Pressbooks\Image\resize_down( 'png', $resized, 400 );

		$image_to_check = new Imagick( $resized );
		$this->assertTrue( (bool) $image_to_check->getImageAlphaChannel() );
		$this->assertEquals( 400, getimagesize( $resized )[0] );


		/*
		 * Test Jpeg
		 */

		$original = "${path}skates.jpg";
		$resized = "${path}skates_resized.jpg";
		copy( $original, $resized );

		\Pressbooks\Image\resize_down( 'jpeg', $resized, 200 );

		$image_to_check = new Imagick( $resized );
		$this->assertFalse( (bool) $image_to_check->getImageAlphaChannel() );
		$this->assertEquals( 200, getimagesize( $resized )[0] );

	}

}
