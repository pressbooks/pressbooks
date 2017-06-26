<?php

class MetadataTest extends \WP_UnitTestCase {

	public function test_getJsonMetadata() {
		$result = \Pressbooks\Metadata::getJsonMetadata();
		$this->assertEquals( $result, '{"pb_title":"Test Blog","pb_author":"admin","pb_cover_image":"http:\/\/example.org\/wp-content\/plugins\/pressbooks\/assets\/dist\/images\/default-book-cover.jpg"}' );
	}

	public function test_getUrlForLicense() {
		$result = \Pressbooks\Metadata::getUrlForLicense('public-domain');
		$this->assertEquals( $result, 'https://creativecommons.org/publicdomain/zero/1.0/' );
	}
}
