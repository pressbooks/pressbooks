<?php

class Login extends \WP_UnitTestCase {

	/**
	 * @group Login
	 */
	public function test_if_wp_prefix_is_removed() {

		$this->assertEquals(
			'https://network.pressbooks.pub/booktitle/wp-login.php?action=lostpassword',
			\Pressbooks\Registration\remove_wp_prefix( 'https://network.pressbooks.pub/booktitle/wp-login.php?action=lostpassword' )
		);

		$this->assertEquals(
			'https://network.pressbooks.pub/wp/wp-login.php?action=lostpassword',
			\Pressbooks\Registration\remove_wp_prefix( 'https://network.pressbooks.pub/wp/wp-login.php?action=lostpassword' )
		);

		$this->assertEquals(
			'https://network.pressbooks.pub/wp-login.php?action=lostpassword',
			\Pressbooks\Registration\remove_wp_prefix( 'https://network.pressbooks.pub/wp-login.php?action=lostpassword' )
		);

	}

}
