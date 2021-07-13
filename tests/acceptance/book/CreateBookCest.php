<?php

use \Codeception\Util\HttpCode;

class CreateBookCest
{

	public function _before(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
	}

	/**
	 * @example { "blogPublic": "1" }
	 * @example { "blogPublic": "0" }
	 */
	public function tryToCreateABook(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage, \Codeception\Example $example)
	{
		$bookWebAddress = "book".rand();
		$bookTitle = "$bookWebAddress Title";
		$createBookPage->createBook($bookWebAddress, $bookTitle, (bool) $example['blogPublic']);
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
