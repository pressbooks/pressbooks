<?php

class LoginCest
{
	public function _before(AcceptanceTester $I)
	{
	}

	/*
	 * @group login
	 */
	public function tryToLoginAsAdminInLoginPage(AcceptanceTester $I, \Page\Acceptance\Login $loginPage)
	{
		$loginPage->login($_ENV['TEST_SITE_ADMIN_USERNAME'], $_ENV['TEST_SITE_ADMIN_PASSWORD']);
		$I->dontSee('Unknown username. Check again or try your email address.');
		$I->see( $_ENV['TEST_SITE_ADMIN_USERNAME'] );
		$I->see('Dashboard');
	}

	/*
	 * @group login
	 */
	public function tryToLoginWithWrongCredentialsInLoginPage(AcceptanceTester $I, \Page\Acceptance\Login $loginPage)
	{
		$loginPage->login('billevans', 'debby');
		$I->see('Unknown username. Check again or try your email address.');
	}

}
