<?php

class EventStreamsTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\EventStreams
	 * @group eventstreams
	 */
	protected $eventStreams;

	/**
	 * @group eventstreams
	 */
	public function set_up() {
		parent::set_up();
		$this->eventStreams = new \Pressbooks\EventStreams();
	}


	/**
	 * @return Generator
	 * @group eventstreams
	 */
	protected function generator() {
		yield 1 => 'a';
		yield 2 => 'b';
		yield 3 => 'c';
		// ...
		yield 100 => 'z';
		yield 99 => 'nothing special';
		yield 50 => 'incrementing percentage is only a convention';
		yield 999 => 'that we can fudge';
	}

	/**
	 * @return Generator
	 * @throws Exception
	 * @group eventstreams
	 */
	protected function generatorWithError() {
		yield 1 => 'a';
		throw new \Exception( 'Nooooooooooooooo!' );
	}

	/**
	 * @group eventstreams
	 */
	public function test_emit() {
		ob_start();
		$result = $this->eventStreams->emit( $this->generator(), true );
		ob_end_clean();
		$this->assertCount( 8, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );
		$this->assertTrue( $result );
		$this->assertStringContainsString( 'event: message', $buffer );
		$this->assertStringContainsString( 'data: {"action":"updateStatusBar","percentage":1,"info":"a"}', $buffer );
		$this->assertStringContainsString( 'data: {"action":"updateStatusBar","percentage":100,"info":"z"}', $buffer );
		$this->assertStringContainsString( 'data: {"action":"updateStatusBar","percentage":50,"info":"incrementing percentage is only a convention"}', $buffer );
		$this->assertStringContainsString( 'data: {"action":"complete","error":false}', $buffer );

		ob_start();
		$result = $this->eventStreams->emit( $this->generatorWithError() );
		ob_end_clean();
		$this->assertCount( 2, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );
		$this->assertFalse( $result );
		$this->assertStringContainsString( 'event: message', $buffer );
		$this->assertStringContainsString( 'data: {"action":"updateStatusBar","percentage":1,"info":"a"}', $buffer );
		$this->assertStringContainsString( 'data: {"action":"complete","error":"Nooooooooooooooo!"}', $buffer );
	}

	/**
	 * @group eventstreams
	 */
	public function test_emitOneTimeError() {
		ob_start();
		$this->eventStreams->emitOneTimeError( 'Nooooooooooooooo, again!' );
		ob_end_clean();
		$this->assertCount( 1, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );

		$this->assertStringContainsString( 'event: message', $buffer );
		$this->assertStringContainsString( 'data: {"action":"complete","error":"Nooooooooooooooo, again!"}', $buffer );
	}

	/**
	 * @group eventstreams
	 */
	public function test_emitComplete() {
		ob_start();
		$this->eventStreams->emitComplete();
		ob_end_clean();
		$this->assertCount( 1, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );
		$this->assertStringContainsString( 'data: {"action":"complete","error":false}', $buffer );
	}

	public function test_importBook_noChaptersError() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-import' );
		set_transient( 'pressbooks_current_import_POST', [ 'chapters' => [] ] );
		ob_start();
		$this->eventStreams->importBook();
		ob_end_clean();
		$this->assertCount( 1, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );

		$this->assertStringContainsString( 'event: message', $buffer );
		$this->assertStringContainsString( 'data: {"action":"complete","error":"No chapters were selected for import."}', $buffer );
	}

}
