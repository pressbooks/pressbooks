<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\L10n;

/**
 * KindleGen is based on Mobipocket Creator and apparently supports only the following language codes.
 * This populates the language dropdown on the Book Info page.
 *
 * @see http://www.mobileread.com/forums/showpost.php?p=2453537&postcount=2
 * @return array
 */
function supported_languages() {

	$languages = array(
		'' => '',
		'af' => 'Afrikaans',
		'sq' => 'Albanian',
		'ar' => 'Arabic',
		'ar-dz' => 'Arabic (Algeria)',
		'ar-bh' => 'Arabic (Bahrain)',
		'ar-eg' => 'Arabic (Egypt)',
		'ar-jo' => 'Arabic (Jordan)',
		'ar-kw' => 'Arabic (Kuwait)',
		'ar-lb' => 'Arabic (Lebanon)',
		'ar-ma' => 'Arabic (Morocco)',
		'ar-om' => 'Arabic (Oman)',
		'ar-qa' => 'Arabic (Qatar)',
		'ar-sa' => 'Arabic (Saudi Arabia)',
		'ar-sy' => 'Arabic (Syria)',
		'ar-tn' => 'Arabic (Tunisia)',
		'ar-ae' => 'Arabic (U.A.E.)',
		'ar-ye' => 'Arabic (Yemen)',
		'hy' => 'Armenian',
		'az' => 'Azeri',
		'eu' => 'Basque',
		'be' => 'Belarusian',
		'bn' => 'Bengali',
		'bg' => 'Bulgarian',
		'ca' => 'Catalan',
		'zh' => 'Chinese',
		'zh-hk' => 'Chinese (Hong Kong)',
		'zh-cn' => 'Chinese (PRC)',
		'zh-sg' => 'Chinese (Singapore)',
		'zh-tw' => 'Chinese (Taiwan)',
		'hr' => 'Croatian',
		'cs' => 'Czech',
		'da' => 'Danish',
		'nl' => 'Dutch',
		'nl-be' => 'Dutch (Belgium)',
		'en' => 'English',
		'en-au' => 'English (Australia)',
		'en-bz' => 'English (Belize)',
		'en-ca' => 'English (Canada)',
		'en-ie' => 'English (Ireland)',
		'en-jm' => 'English (Jamaica)',
		'en-nz' => 'English (New Zealand)',
		'en-ph' => 'English (Philippines)',
		'en-za' => 'English (South Africa)',
		'en-tt' => 'English (Trinidad)',
		'en-gb' => 'English (United Kingdom)',
		'en-us' => 'English (United States)',
		'en-zw' => 'English (Zimbabwe)',
		'et' => 'Estonian',
		'fo' => 'Faeroese',
		'fa' => 'Farsi',
		'fi' => 'Finnish',
		'fr-be' => 'French (Belgium)',
		'fr-ca' => 'French (Canada)',
		'fr' => 'French',
		'fr-lu' => 'French (Luxembourg)',
		'fr-mc' => 'French (Monaco)',
		'fr-ch' => 'French (Switzerland)',
		'ka' => 'Georgian',
		'de' => 'German',
		'de-at' => 'German (Austria)',
		'de-li' => 'German (Liechtenstein)',
		'de-lu' => 'German (Luxembourg)',
		'de-ch' => 'German (Switzerland)',
		'el' => 'Greek',
		'gu' => 'Gujarati',
		'he' => 'Hebrew',
		'hi' => 'Hindi',
		'hu' => 'Hungarian',
		'is' => 'Icelandic',
		'id' => 'Indonesian',
		'it' => 'Italian',
		'it-ch' => 'Italian (Switzerland)',
		'ja' => 'Japanese',
		'kn' => 'Kannada',
		'kk' => 'Kazakh',
		'x-kok' => 'Konkani',
		'ko' => 'Korean',
		'lv' => 'Latvian',
		'lt' => 'Lithuanian',
		'mk' => 'Macedonian',
		'ms' => 'Malay',
		'ml' => 'Malayalam',
		'mt' => 'Maltese',
		'mr' => 'Marathi',
		'ne' => 'Nepali',
		'no' => 'Norwegian',
		'nb' => 'Norwegian (Bokm&aring;l)',
		'nn' => 'Norwegian (Nynorsk)',
		'or' => 'Oriya',
		'pl' => 'Polish',
		'pt' => 'Portuguese',
		'pt-br' => 'Portuguese (Brazil)',
		'pa' => 'Punjabi',
		'rm' => 'Rhaeto-Romanic',
		'ro' => 'Romanian',
		'ro-mo' => 'Romanian (Moldova)',
		'ru' => 'Russian',
		'ru-mo' => 'Russian (Moldova)',
		'sz' => 'Sami (Lappish)',
		'sa' => 'Sanskrit',
		'sr' => 'Serbian',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'sb' => 'Sorbian',
		'es' => 'Spanish',
		'es-ar' => 'Spanish (Argentina)',
		'es-bo' => 'Spanish (Bolivia)',
		'es-cl' => 'Spanish (Chile)',
		'es-co' => 'Spanish (Colombia)',
		'es-cr' => 'Spanish (Costa Rica)',
		'es-do' => 'Spanish (Dominican Republic)',
		'es-ec' => 'Spanish (Ecuador)',
		'es-sv' => 'Spanish (El Salvador)',
		'es-gt' => 'Spanish (Guatemala)',
		'es-hn' => 'Spanish (Honduras)',
		'es-mx' => 'Spanish (Mexico)',
		'es-ni' => 'Spanish (Nicaragua)',
		'es-pa' => 'Spanish (Panama)',
		'es-py' => 'Spanish (Paraguay)',
		'es-pe' => 'Spanish (Peru)',
		'es-pr' => 'Spanish (Puerto Rico)',
		'es-uy' => 'Spanish (Uruguay)',
		'es-ve' => 'Spanish (Venezuela)',
		'sx' => 'Sutu',
		'sw' => 'Swahili',
		'sv' => 'Swedish',
		'sv-fi' => 'Swedish (Finland)',
		'ta' => 'Tamil',
		'tt' => 'Tatar',
		'te' => 'Telugu',
		'th' => 'Thai',
		'ts' => 'Tsonga',
		'tn' => 'Tswana',
		'tr' => 'Turkish',
		'uk' => 'Ukranian',
		'ur' => 'Urdu',
		'uz' => 'Uzbek',
		'vi' => 'Vietnamese',
		'xh' => 'Xhosa',
		'zu' => 'Zulu',
	);

	asort( $languages );

	return $languages;
}

