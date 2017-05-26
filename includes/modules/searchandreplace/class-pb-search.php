<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2+
 */

namespace Pressbooks\Modules\SearchAndReplace;

class Search {
	var $search = null;
	var $replace = null;
	var $search_params = null;
	var $save = false;
	var $source = null;
	var $error = null;

	var $regex = false;

	function name() {
		return '';
	}

	function regex_validate( $expr ) {
		// evaluate expression without imput and capture potential error message
		$regex_error = 'invalid';
		$error_handler = function( $errno, $errstr, $errfile, $errline ) use ( &$regex_error ) {
			$regex_error = preg_replace( '/(.*?):/', '', $errstr, 1 );
		};
		set_error_handler( $error_handler );
		// @codingStandardsIgnoreLine
		$valid = @preg_match( $expr, null, $matches );
		restore_error_handler();
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

	function search_and_replace( $search, $replace, $limit, $offset, $orderby, $save = false ) {
		// escape potential backreferences when not in regex mode
		if ( ! $this->regex ) {
			$replace = str_replace( '\\', '\\\\', $replace );
			$replace = str_replace( '$', '\\$', $replace );
		}
		$this->replace = $replace;
		$results = $this->search_for_pattern( $search, $limit, $offset, $orderby );
		if ( false !== $results && $save ) {
			$this->replace( $results );
		}
		return $results;
	}

	function search_for_pattern( $search, $limit, $offset, $orderby ) {
		if ( ! in_array( $orderby, [ 'asc', 'desc' ] ) ) {
			$orderby = 'asc';
		}
		$limit = intval( $limit );
		$offset = intval( $offset );
		if ( strlen( $search ) > 0 ) {
			if ( ! ini_get( 'safe_mode' ) ) {
				set_time_limit( 0 );
			}
			if ( $this->regex ) {
				$error = $this->regex_validate( $search );
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

	static function get_searches() {
		global $search_types;
		if ( ! is_array( $search_types ) ) {
			$available = get_declared_classes();
			$files = glob( PB_PLUGIN_DIR . 'includes/modules/searchandreplace/types/*.php' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					include_once( $file );
				}
			}
			$classes = array();
			$available = array_diff( get_declared_classes(), $available );
			if ( count( $available ) > 0 ) {
				foreach ( $available as $class ) {
					$classes[] = new $class;
				}
			}
			$search_types = $classes;
		}
		return $search_types;
	}

	static function valid_search( $class ) {
		$classes = \Pressbooks\Modules\SearchAndReplace\Search::get_searches();
		foreach ( $classes as $item ) {
			if ( strcasecmp( get_class( $item ), $class ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	function matches( $pattern, $content, $id ) {
		if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			$results = array();
			foreach ( $matches[0] as $found ) {
				$result = new \Pressbooks\Modules\SearchAndReplace\Result();
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
				$result->left_length = strlen( $found[0] ) + ( $found[1] - $start) + $end;
				if ( 0 != $start ) {
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
					if ( 0 != $start ) {
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
					$result->content = preg_replace( $pattern, $this->replace, $content );
				}
				$results[] = $result;
			}
			return $results;
		}
		return false;
	}

	function replace( $results ) {
		global $wpdb;

		// Update database, if appropriate
		if ( count( $results ) > 0 ) {
			// We only do the first replace of any set, as that will cover everything
			$lastid = '';
			foreach ( $results as $result ) {
				if ( $result->id !== $lastid ) {
					$this->replace_content( $result->id, $result->content );
					$lastid = $result->id;
				}
			}
		}
	}

	function replace_inline( $id, $offset, $length, $replace ) {
		$content = $this->get_content( $id );

		// Delete the original string
		$before = substr( $content, 0, $offset );
		$after  = substr( $content, $offset + $length );

		// Stick the new string between
		$content = $before . $replace . $after;

		// Insert back into database
		$this->replace_content( $id, $content );
	}
}
