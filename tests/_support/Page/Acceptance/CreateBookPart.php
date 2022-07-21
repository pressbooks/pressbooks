<?php
namespace Page\Acceptance;

class CreateBookPart {

	public static string $URL = '/wp-admin/post-new.php?post_type=part';

	protected string $titleField = '#title';
	protected string $saveButton = '#submitpost input[type=submit]';

	public function __construct( protected \AcceptanceTester $acceptanceTester ) {  }

	public function createPart( string $bookURL, string $title ): void {
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL );
		$I->fillField( $this->titleField, $title );
		$I->click( $this->saveButton );
	}
}
