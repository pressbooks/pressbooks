<?php

use Pressbooks\EventStreams;

/**
 * @group eventstreams
 */
class EventStreamsTest extends \WP_UnitTestCase {
	/**
	 * @var EventStreams
	 */
	protected $eventStreams;

	public function set_up() {
		parent::set_up();
		$this->eventStreams = new EventStreams();
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
	 * @return Generator
	 */
	protected function generatorWithBadEncoding() {
		$resource = fopen('php://memory', 'r');
		yield 1 => $resource;
	}

	/**
	 * @test
	 */
	public function it_emits_with_error(): void {
		ob_start();
		$result = $this->eventStreams->emit($this->generatorWithBadEncoding());
		ob_end_clean();
		$this->assertTrue($result);
		$this->assertCount(1, $this->eventStreams->msgStack);
		$buffer = implode('', $this->eventStreams->msgStack);
		$this->assertStringContainsString('event: message', $buffer);
		$this->assertStringContainsString('data: {"error":"Failed to encode data"}', $buffer);
	}

	/**
	 * @test
	 */
	public function it_streams_user_job_statuses_missing_params(): void {
		$this->eventStreams->msgStack = [];

		$refSetup = new \ReflectionMethod( $this->eventStreams, 'setupHeaders' );
		$refSetup->setAccessible( true );
		$refSetup->invoke( $this->eventStreams );

		$refSetup = new \ReflectionMethod( $this->eventStreams, 'setupHeaders' );
		$refSetup->setAccessible( true );
		$refSetup->invoke( 
			$this->eventStreams,
			[ 'error' => 'Missing book ID for job status stream.' ],
			'error'
		);

		$this->assertCount( 0, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );
	}

	public function test_emitOneTimeError() {
		ob_start();
		$this->eventStreams->emitOneTimeError( 'Nooooooooooooooo, again!' );
		ob_end_clean();
		$this->assertCount( 1, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );

		$this->assertStringContainsString( 'event: message', $buffer );
		$this->assertStringContainsString( 'data: {"action":"complete","error":"Nooooooooooooooo, again!"}', $buffer );
	}

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


	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_ajax_stream_user_exports_jobs_missing_book_id(): void {
		$this->eventStreams->msgStack = [];

		$refSetup = new \ReflectionMethod( $this->eventStreams, 'setupHeaders' );
		$refSetup->setAccessible( true );
		$refSetup->invoke( $this->eventStreams );

		$refEmit = new \ReflectionMethod( $this->eventStreams, 'emitMessage' );
		$refEmit->setAccessible( true );
		$refEmit->invoke(
			$this->eventStreams,
			[ 'error' => 'Missing book ID for job status stream.' ],
			'error'
		);

		$this->assertCount( 1, $this->eventStreams->msgStack );
		$buffer = implode( '', $this->eventStreams->msgStack );
		$this->assertStringContainsString( 'event: error', $buffer );
		$this->assertStringContainsString( 'data: {"error":"Missing book ID for job status stream."}', $buffer );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_ajax_stream_user_exports_jobs_calls_streamUserJobStatuses(): void {
		$book_id = 123;
		$_GET['book_id'] = $book_id;
		$_REQUEST['nonce'] = wp_create_nonce( 'pressbooks_user_export_feed' );
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->eventStreams = new class extends \Pressbooks\EventStreams {
			public $invoked = false;
			public function streamUserJobStatuses( int $book_id_param, int $user_id_param ): void {
				$this->invoked = true;
			}
		};

		$refSetup = new \ReflectionMethod( $this->eventStreams, 'setupHeaders' );
		$refSetup->setAccessible( true );
		$refSetup->invoke( $this->eventStreams );

		$this->eventStreams->streamUserJobStatuses( 123, $user_id );

		$this->assertTrue( $this->eventStreams->invoked, 'Expected streamUserJobStatuses to be called.' );
	}

}
