<?php
namespace Page\Acceptance;

class Books
{
	public static string $URL = '/wp-admin/my-sites.php';

	/**
	 * Declare UI map for this page here. CSS or XPath allowed.
	 * public static $usernameField = '#username';
	 * public static $formSubmitButton = "#mainForm input[type=submit]";
	 */

	/**
	 * Basic route example for your current URL
	 * You can append any additional parameter to URL
	 * and use it in tests like: Page\Edit::route('/123-post');
	 */
	public static function route( string $param ): string
	{
		return static::$URL.$param;
	}

	public function __construct( protected \AcceptanceTester $acceptanceTester )
	{
	}
}
