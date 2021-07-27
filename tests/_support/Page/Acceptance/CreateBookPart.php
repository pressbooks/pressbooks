<?php
namespace Page\Acceptance;

class CreateBookPart
{
    // include url of current page
    public static $URL = '/wp-admin/post-new.php?post_type=part';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    public $partTitleField = '#title';
    public $saveButton = '#submitpost input[type=submit]';

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function createPart(string $bookURL, string $partTitle)
    {
        $I = $this->acceptanceTester;

        $I->amOnPage( $bookURL . self::$URL);
        $I->fillField($this->partTitleField, $partTitle);
        $I->click($this->saveButton);
    }

}
