<?php

class ImageTest extends \WP_UnitTestCase {

	public function test_is_default_cover() {
		$url = \Pressbooks\Image\default_cover_url();
		$path = \Pressbooks\Image\default_cover_path();
		$this->assertTrue( \Pressbooks\Image\is_default_cover( $url ) );
		$this->assertTrue( \Pressbooks\Image\is_default_cover( $path ) );
		$this->assertFalse( \Pressbooks\Image\is_default_cover( 'https://pressbooks.com/wp-content/uploads/2015/04/hero-image-4.png' ) );
	}

	public function test_is_valid_image() {
		$this->assertTrue( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/pb.png', 'pb.png' ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( 'binary', 'pb.png', true ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/pb.png', 'pb.unknown' ) );
		$this->assertFalse( \Pressbooks\Image\is_valid_image( __DIR__ . '/data/template.php', 'pb.png' ) );
	}

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

	public function test_strip_baseurl() {
		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar.png';
		$result = \Pressbooks\Image\strip_baseurl( $test );
		$this->assertEquals( '2017/08/foo-bar.png', $result );

		$test = 'https://pressbooks.dev/upload/zig/zag/foo-bar.png';
		$result = \Pressbooks\Image\strip_baseurl( $test );
		$this->assertEquals( 'https://pressbooks.dev/upload/zig/zag/foo-bar.png', $result );
	}

	public function test_fudge_factor() {
		$before = (int) ini_get( 'memory_limit' );
		$format = 'png';
		$file = __DIR__ . '/data/pb.png';
		@\Pressbooks\Image\fudge_factor( $format, $file );
		$after = (int) ini_get( 'memory_limit' );
		$this->assertTrue( $before < $after );
		ini_set( 'memory_limit', $before );
	}

	public function test_proper_image_extension() {
		$file = __DIR__ . '/data/pb.png';
		$result = \Pressbooks\Image\proper_image_extension( $file, 'pb.jpg' );
		$this->assertEquals( 'pb.png', $result );

		$result = \Pressbooks\Image\proper_image_extension( $file, 'pb.unknown' ); // Not an image name
		$this->assertEquals( 'pb.unknown', $result );
	}

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

		$file = __DIR__ . '/data/skates.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file );
		$this->assertEquals( 72, $dpi );

		$file = __DIR__ . '/data/skates.jpg';
		$dpi = \Pressbooks\Image\get_dpi( $file, true );
		$this->assertEquals( 72, $dpi );
	}

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

		$file = __DIR__ . '/data/skates.jpg';
		$aspect_ratio = \Pressbooks\Image\get_aspect_ratio( $file );
		$this->assertEquals( '3:4', $aspect_ratio );
	}

	public function test_is_similar() {

		$file1 = __DIR__ . '/data/template.php';
		$file2 = __DIR__ . '/data/pb.png';
		$file3 = __DIR__ . '/data/mountains.jpg';
		$file4 = __DIR__ . '/data/skates.jpg';

		$distance = \Pressbooks\Image\is_similar( $file1, $file2 );
		$this->assertTrue( false === $distance );

		$distance = \Pressbooks\Image\is_similar( $file3, $file3 );
		$this->assertTrue( 0 === $distance );

		$distance = \Pressbooks\Image\is_similar( $file3, $file4 );
		$this->assertTrue( $distance > 0 );
	}

}