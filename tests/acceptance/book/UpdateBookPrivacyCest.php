<?php

use \Codeception\Util\HttpCode;

class UpdateBookPrivacyCest
{

	public function _before(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
	}

	public function tryToUpdateABookPrivacyFromPublicToPrivate(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
		$createBookPage->createBook( 'samplebook', 'Sample Book', true );

		$I->amOnPage("/samplebook/wp-admin");

		$I->click('Organize');
		$I->seeCheckboxIsChecked('#publicize-panel #blog-public');
		$I->dontSeeCheckboxIsChecked('#publicize-panel #blog-private');

		$I->amOnPage("/samplebook/");
		$I->see('Sample Book');

		$I->amOnPage("/samplebook/wp-admin");
		$I->click('Organize');
		$I->checkOption('#publicize-panel #blog-private');
		$I->waitForText('PRIVATE', 10, '#publicize-panel .publicize-alert');
		$I->logOut();

		$I->amOnPage("/samplebook/");
		$I->see('ACCESS DENIED');
	}

	public function tryToUpdateABookPrivacyFromPrivateToPublic(AcceptanceTester $I, \Page\Acceptance\CreateBook $createBookPage)
	{
		$createBookPage->createBook( 'samplebook', 'Sample Book', false );

		$I->amOnPage("/samplebook/wp-admin");
		$I->click('Organize');
		$I->seeCheckboxIsChecked('#publicize-panel #blog-private');
		$I->dontSeeCheckboxIsChecked('#publicize-panel #blog-public');

		$I->checkOption('#publicize-panel #blog-public');
		$I->waitForText('PUBLIC', 10, '#publicize-panel .publicize-alert');
		$I->logOut();

		$I->amOnPage("/samplebook/");
		$I->see('Sample Book');
		$I->dontSee('ACCESS DENIED');
	}

}
