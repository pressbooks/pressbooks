<?php

class Search
{
	var $search        = null;
	var $replace       = null;
	var $search_params = null;
	var $save          = false;
	var $source        = null;
	var $error         = null;

	var $regex_options = null;
	var $regex_error   = null;
	var $regex         = false;

	function set_regex_options( $dotall, $case, $multi)
	{
		$this->regex = true;
		if( !empty( $case))
			$this->regex_options .= 'i';

		if( !empty( $dotall))
			$this->regex_options .= 's';

		if( !empty( $multi))
			$this->regex_options .= 'm';
	}

	function regex_error( $errno, $errstr, $errfile, $errline)
	{
		$this->regex_error = __( "Invalid regular expression", 'pressbooks' ).": ".preg_replace( '/(.*?):/', '', $errstr);
	}

	function name() { return '';}
	function run_search( $search) { return false; }

	function search_and_replace( $search, $replace, $limit, $offset, $orderby, $save = false)
	{
		$this->replace = $replace;
		$results = $this->search_for_pattern( $search, $limit, $offset, $orderby);

		if( $results !== false && $save)
			$this->replace( $results);
		return $results;
	}

	function search_for_pattern( $search, $limit, $offset, $orderby ) {
		if ( !in_array( $orderby, array( 'asc', 'desc' ) ) )
			$orderby = 'asc';

		$limit = intval( $limit );
		$offset = intval( $offset );

		if ( strlen( $search ) > 0 ) {
			if ( !ini_get( 'safe_mode' ) )
				set_time_limit( 0 );

			// First test that the search and replace strings are valid regex
			if ( $this->regex ) {
				set_error_handler( array( &$this, 'regex_error' ) );
				$valid = @preg_match( $search, '', $matches );
				restore_error_handler();

				if ( $valid === false )
					return $this->regex_error;

				return $this->find( $search, $limit, $offset, $orderby );
			}
			else
				return $this->find( '@'.preg_quote( $search, '@' ).'@', $limit, $offset, $orderby );
		}

		return __( "No search pattern", 'pressbooks' );
	}

	function get_searches()
	{
		global $search_regex_searches;
		if( !is_array( $search_regex_searches))
		{
			$available = get_declared_classes();
			$files = glob( dirname( __FILE__).'/../searches/*.php');
			if( !empty( $files))
			{
				foreach( $files AS $file)
					include_once( $file);
			}

			$classes = array();
			$available = array_diff( get_declared_classes(), $available);
			if( count( $available) > 0)
			{
				foreach( $available AS $class)
					$classes[] = new $class;
			}

			$search_regex_searches = $classes;
		}

		return $search_regex_searches;
	}

	function valid_search( $class )	{
		$classes = Search::get_searches();
		foreach ( $classes AS $item )	{
			if ( strcasecmp( get_class( $item ), $class ) == 0 )
				return true;
		}

		return false;
	}

	function matches( $pattern, $content, $id)
	{
		if( preg_match_all( $pattern.$this->regex_options, $content, $matches, PREG_OFFSET_CAPTURE) > 0)
		{
			$results = array();

			// We found something
			foreach( $matches[0] AS $found)
			{
				$result = new Result();
				$result->id = $id;

				$result->offset = $found[1];
				$result->length = strlen( $found[0]);

				// Extract the context - surrounding 40 characters either side
				// Index 0 is the match, index 1 is the position
				$start = $found[1] - 40;
				if( $start < 0)
					$start = 0;

				$end = $found[1] + 40;
				if( $end > strlen( $content))
					$end = strlen( $content);

				$end -= $start;

				$left  = substr( $content, $start, $found[1] - $start);
				$right = substr( $content, $found[1] + strlen( $found[0]), $end);

				$result->left        = $start;
				$result->left_length = strlen( $found[0]) +( $found[1] - $start) + $end;

				if( $start != 0)
					$result->search = '&hellip; ';

				$result->search .= htmlspecialchars( $left, HTML_ENTITIES, 'UTF-8');
				$result->search .= '<a href="#" onclick="return false">';
				$result->search .= htmlspecialchars( $found[0], HTML_ENTITIES, 'UTF-8').'</a>';
				$result->search .= htmlspecialchars( $right, HTML_ENTITIES, 'UTF-8');

				$result->search_plain = htmlspecialchars( $left, HTML_ENTITIES, 'UTF-8');
				$result->search_plain .= htmlspecialchars( $found[0], HTML_ENTITIES, 'UTF-8');
				$result->search_plain .= htmlspecialchars( $right, HTML_ENTITIES, 'UTF-8');

				if( $start + $end < strlen( $content))
					$result->search .= ' &hellip;';

				if( !is_null( $this->replace))
				{
					// Produce preview
					$rep = preg_replace( $pattern.$this->regex_options, $this->replace, $found[0]);
					$result->replace_string = $rep;

					if( $start != 0)
						$result->replace = '&hellip; ';

					$result->replace .= htmlspecialchars( $left, HTML_ENTITIES, 'UTF-8');
					$result->replace .= '<a title="'.__( 'edit').'" onclick="return false;" ondblclick="regex_edit_replace(\''.get_class( $this).'\','.$result->id.','.$result->offset.','.$result->length.'); return false" href="#">';
					if( $rep != '')
 						$result->replace .= '<strong>'.htmlspecialchars( $rep, HTML_ENTITIES, 'UTF-8').'</strong></a>';
					else
						$result->replace .= '<strong>['.__( 'deleted','pressbooks').']</strong></a>';
					$result->replace .= htmlspecialchars( $right, HTML_ENTITIES, 'UTF-8');

					$result->left_length_replace = strlen( $left) + strlen( $rep) + strlen( $right) + 1;

					$result->replace_plain  = htmlspecialchars( $left, HTML_ENTITIES, 'UTF-8');
					$result->replace_plain .= htmlspecialchars( $rep, HTML_ENTITIES, 'UTF-8');
					$result->replace_plain .= htmlspecialchars( $right, HTML_ENTITIES, 'UTF-8');

					if( $start + $end < strlen( $content))
						$result->replace .= ' &hellip;';

					// And the real thing
					$result->content = preg_replace( $pattern.$this->regex_options, $this->replace, $content);
				}

				$results[] = $result;
			}

			return $results;
		}
		return false;
	}


	function replace( $results)
	{
		global $wpdb;

		// Update database, if appropriate
		if( count( $results) > 0)
		{
			// We only do the first replace of any set, as that will cover everything
			$lastid = '';
			foreach( $results AS $result)
			{
				if( $result->id != $lastid)
				{
					$this->replace_content( $result->id, $result->content);
					$lastid = $result->id;
				}
			}
		}
	}

	function replace_inline( $id, $offset, $length, $replace)
	{
		$content = $this->get_content( $id);

		// Delete the original string
		$before = substr( $content, 0, $offset);
		$after  = substr( $content, $offset + $length);

		// Stick the new string between
		$content = $before.$replace.$after;

		// Insert back into database
		$this->replace_content( $id, $content);
	}
}

global $search_regex_searches;
