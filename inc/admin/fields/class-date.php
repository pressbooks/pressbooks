<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class Date extends Field {
	/* The view used to render the field. */
	public string $view = 'date';

	public function sanitize( mixed $value ): mixed {
		$d = \DateTime::createFromFormat( 'Y-m-d', $value );

		if ( $d && $d->format( 'Y-m-d' ) === $value ) {
			return strtotime( $value );
		};

		return $value;
	}
};
