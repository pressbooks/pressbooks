<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Organize;

use Pressbooks\Book;

/**
 * Update the status of one more posts.
 *
 * @since 5.0.0
 */
function update_post_visibility() {
	if ( check_ajax_referer( 'pb-organize-visibility' ) ) {
		$post_ids = explode( ',', $_POST['post_ids'] );
		$format = ( isset( $_POST['export'] ) ) ? 'export' : 'web';
		$visibility = absint( $_POST[ $format ] );

		if ( current_user_can( 'publish_posts' ) ) {
			foreach ( $post_ids as $post_id ) {
				$post_id = (int) $post_id; // Paranoia reasons
				$current_status = get_post_status( $post_id );

				if ( $format === 'web' ) {
					if ( $visibility === 1 ) {
						if ( in_array( $current_status, [ 'private', 'publish' ], true ) ) {
							$post_status = 'publish';
						} else {
							$post_status = 'web-only';
						}
					} elseif ( $visibility === 0 ) {
						if ( in_array( $current_status, [ 'private', 'publish' ], true ) ) {
							$post_status = 'private';
						} else {
							$post_status = 'draft';
						}
					}
				} elseif ( $format === 'export' ) {
					if ( $visibility === 1 ) {
						if ( in_array( $current_status, [ 'web-only', 'publish' ], true ) ) {
							$post_status = 'publish';
						} else {
							$post_status = 'private';
						}
					} elseif ( $visibility === 0 ) {
						if ( in_array( $current_status, [ 'web-only', 'publish' ], true ) ) {
							$post_status = 'web-only';
						} else {
							$post_status = 'draft';
						}
					}
				}

				if ( ! empty( $post_status ) ) {
					$result = wp_update_post(
						[
							'ID' => $post_id,
							'post_status' => $post_status,
						]
					);
					clean_post_cache( $post_id );
				}
			}
		}

		Book::deleteBookObjectCache();
	}
	// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
	// Will append 0 to returned json string if we don't die()
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		die();
	}
}

/**
 * Update the pb_show_title metadata value for one or more posts.
 *
 * @since 5.0.0
 */
function update_post_title_visibility() {
	if ( check_ajax_referer( 'pb-organize-showtitle' ) ) {
		$post_ids = explode( ',', $_POST['post_ids'] );
		$pb_show_title = $_POST['show_title'];

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id; // Paranoia reasons
			if ( current_user_can( 'edit_post', $post_id ) ) {
				update_post_meta( $post_id, 'pb_show_title', $pb_show_title );
			}
		}

		Book::deleteBookObjectCache();
	}
	// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
	// Will append 0 to returned json string if we don't die()
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		die();
	}
}

/**
 * WP_Ajax hook
 * Updates the menu_order field associated with a post after reordering it
 * and update its associated parent, if necessary.
 *
 * @since 5.0.0
 */
function reorder() {
	/** @var $wpdb \wpdb */
	global $wpdb;
	if ( check_ajax_referer( 'pb-organize-reorder' ) ) {
		$id = absint( $_POST['id'] );
		parse_str( $_POST['new_order'], $new_order );
		parse_str( $_POST['old_order'], $old_order );
		$new_parent = (int) $_POST['new_parent'];
		$old_parent = (int) $_POST['old_parent'];
		// If the parent changed, set new parent for chapter
		// and new order for parent
		if ( $new_parent !== $old_parent ) {
			$post = [];
			$post['ID'] = $id;
			$post['post_parent'] = $new_parent;
			wp_update_post( $post );
			if ( is_array( $new_order ) ) {
				foreach ( $new_order as $key => $values ) {
					foreach ( $values as $position => $id ) {
						$position += 1; // array is 0-indexed, but we want it to start from 1
						$wpdb->update(
							$wpdb->posts, [
								'menu_order' => $position,
							], [
								'ID' => $id,
							]
						);
						clean_post_cache( $id );
					}
				}
			}
		}
		// always update the order of the part this chapter was originally in
		if ( is_array( $old_order ) ) {
			foreach ( $old_order as $key => $values ) {
				foreach ( $values as $position => $id ) {
					$position += 1; // array is 0-indexed, but we want it to start from 1
					$wpdb->update(
						$wpdb->posts, [
							'menu_order' => $position,
						], [
							'ID' => $id,
						]
					);
					clean_post_cache( $id );
				}
			}
		}

		Book::deleteBookObjectCache();
	}
	// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
	// Will append 0 to returned json string if we don't die()
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		die();
	}
}
