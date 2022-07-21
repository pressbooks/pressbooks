<?php
namespace Page\Acceptance;

class BookInfo
{
	public static string $URL = '/wp-admin/post.php?post=16&action=edit';

	protected string $titleField = '#pb_title';

	protected string $saveButton = '#submitpost input[type=submit]';

	public function __construct( protected \AcceptanceTester $acceptanceTester )
	{
	}

	public function updateBookTitle( string $bookURL, string $newTitle ): void
	{
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL );

		$I->dontSeeInField( $this->titleField, $newTitle );
		$I->fillField( $this->titleField, $newTitle );

		$I->click( $this->saveButton );
	}
}
