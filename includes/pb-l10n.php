<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\L10n;


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

	// get_current_user_id uses wp_get_current_user which may not be available the first time(s) get_locale is called
	if ( '__UNSET__' == $loc && function_exists( 'wp_get_current_user' ) ) {
		$loc = get_user_option( 'user_interface_lang' );
	}

	// Return
	if ( '__UNSET__' == $loc ) {
		return $lang;
	} else {
		return ( $loc ? $loc : $lang );
	}
}


/**
 * KindleGen is based on Mobipocket Creator and apparently supports only the following language codes:
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
		'ka' => 'Geogian',
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
 * Number to words.
 *
 * @param int $number
 *
 * @return string
 */
function number_to_words( $number ) {

	$words = array(
		'zero',
		'one',
		'two',
		'three',
		'four',
		'five',
		'six',
		'seven',
		'eight',
		'nine',
		'ten',
		'eleven',
		'twelve',
		'thirteen',
		'fourteen',
		'fifteen',
		'sixteen',
		'seventeen',
		'eighteen',
		'nineteen',
		'twenty',
		30 => 'thirty',
		40 => 'fourty',
		50 => 'fifty',
		60 => 'sixty',
		70 => 'seventy',
		80 => 'eighty',
		90 => 'ninety',
		100 => 'hundred',
		1000 => 'thousand'
	);

	$number_in_words = '';
	if ( is_numeric( $number ) ) {
		$number = (int) round( $number );
		if ( $number < 0 ) {
			$number = - $number;
			$number_in_words = 'minus ';
		}
		if ( $number > 1000 ) {
			$number_in_words = $number_in_words . number_to_words( floor( $number / 1000 ) ) . " " . $words[1000];
			$hundreds = $number % 1000;
			$tens = $hundreds % 100;
			if ( $hundreds > 100 ) {
				$number_in_words = $number_in_words . ", " . number_to_words( $hundreds );
			} elseif ( $tens ) {
				$number_in_words = $number_in_words . " and " . number_to_words( $tens );
			}
		} elseif ( $number > 100 ) {
			$number_in_words = $number_in_words . number_to_words( floor( $number / 100 ) ) . " " . $words[100];
			$tens = $number % 100;
			if ( $tens ) {
				$number_in_words = $number_in_words . " and " . number_to_words( $tens );
			}
		} elseif ( $number > 20 ) {
			$number_in_words = $number_in_words . " " . $words[10 * floor( $number / 10 )];
			$units = $number % 10;
			if ( $units ) {
				$number_in_words = $number_in_words . number_to_words( $units );
			}
		} else {
			$number_in_words = $number_in_words . " " . $words[$number];
		}

		return trim( $number_in_words );
	}

	return 'unknown';
}


/**
 * Convert integer to roman numeral
 *
 * @param      $integer
 * @param bool $upcase
 *
 * @return string
 */
function romanize( $integer, $upcase = true ) {

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

	if ( \PressBooks\Export\Export::isFormSubmission() && is_array( @$_POST['export_formats'] ) ) {
		return true;
	}

	$uri = $_SERVER['REQUEST_URI'];
	if ( strpos( $uri, '/format/xhtml' ) !== false ) {
		return true;
	}

	return false;
}