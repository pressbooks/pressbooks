<?php
namespace Page\Acceptance;

class Login
{
	// include url of current page
	public static $URL = '/wp/wp-login.php';

	/**
	 * Declare UI map for this page here. CSS or XPath allowed.
	 * public static $usernameField = '#username';
	 * public static $formSubmitButton = "#mainForm input[type=submit]";
	 */

	public $usernameField = '#user_login';
	public $passwordField = '#user_pass';
	public $loginButton = '#wp-submit';

	/**
	 * Basic route example for your current URL
	 * You can append any additional parameter to URL
	 * and use it in tests like: Page\Edit::route('/123-post');
	 */
	public static function route($param)
	{
		return static::$URL.$param;
	}

	/**
	 * @var \AcceptanceTester;
	 */
	protected $acceptanceTester;

	public function __construct(\AcceptanceTester $I)
	{
		$this->acceptanceTester = $I;
	}

	public function login(string $name, string $password)
	{
		$I = $this->acceptanceTester;

		$I->amOnPage(self::$URL);
		$I->fillField($this->usernameField, $name);
		$I->fillField($this->passwordField, $password);
		$I->click($this->loginButton);
	}

}
