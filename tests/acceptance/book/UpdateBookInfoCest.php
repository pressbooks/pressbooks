<?php

use Page\Acceptance\BookInfo;
use Page\Acceptance\CreateBook;

class UpdateBookInfoCest {

	public $bookURL = 'samplebook';

	public $bookTitle = 'Sample Book';

	public $saveButton = '#submitpost input[type=submit]';

	public function _before( AcceptanceTester $I, CreateBook $createBookPage ): void {
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, $this->bookTitle );
	}

	public function tryToUpdateBookTitle( AcceptanceTester $I, BookInfo $bookInfoPage ): void {
		$I->amOnPage( $this->bookURL );
		$I->see( $this->bookTitle );
		$I->dontSee( $newBookTitle = 'Updated Book Title' );

		$bookInfoPage->updateBookTitle( $this->bookURL, $newBookTitle );

		$I->see( 'Book Information updated.' );

		$I->amOnPage( $this->bookURL );
		$I->see( $newBookTitle );
		$I->dontSee( $this->bookTitle );
	}
}
