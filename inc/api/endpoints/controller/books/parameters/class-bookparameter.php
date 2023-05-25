<?php

namespace Pressbooks\Api\Endpoints\Controller\books\parameters;

interface BookParameter {
	public function getQueryCondition(): string;

	public function getPlaceHoldervalues(): array;
}
