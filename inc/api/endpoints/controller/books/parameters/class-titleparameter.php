<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class TitleParameter extends ExclusionParameter implements BookParameter {

	public function getQueryCondition(): string {
		global $wpdb;

		$query = " AND EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND (";

		$included = ! empty( $this->includedValues );
		if ( $included ) {
			$query .= 'LOWER(meta_value) REGEXP %s';

		}
		if ( ! empty( $this->excludedValues ) ) {
			if ( $included ) {
				$query .= ' AND ';
			}

			$query .= 'LOWER(meta_value) NOT REGEXP %s';
		}
		$query .= ') AND b.blog_id = blog_id)';

		return $query;
	}

	public function getPlaceHoldervalues(): array {
		return $this->getPlaceHolders( BookDataCollector::TITLE );
	}
}
