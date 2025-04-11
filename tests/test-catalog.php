<?php

// Legacy "My Catalog" code

class CatalogTest extends \WP_UnitTestCase {
	/**
	 * @group my_catalog
	 */
	public function test_saveProfile() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$c = new \Pressbooks\Catalog( $user_id );

		$items = [
			'pb_catalog_about' => 'I am <script>alert(1);</script><b>cool</b>!',
			'pb_catalog_url' => 'http://<script>alert(1);</script>/pressbooks',
			'pb_catalog_color' => '#<script>alert(1);</script>1111',
			'pb_garbage_key' => 'Hello World!',
		];

		$c->saveProfile( $items );

		$this->assertEquals(
			get_user_meta( $user_id, 'pb_catalog_about', true ),
			'I am alert(1);<b>cool</b>!'
		);

		$this->assertEquals(
			get_user_meta( $user_id, 'pb_catalog_url', true ),
			'http://alert(1);/pressbooks'
		);

		$this->assertEquals(
			get_user_meta( $user_id, 'pb_catalog_color', true ),
			'#alert(1);1111'
		);

		$this->assertEmpty( get_user_meta( $user_id, 'pb_garbage_key', true ) );
	}
}
