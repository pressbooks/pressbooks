<?php

use \Codeception\Util\HttpCode;
use Page\Acceptance\CreateBook;
use Page\Acceptance\CreateBookPart;

class CreatePartCest
{
	public $bookURL = 'testbookpart';

	public function _before(AcceptanceTester $I, CreateBook $createBookPage)
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, 'Test Book Part', true );
	}

	public function tryToCreateBookPart(AcceptanceTester $I, CreateBookPart $createBookPartPage)
	{
		$I->amOnPage( "$this->bookURL/wp-admin/admin.php?page=pb_organize" );
		$I->dontSee( $partName = 'Part '.mt_rand() );

		$createBookPartPage->createPart( $this->bookURL, $partName );

		$I->amOnPage( "$this->bookURL/wp-admin/admin.php?page=pb_organize" );
		$I->see( $partName );
	}

}
