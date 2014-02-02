<?php

class Result
{
	var $search;
	var $replace;
	var $content;
	var $id;
	var $title;

	var $offset;
	var $length;
	var $replace_string;

	function for_js ($js)
	{
		$js = str_replace ('\\', '\\\\', $js);
		$js = str_replace ('/', '\/', $js);
		$js = str_replace ('<', '\<', $js);
		$js = str_replace ("'", "\\'", $js);
		$js = str_replace ('"', '&quot;', $js);
		$js = str_replace ("\n", "\\n", $js);
		$js = str_replace ("\r", "\\r", $js);
		return $js;
	}

	function single_line ()
	{
		if (strpos ($this->search_plain, "\r") !== false || strpos ($this->search_plain, "\n") !== false)
			return false;
		return true;
	}
}

