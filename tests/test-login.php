<?php

class Login extends \WP_UnitTestCase {

	/**
	 * @group Login
	 */
	public function test_if_wp_prefix_is_removed() {

		$scenario1 = 'https://institution.pressbooks.pub/wp/booktitle/wp-login.php?action=lostpassword';
		$expected = 'https://institution.pressbooks.pub/booktitle/wp-login.php?action=lostpassword';

		$actual = \Pressbooks\Registration\remove_wp_prefix($scenario1);

		$this->assertEquals($expected, $actual);

		$scenario = 'https://institution.pressbooks.pub/wp/wp-login.php?action=lostpassword';
		$expected = 'https://institution.pressbooks.pub/wp-login.php?action=lostpassword';

		$actual = \Pressbooks\Registration\remove_wp_prefix($scenario);

		$this->assertEquals($expected, $actual);


	}

}
