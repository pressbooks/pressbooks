<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv3+
 */

namespace Pressbooks\Modules\SearchAndReplace;

abstract class Search {

	/** @var mixed */
	public $replace;

	/** @var bool */
	public $regex = false;

	/**
	 * @return string
	 */
	abstract public function name();

	/**
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @param string $orderby
	 *
	 * @return Result[] array of result objects
	 */
	abstract public function find( $pattern, $limit, $offset, $orderby );

	/**
	 * @param int $id
	 * @param string $content
	 *
	 * @return void
	 */
	abstract public function replaceContent( $id, $content );

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	abstract public function getContent( $id );

	/**
	 * @param string $expr
	 *
	 * @return null|string
	 */
	function regexValidate( $expr ) {
		// evaluate expression without input and capture potential error message
		$regex_error = 'invalid';
		$error_handler = function( $errno, $errstr, $errfile, $errline ) use ( &$regex_error ) {
			$regex_error = preg_replace( '/(.*?):/', '', $errstr, 1 );
		};
		// @codingStandardsIgnoreStart
		set_error_handler( $error_handler );
		$valid = @preg_match( $expr, null, $matches );
		restore_error_handler();
		// @codingStandardsIgnoreEnd
		if ( false === $valid ) {
			return $regex_error;
		}
		// detect possibility to execute code:
		// https://bitquark.co.uk/blog/2013/07/23/the_unexpected_dangers_of_preg_replace
		if ( false !== strpos( $expr, "\0" ) ) {
			return 'Null byte in regex';
		}
		$modifiers = preg_replace( '/^.*[^\\w\\s]([\\w\\s]*)$/s', '$1', $expr );
		if ( false !== strpos( $modifiers, 'e' ) ) {
			return 'Unknown modifier \'e\'';
		}
		// expression seems valid
		return null;
	}

	/**
	 * @param string $search
	 * @param string $replace
	 * @param int $limit
	 * @param int $offset
	 * @param string $orderby
	 * @param bool $save
	 *
	 * @return \Pressbooks\Modules\SearchAndReplace\Result[]
	 */
	function searchAndReplace( $search, $replace, $limit, $offset, $orderby, $save = false ) {
		// escape potential backreferences when not in regex mode
		if ( ! $this->regex ) {
			$replace = str_replace( '\\', '\\\\', $replace );
			$replace = str_replace( '$', '\\$', $replace );
		}
		$this->replace = $replace;
		$results = $this->searchForPattern( $search, $limit, $offset, $orderby );
		if ( is_array( $results ) && $save ) {
			$this->replace( $results );
		}
		return $results;
	}

	/**
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @param string $orderby
	 *
	 * @return string|\Pressbooks\Modules\SearchAndReplace\Result[]
	 */
	function searchForPattern( $search, $limit, $offset, $orderby ) {
		if ( ! in_array( $orderby, [ 'asc', 'desc' ], true ) ) {
			$orderby = 'asc';
		}
		$limit = intval( $limit );
		$offset = intval( $offset );
		if ( strlen( $search ) > 0 ) {
			/**
			 * Maximum execution time, in seconds. If set to zero, no time limit
			 * Overrides PHP's max_execution_time of a Nginx->PHP-FPM->PHP configuration
			 * See also request_terminate_timeout (PHP-FPM) and fastcgi_read_timeout (Nginx)
			 *
			 * @since 5.6.0
			 *
			 * @param int $seconds
			 * @param string $some_action
			 *
			 * @return int
			 */
			@set_time_limit( apply_filters( 'pb_set_time_limit', 300, 'search' ) ); // @codingStandardsIgnoreLine
			if ( $this->regex ) {
				$error = $this->regexValidate( $search );
				if ( null !== $error ) {
					return __( 'Invalid regular expression', 'pressbooks' ) . ': ' . $error;
				}
				return $this->find( $search, $limit, $offset, $orderby );
			} else {
				return $this->find( '@' . preg_quote( $search, '@' ) . '@', $limit, $offset, $orderby );
			}
		}

		return __( 'No search pattern.', 'pressbooks' );
	}