/**
 * This helps us convert KindleGen language codes to WordPress-compatible ones and vice versa.
 *
 * @return array
 */
function wplang_codes() {

	$languages = array(
		'af' => '', // Afrikaans
		'sq' => 'sq', // Albanian
		'ar' => 'ar', // Arabic
		'ar-dz' => 'ar', // Arabic (Algeria)
		'ar-bh' => 'ar', // Arabic (Bahrain)
		'ar-eg' => 'ar', // Arabic (Egypt)
		'ar-jo' => 'ar', // Arabic (Jordan)
		'ar-kw' => 'ar', // Arabic (Kuwait)
		'ar-lb' => 'ar', // Arabic (Lebanon)
		'ar-ma' => 'ar', // Arabic (Morocco)
		'ar-om' => 'ar', // Arabic (Oman)
		'ar-qa' => 'ar', // Arabic (Qatar)
		'ar-sa' => 'ar', // Arabic (Saudi Aria)
		'ar-sy' => 'ar', // Arabic (Syria)
		'ar-tn' => 'ar', // Arabic (Tunisia)
		'ar-ae' => 'ar', // Arabic (U.A.E.)
		'ar-ye' => 'ar', // Arabic (Yemen)
		'hy' => 'hy', // Armenian
		'az' => 'az',
		'eu' => 'eu',
		'be' => '', // Belarusian
		'bn' => '', // Bengali
		'bg' => 'bg_BG',
		'ca' => 'ca', // Catalan
		'zh' => 'zh_CN', // Chinese
		'zh-hk' => 'zh_CN', // Chinese (Hong Kong)
		'zh-cn' => 'zh_CN', // Chinese (PRC)
		'zh-sg' => 'zh_CN', // Chinese (Singapore)
		'zh-tw' => 'zh_TW', // Chinese (Taiwan)
		'hr' => 'hr',
		'cs' => '', // Czech
		'da' => 'da_DK',
		'nl' => 'nl_NL',
		'nl-be' => 'nl_NL', // Dutch (Belgium)
		'en' => 'en_US',
		'en-au' => 'en_AU',
		'en-bz' => '', // English (Belize)
		'en-ca' => 'en_CA',
		'en-ie' => '', // English (Ireland)
		'en-jm' => '', // English (Jamaica)
		'en-nz' => '', // English (New Zealand)
		'en-ph' => '', // English (Philippines)
		'en-za' => '', // English (South Africa)
		'en-tt' => '', // English (Trinidad)
		'en-gb' => 'en_GB',
		'en-us' => 'en_US',
		'en-zw' => '', // English (Zimbabwe)
		'et' => 'et',
		'fo' => '', // Faeroese
		'fa' => '', // Farsi
		'fi' => 'fi',
		'fr-be' => 'fr_FR', // French (Belgium)
		'fr-ca' => 'fr_FR', // French (Canada)
		'fr' => 'fr_FR',
		'fr-lu' => 'fr_FR', // French (Luxembourg)
		'fr-mc' => 'fr_FR', // French (Monaco)
		'fr-ch' => 'fr_FR', // French (Switzerland)
		'ka' => '', // Georgian
		'de' => 'de_DE',
		'de-at' => 'de_DE', // German (Austria)
		'de-li' => 'de_DE', // German (Liechtenstein)
		'de-lu' => 'de_DE', // German (Luxembourg)
		'de-ch' => 'de_CH',
		'el' => 'el',
		'gu' => '', // Gujarati
		'he' => 'he_IL',
		'hi' => '', // Hindi
		'hu' => 'hu_HU',
		'is' => 'is_IS',
		'id' => 'id_ID',
		'it' => 'it_IT',
		'it-ch' => 'it_IT', // Italian (Switzerland)
		'ja' => 'ja',
		'kn' => '', // Kannada
		'kk' => '', // Kazakh
		'x-kok' => '', // Konkani
		'ko' => 'ko_KR',
		'lv' => '', // Latvian
		'lt' => 'lt_LT',
		'mk' => '', // Macedonian
		'ms' => '', // Malay
		'ml' => '', // Malayalam
		'mt' => '', // Maltese
		'mr' => '', // Marathi
		'ne' => '', // Nepali
		'no' => 'nb_NO',
		'nb' => 'nb_NO',
		'nn' => 'nn_NO',
		'or' => 'Oriya',
		'pl' => 'pl_PL',
		'pt' => 'pt_PT',
		'pt-br' => 'pt_BR',
		'pa' => '', // Punjabi
		'rm' => '', // Rhaeto-Romanic
		'ro' => 'ro_RO',
		'ro-mo' => 'ro_RO', // Romanian (Moldova)
		'ru' => 'ru_RU',
		'ru-mo' => 'ru_RU', // Russian (Moldova)
		'sz' => '', // Sami (Lappish)
		'sa' => '', // Sanskrit
		'sr' => 'sr_RS',
		'sk' => 'sk_SK',
		'sl' => 'sl_SI',
		'sb' => '', // Sorbian
		'es' => 'es_ES',
		'es-ar' => '', // Spanish (Argentina)
		'es-bo' => '', // Spanish (Bolivia)
		'es-cl' => 'es_CL',
		'es-co' => '', // Spanish (Colombia)
		'es-cr' => '', // Spanish (Costa Rica)
		'es-do' => '', // Spanish (Dominican Republic)
		'es-ec' => '', // Spanish (Ecuador)
		'es-sv' => '', // Spanish (El Salvador)
		'es-gt' => '', // Spanish (Guatemala)
		'es-hn' => '', // Spanish (Honduras)
		'es-mx' => 'es_MX',
		'es-ni' => '', // Spanish (Nicaragua)
		'es-pa' => '', // Spanish (Panama)
		'es-py' => '', // Spanish (Paraguay)
		'es-pe' => 'es_PE',
		'es-pr' => '', // Spanish (Puerto Rico)
		'es-uy' => '', // Spanish (Uruguay)
		'es-ve' => '', // Spanish (Venezuela)
		'sx' => '', // Sutu
		'sw' => '', // Swahili
		'sv' => 'sv_SE',
		'sv-fi' => 'sv_SE', // Swedish (Finland)
		'ta' => '', // Tamil
		'tt' => '', // Tatar
		'te' => '', // Telugu
		'th' => 'th',
		'ts' => '', // Tsonga
		'tn' => '', // Tswana
		'tr' => 'tr_TR',
		'uk' => 'uk',
		'ur' => '', // Urdu
		'uz' => '', // Uzbek
		'vi' => '', // Vietnamese
		'xh' => '', // Xhosa
		'zu' => '', // Zulu
	);

	return $languages;
}


