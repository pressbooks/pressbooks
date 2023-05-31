<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\parameters;

use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\DataCollector\Book as BookDataCollector;

class InDirectoryParameter implements BookParameter {

	private bool $network_directory_excluded;

	private array $place_holder_values = [];

	public function __construct( private readonly bool $in_directory ) {
		$network_options = get_site_option( SharingAndPrivacyOptions::getSlug() );

		$this->network_directory_excluded = $network_options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] ?? false;
	}

	public function getQueryCondition(): string {
		global $wpdb;

		if ( $this->network_directory_excluded ) {
			$query = " AND EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key= '%s' AND meta_value=%s AND b.blog_id = blog_id)";
			$this->place_holder_values[] = BookDataCollector::IN_CATALOG;
			$this->place_holder_values[] = $this->in_directory ? 1 : 0;
		} else {
			$this->place_holder_values[] = BookDataCollector::BOOK_DIRECTORY_EXCLUDED;
			if ( ! $this->in_directory ) {
				$query = " AND EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key= '%s' AND meta_value = %s AND b.blog_id = blog_id)";
				$this->place_holder_values[] = 1;
			} else {
				$query = " AND (NOT EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND b.blog_id = blog_id)";
				$query .= " OR EXISTS (SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value = %s AND b.blog_id = blog_id))";
				$this->place_holder_values[] = BookDataCollector::BOOK_DIRECTORY_EXCLUDED;
				$this->place_holder_values[] = 0;
			}
		}

		return $query;
	}

	public function getPlaceHolderValues(): array {
		return $this->place_holder_values;
	}
}
