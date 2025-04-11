<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use WP_Site;

class Privacy {

	/**
	 * @var Privacy
	 */
	static $instance = null;

	/**
	 * @return Privacy
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Privacy $obj
	 */
	static public function hooks( Privacy $obj ) {
		add_filter( 'schedule_event', [ $obj, 'reschedulePrivacyCron' ] );
	}

	/**
	 * @since 5.4.0
	 *
	 * Change the `wp_privacy_delete_old_export_files schedule` from hourly to twicedaily
	 *
	 * @see wp_schedule_delete_old_privacy_export_files
	 *
	 * @param object $event
	 *
	 * @return object $event
	 */
	public function reschedulePrivacyCron( $event ) {
		if ( isset( $event->hook ) && $event->hook === 'wp_privacy_delete_old_export_files' ) {
			$s = 'twicedaily';
			$event->schedule = $s;
			$schedules = wp_get_schedules();
			if ( isset( $event->interval ) && isset( $schedules[ $s ] ) ) {
				$event->interval = $schedules[ $s ]['interval'];
			}
		}
		return $event;
	}

	/**
	 * @since 5.4.0
	 *
	 * Suggest text for the Privacy Policy.
	 *
	 * @see https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/
	 */
	public function addPrivacyPolicyContent() {

		$content = 'TODO.'; // TODO: Add real privacy policy suggestions.

		wp_add_privacy_policy_content( 'Pressbooks', wp_kses_post( wpautop( $content, false ) ) );
	}

	/**
	 * @since 6.15.2
	 *
	 * A filter to allow permissive private content for certain roles.
	 */
	public static function showPermissivePrivateContent(): void {
		add_filter( 'pre_get_posts', function ( $query ) {
			if ( is_user_logged_in() ) {
				$permissive_private_content = (int) get_option( 'permissive_private_content', 0 );
				$current_user = wp_get_current_user();
				$permissive_roles = [ 'subscriber', 'collaborator', 'author' ];
				if ( $permissive_private_content && array_intersect( $permissive_roles, $current_user->roles ) ) {
					$query->set( 'post_status', [ 'publish', 'pending', 'draft', 'private', 'web-only' ] );
				}
			}
			return $query;
		});
	}

	public static function setDefaultPermissivePrivateContent( WP_Site $site ): void {
		switch_to_blog( $site->blog_id );
		update_option( 'permissive_private_content', 1 );
		restore_current_blog();
	}
}
