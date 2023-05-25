<?php

namespace Pressbooks\Api\Endpoints\Controller\books\parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class TitleParameter implements BookParameter {

	private array $includedTitles = [];

	private array $excludedTitles = [];

	public function __construct( private array $titles ) {
		$this->titles = array_map( 'strtolower', $this->titles );
		$this->includedTitles = array_filter( $this->titles, fn( string $title ) => ! str_starts_with( $title, '-' ) );
		$excluded_titles = array_filter( $this->titles, fn( string $title ) => str_starts_with( $title, '-' ) );

		$this->excludedTitles = ! empty( $excluded_titles ) ?
			array_map( fn( string $title ) => substr( $title, 1 ), $excluded_titles ) : [];
	}

	public function getQueryCondition(): string {
		global $wpdb;

		$query = " AND EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND (";

		$included = ! empty( $this->includedTitles );
		if ( $included ) {
			$query .= 'LOWER(meta_value) REGEXP %s';

		}
		if ( ! empty( $this->excludedTitles ) ) {
			if ( $included ) {
				$query .= ' AND ';
			}

			$query .= 'LOWER(meta_value) NOT REGEXP %s';
		}
		$query .= ') AND b.blog_id = blog_id)';

		return $query;
	}

	public function getPlaceHoldervalues(): array {
		$included_values = implode( '|', $this->includedTitles );
		$excluded_values = implode( '|', $this->excludedTitles );
		return array_merge(
			[ BookDataCollector::TITLE ],
			$this->includedTitles ? [ $included_values ] : [],
			$this->excludedTitles ? [ $excluded_values ] : []
		);
	}
}
