<?php
namespace Page\Acceptance;

class BookInfo
{
	public static $URL = '/wp-admin/post.php?post=16&action=edit';

	protected $bookTitleField = '#pb_title';
	protected $saveButton = '#submitpost input[type=submit]';

	/**
	 * @var \AcceptanceTester;
	 */
	protected $acceptanceTester;

	public function __construct(\AcceptanceTester $I)
	{
		$this->acceptanceTester = $I;
	}

	public function updateBookTitle(string $bookURL, string $newTitle)
	{
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL);

		$I->dontSeeInField( $this->bookTitleField, $newTitle );
		$I->fillField($this->bookTitleField, $newTitle);

		$I->click($this->saveButton);
	}
}
