<?php

class MetadataTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @see \Pressbooks\Metadata::jsonSerialize
	 */
	public function test_Metadata_JsonSerialize() {
		$result = json_encode( new \Pressbooks\Metadata() );
		$this->assertJson( $result );
		$this->assertContains( '{"@context":"http:\/\/schema.org","@type":"Book","name":"Test Blog",', $result );

	}

	public function test_get_url_for_license() {
		$result = \Pressbooks\Metadata\get_url_for_license( 'public-domain' );
		$this->assertEquals( $result, 'https://creativecommons.org/publicdomain/zero/1.0/' );
	}

	public function test_get_web_license_html() {

		$xml = new \SimpleXMLElement( '<book><title>Hello World!</title></book>' );
		$result = \Pressbooks\Metadata\get_web_license_html( $xml );
		$this->assertContains( 'Hello World!', $result );
		$this->assertContains( 'creativecommons.org', $result );
		$this->assertContains( '</div>', $result );
	}

	public function test_get_license_xml() {

		$result = \Pressbooks\Metadata\get_license_xml( 'all-rights-reserved', 'Foo', 'http://pressbooks.dev', 'Bar', 'en' );
		$this->assertContains( 'All Rights Reserved', $result );
		$this->assertContains( '</result>', $result );

		$result = \Pressbooks\Metadata\get_license_xml( 'cc-by-nc-nd', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertContains( 'by-nc-nd', $result );
		$this->assertContains( 'Ceci peut être', $result );
		$this->assertContains( '</result>', $result );

		$result = \Pressbooks\Metadata\get_license_xml( 'unsupported-type', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertEmpty( $result );
	}

	public function test_get_microdata_elements() {

		$result = \Pressbooks\Metadata\get_microdata_elements();
		$this->assertContains( '<meta', $result );
	}

	public function test_get_seo_meta_elements() {

		$result = \Pressbooks\Metadata\get_seo_meta_elements();
		$this->assertContains( '<meta', $result );
	}

	public function test_show_expanded_metadata() {
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertFalse( $result );
		update_option( 'pressbooks_show_expanded_metadata', 1 );
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertTrue( $result );
	}

	public function test_has_expanded_metadata() {
		$book = \Pressbooks\Book::getInstance();

		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertFalse( $result );

		\Pressbooks\Book::deleteBookObjectCache();

		update_post_meta( $meta_post->ID, 'pb_author_file_as', 'Zimmerman, Ned' );

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertTrue( $result );
	}

}
