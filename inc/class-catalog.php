<?php
/**
 * Contains functions for creating and managing a user's Pressbooks Catalog.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Utility\oxford_comma_explode;
use function \Pressbooks\Utility\getset;

class Catalog {


	/**
	 * The value for option: pressbooks_catalog_version
	 *
	 * @see install()
	 * @var int
	 */
	const VERSION = 3;


	/**
	 * Maximum number allowed in tags_group column
	 *
	 * @var int
	 */
	const MAX_TAGS_GROUP = 2;


	/**
	 * Catalog tables, set in constructor
	 *
	 * @var string
	 */
	protected $dbTable, $dbTagsTable, $dbLinkTable;


	/**
	 * User ID to construct this object
	 *
	 * @var int
	 */
	protected $userId;


	/**
	 * Column structure of catalog_table
	 *
	 * @var array
	 */
	protected $dbColumns = [
		'users_id' => '%d',
		'blogs_id' => '%d',
		'deleted' => '%d',
		'featured' => '%d',
	];


	/**
	 * Profile keys, stored in user_meta table
	 *
	 * @var array
	 */
	protected $profileMetaKeys = [
		'pb_catalog_about' => '%s',
		'pb_catalog_logo' => '%s',
		'pb_catalog_url' => '%s',
		'pb_catalog_color' => '%s',
		// Tags added in constructor (Ie. pb_catalog_tag_1_name, pb_catalog_tag_2_name, ...)
	];


	/**
	 * @param int $user_id (optional)
	 */
	function __construct( $user_id = 0 ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		// Tables
		$this->dbTable = $wpdb->base_prefix . 'pressbooks_catalog';
		$this->dbTagsTable = $wpdb->base_prefix . 'pressbooks_tags';
		$this->dbLinkTable = $wpdb->base_prefix . 'pressbooks__catalog__tags';

		// Tags
		for ( $i = 1; $i <= self::MAX_TAGS_GROUP; ++$i ) {
			$this->profileMetaKeys[ "pb_catalog_tag_{$i}_name" ] = '%s';
		}

		// User
		if ( $user_id ) {
			$this->userId = $user_id;
		} elseif ( isset( $_REQUEST['user_id'] ) && current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) ) {
			$this->userId = (int) $_REQUEST['user_id'];
		} else {
			$this->userId = get_current_user_id();
		}

		//  Might be missing because Catalog code can be run in the root site
		\Pressbooks\Metadata\init_book_data_models();
	}


	/**
	 * Get User ID
	 *
	 * @return int
	 */
	function getUserId() {

		return $this->userId;
	}


	/**
	 * Get an entire catalog.
	 *
	 * @return array
	 */
	function get() {
		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT * FROM {$this->dbTable} WHERE users_id = %d AND deleted = 0 ";

		return $wpdb->get_results( $wpdb->prepare( $sql, $this->userId ), ARRAY_A ); // @codingStandardsIgnoreLine
	}


	/**
	 * Get all data for an entire catalog, cached
	 *
	 * @return array
	 */
	function getAggregate() {

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		$cache_id = "cat-{$this->userId}";
		$data = wp_cache_get( $cache_id, 'pb' );
		if ( $data ) {
			return $data;
		}

		// ----------------------------------------------------------------------------
		// User Catalog
		// ----------------------------------------------------------------------------

		$cover_sizes = [
			'thumbnail' => \Pressbooks\Image\default_cover_url( 'thumbnail' ),
			'pb_cover_small' => \Pressbooks\Image\default_cover_url( 'small' ),
			'pb_cover_medium' => \Pressbooks\Image\default_cover_url( 'medium' ),
			'pb_cover_large' => \Pressbooks\Image\default_cover_url(),
		];

		$catalog = $this->get();
		$usercatalog = new static( $this->userId );
		$data = [];
		$i = 0;
		$already_loaded = [];

		foreach ( $catalog as $val ) {
			if ( ! get_site( $val['blogs_id'] ) ) {
				$usercatalog->deleteBook( $val['blogs_id'], true );
			} else {
				switch_to_blog( $val['blogs_id'] );

				$metadata = Book::getBookInformation();
				$meta_version = get_option( 'pressbooks_metadata_version', 0 );

				$data[ $i ]['ID'] = "{$val['users_id']}:{$val['blogs_id']}";
				$data[ $i ]['users_id'] = $val['users_id'];
				$data[ $i ]['blogs_id'] = $val['blogs_id'];
				$data[ $i ]['featured'] = $val['featured'];
				$data[ $i ]['deleted'] = 0;
				$data[ $i ]['title'] = ! empty( $metadata['pb_title'] ) ? $metadata['pb_title'] : get_bloginfo( 'name' );
				$data[ $i ]['author'] = empty( $metadata['pb_credit_override'] ) ? ( ( ! \Pressbooks\Utility\empty_space( $metadata['pb_authors'] ) ) ? oxford_comma_explode( $metadata['pb_authors'] )[0] : '' ) : $metadata['pb_credit_override'];
				$data[ $i ]['pub_date'] = ! empty( $metadata['pb_publication_date'] ) ? date( 'Y-m-d', (int) $metadata['pb_publication_date'] ) : '';
				$data[ $i ]['private'] = ( ! empty( get_option( 'blog_public' ) ) ? 0 : 1 );

				// About
				if ( ! empty( $metadata['pb_about_50'] ) ) {
					$about = $metadata['pb_about_50'];
				} elseif ( ! empty( $metadata['pb_about_140'] ) ) {
					$about = $metadata['pb_about_140'];
				} elseif ( ! empty( $metadata['pb_about_unlimited'] ) ) {
					$about = $metadata['pb_about_unlimited'];
				} else {
					$about = '';
				}
				$data[ $i ]['about'] = $about;

				// Cover Full
				if ( $meta_version < 7 ) {
					$cover = \Pressbooks\Image\default_cover_url();
				} elseif ( empty( $metadata['pb_cover_image'] ) ) {
					$cover = \Pressbooks\Image\default_cover_url();
				} elseif ( \Pressbooks\Image\is_default_cover( $metadata['pb_cover_image'] ) ) {
					$cover = \Pressbooks\Image\default_cover_url();
				} else {
					$cover = \Pressbooks\Image\thumbnail_from_url( $metadata['pb_cover_image'], 'full' );
				}
				$data[ $i ]['cover_url']['full'] = $cover;

				// Cover Thumbnails
				/**
				 * Exposes $cover variable to be changed as-needed for cover images.
				 *
				 * Some users store their images on an outside server, which can result
				 * in cover images not displaying correctly. This gives users the option
				 * of altering $cover to point to the correct path to the cover image.
				 *
				 * @since 3.9.5.1
				 *
				 * @param string $cover The url to cover image.
				 * @param string $original The original url to the cover image.
				 */
				$cid = \Pressbooks\Image\attachment_id_from_url( apply_filters( 'pb_cover_image', $cover, $metadata['pb_cover_image'] ) );
				foreach ( $cover_sizes as $size => $default ) {
					$cid_thumb = wp_get_attachment_image_src( $cid, $size );
					if ( $cid_thumb ) {
						$data[ $i ]['cover_url'][ $size ] = $cid_thumb[0];
					} else {
						$data[ $i ]['cover_url'][ $size ] = $default;
					}
				}

				// Tags
				for ( $j = 1; $j <= self::MAX_TAGS_GROUP; ++$j ) {
					$data[ $i ][ "tag_{$j}" ] = $this->getTagsByBook( $val['blogs_id'], $j );
				}

				$already_loaded[ $val['blogs_id'] ] = true;
				++$i;

				restore_current_blog();
			}
		}

		$userblogs = get_blogs_of_user( $this->userId );
		foreach ( $userblogs as $book ) {

			// Skip
			if ( is_main_site( $book->userblog_id ) ) {
				continue;
			}
			if ( isset( $already_loaded[ $book->userblog_id ] ) ) {
				continue;
			}

			switch_to_blog( $book->userblog_id );

			$metadata = Book::getBookInformation();
			$meta_version = get_option( 'pressbooks_metadata_version', 0 );

			$data[ $i ]['ID'] = "{$this->userId}:{$book->userblog_id}";
			$data[ $i ]['users_id'] = $this->userId;
			$data[ $i ]['blogs_id'] = $book->userblog_id;
			$data[ $i ]['featured'] = 0;
			$data[ $i ]['deleted'] = 1;
			$data[ $i ]['title'] = ! empty( $metadata['pb_title'] ) ? $metadata['pb_title'] : get_bloginfo( 'name' );
			$data[ $i ]['author'] = empty( $metadata['pb_credit_override'] ) ? ( ( ! \Pressbooks\Utility\empty_space( $metadata['pb_authors'] ) ) ? oxford_comma_explode( $metadata['pb_authors'] )[0] : '' ) : $metadata['pb_credit_override'];
			$data[ $i ]['pub_date'] = ! empty( $metadata['pb_publication_date'] ) ? date( 'Y-m-d', (int) $metadata['pb_publication_date'] ) : '';
			$data[ $i ]['private'] = ( ! empty( get_option( 'blog_public' ) ) ? 0 : 1 );

			// About
			if ( ! empty( $metadata['pb_about_50'] ) ) {
				$about = $metadata['pb_about_50'];
			} elseif ( ! empty( $metadata['pb_about_140'] ) ) {
				$about = $metadata['pb_about_140'];
			} elseif ( ! empty( $metadata['pb_about_unlimited'] ) ) {
				$about = $metadata['pb_about_unlimited'];
			} else {
				$about = '';
			}
			$data[ $i ]['about'] = $about;

			// Cover Full
			if ( $meta_version < 7 ) {
				$cover = \Pressbooks\Image\default_cover_url();
			} elseif ( empty( $metadata['pb_cover_image'] ) ) {
				$cover = \Pressbooks\Image\default_cover_url();
			} elseif ( \Pressbooks\Image\is_default_cover( $metadata['pb_cover_image'] ) ) {
				$cover = \Pressbooks\Image\default_cover_url();
			} else {
				$cover = \Pressbooks\Image\thumbnail_from_url( $metadata['pb_cover_image'], 'full' );
			}
			$data[ $i ]['cover_url']['full'] = $cover;

			// Cover Thumbnails
			/** This filter is documented in pressbooks/includes/class-pb-catalog.php */
			$cid = \Pressbooks\Image\attachment_id_from_url( apply_filters( 'pb_cover_image', $cover, $metadata['pb_cover_image'] ) );
			foreach ( $cover_sizes as $size => $default ) {
				$cid_thumb = wp_get_attachment_image_src( $cid, $size );
				if ( $cid_thumb ) {
					$data[ $i ]['cover_url'][ $size ] = $cid_thumb[0];
				} else {
					$data[ $i ]['cover_url'][ $size ] = $default;
				}
			}

			// Tags
			for ( $j = 1; $j <= self::MAX_TAGS_GROUP; ++$j ) {
				$data[ $i ][ "tag_{$j}" ] = $this->getTagsByBook( $book->userblog_id, $j );
			}

			++$i;

			restore_current_blog();
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		wp_cache_set( $cache_id, $data, 'pb', DAY_IN_SECONDS );

		return $data;
	}


	/**
	 * Get catalog by tag id
	 *
	 * @param int $tag_group
	 * @param int $tag_id
	 *
	 * @return array
	 */
	function getByTagId( $tag_group, $tag_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT DISTINCT {$this->dbTable}.* FROM {$this->dbTable}
				INNER JOIN {$this->dbLinkTable} ON {$this->dbLinkTable}.blogs_id = {$this->dbTable}.blogs_id
 				INNER JOIN {$this->dbTagsTable} ON {$this->dbTagsTable}.id = {$this->dbLinkTable}.tags_id
 				WHERE {$this->dbLinkTable}.users_id = %d AND {$this->dbLinkTable}.tags_group = %d AND {$this->dbLinkTable}.tags_id = %d AND {$this->dbTable}.deleted = 0 ";

		return $wpdb->get_results( $wpdb->prepare( $sql, $this->userId, $tag_group, $tag_id ), ARRAY_A ); // @codingStandardsIgnoreLine
	}


	/**
	 * Save an entire catalog.
	 *
	 * @param array $items
	 */
	function save( array $items ) {

		foreach ( $items as $item ) {
			if ( isset( $item['blogs_id'] ) ) {
				$this->saveBook( $this->userId, $item );
			}
		}
	}


	/**
	 * Delete an entire catalog.
	 *
	 * @param bool $for_real (optional)
	 *
	 * @return int|false
	 */
	function delete( $for_real = false ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( $for_real ) {
			return $wpdb->delete(
				$this->dbTable, [
					'users_id' => $this->userId,
				], [ '%d' ]
			);
		} else {
			return $wpdb->update(
				$this->dbTable, [
					'deleted' => 1,
				], [
					'users_id' => $this->userId,
				], [ '%d' ], [ '%d' ]
			);
		}
	}


	/**
	 * Get a book from a user catalog.
	 *
	 * @param int $blog_id
	 *
	 * @return array
	 */
	function getBook( $blog_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT * FROM {$this->dbTable} WHERE users_id = %d AND blogs_id = %d AND deleted = 0 ";

		return $wpdb->get_row( $wpdb->prepare( $sql, $this->userId, $blog_id ), ARRAY_A ); // @codingStandardsIgnoreLine
	}


	/**
	 * Get only blog IDs.
	 *
	 * @return array
	 */
	function getBookIds() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT blogs_id FROM {$this->dbTable} WHERE users_id = %d AND deleted = 0 ";

		return $wpdb->get_col( $wpdb->prepare( $sql, $this->userId ) ); // @codingStandardsIgnoreLine
	}


	/**
	 * Save a book to a user catalog.
	 *
	 * @param $blog_id
	 * @param array $item
	 *
	 * @return int|false
	 */
	function saveBook( $blog_id, array $item ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		unset( $item['users_id'], $item['blogs_id'], $item['deleted'] ); // Don't allow spoofing

		$data = [
			'users_id' => $this->userId,
			'blogs_id' => $blog_id,
			'deleted' => 0,
		];
		$format = [
			'users_id' => $this->dbColumns['users_id'],
			'blogs_id' => $this->dbColumns['blogs_id'],
			'deleted' => $this->dbColumns['deleted'],
		];

		foreach ( $item as $key => $val ) {
			if ( isset( $this->dbColumns[ $key ] ) ) {
				$data[ $key ] = $val;
				$format[ $key ] = $this->dbColumns[ $key ];
			}
		}

		// INSERT ... ON DUPLICATE KEY UPDATE
		// @see http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html

		$args = [];
		$sql = "INSERT INTO {$this->dbTable} ( ";
		foreach ( $data as $key => $val ) {
			$sql .= "`$key`, ";
		}
		$sql = rtrim( $sql, ', ' ) . ' ) VALUES ( ';

		foreach ( $format as $key => $val ) {
			$sql .= $val . ', ';
			$args[] = $data[ $key ];
		}
		$sql = rtrim( $sql, ', ' ) . ' ) ON DUPLICATE KEY UPDATE ';

		$i = 0;
		foreach ( $data as $key => $val ) {
			if ( 'users_id' === $key || 'blogs_id' === $key ) {
				continue;
			}
			$sql .= "`$key` = {$format[$key]}, ";
			$args[] = $val;
			++$i;
		}
		$sql = rtrim( $sql, ', ' );
		if ( ! $i ) {
			$sql .= ' users_id = users_id '; // Do nothing
		}

		return $wpdb->query( $wpdb->prepare( $sql, $args ) ); // @codingStandardsIgnoreLine
	}


	/**
	 * Delete a book from a user catalog.
	 *
	 * @param int $blog_id
	 * @param bool $for_real (optional)
	 *
	 * @return int|false
	 */
	function deleteBook( $blog_id, $for_real = false ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( $for_real ) {
			return $wpdb->delete(
				$this->dbTable, [
					'users_id' => $this->userId,
					'blogs_id' => $blog_id,
				], [ '%d', '%d' ]
			);
		} else {
			return $wpdb->update(
				$this->dbTable, [
					'deleted' => 1,
				], [
					'users_id' => $this->userId,
					'blogs_id' => $blog_id,
				], [ '%d' ], [ '%d', '%d' ]
			);
		}
	}


	/**
	 * Get tags
	 *
	 * @param int $tag_group
	 * @param bool $show_hidden_tags (optional)
	 *
	 * @return array
	 */
	function getTags( $tag_group, $show_hidden_tags = true ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT DISTINCT {$this->dbTagsTable}.id, {$this->dbTagsTable}.tag FROM {$this->dbTagsTable}
 				INNER JOIN {$this->dbLinkTable} ON {$this->dbLinkTable}.tags_id = {$this->dbTagsTable}.id
 				INNER JOIN {$this->dbTable} ON {$this->dbTable}.users_id = {$this->dbLinkTable}.users_id AND {$this->dbTable}.blogs_id = {$this->dbLinkTable}.blogs_id
 				WHERE {$this->dbLinkTable}.tags_group = %d AND {$this->dbLinkTable}.users_id = %d ";

		if ( true !== $show_hidden_tags ) {
			$sql .= "AND {$this->dbTable}.deleted = 0 ";
		}
		$sql .= "ORDER BY {$this->dbTagsTable}.tag ASC ";

		return $wpdb->get_results( $wpdb->prepare( $sql, $tag_group, $this->userId ), ARRAY_A ); // @codingStandardsIgnoreLine
	}


	/**
	 * Get all tags for a book
	 *
	 * @param int $blog_id
	 * @param int $tag_group
	 *
	 * @return array
	 */
	function getTagsByBook( $blog_id, $tag_group ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT DISTINCT {$this->dbTagsTable}.id, {$this->dbTagsTable}.tag FROM {$this->dbTagsTable}
 				INNER JOIN {$this->dbLinkTable} ON {$this->dbLinkTable}.tags_id = {$this->dbTagsTable}.id
 				INNER JOIN {$this->dbTable} ON {$this->dbTable}.users_id = {$this->dbLinkTable}.users_id AND {$this->dbTable}.blogs_id = {$this->dbLinkTable}.blogs_id
 				WHERE {$this->dbLinkTable}.tags_group = %d AND {$this->dbLinkTable}.users_id = %d AND {$this->dbLinkTable}.blogs_id = %d
 				ORDER BY {$this->dbTagsTable}.tag ASC ";

		return $wpdb->get_results( $wpdb->prepare( $sql, $tag_group, $this->userId, $blog_id ), ARRAY_A ); // @codingStandardsIgnoreLine
	}


	/**
	 * Save tag
	 *
	 * @param string $tag
	 * @param int $blog_id
	 * @param int $tag_group
	 *
	 * @return int|false
	 */
	function saveTag( $tag, $blog_id, $tag_group ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$tag = strip_tags( $tag );
		$tag = trim( $tag );

		// INSERT ... ON DUPLICATE KEY UPDATE
		// @see http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html

		$sql = "INSERT INTO {$this->dbTagsTable} ( users_id, tag ) VALUES ( %d, %s ) ON DUPLICATE KEY UPDATE id = id ";
		$_ = $wpdb->query( $wpdb->prepare( $sql, $this->userId, $tag ) ); // @codingStandardsIgnoreLine

		// Get ID

		$sql = "SELECT id FROM {$this->dbTagsTable} WHERE tag = %s ";
		$tag_id = $wpdb->get_var( $wpdb->prepare( $sql, $tag ) ); // @codingStandardsIgnoreLine

		// Create JOIN

		$sql = "INSERT INTO {$this->dbLinkTable} ( users_id, blogs_id, tags_id, tags_group ) VALUES ( %d, %d, %d, %d ) ON DUPLICATE KEY UPDATE users_id = users_id ";
		$result = $wpdb->query( $wpdb->prepare( $sql, $this->userId, $blog_id, $tag_id, $tag_group ) ); // @codingStandardsIgnoreLine

		return $result;
	}


	/**
	 * Delete a tag.
	 *
	 * IMPORTANT: The 'for_real' option is extremely destructive. Do not use unless you know what you are doing.
	 *
	 * @param string $tag
	 * @param int $blog_id
	 * @param int $tag_group
	 * @param bool $for_real (optional)
	 *
	 * @return int|false
	 */
	function deleteTag( $tag, $blog_id, $tag_group, $for_real = false ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		// Get ID

		$sql = "SELECT id FROM {$this->dbTagsTable} WHERE tag = %s ";
		$tag_id = $wpdb->get_var( $wpdb->prepare( $sql, $tag ) ); // @codingStandardsIgnoreLine

		if ( ! $tag_id ) {
			return false;
		}

		if ( $for_real && is_super_admin() ) {
			$wpdb->delete(
				$this->dbLinkTable, [
					'tags_id' => $tag_id,
				], [ '%d' ]
			);
			$wpdb->delete(
				$this->dbTagsTable, [
					'id' => $tag_id,
				], [ '%d' ]
			);
			$result = 1;
		} else {
			$result = $wpdb->delete(
				$this->dbLinkTable,
				[
					'users_id' => $this->userId,
					'blogs_id' => $blog_id,
					'tags_id' => $tag_id,
					'tags_group' => $tag_group,
				],
				[ '%d', '%d', '%d', '%d' ]
			);
		}

		// TODO:
		// Optimize the links table: $wpdb->query( "OPTIMIZE TABLE {$this->dbLinkTable} " );
		// Optimize the tags table: $wpdb->query( "OPTIMIZE TABLE {$this->dbTagsTable} " );

		return $result;
	}


	/**
	 * Delete all tags from a user catalog
	 *
	 * Note: Doesn't actually delete a tag, just removes the association in dbLinkTable
	 *
	 * @param $blog_id
	 * @param $tag_group
	 *
	 * @return int|false
	 */
	function deleteTags( $blog_id, $tag_group ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$result = $wpdb->delete(
			$this->dbLinkTable, [
				'users_id' => $this->userId,
				'blogs_id' => $blog_id,
				'tags_group' => $tag_group,
			], [ '%d', '%d', '%d' ]
		);

		// TODO:
		// Optimize the links table: $wpdb->query( "OPTIMIZE TABLE {$this->dbLinkTable} " );

		return $result;

	}


	/**
	 * Find all IDs in dbTagsTable that have no matching ID in dbLinkTable and delete them.
	 */
	function purgeOrphanTags() {

		// TODO
	}


	/**
	 * Get catalog profile.
	 *
	 * @return array
	 */
	function getProfile() {

		$profile['users_id'] = $this->userId;
		foreach ( $this->profileMetaKeys as $key => $type ) {
			$profile[ $key ] = get_user_meta( $this->userId, $key, true );
		}

		return $profile;
	}


	/**
	 * Save catalog profile
	 *
	 * @param array $item
	 */
	function saveProfile( array $item ) {

		// Sanitize
		$item = array_intersect_key( $item, $this->profileMetaKeys );

		foreach ( $item as $key => $val ) {

			if ( 'pb_catalog_logo' === $key ) {
				continue; // Skip, dev should use uploadLogo() instead
			}

			if ( 'pb_catalog_url' === $key && $val ) {
				$val = \Pressbooks\Sanitize\canonicalize_url( $val );
			}

			if ( '%d' === $this->profileMetaKeys[ $key ] ) {
				$val = (int) $val;
			} elseif ( '%f' === $this->profileMetaKeys[ $key ] ) {
				$val = (float) $val;
			} else {
				$val = (string) $val;
			}

			update_user_meta( $this->userId, $key, $val );
		}
	}


	/**
	 * @param string $meta_key
	 */
	function uploadLogo( $meta_key ) {
		// Include media utilities
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		if ( isset( $_FILES[ $meta_key ]['name'] ) && empty( $_FILES[ $meta_key ]['name'] ) ) {
			return; // Bail
		}

		$book = get_active_blog_for_user( $this->userId );
		if ( ! current_user_can_for_blog( $book->blog_id, 'upload_files' ) ) {
			return; // Bail
		}

		switch_to_blog( $book->blog_id );

		$allowed_file_types = [
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
		];
		$overrides = [
			'test_form' => false,
			'mimes' => $allowed_file_types,
		];
		$image = wp_handle_upload( $_FILES[ $meta_key ], $overrides );

		if ( ! empty( $image['error'] ) ) {
			restore_current_blog();
			wp_die( $image['error'] );
		}

		$old = get_user_meta( $this->userId, $meta_key, false );
		update_user_meta( $this->userId, $meta_key, $image['url'] );

		// Delete old images
		foreach ( $old as $old_url ) {
			$old_id = \Pressbooks\Image\attachment_id_from_url( $old_url );
			if ( $old_id ) {
				wp_delete_attachment( $old_id, true );
			}
		}

		// Insert new image, create thumbnails
		$args = [
			'post_mime_type' => $image['type'],
			'post_title' => __( 'Catalog Logo', 'pressbooks' ),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_name' => "pb-catalog-logo-{$this->userId}",
			'post_author' => $this->userId,
		];
		$id = wp_insert_attachment( $args, $image['file'], 0 );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $image['file'] ) );

		restore_current_blog();
	}


	/**
	 * Delete the cache(s)
	 */
	function deleteCache() {

		wp_cache_delete( "cat-{$this->userId}", 'pb' );
	}


	/**
	 * Delete the cache(s) by Book ID
	 *
	 * @param int $book_id
	 */
	function deleteCacheByBookId( $book_id ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$sql = "SELECT users_id FROM {$this->dbTable} WHERE blogs_id = %d ";
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $book_id ) ); // @codingStandardsIgnoreLine

		foreach ( $results as $user_id ) {
			wp_cache_delete( "cat-$user_id", 'pb' );
		}
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Upgrades
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Upgrade catalog.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {

		if ( $version < self::VERSION ) {
			$this->createOrUpdateTables();
		}
	}


	/**
	 * DB Delta the initial Catalog tables.
	 *
	 * If you change this, then don't forget to also change $this->dbColumns
	 *
	 * @see dbColumns
	 * @see http://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
	 */
	protected function createOrUpdateTables() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->dbTable} (
				users_id INT(11) NOT null,
  				blogs_id INT(11) NOT null,
  				deleted TINYINT(1) NOT null,
  				featured INT(11) DEFAULT 0 NOT null ,
  				PRIMARY KEY  (users_id,blogs_id),
  				KEY featured (featured)
				); ";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$this->dbLinkTable} (
				users_id INT(11) NOT null,
  				blogs_id INT(11) NOT null,
  				tags_id INT(11) NOT null,
  				tags_group INT(3) NOT null,
  				PRIMARY KEY  (users_id,blogs_id,tags_id,tags_group)
				); ";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$this->dbTagsTable} (
				id INT(11) NOT null AUTO_INCREMENT,
  				users_id INT(11) NOT null,
  				tag VARCHAR(200) NOT null,
  				PRIMARY KEY  (id),
  				UNIQUE KEY tag (tag(191))
				); ";
		dbDelta( $sql );
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Helpers
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Return an array of tags from a comma delimited string
	 *
	 * @param string $tags
	 *
	 * @return array
	 */
	static function stringToTags( $tags ) {

		$tags = mb_split( ',', $tags );

		foreach ( $tags as $key => &$val ) {
			$val = strip_tags( $val );
			$val = mb_convert_case( $val, MB_CASE_TITLE, 'UTF-8' );
			$val = mb_split( '\W', $val ); // Split on negated \w
			$val = implode( ' ', $val ); // Put back together with spaces
			$val = trim( $val );
			if ( ! $val ) {
				unset( $tags[ $key ] );
			}
		}

		return $tags;
	}


	/**
	 * Return a comma delimited string from an SQL array of tags, in alphabetical order.
	 *
	 * @param array $tags
	 *
	 * @return string
	 */
	static function tagsToString( array $tags ) {

		$tags = wp_list_sort( $tags, 'tag', 'asc' );

		$str = '';
		foreach ( $tags as $tag ) {
			$str .= $tag['tag'] . ', ';
		}

		return rtrim( $str, ', ' );
	}


	/**
	 * Catalog image is stored in user's active Media Library.
	 *
	 * @param int $user_id
	 * @param string $size
	 *
	 * @return string
	 */
	static function thumbnailFromUserId( $user_id, $size ) {

		$image_url = get_user_meta( $user_id, 'pb_catalog_logo', true );
		$book = get_active_blog_for_user( $user_id );
		if ( $book ) {
			switch_to_blog( $book->blog_id );
			$image_url = \Pressbooks\Image\thumbnail_from_url( $image_url, $size );
			restore_current_blog();
		}

		return $image_url;
	}


	/**
	 * WP Hook, Instantiate UI
	 */
	static function addMenu() {
		switch ( getset( '_REQUEST', 'action' ) ) {
			case 'edit_profile':
			case 'edit_tags':
				require( PB_PLUGIN_DIR . 'templates/admin/catalog.php' );
				break;
			case 'add':
			case 'remove':
				// This should not happen, formSubmit() is supposed to catch this
				break;
			default:
				Admin\Catalog_List_Table::addMenu();
				break;
		}
	}


	/**
	 * Find and load our catalog template.
	 *
	 * @return string
	 */
	static function getTemplatePath() {
		$overridden_template = locate_template( 'pb-catalog.php' );
		if ( $overridden_template ) {
			return $overridden_template;
		} else {
			return PB_PLUGIN_DIR . 'templates/pb-catalog.php';
		}
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Form stuff
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Catch me
	 */
	static function formSubmit() {

		if ( empty( static::isFormSubmission() ) || empty( current_user_can( 'read' ) ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		if ( static::isCurrentAction( 'add' ) ) {
			static::formBulk( 'add' );
		} elseif ( static::isCurrentAction( 'remove' ) ) {
			static::formBulk( 'remove' );
		} elseif ( static::isCurrentAction( 'edit_tags' ) ) {
			static::formTags();
		} elseif ( static::isCurrentAction( 'edit_profile' ) ) {
			static::formProfile();
		} elseif ( ! empty( $_REQUEST['add_book_by_url'] ) ) {
			static::formAddByUrl();
		}

	}


	/**
	 * Check if a user submitted something to index.php?page=pb_catalog
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'pb_catalog' !== $_REQUEST['page'] ) {
			return false;
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return true;
		}

		if ( static::isCurrentAction( 'add' ) || static::isCurrentAction( 'remove' ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Two actions are possible in a generic WP_List_Table form. The first takes precedence.
	 *
	 * @param $action
	 *
	 * @see \WP_List_Table::current_action
	 *
	 * @return bool
	 */
	static function isCurrentAction( $action ) {

		if ( isset( $_REQUEST['action'] ) && -1 !== (int) $_REQUEST['action'] ) {
			$compare = $_REQUEST['action'];
		} elseif ( isset( $_REQUEST['action2'] ) && -1 !== (int) $_REQUEST['action2'] ) {
			$compare = $_REQUEST['action2'];
		} else {
			return false;
		}

		return ( $action === $compare );
	}


	/**
	 * WP_Ajax hook for pb_delete_catalog_logo
	 */
	static function deleteLogo() {

		check_ajax_referer( 'pb-delete-catalog-logo' );

		$image_url = $_POST['filename'];
		$user_id = (int) $_POST['pid'];

		$book = get_active_blog_for_user( $user_id );
		if ( current_user_can_for_blog( $book->blog_id, 'upload_files' ) ) {

			switch_to_blog( $book->blog_id );

			// Delete old images
			$old_id = \Pressbooks\Image\attachment_id_from_url( $image_url );
			if ( $old_id ) {
				wp_delete_attachment( $old_id, true );
			}

			update_user_meta( $user_id, 'pb_catalog_logo', \Pressbooks\Image\default_cover_url() );

			restore_current_blog();
		}

		// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
		// Will append 0 to returned json string if we don't die()
		die();
	}


	/**
	 * Save bulk actions
	 *
	 * @param $action
	 */
	protected static function formBulk( $action ) {

		if ( ! class_exists( '\WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		$redirect_url = Admin\Catalog_List_Table::addSearchParamsToUrl( $redirect_url );

		/* Sanity check */

		if ( ! empty( $_REQUEST['book'] ) ) {
			// Bulk
			check_admin_referer( 'bulk-books' ); // Nonce auto-generated by WP_List_Table
			$books = $_REQUEST['book'];
		} elseif ( ! empty( $_REQUEST['ID'] ) ) {
			// Single item
			check_admin_referer( $_REQUEST['ID'] );
			$books = [ $_REQUEST['ID'] ];
		} else {
			// Handle empty bulk submission
			if ( ! empty( $_REQUEST['user_id'] ) ) {
				$redirect_url .= '&user_id=' . $_REQUEST['user_id'];
			}
			\Pressbooks\Redirect\location( $redirect_url );
		}

		// Make an educated guess as to who's catalog we are editing
		list( $user_id, $_ ) = explode( ':', $books[0] );

		if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
		}

		// Fix redirect URL
		if ( get_current_user_id() !== $user_id ) {
			$redirect_url .= '&user_id=' . $user_id;
		}

		/* Go! */

		$catalog = new static( $user_id );

		foreach ( $books as $book ) {
			list( $_, $book_id ) = explode( ':', $book );
			if ( 'add' === $action ) {
				$catalog->saveBook( $book_id, [] );
			} elseif ( 'remove' === $action ) {
				$catalog->deleteBook( $book_id );
			} else {
				// TODO: Throw Error
				$_SESSION['pb_errors'][] = "Invalid action: $action";
			}
		}

		$catalog->deleteCache();

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );

		// Redirect back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}


	/**
	 * Save tags to database
	 */
	protected static function formTags() {

		check_admin_referer( 'pb-user-catalog' );

		if ( ! empty( $_REQUEST['ID'] ) ) {
			list( $user_id, $blog_id ) = explode( ':', $_REQUEST['ID'] );
		} else {
			$user_id = '';
			$blog_id = '';
		}
		if ( ! empty( $_REQUEST['user_id'] ) ) {
			$user_id = absint( $_REQUEST['user_id'] );
		}
		if ( ! empty( $_REQUEST['blog_id'] ) ) {
			$blog_id = absint( $_REQUEST['blog_id'] );
		}

		if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
		}

		// Set Redirect URL
		if ( get_current_user_id() !== $user_id ) {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog&user_id=' . $user_id );
		} else {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		}

		/* Go! */
		$catalog = new static( $user_id );
		$featured = ( isset( $_REQUEST['featured'] ) ) ? absint( $_REQUEST['featured'] ) : 0;
		$catalog->saveBook(
			$blog_id, [
				'featured' => $featured,
			]
		);

		// Tags
		for ( $i = 1; $i <= self::MAX_TAGS_GROUP; ++$i ) {
			$catalog->deleteTags( $blog_id, $i );
			$tags = ( isset( $_REQUEST[ "tags_$i" ] ) ) ? $_REQUEST[ "tags_$i" ] : [];
			foreach ( $tags as $tag ) {
				$catalog->saveTag( $tag, $blog_id, $i );
			}
		}

		$catalog->deleteCache();

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );

		// Redirect back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}


	/**
	 * Save catalog profile to database
	 */
	protected static function formProfile() {

		check_admin_referer( 'pb-user-catalog' );

		$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;

		if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
		}

		// Set Redirect URL
		if ( get_current_user_id() !== $user_id ) {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog&user_id=' . $user_id );
		} else {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		}

		/* Go! */

		$catalog = new static( $user_id );
		$catalog->saveProfile( $_POST );
		$catalog->uploadLogo( 'pb_catalog_logo' );

		$catalog->deleteCache();

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );

		// Redirect back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}


	/**
	 * Add Book by URL
	 */
	static function formAddByUrl() {

		check_admin_referer( 'bulk-books' ); // Nonce auto-generated by WP_List_Table

		$catalog = new static();
		$user_id = $catalog->getUserId();

		// Set Redirect URL
		if ( get_current_user_id() !== $user_id ) {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog&user_id=' . $user_id );
		} else {
			$redirect_url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		}

		$url = wp_parse_url( \Pressbooks\Sanitize\canonicalize_url( $_REQUEST['add_book_by_url'] ) );
		$main = wp_parse_url( network_home_url() );

		if ( strpos( $url['host'], $main['host'] ) === false ) {
			$_SESSION['pb_errors'][] = __( 'Invalid URL.', 'pressbooks' );
			\Pressbooks\Redirect\location( $redirect_url );
		}

		if ( $url['host'] === $main['host'] ) {
			// Get slug using the path
			$slug = str_replace( $main['path'], '', $url['path'] );
			$slug = trim( $slug, '/' );
			$slug = explode( '/', $slug );
			$slug = $slug[0];
		} else {
			// Get slug using host
			$slug = str_replace( $main['host'], '', $url['host'] );
			$slug = trim( $slug, '.' );
			$slug = explode( '.', $slug );
			$slug = $slug[0];
		}

		$book_id = get_id_from_blogname( $slug );
		if ( ! $book_id ) {
			$_SESSION['pb_errors'][] = __( 'No book found.', 'pressbooks' );
			\Pressbooks\Redirect\location( $redirect_url );
		}

		$catalog->saveBook( $book_id, [] );
		$catalog->deleteCache();

		// Ok!
		$_SESSION['pb_notices'][] = __( 'Settings saved.' );

		// Redirect back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}
}
