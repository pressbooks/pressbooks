<?php

use Pressbooks\Admin\Users\User;
use Pressbooks\Contributors;
use Pressbooks\HtmlParser;

/**
 * @group users
 */
class UserTest extends \WP_UnitTestCase {

    use utilsTrait;

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

    /**
     * @test
     */
    public function it_tests_hooks() {
        global $wp_filter;

		$result = $this->user->init();

		$this->assertInstanceOf( User::class, $result );
		
        $this->user->hooks( $result );

        $this->assertNotEmpty( $wp_filter );
        $this->assertEquals( 10, has_filter( 'pre_user_login', [ $result, 'getUsernameFromBookForm' ] ) );
        $this->assertEquals( 10, has_action( 'pb_new_blog', [ $result, 'setBookAdminAsAuthor' ] ) );
	}

    /**
     * @test
     */
    public function it_test_sanitizeUser() {
		$this->assertEquals( 'test', User::sanitizeUser( 'test' ) );
		$this->assertEquals( 'test', $this->user->sanitizeUser( '(:test:)' ) );
		$this->assertEquals( 'tst1', User::sanitizeUser( 'tst' ) );
		$this->assertEquals( 'tst1', User::sanitizeUser( '(:tst:)' ) );
		$this->assertEquals( 'yo11', User::sanitizeUser( 'yo' ) );
		$this->assertEquals( 'yo11', User::sanitizeUser( '(:yo:)' ) );
		$this->assertEquals( '1111a', User::sanitizeUser( '1111' ) );
		$this->assertEquals( '1a11', User::sanitizeUser( '1' ) );
	}

    /**
     * @test
     */
    public function it_tests_generateUserNameFromEmail() {
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

    /**
     * @test
     */
    public function it_tests_getUsernameFromBookForm() {
        global $current_screen;

        $current_screen = \WP_Screen::get( 'random-screen' );
        
        $_POST['blog']['email'] = 'username@pressbooks.com';
        $_POST['_wpnonce_add-blog'] = wp_create_nonce( 'add-blog' );

        $this->assertEquals( 'username_test', $this->user->getUsernameFromBookForm( 'username_test' ) );

        $current_screen = \WP_Screen::get( 'site-new-network' );
        $current_screen->base = 'site-new-network';
        $current_screen->id = 'site-new-network';

        $this->assertEquals( 'username', $this->user->getUsernameFromBookForm( 'username_test' ) );
        
        unset( $_POST['blog'] );
        unset( $_POST['_wpnonce_add-blog'] );
    }

    /**
     * @test
     */
    public function it_tests_setBookAdminAsAuthor() {
        global $current_screen;

        $current_screen = \WP_Screen::get( 'site-new-network' );
        $current_screen->base = 'site-new-network';
        $current_screen->id = 'site-new-network';

        $admin_user_id = $this->createSuperAdminUser();
        $book_admin_id = $this->factory()->user->create( [ 
            'role' => 'administrator', 
            'user_email' => 'book_admin@pressbooks.com',
            'user_login' => 'book_admin'
        ] );

        add_user_to_blog( $book_admin_id, get_current_blog_id(), 'administrator' );

        $this->user->setBookAdminAsAuthor();

        $this->assertTrue( user_can( $book_admin_id, 'edit_posts' ) );
        
        $metadata_post_id = ( new \Pressbooks\Metadata )->getMetaPostId();

        $authors = (new Contributors)->getArray( $metadata_post_id, 'pb_authors' );
        $user_details = get_userdata( $book_admin_id );
        
        $this->assertCount( 1, $authors );
        $this->assertEquals( $user_details->user_login, $authors[0] );

    }
}