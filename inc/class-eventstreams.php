<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Utility\getset;

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
	 * @return bool
	 */
	public function emit( \Generator $generator ) {
		$this->setupHeaders();

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

		if ( $complete['error'] === false ) {
			// No errors
			return true;
		} else {
			// Something went wrong
			return false;
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
	 * @param string $error
	 */
	public function emitOneTimeError( $error ) {
		$this->setupHeaders();
		$this->emitMessage(
			[
				'action' => 'complete',
				'error' => $error,
			]
		);
	}

	/**
	 *
	 */
	protected function setupHeaders() {
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
	}

	/**
	 * Clone a book
	 */
	public function cloneBook() {

		check_admin_referer( 'pb-cloner' );

		$source_url = $_GET['sourceUrl'] ?? '';

		$target_url = \Pressbooks\Cloner::validateNewBookName( $_GET['targetUrl'] );
		if ( is_wp_error( $target_url ) ) {
			$this->emitOneTimeError( $target_url->get_error_message() );
			return;
		}

		$target_title = $_GET['targetTitle'] ?? '';

		$cloner = new \Pressbooks\Cloner( $source_url, $target_url, $target_title );
		$everything_ok = $this->emit( $cloner->cloneBookGenerator() );

		if ( $everything_ok ) {
			$cloned_items = $cloner->getClonedItems();
			$pb_notices = sprintf(
				__( 'Cloning succeeded! Cloned %1$s, %2$s, %3$s, %4$s, %5$s, %6$s, and %7$s to %8$s.', 'pressbooks' ),
				sprintf( _n( '%s term', '%s terms', count( getset( $cloned_items, 'terms', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'terms', [] ) ) ),
				sprintf( _n( '%s front matter', '%s front matter', count( getset( $cloned_items, 'front-matter', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'front-matter', [] ) ) ),
				sprintf( _n( '%s part', '%s parts', count( getset( $cloned_items, 'parts', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'parts', [] ) ) ),
				sprintf( _n( '%s chapter', '%s chapters', count( getset( $cloned_items, 'chapters', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'chapters', [] ) ) ),
				sprintf( _n( '%s back matter', '%s back matter', count( getset( $cloned_items, 'back-matter', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'back-matter', [] ) ) ),
				sprintf( _n( '%s media attachment', '%s media attachments', count( getset( $cloned_items, 'media', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'media', [] ) ) ),
				sprintf( _n( '%s glossary term', '%s glossary terms', count( getset( $cloned_items, 'glossary', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'glossary', [] ) ) ),
				sprintf( '<a href="%1$s"><em>%2$s</em></a>', trailingslashit( $cloner->getTargetBookUrl() ) . 'wp-admin/', $cloner->getTargetBookTitle() )
			);
			set_transient( 'pb_notices' . get_current_user_id(), $pb_notices, 5 * MINUTE_IN_SECONDS );
		}

		// Tell the browser to stop reconnecting.
		status_header( 204 );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit; // Short circuit wp_die(0);
		}
	}

}
