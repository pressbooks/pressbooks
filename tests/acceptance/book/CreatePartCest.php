<?php

use \Codeception\Util\HttpCode;

class CreatePartCest
{
	public $bookURL = 'testbookpart';

	public function _before(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, 'Test Book Part', true );
	}

	public function tryToCreateBookPart(AcceptanceTester $I, \Page\Acceptance\CreateBookPart $createBookPartPage)
	{
		$I->amOnPage( "$this->bookURL/wp-admin/admin.php?page=pb_organize" );
		$I->dontSee( $partName = 'Part '.mt_rand() );

		$createBookPartPage->createPart( $this->bookURL, $partName );

		$I->amOnPage( "$this->bookURL/wp-admin/admin.php?page=pb_organize" );
		$I->see( $partName );
	}

}
