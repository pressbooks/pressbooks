<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Analytics;

/**
 * Print the script.
 */
function print_analytics() {

	switch_to_blog( 1 );
	$ga_mu_uaid_network = get_option( 'ga_mu_uaid' );
	$ga_mu_maindomain = get_option( 'ga_mu_maindomain' );
	$ga_mu_site_specific_allowed = get_option( 'ga_mu_site_specific_allowed' );
	restore_current_blog();

	$ga_mu_uaid = get_option( 'ga_mu_uaid' );

	$network = false;
	$book = false;

	if ( isset( $ga_mu_uaid_network ) && $ga_mu_uaid_network !== '' && $ga_mu_uaid_network !== '0') {
		$network = true;
	}
	if ( isset( $ga_mu_uaid ) && $ga_mu_uaid !== '' && $ga_mu_uaid !== '0') {
		$book = true;
	}

	if ( $network && $book ) {
		if ( $ga_mu_uaid_network == $ga_mu_uaid ) {
			$book = false;
		}
	}

	if ( $book == true && ( !isset( $ga_mu_site_specific_allowed ) || $ga_mu_site_specific_allowed == '' || $ga_mu_site_specific_allowed == '0' ) ) {
		$book = false;
	}

	if ( $network || $book ) {
		$html = "<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n";
		if ( $network ) {
			$html .= "ga('create', '". $ga_mu_uaid_network . "', 'auto');\n";
			$html .= "ga('send', 'pageview');\n";
		}
		$html .= apply_filters( 'pb_ecommerce_tracking', '' );
		if ( $book ) {
			$html .= "ga('create', '". $ga_mu_uaid . "', 'auto', 'bookTracker');";
			$html .= "ga('bookTracker.send', 'pageview');";
		}
		$html .= "</script>\n<!-- End Google Analytics -->";
	}
	echo $html;
}
