<?php

use Codeception\Actor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends Actor
{
	use _generated\AcceptanceTesterActions;

	public function fillTinyMceEditorById( $id, $content ) {
		$this->fillTinyMceEditor( 'id', $id, $content );
	}

	public function fillTinyMceEditorByName( $name, $content ) {
		$this->fillTinyMceEditor( 'name', $name, $content );
	}

	private function fillTinyMceEditor( $attribute, $value, $content ) {
		$this->fillRteEditor(
			WebDriverBy::xpath(
				'//textarea[@' . $attribute . '=\'' . $value . '\']/../div[contains(@class, \'mce-tinymce\')]//iframe'
			),
			$content
		);
	}

	private function fillRteEditor( $selector, $content ) {
		$this->executeInSelenium(
			function ( RemoteWebDriver $webDriver ) use ( $selector, $content ) {
				$webDriver->switchTo()->frame(
					$webDriver->findElement( $selector )
				);

				$webDriver->executeScript(
					'arguments[0].innerHTML = "' . addslashes($content) . '"',
					[ $webDriver->findElement( WebDriverBy::tagName( 'body' ) ) ]
				);

				$webDriver->switchTo()->defaultContent();
			});
	}
}
