<?php

class Login extends \WP_UnitTestCase {

	/**
	 * @group Login
	 */
	public function test_if_wp_prefix_is_removed() {

		$scenario1 = 'https://network.pressbooks.pub/wp/booktitle/wp-login.php?action=lostpassword';
		$expected = 'https://network.pressbooks.pub/booktitle/wp-login.php?action=lostpassword';

		$actual = \Pressbooks\Registration\remove_wp_prefix($scenario1);

		$this->assertEquals($expected, $actual);

		$scenario2 = 'https://network.pressbooks.pub/wp/wp-login.php?action=lostpassword';
		$expected = 'https://network.pressbooks.pub/wp/wp-login.php?action=lostpassword';

		$actual = \Pressbooks\Registration\remove_wp_prefix($scenario2);

		$this->assertEquals($expected, $actual);


		$scenario3 = 'https://network.pressbooks.pub/wp-login.php?action=lostpassword';
		$expected = 'https://network.pressbooks.pub/wp-login.php?action=lostpassword';

		$actual = \Pressbooks\Registration\remove_wp_prefix($scenario3);

		$this->assertEquals($expected, $actual);


	}

}
