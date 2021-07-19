<?php

class MediaTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group media
	 */
	public function test_add_mime_types() {

		$supportedFileExtensions = [ 'mp4', 'webm', 'ogv', 'ogg', 'mp3', 'aac', 'vorbis' ];
		$jitMimes = [ 'foobar' => 'foo/bar' ];

		$mimes = \Pressbooks\Media\add_mime_types( $jitMimes );

		$this->assertArrayHasKey( 'foobar', $mimes );
		$this->assertEquals( 'foo/bar', $mimes['foobar'] );

		foreach ( $supportedFileExtensions as $ext ) {
			$this->assertArrayHasKey( $ext, $mimes );
		}

		$this->assertArrayNotHasKey( 'baz', $mimes );
	}

	public function test_unknown_upload_types() {
		// Setup
		$old_value = get_site_option( 'upload_filetypes' );
		update_site_option( 'upload_filetypes', 'svg epub foobar' );
		$jitMimes = [ 'foobar' => 'foo/bar' ];

		$mimes = \Pressbooks\Media\unknown_upload_types( $jitMimes );

		$this->assertArrayHasKey( 'svg', $mimes );
		$this->assertEquals( null, $mimes['svg'] );
		$this->assertArrayHasKey( 'epub', $mimes );
		$this->assertEquals( null, $mimes['epub'] );
		$this->assertArrayNotHasKey( 'foobar', $mimes );

		// Cleanup
		if ( $old_value === false ) {
			delete_site_option( 'upload_filetypes' );
		} else {
			update_site_option( 'upload_filetypes', $old_value );
		}
	}

	public function test_add_lord_of_the_files_types() {
		$jitMimes = [ 'foobar' => 'foo/bar' ];
		$mimes = \Pressbooks\Media\add_lord_of_the_files_types( $jitMimes );
		$this->assertArrayHasKey( 'foobar', $mimes );
		$this->assertEquals( 'foo/bar', $mimes['foobar'] );
		// TODO: Test with LOTF plugin enabled
	}

	public function test_get_lord_of_the_files_mime_aliases() {
		$match = \Pressbooks\Media\get_lord_of_the_files_mime_aliases( false, 'unknown' );
		$this->assertFalse( $match );

		$match = \Pressbooks\Media\get_lord_of_the_files_mime_aliases( false, 'nlogo' );
		$this->assertEquals( [ 'text/plain' ], $match );

		$match = \Pressbooks\Media\get_lord_of_the_files_mime_aliases( [ 'text/plain' ], 'nlogo' ); // No duplicates
		$this->assertEquals( [ 'text/plain' ], $match );

		$match = \Pressbooks\Media\get_lord_of_the_files_mime_aliases( [ 'fake/records' ], 'nlogo' );
		$this->assertEquals( [ 'fake/records', 'text/plain' ], $match );

		$match = \Pressbooks\Media\get_lord_of_the_files_mime_aliases( [ 'fake/records' ], '.nlogo' ); // Not expected to match dot
		$this->assertEquals( [ 'fake/records' ], $match );
	}

	/**
	 * @group media
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
			$this->assertTrue( \Pressbooks\Media\is_valid_media( '__UNUSED__', $file ) );
		}

		$badFiles = [
			'image.png',
			'image.gif',
			'image.jpg',
			'image.jpeg',
			'foo.bar',
			'/etc/hosts',
		];

		foreach ( $badFiles as $file ) {
			$this->assertFalse( \Pressbooks\Media\is_valid_media( '__UNUSED__', $file ) );
		}
	}


	/**
	 * @group media
	 * @see https://github.com/pressbooks/pressbooks/issues/263
	 */
	public function test_force_wrap_images() {

		// WordPress markup for images with captions wraps the image (and caption) in a .wp-caption div, generating something like the following: (We *don't* want to change this)
		$case = '<div id="attachment_295" style="width: 2394px" class="wp-caption aligncenter"><img class="size-full wp-image-295" src="http://standardtest.pressbooks.com/files/2015/10/Denison-big.jpg" alt="...." width="2384" height="2984" /> <p class="wp-caption-text">A caption about the things captions are about.</p> </div>';
		$converted = \Pressbooks\Media\force_wrap_images( $case );
		$this->assertStringStartsNotWith( '<div class="wp-nocaption ', $converted );
		$this->assertStringEndsWith( 'about.</p> </div>', $converted );

		// WordPress does not wrap images without captions in a similar way, so we get the following in the case of an image (with no link): (We *do* want to change this)
		$case = '<p><img class="aligncenter size-full wp-image-294" src="http://standardtest.pressbooks.com/files/2015/10/Denison-small.jpg" alt="Denison-small" width="191" height="240" /></p>';
		$converted = \Pressbooks\Media\force_wrap_images( $case );
		$this->assertStringStartsWith( '<div class="wp-nocaption aligncenter size-full wp-image-294"><img ', $converted );
		$this->assertStringEndsWith( ' /></div>', $converted );

		// WordPress generates this in the case of an image with a link: (We *do* want to change this)
		$case = '<p><a href="http://imagelink.com/image.jpg"><img class="aligncenter wp-image-294 size-full" src="http://standardtest.pressbooks.com/files/2015/10/Denison-small.jpg" alt="Denison-small" width="191" height="240" /></a></p>';
		$converted = \Pressbooks\Media\force_wrap_images( $case );
		$this->assertStringStartsWith( '<div class="wp-nocaption aligncenter wp-image-294 size-full"><a ', $converted );
		$this->assertStringEndsWith( '</a></div>', $converted );

		// Wordpress will insert a break in specific use cases on center aligned images, we want to convert this.
		$case = '<p><a href="https://university.pressbooks.pub/app/uploads/sites/112/2018/12/image1-1.jpeg"><img class="aligncenter wp-image-33 size-thumbnail" src="https://university.pressbooks.pub/app/uploads/sites/112/2018/12/image1-1-150x150.jpeg" alt="Green cacti and a grey sky" width="150" height="150" /></a><br />Lorem ipsum.</p>';
		$converted = \Pressbooks\Media\force_wrap_images( $case );
		$this->assertStringStartsWith( '<div class="wp-nocaption aligncenter wp-image-33 size-thumbnail"><a ', $converted );
		$this->assertStringEndsWith( '</a></div><p>Lorem ipsum.</p>', $converted );
	}

	/**
	 * @group media
	 */
	public function test_force_attach_media() {
		global $post_ID;
		$post_ID = '42';

		$params = [];
		$return = \Pressbooks\Media\force_attach_media( $params );
		$this->assertEquals( $return['post_id'], 42 );
	}

	/**
	 * @group media
	 */
	public function test_mime_type() {
		$mime = \Pressbooks\Media\mime_type( __DIR__ . '/data/htmlbook.html' );
		$this->assertContains( 'html', $mime );

		$mime = \Pressbooks\Media\mime_type( __DIR__ . '/data/mountains.jpg' );
		$this->assertContains( 'jpeg', $mime );
	}

	/**
	 * @group media
	 */
	public function test_extract_id_from_media() {
		$media_img = [
			0 => '<img src="https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-225x300.jpg" alt="" width="225" height="300" class="wp-image-33 size-medium" srcset="https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-225x300.jpg 225w, https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-65x87.jpg 65w, https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-350x467.jpg 350w, https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691.jpg 480w" sizes="(max-width: 225px) 100vw, 225px" />',
			1 => '<img src="https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-300x224.jpg" alt="" width="300" height="224" class="alignnone size-medium wp-image-75" srcset="https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-300x224.jpg 300w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-768x574.jpg 768w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-1024x765.jpg 1024w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-65x49.jpg 65w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-225x168.jpg 225w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-350x261.jpg 350w, https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2.jpg 1296w" sizes="(max-width: 300px) 100vw, 300px" />',
		];
		$media_audio = [
			0 => '<audio class="wp-audio-shortcode" id="audio-5-1" preload="none" style="width: 100%;" controls="controls"><source type="audio/mpeg" src="https://pressbooks.test/app/uploads/sites/2/2018/06/snap.m4a?_=1" /><a href="https://pressbooks.test/app/uploads/sites/2/2018/06/snap.m4a">https://pressbooks.test/app/uploads/sites/2/2018/06/snap.m4a</a></audio>',
		];

		$result = \Pressbooks\Media\extract_id_from_media( $media_img );
		$this->assertEquals( [
			33 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-225x300.jpg',
			75 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-300x224.jpg',
		], $result );
		$result = \Pressbooks\Media\extract_id_from_media( $media_audio );
		$this->assertEquals( [], $result );
	}

	/**
	 * @group media
	 */
	public function test_intersect_media_ids() {
		$book_media    = [
			90 => 'https://pressbooks.test/app/uploads/sites/2/2018/07/Movie-on-2018-07-16-at-2.19-PM.mp4',
			87 => 'https://pressbooks.test/app/uploads/sites/2/2018/07/Movie-on-2018-07-16-at-2.19-PM.mov',
			78 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/photo-3.jpg',
			75 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2.jpg',
			39 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/snap.m4a',
			33 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863.jpg',
		];
		$page_media    = [
			33 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/IMG_5863-e1530742020691-225x300.jpg',
			75 => 'https://pressbooks.test/app/uploads/sites/2/2018/06/photo-2-300x224.jpg',
		];
		$no_page_media = [];

		$result = \Pressbooks\Media\intersect_media_ids( $page_media, $book_media );
		$this->assertEquals( [ 33, 75 ], $result );
		$result = \Pressbooks\Media\intersect_media_ids( $no_page_media, $book_media );
		$this->assertEquals( [], $result );
	}

	/**
	 * @group media
	 */
	public function test_strip_baseurl() {
		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar.mp3';
		$result = \Pressbooks\Media\strip_baseurl( $test );
		$this->assertEquals( '2017/08/foo-bar.mp3', $result );

		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar-300x225.mp4';
		$result = \Pressbooks\Media\strip_baseurl( $test );
		$this->assertEquals( '2017/08/foo-bar-300x225.mp4', $result );

		$test = 'https://pressbooks.dev/upload/zig/zag/foo-bar.mp3';
		$result = \Pressbooks\Media\strip_baseurl( $test );
		$this->assertEquals( 'https://pressbooks.dev/upload/zig/zag/foo-bar.mp3', $result );

		$test = 'https://pressbooks.dev/upload/2017/08/foo-bar.invalid_extension';
		$result = \Pressbooks\Media\strip_baseurl( $test );
		$this->assertEquals( 'https://pressbooks.dev/upload/2017/08/foo-bar.invalid_extension', $result );
	}

	/**
	 * @group upload
	 */
	public function test_upload_cover_image() {
		$this->_book();
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$_FILES = [
			'pb_cover_image' => [
				'name' => 'image.jpeg',
			],
		];

		copy( __DIR__ . '/data/upload/image.jpeg', __DIR__ . '/data/upload/image_test.jpeg' );

		$image = [
			'file' => __DIR__ . '/data/upload/image_test.jpeg',
			'url' => 'https://pressbooks.test/app/uploads/sites/4/2021/07/image.jpeg',
			'type' => 'image/jpeg',
		];

		global $post_ID;
		$post_ID = '42';

		$cover_image = get_post_meta( $post_ID, 'pb_cover_image', true );

		$this->assertNotEquals( $cover_image, $image['url']);

		\Pressbooks\Admin\Metaboxes\upload_cover_image( $post_ID, null, $image );
		$cover_image = get_post_meta( $post_ID, 'pb_cover_image', true );

		$this->assertEquals( $cover_image, $image['url']);
    }

	/**
	 * @group upload
	 */
	public function test_upload_cover_image_fails_with_wrong_aspect_ratio() {
		$this->_book();
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$_FILES = [
			'pb_cover_image' => [
				'name' => 'gt_1_aspect_ratio.jpeg',
			],
		];

		$image = [
			'file' => __DIR__ . '/data/upload/gt_1_aspect_ratio.jpeg',
			'url' => 'https://pressbooks.test/app/uploads/sites/4/2021/07/gt_1_aspect_ratio.jpeg',
			'type' => 'image/jpeg',
		];

		global $post_ID;
		$post_ID = '42';

		\Pressbooks\Admin\Metaboxes\upload_cover_image( $post_ID, null, $image );
		$cover_image = get_post_meta( $post_ID, 'pb_cover_image', true );

		$this->assertEmpty( $cover_image );

		$_FILES = [
			'pb_cover_image' => [
				'name' => 'lt_66_aspect_ratio.jpeg',
			],
		];

		$image = [
			'file' => __DIR__ . '/data/upload/lt_66_aspect_ratio.jpeg',
			'url' => 'https://pressbooks.test/app/uploads/sites/4/2021/07/lt_66_aspect_ratio.jpeg',
			'type' => 'image/jpeg',
		];

		\Pressbooks\Admin\Metaboxes\upload_cover_image( $post_ID, null, $image );
		$cover_image = get_post_meta( $post_ID, 'pb_cover_image', true );

		$this->assertEmpty( $cover_image );
    }

    /**
     * @group upload
     */
    public function test_upload_cover_image_fails_if_less_than_800_tall() {
        $this->_book();
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

        $_FILES = [
            'pb_cover_image' => [
                'name' => 'lt_800_tall.jpeg',
            ],
        ];

        $image = [
            'file' => __DIR__ . '/data/upload/lt_800_tall.jpeg',
            'url' => 'https://pressbooks.test/app/uploads/sites/4/2021/07/lt_800_tall.jpeg',
            'type' => 'image/jpeg',
        ];

        global $post_ID;
        $post_ID = '42';

        \Pressbooks\Admin\Metaboxes\upload_cover_image( $post_ID, null, $image );
        $cover_image = get_post_meta( $post_ID, 'pb_cover_image', true );

        $this->assertEmpty( $cover_image );
    }
}
