<?php

use Pressbooks\Admin\Users\User;
use Pressbooks\HtmlParser;

/**
 * @group users
 */
class UserTest extends \WP_UnitTestCase {
    /**
	 * @var User
	 */
	protected $user;

    /**
	 * Test setup
	 */
	public function set_up() {
		parent::set_up();
		$this->user = new User();
	}

    public function test_hooks() {
        global $wp_filter;

		$result = $this->user->init();

		$this->assertInstanceOf( User::class, $result );
		
        $this->user->hooks( $result );
		
        $this->assertNotEmpty( $wp_filter );
		$this->assertEquals( 10, has_filter( 'pre_user_login', [ $result, 'sanitizeUsernameByEmail' ] ) );
		$this->assertEquals( 10, has_action( 'pb_new_blog', [ $result, 'setBookAdminAsAuthor' ] ) );
	}

    public function test_sanitizeUser() {
		$this->assertEquals( 'test', User::sanitizeUser( 'test' ) );
		$this->assertEquals( 'test', $this->user->sanitizeUser( '(:test:)' ) );
		$this->assertEquals( 'tst1', User::sanitizeUser( 'tst' ) );
		$this->assertEquals( 'tst1', User::sanitizeUser( '(:tst:)' ) );
		$this->assertEquals( 'yo11', User::sanitizeUser( 'yo' ) );
		$this->assertEquals( 'yo11', User::sanitizeUser( '(:yo:)' ) );
		$this->assertEquals( '1111a', User::sanitizeUser( '1111' ) );
		$this->assertEquals( '1a11', User::sanitizeUser( '1' ) );
	}

    public function test_generateUserNameFromEmail() {
		$invalid_email = 'invalid@email@.com';
		$invalid_user_name = User::generateUserNameFromEmail( $invalid_email );
		$valid_email = 'validemail@pressbooks.com';
		$valid_user_data = User::generateUserNameFromEmail( $valid_email );

		$this->assertInstanceOf( WP_Error::class, $invalid_user_name['errors'] );
		$this->assertEquals( 'validemail', $valid_user_data['user_name'] );
		$this->assertEquals( $valid_email, $valid_user_data['user_email'] );
		$this->assertFalse( $valid_user_data['errors']->has_errors() );

		// Persist user in order to test user login deduplication
		$this->factory()->user->create(
			[
				'user_login' => 'validemail',
				'user_email' => 'validemail@pressbooks.com',
			]
		);
		$valid_email_existing_username = 'validemail@gmail.com';
		$valid_user_data_dedup = User::generateUserNameFromEmail( $valid_email_existing_username );

		$this->assertEquals( 'validemail1', $valid_user_data_dedup['user_name'] );
		$this->assertEquals( $valid_email_existing_username, $valid_user_data_dedup['user_email'] );
		$this->assertFalse( $valid_user_data_dedup['errors']->has_errors() );
	}

}