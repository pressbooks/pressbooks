<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class TitleParameter extends ExclusionParameter implements BookParameter {

	public function getQueryCondition(): string {
		return $this->getSubQuery( 'meta_value REGEXP %s', 'meta_value NOT REGEXP %s' );
	}

	public function getPlaceHoldervalues(): array {
		$included_values = implode( '|', $this->includedValues );
		$excluded_values = implode( '|', $this->excludedValues );
		return array_merge(
			[ BookDataCollector::TITLE ],
			$this->includedValues ? [ $included_values ] : [],
			$this->excludedValues ? [ $excluded_values ] : []
		);
	}
}
