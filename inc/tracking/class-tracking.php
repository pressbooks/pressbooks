<?php

namespace Pressbooks\Tracking;

abstract class Tracking {

	const DB_VERSION = '1.1';

	const DB_VERSION_OPTION = 'pressbooks_tracking_db_version';

	const IP_SALT_OPTION = 'pressbooks_tracking_ip_salt';

	protected static $instance;

	/**
	 * Tracking table
	 *
	 * @var string
	 */
	protected $dbTable;

	/**
	 * Tracking type
	 *
	 * @var string
	 */
	protected $type;

	protected function __construct() {
		global $wpdb;

		$this->dbTable = $wpdb->base_prefix . 'pressbooks_tracking';
	}

	public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static;

			static::$instance->setup();
		}

		return static::$instance;
	}

	/**
	 * Set up the database table.
	 *
	 * Version-gated: dbDelta only runs when DB_VERSION changes. The schema
	 * string must stay dbDelta-compatible: no IF NOT EXISTS (its table-name
	 * regex captures IF as the name and skips the ALTER/diff path on existing
	 * tables), no backticks (they mismatch DESCRIBE output and can re-attempt
	 * ADDs of existing columns), one definition per line, two spaces after
	 * PRIMARY KEY.
	 *
	 * @return void
	 */
	protected function setup(): void {
		if ( get_site_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->dbTable} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				blog_id bigint(20) NOT NULL,
				track_type varchar(30) NOT NULL,
				track_value varchar(255),
				logged_in boolean NOT NULL default false,
				created_at datetime NOT NULL,
				user_agent varchar(500) DEFAULT NULL,
				referrer varchar(500) DEFAULT NULL,
				ip_hash char(64) DEFAULT NULL,
				PRIMARY KEY  (id)
				);";

		dbDelta( $sql );

		update_site_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Store tracking event data.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function store( $value ): void {
		global $wpdb;

		$date = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		$wpdb->insert(
			$this->dbTable, [
				'blog_id' => get_current_blog_id(),
				'track_type' => $this->type,
				'track_value' => $value,
				'logged_in' => is_user_logged_in(),
				'created_at' => $date->format( 'Y-m-d H:i:s' ),
				'user_agent' => $this->getUserAgent(),
				'referrer' => $this->getReferrer(),
				'ip_hash' => $this->getIpHash(),
			]
		);
	}

	/**
	 * Sanitized, truncated user agent, or null when absent.
	 *
	 * @return string|null
	 */
	protected function getUserAgent(): ?string {
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		return $user_agent === '' ? null : mb_substr( $user_agent, 0, 500 );
	}

	/**
	 * Sanitized, truncated referrer, or null when absent.
	 *
	 * @return string|null
	 */
	protected function getReferrer(): ?string {
		$referrer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );

		return $referrer === '' ? null : mb_substr( $referrer, 0, 500 );
	}

	/**
	 * Salted SHA-256 hash of the client IP, or null when unavailable.
	 * The raw IP is never stored.
	 *
	 * @return string|null
	 */
	protected function getIpHash(): ?string {
		/**
		 * Filter the client IP used for hashed tracking.
		 *
		 * Behind a proxy/CDN, REMOTE_ADDR may be the proxy address; ops can
		 * point this at the sanitized real-client-IP source for the network.
		 *
		 * @since 6.43.0
		 *
		 * @param string $ip
		 */
		$ip = apply_filters( 'pb_tracking_client_ip', isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '' );

		if ( ! $ip ) {
			return null;
		}

		return hash( 'sha256', $ip . $this->getIpSalt() );
	}

	/**
	 * Dedicated persistent salt, independent of wp_salt() so WordPress key
	 * rotation does not break hash continuity.
	 *
	 * @return string
	 */
	protected function getIpSalt(): string {
		$salt = get_site_option( self::IP_SALT_OPTION );

		if ( ! $salt ) {
			$salt = wp_generate_password( 64, true, true );
			add_site_option( self::IP_SALT_OPTION, $salt );
		}

		return $salt;
	}
}
