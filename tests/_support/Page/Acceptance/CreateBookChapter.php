<?php
namespace Page\Acceptance;

class CreateBookChapter {

	public static string $URL = '/wp-admin/post-new.php?post_type=chapter';

	protected string $titleField = '#title';
	protected string $contentField = '#content';
	protected string $saveButton = '#publishing-action input[type=submit]';

	public function __construct( protected \AcceptanceTester $acceptanceTester ) {  }

	public function createChapter( string $bookURL, string $title, string $content = null ): void {
		$I = $this->acceptanceTester;

		$I->amOnPage( $bookURL . self::$URL );
		$I->fillField( $this->titleField, $title );
		$I->fillField( $this->contentField, $content );
		// Figure it out how to enable tinymce when running tests
		// $I->fillTinyMceEditorByName( 'content', $content );
		$I->click( $this->saveButton );
	}
}
