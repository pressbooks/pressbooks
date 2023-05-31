<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\parameters;

interface BookParameter {
	public function getQueryCondition(): string;

	public function getPlaceHoldervalues(): array;
}
