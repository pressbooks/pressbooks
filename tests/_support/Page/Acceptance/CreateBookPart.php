<?php
namespace Page\Acceptance;

use AcceptanceTester;

class CreateBookPart
{
	public static $URL = '/wp-admin/post-new.php?post_type=part';

	protected $titleField = '#title';
	protected $saveButton = '#submitpost input[type=submit]';

	/**
	 * @var AcceptanceTester;
	 */
	protected $acceptanceTester;

	public function __construct( AcceptanceTester $I )
	{
		$this->acceptanceTester = $I;
	}

	public function createPart( string $bookURL, string $title ): void
	{
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL );
		$I->fillField( $this->titleField, $title );
		$I->click( $this->saveButton );
	}
}
