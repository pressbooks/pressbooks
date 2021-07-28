<?php

use \Codeception\Util\HttpCode;
use Page\Acceptance\CreateBook;
use Page\Acceptance\CreateBookPart;

class CreatePartCest
{
	public $bookURL = 'samplebook';

	public function _before(AcceptanceTester $I, CreateBook $createBookPage)
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, 'Sample Book' );

		$I->amOnPage( "$this->bookURL/wp-admin" );
	}

	public function tryToCreateBookPart(AcceptanceTester $I, CreateBookPart $createBookPartPage)
	{
		$I->click('Organize');

		$I->dontSee( $partName = 'Part '.mt_rand() );

		$createBookPartPage->createPart( $this->bookURL, $partName );

		$I->see( 'Part published. View part' );
		$I->click('Organize');
		$I->see( $partName );
	}

}
