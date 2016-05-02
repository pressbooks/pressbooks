<?php

// If you add stuff here, don't forget to also edit .phpstorm.meta.php

$c = new \Pimple\Container();

$c['Sass'] = function () {
	return new \Pressbooks\Sass();
};

$c['GlobalTypography'] = function() {
	return new \Pressbooks\GlobalTypography();
};

return $c;
