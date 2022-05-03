<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Analytics;

use Pressbooks\Book;

/**
 * Print the script.
 */
function print_analytics() {

	$network_analytics_code = get_site_option( 'ga_mu_uaid' );
	$books_codes_are_allowed = get_site_option( 'ga_mu_site_specific_allowed' );
	if ( $books_codes_are_allowed ) {
		$book_analytics_code = get_option( 'ga_mu_uaid' );
	} else {
		$book_analytics_code = false;
	}

	$tracking_html = '';
	if ( ! empty( $network_analytics_code ) ) {
		$tracking_html .= "ga('create', '{$network_analytics_code}', 'auto');\n";
		$tracking_html .= "ga('send', 'pageview');\n";
	}
	$ecommerce_tracking = apply_filters( 'pb_ecommerce_tracking', '' );
	if ( ! empty( $ecommerce_tracking ) ) {
		$tracking_html .= $ecommerce_tracking;
	}
	if ( ! empty( $book_analytics_code ) && Book::isBook() ) {
		if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
			$tracking_html .= "ga('create', '{$book_analytics_code}', 'auto', 'bookTracker');\n";
			$tracking_html .= "ga('bookTracker.send', 'pageview');\n";
		} else {
			// TODO: https://developers.google.com/analytics/devguides/collection/upgrade/reference/gajs-analyticsjs#cookiepath
			// TODO: https://core.trac.wordpress.org/ticket/42093
			$path = trailingslashit( parse_url( home_url(), PHP_URL_PATH ) );
			$tracking_html .= "ga('create', '{$book_analytics_code}', 'auto', 'bookTracker', {'cookiePath': '{$path}'});\n";
			$tracking_html .= "ga('bookTracker.send', 'pageview');\n";
		}
	}
	$html = '';
	if ( ! empty( $tracking_html ) ) {
		$html .= "<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');\n";
		$html .= $tracking_html;
		$html .= "</script>\n<!-- End Google Analytics -->";
	}
	echo $html;
}

/**
 * Migrate analytics settings
 */
function migrate() {
	if ( get_site_option( 'pressbooks_analytics_migration' ) === false ) {

		switch_to_blog( 1 );
		$ga_mu_uaid_network = get_option( 'ga_mu_uaid' );
		$ga_mu_site_specific_allowed = get_option( 'ga_mu_site_specific_allowed' );
		restore_current_blog();

		update_site_option( 'ga_mu_uaid', $ga_mu_uaid_network );
		update_site_option( 'ga_mu_site_specific_allowed', $ga_mu_site_specific_allowed );

		update_site_option( 'pressbooks_analytics_migration', 1 );
	}
}
