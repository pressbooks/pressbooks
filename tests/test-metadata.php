<?php

class MetadataTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\Metadata::getJsonMetadata
	 */
	public function test_getJsonMetadata() {
		$result = \Pressbooks\Metadata::getJsonMetadata();
		$this->assertEquals( $result, '{"pb_title":"Test Blog","pb_author":"admin","pb_cover_image":"http:\/\/example.org\/wp-content\/plugins\/pressbooks\/assets\/dist\/images\/default-book-cover.jpg"}' );
	}
}
