<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function ( RectorConfig $config ): void {
	$config->paths( [
		__DIR__ . '/bin',
		__DIR__ . '/inc',
		__DIR__ . '/symbionts',
		__DIR__ . '/templates',
		__DIR__ . '/tests',
	] );

	// Directories to skip, rules I don't agree with...
	$config->skip( [
		\Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
		\Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector::class,
	] );

	// define sets of rules
	$config->sets( [
		LevelSetList::UP_TO_PHP_80,
	] );
};
