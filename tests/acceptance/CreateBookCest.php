<?php

use \Codeception\Util\HttpCode;

class CreateBookCest
{

	public function _before(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
	}

	public function tryToCreateABook(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
    	$bookWebAddress = "book".rand();
    	$bookTitle = "$bookWebAddress Title";
		$createBookPage->createBook($bookWebAddress, $bookTitle, true);
		$I->amOnPage("/$bookWebAddress/wp-admin");
		$I->see($bookTitle);
	}

	public function tryToCreateABookWithShortWebAddress(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
		$bookWebAddress = rand(0,999);
		$bookTitle = "$bookWebAddress Title";
		$createBookPage->createBook($bookWebAddress, $bookTitle, true);
		$I->see('Site name must be at least 4 characters.');
		$I->amOnPage("/wp-admin/my-sites.php");
		$I->dontSee($bookTitle);
	}

}
