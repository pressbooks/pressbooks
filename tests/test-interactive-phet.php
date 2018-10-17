<?php

class Interactive_PhetTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Interactive\Phet
	 */
	protected $phet;

	public function setUp() {
		parent::setUp();
		$blade = \Pressbooks\Container::get( 'Blade' );
		$this->phet = new \Pressbooks\Interactive\Phet( $blade );
	}

	public function test_registerEmbedHandlerForWeb() {
		global $wp_embed;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForWeb();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

	public function test_registerEmbedHandlerForExport() {
		global $wp_embed;
		unset( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
		$this->phet->registerEmbedHandlerForExport();
		$this->assertNotEmpty( $wp_embed->handlers[10][ $this->phet::EMBED_ID ] );
	}

}
