<?php

class Interactive_PhetTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Interactive\Phet
	 * @group interactivecontent
	 */
	protected $phet;

	/**
	 * @group interactivecontent
	 */
	public function set_up() {
		parent::set_up();
		$blade = \Pressbooks\Container::get( 'Blade' );
		$this->phet = new \Pressbooks\Interactive\Phet( $blade );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_registerEmbedHandlerForWeb() {
		global $wp_embed;
		global $id;
		$id = 2;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForWeb();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_registerEmbedHandlerForExport() {
		global $wp_embed;
		global $id;
		$id = 2;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForExport();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_applyEmbedFilterForWeb() {
		$iframe_html = $this->phet->applyEmbedFilterForWeb(
			"<iframe src='https://pressbooks.com'></iframe>",
			[],
			'https://pressbooks.com',
			''
		);
		$this->assertContains(
			"<iframe id=\"iframe-phet-1\" src=\"https://phet.colorado.edu/sims/html/i\" width=\"800\" height=\"600\" scrolling=\"no\" allowfullscreen></iframe>",
			$iframe_html
		);
	}

	/**
	 * @group interactivecontent
	 */
	public function test_applyEmbedFilterForExport() {
		$iframe_html = $this->phet->applyEmbedFilterForExport(
			"<iframe src='https://pressbooks.com'></iframe>",
			[],
			'https://pressbooks.com',
			''
		);
		$this->assertContains(
			'<div class="textbox interactive-content interactive-content--oembed">',
			$iframe_html
		);
		$this->assertContains(
			'One or more interactive elements has been excluded from this version of the text. You can view them online here',
			$iframe_html
		);
		$this->assertContains(
			'<a href="#iframe-phet-1" title="">#iframe-phet-1</a>',
			$iframe_html
		);
	}

}
