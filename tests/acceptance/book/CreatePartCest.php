<?php

use Page\Acceptance\CreateBook;
use Page\Acceptance\CreateBookPart;

class CreatePartCest
{
	public $bookURL = 'samplebook';

	public function _before( AcceptanceTester $I, CreateBook $createBookPage ): void
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, 'Sample Book' );

		$I->amOnPage( "$this->bookURL/wp-admin" );
	}

	public function tryToCreateABookPart( AcceptanceTester $I, CreateBookPart $createBookPartPage ): void
	{
		$I->click( 'Organize' );

		$I->dontSee( $partName = 'Section A' );

		$createBookPartPage->createPart( $this->bookURL, $partName );

		$I->see( 'Part published. View part' );
		$I->click( 'Organize' );
		$I->see( $partName );
	}
}
