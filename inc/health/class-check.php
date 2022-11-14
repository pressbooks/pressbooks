<?php

namespace Pressbooks\Health;

abstract class Check {
	protected ?string $name = null;

	abstract public function run(): Result;

	public function getName(): ?string {
		return $this->name;
	}
}
