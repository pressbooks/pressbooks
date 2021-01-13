<?php
namespace Page\Acceptance;

class CreateBook
{
    // include url of current page
    public static $URL = '/wp/wp-signup.php';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

	public $bookWebAddressField = '#blogname';
	public $bookTitleField = '#blog_title';
	public $bookPrivacyOptionOn = 'input#blog_public_on';
	public $bookPrivacyOptionOff = 'input#blog_public_off';
	public $createButton = '#setupform input[type=submit]';

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

	public function createBook(string $bookWebAddress, string $bookTitle, bool $publicPrivacy = true)
	{
		$I = $this->acceptanceTester;

		$I->amOnPage(self::$URL);
		$I->fillField($this->bookWebAddressField, $bookWebAddress);
		$I->fillField($this->bookTitleField, $bookTitle);
//		if ( $publicPrivacy ) {
//			$I->checkOption($this->bookPrivacyOptionOn);
//		} else {
//			$I->checkOption($this->bookPrivacyOptionOff);
//		}
		$I->click($this->createButton);
	}

}
