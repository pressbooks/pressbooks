<?php

namespace Pressbooks;

class CloneComplete {

	public static string $table = 'pressbooks_clones_complete';

	public static function install(): void {
		static::createTable();
	}

	public static function uninstall(): void {
		static::dropTable();
	}

	/**
	 * @return void
	 */
	public static function createTable(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table = static::$table;
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}{$table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) NOT NULL,
			target_book_name varchar(255) NOT NULL,
			target_book_url varchar(255) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * @return void
	 */
	public static function dropTable(): void {
		global $wpdb;
		$table = static::$table;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}{$table}" ); // @codingStandardsIgnoreLine
	}

	/**
	 * @param int $blog_id
	 * @param string $target_book_url
	 * @param string $target_book_name
	 * @return void
	 */
	public function store( int $blog_id, string $target_book_url, string $target_book_name ): void {
		global $wpdb;
		$table = static::$table;
		$wpdb->insert(
			"{$wpdb->base_prefix}{$table}",
			[
				'blog_id' => $blog_id,
				'target_book_name' => $target_book_name,
				'target_book_url' => $target_book_url,
				'created_at' => current_time( 'mysql' ),
			]
		);
	}

	public static function getCloningStats()
	{
		global $wpdb;
		$table = static::$table;
		$blog_id = get_current_blog_id();
		return $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}{$table} WHERE blog_id = $blog_id" );
	}

}
