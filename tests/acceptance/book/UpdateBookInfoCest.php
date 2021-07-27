<?php

use \Codeception\Util\HttpCode;

class UpdateBookInfoCest
{
	public $bookURL = 'testbookinfo';

	public $bookTitle = 'Test Book Info';

    public $saveButton = '#submitpost input[type=submit]';

	public function _before(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, $this->bookTitle, true );

		$I->amOnPage( "$this->bookURL/wp-admin" );
		$I->click( 'Book Info' );
	}

	public function tryToUpdateBookTitle(AcceptanceTester $I)
	{
		$I->seeInField( $titleField = '#pb_title', $this->bookTitle );

		$I->fillField( $titleField, $newBookTitle = 'Updated Test Book Info' );

		$I->click( $this->saveButton );

		$I->see( 'Book Information updated.' );
        $I->seeInField( $titleField, $newBookTitle );

		$I->amOnPage( $this->bookURL );

		$I->see( $newBookTitle );
	}

}
