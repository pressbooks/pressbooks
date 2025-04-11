<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class LicenseCodeParameter extends ExclusionParameter implements BookParameter {
	public function getQueryCondition(): string {

		$placeholders_inclusion = implode( ',', array_fill( 0, count( $this->includedValues ), '%s' ) );
		$placeholders_exclusion = implode( ',', array_fill( 0, count( $this->excludedValues ), '%s' ) );

		return $this->getSubQuery( " meta_value IN ($placeholders_inclusion)", " meta_value NOT IN ($placeholders_exclusion)" );
	}

	public function getPlaceHoldervalues(): array {
		return array_merge(
			[ BookDataCollector::LICENSE_CODE ],
			$this->includedValues ?? [],
			$this->excludedValues ?? []
		);
	}
}
