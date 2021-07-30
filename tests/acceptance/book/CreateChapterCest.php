<?php

use Page\Acceptance\CreateBook;
use Page\Acceptance\CreateBookChapter;

class CreateChapterCest
{
	public $bookURL = 'samplebook';

	public function _before(AcceptanceTester $I, CreateBook $createBookPage): void
	{
		$I->loginAsAdmin();

		$createBookPage->createBook( $this->bookURL, 'Sample Book' );

		$I->amOnPage( "$this->bookURL/wp-admin" );
	}

	public function tryToCreateABookChapter(AcceptanceTester $I, CreateBookChapter $createBookChapterPage): void
	{
		$I->amOnPage( $this->bookURL);
		$I->dontSee( $chapterTitle = 'Foobar' );

		$createBookChapterPage->createChapter(
			$this->bookURL,
			$chapterTitle,
			$chapterContent = 'Here is the chapter content'
		);

		$I->see( 'Chapter published. View chapter' );
		$I->click( '#wp-admin-bar-view a' ); // View chapter link

		$I->see( $chapterTitle );
		$I->see( $chapterContent );
	}
}
