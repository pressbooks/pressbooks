<?php

namespace Pressbooks\Health;

abstract class Check {
	protected ?string $name = null;

	abstract public function run(): array;

	public function getName(): ?string {
		return $this->name;
	}
}
