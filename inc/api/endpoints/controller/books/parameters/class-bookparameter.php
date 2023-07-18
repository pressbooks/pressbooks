<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

interface BookParameter {
	public function getQueryCondition(): string;

	public function getPlaceHoldervalues(): array;
}
