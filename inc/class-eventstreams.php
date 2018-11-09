<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class EventStreams {

	/**
	 * @var EventStreams
	 */
	private static $instance = null;

	/**
	 * @return EventStreams
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param EventStreams $obj
	 */
	static public function hooks( EventStreams $obj ) {
		add_action( 'wp_ajax_clone-book', [ $obj, 'cloneBook' ] );
	}

	/**
	 */
	public function __construct() {
	}

	/**
	 * This method accepts a generator that yields a key/value pair
	 * The key is an integer between 1-100 that represents percentage completed
	 * The value is a string of information for the user
	 * Emits event-stream responses (SSE)
	 *
	 * @param \Generator $generator
	 */
	public function emit( \Generator $generator ) {

		// Turn off PHP output compression
		ini_set( 'output_buffering', 'off' );
		ini_set( 'zlib.output_compression', false );
		if ( $GLOBALS['is_nginx'] ) {
			header( 'X-Accel-Buffering: no' );
			header( 'Content-Encoding: none' );
		}

		// Start the event stream
		header( 'Content-Type: text/event-stream' );

		// 2KB padding for IE
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

		// Time to run the generator
		ignore_user_abort( true );
		set_time_limit( apply_filters( 'pb_set_time_limit', 0, 'sse' ) );

		// Ensure we're not buffered
		wp_ob_end_flush_all();
		flush();

		$complete = [
			'action' => 'complete',
			'error' => false,
		];

		try {
			foreach ( $generator as $percentage => $info ) {
				$data = [
					'action' => 'updateStatusBar',
					'percentage' => $percentage,
					'info' => $info,
				];
				$this->emitMessage( $data );
			}
		} catch ( \Exception $e ) {
			$complete['error'] = $e->getMessage();
		}

		flush();
		$this->emitMessage( $complete );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

	/**
	 * Emit a Server-Sent Events message.
	 *
	 * @param mixed $data Data to be JSON-encoded and sent in the message.
	 */
	public function emitMessage( $data ) {
		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n"; // Extra padding.
		flush();
	}

	/**
	 * Clone a book
	 */
	public function cloneBook() {

		check_admin_referer( 'pb-cloner' );

		$source_url = $_GET['sourceUrl'];
		$target_url = $_GET['targetUrl'];
		$target_title = $_GET['targetTitle'];

		$cloner = new \Pressbooks\Cloner( $source_url, $target_url, $target_title );
		$this->emit( $cloner->cloneBookGenerator() );
	}

}
