<?php

// If you add stuff here, don't forget to also edit .phpstorm.meta.php

$c = new \Pimple\Container();

$c['Sass'] = function () {
	return new \Pressbooks\Sass();
};

$c['GlobalTypography'] = function ( $c ) {
	return new \Pressbooks\GlobalTypography( $c['Sass'] );
};

$c['Styles'] = function ( $c ) {
	return new \Pressbooks\Styles( $c['Sass'] );
};

return $c;
