<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

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
}