/**
 * The fully-translated and installed languages for the Pressbooks dashboard.
 * Populates the language selector on the User Profile.
 *
 * @return array
 */
function get_dashboard_languages() {

	$languages = array(
		'en_US' =>	__( 'English (United States)', 'pressbooks' ),
		'zh_TW' =>	__( 'Chinese (Taiwan)', 'pressbooks' ),
		'et' =>		__( 'Estonian', 'pressbooks' ),
		'fr_FR' =>	__( 'French (France)', 'pressbooks' ),
		'de_DE' =>	__( 'German', 'pressbooks' ),
		'it_IT' =>	__( 'Italian', 'pressbooks' ),
		'ja' =>		__( 'Japanese', 'pressbooks' ),
		'pt_BR' =>	__( 'Portuguese (Brazil)', 'pressbooks' ),
		'es_ES' =>	__( 'Spanish', 'pressbooks' ),
		'sv_SE' =>	__( 'Swedish', 'pressbooks' ),
	);

	return $languages;
}


/**
 * Override get_locale
 * For performance reasons, we only want functions in this namespace to call WP get_locale once.
 *
 * @return string
 */
function get_locale() {

	// Cheap cache
	static $locale = null;

	if ( empty ( $locale ) ) {
		$locale = \get_locale();
	}

	return $locale;
}

