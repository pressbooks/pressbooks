<?php

namespace Pressbooks\Api\Endpoints\Controller\books;

use Pressbooks\Api\Endpoints\Controller\books\parameters\InDirectoryParameter;
use Pressbooks\Api\Endpoints\Controller\books\parameters\LicenseCodeParameter;
use Pressbooks\Api\Endpoints\Controller\books\parameters\ModifiedSinceParameter;
use Pressbooks\Api\Endpoints\Controller\books\parameters\TitleParameter;
use Pressbooks\Api\Endpoints\Controller\books\parameters\WordsParameter;

class BooksQueryBuilder {

	private string $query;

	private array $placeholder_values = [];

	private array $parameters = [];

	private int $number_of_rows = 0;

	private array $parameters_classes_map = [
		'modified_since' => ModifiedSinceParameter::class,
		'title' => TitleParameter::class,
		'license_code' => LicenseCodeParameter::class,
		'words' => WordsParameter::class,
		'in_directory' => InDirectoryParameter::class,
	];

	public function __construct( private readonly \WP_REST_Request $request ) {
		foreach ( $this->parameters_classes_map as $get_parameter => $parameter ) {
			if ( ! is_null( $this->request->get_param( $get_parameter ) ) ) {
				$this->parameters[] = new $parameter( $this->request[ $get_parameter ] );
			}
		}
	}

	public function build(): self {
		global $wpdb;

		$this->query = "SELECT SQL_CALC_FOUND_ROWS blog_id FROM {$wpdb->blogs} AS b
		WHERE public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d";
		$this->placeholder_values[] = get_main_site_id();

		foreach ( $this->parameters as $parameter ) {
			$this->query .= $parameter->getQueryCondition();
			$this->placeholder_values = array_merge( $this->placeholder_values, $parameter->getPlaceHolderValues() );
		}

		$this->query .= ' ORDER BY blog_id LIMIT %d, %d ';

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : 10;
		$offset = ! empty( $request['page'] ) ? ( $request['page'] - 1 ) * $limit : 0;

		$this->placeholder_values[] = $offset;
		$this->placeholder_values[] = $limit;

		return $this;
	}

	public function get(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$col = $wpdb->get_col( $wpdb->prepare( $this->query, $this->placeholder_values ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$this->number_of_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return $col;
	}

	public function getNumberOfRows(): int {
		return $this->number_of_rows;
	}
}
