<?php

if ( function_exists( 'xdebug_set_filter' ) ) {
	xdebug_set_filter(
		XDEBUG_FILTER_CODE_COVERAGE,
		XDEBUG_PATH_WHITELIST,
		[ __DIR__ . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR ]
	);
}
