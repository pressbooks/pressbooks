<?php
namespace Page\Acceptance;

class Login {

	public static string $URL = '/wp/wp-login.php';

	/**
	 * Declare UI map for this page here. CSS or XPath allowed.
	 * public static $usernameField = '#username';
	 * public static $formSubmitButton = "#mainForm input[type=submit]";
	 */

	public string $usernameField = '#user_login';
	public string $passwordField = '#user_pass';
	public string $loginButton = '#wp-submit';

	/**
	 * Basic route example for your current URL
	 * You can append any additional parameter to URL
	 * and use it in tests like: Page\Edit::route('/123-post');
	 */
	public static function route( string $param ): string {
		return static::$URL . $param;
	}

	public function __construct( protected \AcceptanceTester $acceptanceTester ) {  }

	public function login( string $name, string $password ): void {
		$I = $this->acceptanceTester;

		$I->amOnPage( self::$URL );
		$I->fillField( $this->usernameField, $name );
		$I->fillField( $this->passwordField, $password );
		$I->click( $this->loginButton );
	}
}
