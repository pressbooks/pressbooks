<?php

class ClonerTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_removeDefaultBookContent() {
		$posts = [
			'main-body' => [
				'post_title' => 'Main Body',
				'post_name' => 'main-body',
				'post_type' => 'part',
				'menu_order' => 1,
			],
			'introduction' => [
				'post_title' => 'Introduction',
				'post_name' => 'introduction',
				'post_content' => 'This is where you can write your introduction.',
				'post_type' => 'front-matter',
				'menu_order' => 1,
			],
			'chapter-1' => [
				'post_title' => 'Chapter 1',
				'post_name' => 'chapter-1',
				'post_content' => 'This is the first chapter in the main body of the text. You can change the text, rename the chapter, add new chapters, and add new parts.',
				'post_type' => 'chapter',
				'menu_order' => 1,
			],
			'appendix' => [
				'post_title' => 'Appendix',
				'post_name' => 'appendix',
				'post_content' => 'This is where you can add appendices or other back matter.',
				'post_type' => 'back-matter',
				'menu_order' => 1,
			],
			'authors' => [
				'post_title' => __( 'Authors', 'pressbooks' ),
				'post_name' => 'authors',
				'post_type' => 'page',
			],
			'cover' => [
				'post_title' => __( 'Cover', 'pressbooks' ),
				'post_name' => 'cover',
				'post_type' => 'page',
			],
			'table-of-contents' => [
				'post_title' => __( 'Table of Contents', 'pressbooks' ),
				'post_name' => 'table-of-contents',
				'post_type' => 'page',
			],
		];
		$result = \Pressbooks\Cloner::removeDefaultBookContent( $posts );
		$this->assertArrayNotHasKey( 'main-body', $result );
		$this->assertArrayNotHasKey( 'introduction', $result );
		$this->assertArrayNotHasKey( 'chapter-1', $result );
		$this->assertArrayNotHasKey( 'appendix', $result );
		$this->assertArrayHasKey( 'authors', $result );
		$this->assertArrayHasKey( 'cover', $result );
		$this->assertArrayHasKey( 'table-of-contents', $result );
	}

	public function test_getBookId() {
		global $blog_id;

		$this->_book();

		$result = \Pressbooks\Cloner::getBookId( home_url( '/' ) );
		$this->assertEquals( $result, $blog_id );
	}

	public function test_getSubdomainOrSubDirectory() {
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/path/' );
		$this->assertEquals( $result, 'path' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/path' );
		$this->assertEquals( $result, 'path' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/' );
		$this->assertEquals( $result, 'sub' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com' );
		$this->assertEquals( $result, 'sub' );
	}

	public function test_isEnabled() {
		$result = \Pressbooks\Cloner::isEnabled();
		$this->assertTrue( is_bool( $result ) );
	}

	public function test_validateNewBookName() {
		$result = \Pressbooks\Cloner::validateNewBookName( '12345' );
		$this->assertTrue( is_wp_error( $result ) );
		$result = \Pressbooks\Cloner::validateNewBookName( 'bad-name' );
		$this->assertTrue( is_wp_error( $result ) );
		$result = \Pressbooks\Cloner::validateNewBookName( 'newbook' );
		$this->assertEquals( $result, 'example.org/newbook/' );
	}

	public function test_isSourceCloneable() {
		$cloner = new \Pressbooks\Cloner( home_url() );

		$this->assertFalse( $cloner->isSourceCloneable( 'https://creativecommons.org/licenses/by-nd/4.0/' ) );
		$this->assertFalse( $cloner->isSourceCloneable( 'https://creativecommons.org/licenses/by-nc-nd/4.0/' ) );
		$this->assertFalse( $cloner->isSourceCloneable( 'https://choosealicense.com/no-license/' ) );

		$this->assertFalse( $cloner->isSourceCloneable( [ 'url' => 'https://creativecommons.org/licenses/by-nd/4.0/' ] ) );
		$this->assertFalse( $cloner->isSourceCloneable( [ 'url' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/' ] ) );
		$this->assertFalse( $cloner->isSourceCloneable( [ 'url' => 'https://choosealicense.com/no-license/' ] ) );

		$this->assertTrue( $cloner->isSourceCloneable( 'https://creativecommons.org/licenses/by-sa/4.0/' ) );
		$this->assertTrue( $cloner->isSourceCloneable( 'http://i-have-no-idea-what-license-this-is/' ) );
	}

	public function test_discoverWordPressApi(){

		// Hook a fake HTTP request response.
		add_filter(
			'pre_http_request',
			function ( $false, $arguments, $url ) {
				if ( $url === 'https://bad.com' ) {
					return [
						'headers' => [ 'link' => 'cannot parse this' ],
					];
				}
				if ( $url === 'https://good.com' ) {
					return [
						'headers' => [ 'link' => '<http://example.com/wp-json/>; rel="https://api.w.org/"' ],
					];
				}
				if ( $url === 'https://also-good.com' ) {
					return [
						'headers' => [ 'link' => [ "<http://example.com/?rest_route=/>; rel='https://api.w.org/'", 'extra stuff' ] ],
					];
				}
				return false;

			}, 10, 3
		);

		$cloner = new \Pressbooks\Cloner( home_url() );

		$url = $cloner->discoverWordPressApi( 'https://bad.com' );
		$this->assertFalse( $url );

		$url = $cloner->discoverWordPressApi( 'https://good.com' );
		$this->assertEquals( 'http://example.com', $url ); // REST base removed

		$url = $cloner->discoverWordPressApi( 'https://also-good.com' );
		$this->assertEquals( 'http://example.com/?rest_route=', $url );
	}

}
