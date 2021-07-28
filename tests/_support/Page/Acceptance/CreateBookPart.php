<?php
namespace Page\Acceptance;

class CreateBookPart
{
	public static $URL = '/wp-admin/post-new.php?post_type=part';

	protected $partTitleField = '#title';
	protected $saveButton = '#submitpost input[type=submit]';

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
