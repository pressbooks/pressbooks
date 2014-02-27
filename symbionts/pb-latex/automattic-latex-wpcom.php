<?php
/*
Version: 1.1
Copyright: Automattic, Inc.
License: GPL2+
*/

class Automattic_Latex_WPCOM {
	var $latex;
	var $bg_hex;
	var $fg_hex;
	var $size;
	var $zoom = 1;

	var $url;

	var $error;

	function Automattic_Latex_WPCOM( $latex, $bg_hex = 'ffffff', $fg_hex = '000000', $size = 0 ) {
		$this->__construct( $latex, $bg_hex, $fg_hex, $size );
	}

	function __construct( $latex, $bg_hex = 'ffffff', $fg_hex = '000000', $size = 0 ) {
		$this->latex  = (string) $latex;
		$this->bg_hex = $this->sanitize_hex( $bg_hex );
		$this->fg_hex = $this->sanitize_hex( $fg_hex );
		$this->size   = (int) $size;
	}

	function set_zoom( $zoom ) {
		$this->zoom = $zoom;
	}

	function sanitize_hex( $color ) {
		if ( 'transparent' == $color )
			return 'T';

		// Fix for 3 letter hex codes
		if ( 3 == strlen( $color ) )
			$color = $color[0] . $color[0] . $color[1] . $color[1]. $color[2] . $color[2];

		$color = substr( preg_replace( '/[^0-9a-f]/i', '', (string) $color ), 0, 6 );
		if ( 6 > $l = strlen( $color ) )
			$color .= str_repeat('0', 6 - $l );
		return $color;
	}

	function wrapper( $wrapper = false ) {}

	function url() {
		$this->url = 'http://s.wordpress.com/latex.php?latex=' . rawurlencode( $this->latex ) . "&bg=$this->bg_hex&fg=$this->fg_hex&s=$this->size";
		return $this->url;
	}
}