/**
 * When multiple mo-files are loaded for the same domain, the first found translation will be used. To allow for easier
 * customization we load from the WordPress languages directory by default then fallback on our own, if any.
 */
function load_plugin_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'pressbooks' );
	load_textdomain( 'pressbooks', WP_LANG_DIR . '/pressbooks/pressbooks-' . $locale . '.mo' );
	\load_plugin_textdomain( 'pressbooks', false, 'pressbooks/languages' );
}


/**
 * Change core WordPress strings.
 *
 * @param $translated
 * @param $original
 * @param $domain
 *
 * @return mixed
 */
function override_core_strings( $translated, $original, $domain ) {

	// var_dump( array( $translated, $original, $domain) );

	$overrides = include_core_overrides();

	if ( isset( $overrides[$original] ) ) {
		$translations = get_translations_for_domain( $domain );
		$translated = $translations->translate( $overrides[$original] );
	}

	return $translated;
}

/**
 * Include the core WordPress override file.
 * Looks for ./languages/core-en_US.php, where "en_US" is defined by get_locale()
 * Expects $overrides array.
 * For performance reasons this function will include the file only once.
 *
 * @return array
 */
function include_core_overrides() {

	// Cheap cache
	static $_overrides = array();

	$locale = apply_filters( 'plugin_locale', get_locale(), 'pressbooks' );
	$filename = PB_PLUGIN_DIR . "languages/core-" . $locale . ".php";

	if ( ! isset( $_overrides[$locale] ) ) {
		$_overrides[$locale] = array();
		if ( file_exists( $filename ) ) {
			$_overrides[$locale] = include( $filename );
		}
	}

	return $_overrides[$locale];
}


/**
 * Hook for add_filter('locale ', ...), change the user interface language
 *
 * @param string $lang
 *
 * @return string
 */
function set_locale( $lang ) {

	// Cheap cache
	static $loc = '__UNSET__';

	if ( is_admin() ) { // go with the user setting
		// get_current_user_id uses wp_get_current_user which may not be available the first time(s) get_locale is called
		if ( '__UNSET__' == $loc && function_exists( 'wp_get_current_user' ) ) {
			$loc = get_user_option( 'user_interface_lang' );
		}

	} elseif ( @$GLOBALS['pagenow'] == 'wp-signup.php' ) {
		// use global setting
		$loc = get_site_option( 'WPLANG' );
	} else {
		// go with the book info setting
		$metadata = \Pressbooks\Book::getBookInformation();

		if (  '__UNSET__' == $loc && !empty( $metadata['pb_language'] ) ) {
			$locations = \Pressbooks\L10n\wplang_codes();
			$loc = $locations[$metadata['pb_language']];
		}
	}

	// Return
	if ( '__UNSET__' == $loc ) {
		return $lang;
	} else {
		return ( $loc ? $loc : $lang );
	}

}

/**
 * Hook for add_filter('locale ', ...), change the user interface language
 *
 * @param string $lang
 *
 * @return string
 */
function set_root_locale( $lang ) {

	$loc = get_site_option( 'WPLANG' );
	return $loc;

}


/**
 * Sets the interface language for new users to the site's language.
 *
 * @return array
 */
function set_user_interface_lang( $user_id ) {
	$locale = get_site_option( 'WPLANG' );
    if ( $locale ) {
	    update_user_meta( $user_id, 'user_interface_lang', $locale );
	}
}


/**
 * Convert integer to roman numeral
 *
 * @param int $integer
 *
 * @return string
 */
function romanize( $integer ) {

	$integer = absint( $integer );

	$table = array( 'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1 );
	$return = '';
	while ( $integer > 0 ) {
		foreach ( $table as $rom => $arb ) {
			if ( $integer >= $arb ) {
				$integer -= $arb;
				$return .= $rom;
				break;
			}
		}
	}

	return $return;
}


/**
 * Use the book locale to load POT translations?
 *
 * @return bool
 */
function use_book_locale() {

	if ( \Pressbooks\Modules\Export\Export::isFormSubmission() && is_array( @$_POST['export_formats'] ) ) {
		return true;
	}

	$uri = $_SERVER['REQUEST_URI'];
	if ( strpos( $uri, '/format/xhtml' ) !== false ) {
		return true;
	}

	return false;
}
