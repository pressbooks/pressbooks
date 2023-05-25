<?php

namespace Pressbooks\Api\Endpoints\Controller\books\parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class WordsParameter implements BookParameter {

	public function __construct( private readonly int $words ) {
	}

	public function getQueryCondition(): string {
		$operator = str_starts_with( $this->words, 'gte_' ) ? '>=' : '<=';

		global $wpdb;
		$query = " AND EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_value = %s AND words {$operator} %s)";
		$query .= ' AND b.blog_id = blog_id)';
		return $query;
	}

	public function getPlaceHoldervalues(): array {
		return [ BookDataCollector::WORD_COUNT, explode( '_', $this->words )[1] ];
	}
}
