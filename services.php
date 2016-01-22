<?php

// If you add stuff here, don't forget to also edit .phpstorm.meta.php

$c = new \Pimple\Container();

$c['Sass'] = function () {
	return new \PressBooks\Sass();
};

$c['GlobalTypography'] = function() {
	return new \PressBooks\GlobalTypography();
};

return $c;
