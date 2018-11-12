<?php

class EventStreamsTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\EventStreams
	 */
	protected $eventStreams;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->eventStreams = new \Pressbooks\EventStreams();
	}


	/**
	 * @return Generator
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
	 */
	protected function generatorWithError() {
		yield 1 => 'a';
		throw new \Exception( 'Nooooooooooooooo!' );
	}

	/**
	 *
	 */
	public function test_emit() {
		ob_start();
		$result = $this->eventStreams->emit( $this->generator() );
		$buffer = ob_get_clean();
		$this->assertTrue( $result );
		$this->assertContains( 'event: message', $buffer );
		$this->assertContains( 'data: {"action":"updateStatusBar","percentage":1,"info":"a"}', $buffer );
		$this->assertContains( 'data: {"action":"updateStatusBar","percentage":100,"info":"z"}', $buffer );
		$this->assertContains( 'data: {"action":"updateStatusBar","percentage":50,"info":"incrementing percentage is only a convention"}', $buffer );
		$this->assertContains( 'data: {"action":"complete","error":false}', $buffer );

		ob_start();
		$result = $this->eventStreams->emit( $this->generatorWithError() );
		$buffer = ob_get_clean();
		$this->assertFalse( $result );
		$this->assertContains( 'event: message', $buffer );
		$this->assertContains( 'data: {"action":"updateStatusBar","percentage":1,"info":"a"}', $buffer );
		$this->assertContains( 'data: {"action":"complete","error":"Nooooooooooooooo!"}', $buffer );
	}

	public function test_emitOneTimeError() {
		ob_start();
		$this->eventStreams->emitOneTimeError( 'Nooooooooooooooo, again!' );
		$buffer = ob_get_clean();

		$this->assertContains( 'event: message', $buffer );
		$this->assertContains( 'data: {"action":"complete","error":"Nooooooooooooooo, again!"}', $buffer );
	}

}