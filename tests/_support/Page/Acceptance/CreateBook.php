<?php
namespace Page\Acceptance;

class CreateBook
{
	public static string $URL = '/wp/wp-signup.php';

	/**
	 * Declare UI map for this page here. CSS or XPath allowed.
	 * public static $usernameField = '#username';
	 * public static $formSubmitButton = "#mainForm input[type=submit]";
	 */

	public string $bookWebAddressField = '#blogname';
	public string $bookTitleField = '#blog_title';
	public string $bookPrivacyOptionOn = 'input#blog_public_on';
	public string $bookPrivacyOptionOff = 'input#blog_public_off';
	public string $createButton = '#setupform input[type=submit]';

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

	public function createBook( string $bookWebAddress, string $bookTitle, bool $publicPrivacy = true ): void
	{
		$I = $this->acceptanceTester;

		$I->amOnPage(self::$URL);
		$I->fillField($this->bookWebAddressField, $bookWebAddress);
		$I->fillField($this->bookTitleField, $bookTitle);
		if ( $publicPrivacy ) {
			$I->checkOption($this->bookPrivacyOptionOn);
		} else {
			$I->checkOption($this->bookPrivacyOptionOff);
		}
		$I->click($this->createButton);
	}
}
