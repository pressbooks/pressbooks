<?php
namespace Page\Acceptance;

use AcceptanceTester;

class BookInfo
{
	public static $URL = '/wp-admin/post.php?post=16&action=edit';

	protected $titleField = '#pb_title';
	protected $saveButton = '#submitpost input[type=submit]';

	/**
	 * @var AcceptanceTester;
	 */
	protected $acceptanceTester;

	public function __construct(AcceptanceTester $I)
	{
		$this->acceptanceTester = $I;
	}

	public function updateBookTitle(string $bookURL, string $newTitle): void
	{
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL);

		$I->dontSeeInField( $this->titleField, $newTitle );
		$I->fillField( $this->titleField, $newTitle );

		$I->click( $this->saveButton );
	}
}
