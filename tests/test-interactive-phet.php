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
	public function setUp() {
		parent::setUp();
		$blade = \Pressbooks\Container::get( 'Blade' );
		$this->phet = new \Pressbooks\Interactive\Phet( $blade );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_registerEmbedHandlerForWeb() {
		global $wp_embed;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForWeb();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_registerEmbedHandlerForExport() {
		global $wp_embed;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForExport();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

}
