<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class WordsParameter implements BookParameter {

	public function __construct( private readonly string $words ) {
	}

	public function getQueryCondition(): string {
		$operator = str_starts_with( $this->words, 'gte_' ) ? '>=' : '<=';

		global $wpdb;
		$query = " AND EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value {$operator} %d";
		$query .= ' AND b.blog_id = blog_id)';
		return $query;
	}

	public function getPlaceHoldervalues(): array {
		return [ BookDataCollector::WORD_COUNT, (int) explode( '_', $this->words )[1] ];
	}
}
