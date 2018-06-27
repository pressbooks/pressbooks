<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class Gdpr {

	/**
	 * @var Gdpr
	 */
	static $instance = null;

	/**
	 * @return Gdpr
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Gdpr $obj
	 */
	static public function hooks( Gdpr $obj ) {
		add_filter( 'schedule_event', [ $obj, 'reschedulePrivacyDeleteOldExportFiles' ] );
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
	public function reschedulePrivacyDeleteOldExportFiles( $event ) {
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
}
