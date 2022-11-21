<?php

namespace Pressbooks\Health;

use Illuminate\Support\Str;
use ReflectionClass;

abstract class Check {
	abstract public function run(): Result;

	public function getName(): ?string {
		$class = new ReflectionClass( $this );

		return Str::of( $class->getShortName() )
			->kebab()
			->before( '-check' );
	}
}