	/**
	 * Scan types/*.php directory for classes we can use
	 *
	 * @return array
	 */
	static function getSearches() {
		static $search_types = null; // Cheap cache
		if ( ! is_array( $search_types ) ) {
			$classes = [];
			$files = glob( __DIR__ . '/types/*.php' );
			foreach ( $files as $file ) {
				preg_match( '/class-(.*?)\.php/', $file, $match );
				$class = __NAMESPACE__ . '\Types\\' . ucfirst( $match[1] );
				if ( class_exists( $class ) ) {
					$classes[] = new $class;
				}
			}
			$search_types = $classes;
		}
		return $search_types;
	}

	/**
	 * @param string $class
	 *
	 * @return bool
	 */
	static function validSearch( $class ) {
		$classes = Search::getSearches();
		foreach ( $classes as $item ) {
			if ( strcasecmp( get_class( $item ), $class ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $pattern
	 * @param string $content
	 * @param int $id
	 *
	 * @return \Pressbooks\Modules\SearchAndReplace\Result[]|false
	 */
	function matches( $pattern, $content, $id ) {
		$matches = null;
		if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			// Reduce memory usage by doing preg_replace() for the same $pattern/$replacement combination only once
			$content_replace = preg_replace( $pattern, $this->replace, $content );
			$results = [];
			foreach ( $matches[0] as $found ) {
				if ( empty( $found[0] ) ) {
					continue; // Some weird regex looking for nothing?
				}
				$result = new Result();
				$result->id = $id;
				$result->offset = $found[1];
				$result->length = strlen( $found[0] );
				// Extract the context - surrounding 40 characters either side
				// Index 0 is the match, index 1 is the position

				$start = $found[1] - 40;
				if ( $start < 0 ) {
					$start = 0;
				}

				$end = $found[1] + 40;
				if ( $end > strlen( $content ) ) {
					$end = strlen( $content );
				}

				$end -= $start;
				$left = ltrim( substr( $content, $start, $found[1] - $start ), " \t," );
				$right = rtrim( substr( $content, $found[1] + strlen( $found[0] ), $end ), " \t," );
				$result->left = $start;
				$result->left_length = strlen( $found[0] ) + ( $found[1] - $start ) + $end;
				if ( 0 !== $start ) {
					$result->search = '&hellip;';
				}
				$result->search .= esc_html( $left );
				$result->search .= '<del>' . esc_html( $found[0] ) . '</del>';
				$result->search .= esc_html( $right );
				$result->search_plain = esc_html( $left );
				$result->search_plain .= esc_html( $found[0] );
				$result->search_plain .= esc_html( $right );
				if ( $start + $end < strlen( $content ) ) {
					$result->search .= '&hellip;';
				}
				if ( ! is_null( $this->replace ) ) {
					// Produce preview
					$rep = preg_replace( $pattern, $this->replace, $found[0] );
					$result->replace_string = $rep;
					if ( 0 !== $start ) {
						$result->replace = '&hellip;';
					}
					$result->replace .= esc_html( $left );
					$result->replace .= '<ins>' . esc_html( $rep ) . '</ins></a>';
					$result->replace .= esc_html( $right );
					$result->left_length_replace = strlen( $left ) + strlen( $rep ) + strlen( $right ) + 1;
					$result->replace_plain  = esc_html( $left );
					$result->replace_plain .= esc_html( $rep );
					$result->replace_plain .= esc_html( $right );
					if ( $start + $end < strlen( $content ) ) {
						$result->replace .= '&hellip;';
					}
					// And the real thing
					$result->content = $content_replace;
				}
				$results[] = $result;
			}
			return $results;
		}
		return false;
	}

	/**
	 * @param array $results
	 */
	function replace( $results ) {
		// Update database, if appropriate
		if ( count( $results ) > 0 ) {
			// We only do the first replace of any set, as that will cover everything
			$lastid = '';
			foreach ( $results as $result ) {
				if ( $result->id !== $lastid ) {
					$this->replaceContent( $result->id, $result->content );
					$lastid = $result->id;
				}
			}
		}
	}

	/**
	 * @param int $id
	 * @param int $offset
	 * @param int $length
	 * @param string $replace
	 */
	function replaceInline( $id, $offset, $length, $replace ) {
		$content = $this->getContent( $id );

		// Delete the original string
		$before = substr( $content, 0, $offset );
		$after  = substr( $content, $offset + $length );

		// Stick the new string between
		$content = $before . $replace . $after;

		// Insert back into database
		$this->replaceContent( $id, $content );
	}
}
