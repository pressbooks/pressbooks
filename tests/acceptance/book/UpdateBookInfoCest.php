<?php

use \Codeception\Util\HttpCode;
use Page\Acceptance\BookInfo;
use Page\Acceptance\CreateBook;

class UpdateBookInfoCest
{
	public $bookURL = 'testbookinfo';

	public $bookTitle = 'Test Book Info';

	public $saveButton = '#submitpost input[type=submit]';

	public function _before(AcceptanceTester $I, CreateBook $createBookPage)
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, $this->bookTitle, true );

		$I->amOnPage( "$this->bookURL/wp-admin" );
		$I->click( 'Book Info' );
	}

	public function tryToUpdateBookTitle(AcceptanceTester $I)
	{
		$I->dontSeeInField( $field = '#pb_title', $bookTitle = 'Updated Test Book Info' );

		$I->fillField( $field, $bookTitle );
		$I->click( $this->saveButton );

		$I->see( 'Book Information updated.' );
		$I->seeInField( $field, $bookTitle );

		$I->amOnPage( $this->bookURL );
		$I->see( $bookTitle );
	}
}
