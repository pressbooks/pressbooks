<?php

class MediaTest extends \WP_UnitTestCase {

	/**
	 * @covers \PressBooks\Media\add_mime_types
	 */
	public function test_add_mime_types() {

		$supportedFileExtensions = [ 'mp4', 'webm', 'ogv', 'ogg', 'mp3', 'aac', 'vorbis' ];
		$jitMimes = [ 'foobar' => 'foo/bar' ];

		$mimes = \PressBooks\Media\add_mime_types( $jitMimes );

		$this->assertArrayHasKey( 'foobar', $mimes );
		$this->assertEquals( 'foo/bar', $mimes['foobar'] );

		foreach ( $supportedFileExtensions as $ext ) {
			$this->assertArrayHasKey( $ext, $mimes );
		}

		$this->assertArrayNotHasKey( 'baz', $mimes );
	}


	/**
	 * @covers \PressBooks\Media\is_valid_media
	 */
	public function test_is_valid_media() {

		$goodFiles = [
			'video.mp4',
			'video.webm',
			'video.ogv',
			'audio.ogg',
			'audio.mp3',
			'audio.aac',
			'audio.vorbis',
		];

		foreach ( $goodFiles as $file ) {
			$this->assertTrue( \PressBooks\Media\is_valid_media( '__UNUSED__', $file ) );
		}

		$badFiles = [
			'image.png',
			'image.gif',
			'image.jpg',
			'image.jpeg',
			'foo.bar',
			'/etc/hosts'
		];

		foreach ( $badFiles as $file ) {
			$this->assertFalse( \PressBooks\Media\is_valid_media( '__UNUSED__', $file ) );
		}
	}


	/**
	 * @covers \PressBooks\Media\force_wrap_images
	 *
	 * @see https://github.com/pressbooks/pressbooks/issues/263
	 */
	public function test_force_wrap_images() {

		// WordPress markup for images with captions wraps the image (and caption) in a .wp-caption div, generating something like the following: (We *don't* want to change this)
		$case = '<div id="attachment_295" style="width: 2394px" class="wp-caption aligncenter"><img class="size-full wp-image-295" src="http://standardtest.pressbooks.com/files/2015/10/Denison-big.jpg" alt="...." width="2384" height="2984" /> <p class="wp-caption-text">A caption about the things captions are about.</p> </div>';
		$converted = \PressBooks\Media\force_wrap_images( $case );
		$this->assertStringStartsNotWith( '<div class="wp-nocaption ', $converted );
		$this->assertStringEndsWith( 'about.</p> </div>', $converted );

		// WordPress does not wrap images without captions in a similar way, so we get the following in the case of an image (with no link): (We *do* want to change this)
		$case = '<p><img class="aligncenter size-full wp-image-294" src="http://standardtest.pressbooks.com/files/2015/10/Denison-small.jpg" alt="Denison-small" width="191" height="240" /></p>';
		$converted = \PressBooks\Media\force_wrap_images( $case );
		$this->assertStringStartsWith( '<div class="wp-nocaption aligncenter size-full wp-image-294"><img ', $converted );
		$this->assertStringEndsWith( ' /></div>', $converted );

		// WordPress generates this in the case of an image with a link: (We *do* want to change this)
		$case = '<p><a href="http://imagelink.com/image.jpg"><img class="aligncenter wp-image-294 size-full" src="http://standardtest.pressbooks.com/files/2015/10/Denison-small.jpg" alt="Denison-small" width="191" height="240" /></a></p>';
		$converted = \PressBooks\Media\force_wrap_images( $case );
		$this->assertStringStartsWith( '<div class="wp-nocaption aligncenter wp-image-294 size-full"><a ', $converted );
		$this->assertStringEndsWith( '</a></div>', $converted );
	}


}