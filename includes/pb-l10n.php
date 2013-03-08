<?php
/**
 * @author  PressBooks <code@pressbooks.org>
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
		$translations = & get_translations_for_domain( $domain );
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

	return $loc ? $loc : $lang;
}


/**
 * Get a key => value array of ISO 639 two letter language codes.
 *
 * @return array
 */
function get_languages() {

	$languages = array(
		'' => '',
		'aa' => 'Afar',
		'ab' => 'Abkhazian',
		'ae' => 'Avestan',
		'af' => 'Afrikaans',
		'ak' => 'Akan',
		'am' => 'Amharic',
		'an' => 'Aragonese',
		'ar' => 'Arabic',
		'as' => 'Assamese',
		'av' => 'Avaric',
		'ay' => 'Aymara',
		'az' => 'Azerbaijani',
		'ba' => 'Bashkir',
		'be' => 'Belarusian',
		'bg' => 'Bulgarian',
		'bh' => 'Bihari',
		'bi' => 'Bislama',
		'bm' => 'Bambara',
		'bn' => 'Bengali',
		'bo' => 'Tibetan',
		'br' => 'Breton',
		'bs' => 'Bosnian',
		'ca' => 'Catalan',
		'ce' => 'Chechen',
		'ch' => 'Chamorro',
		'co' => 'Corsican',
		'cr' => 'Cree',
		'cs' => 'Czech',
		'cu' => 'Church Slavic',
		'cv' => 'Chuvash',
		'cy' => 'Welsh',
		'da' => 'Danish',
		'de' => 'German',
		'dv' => 'Divehi',
		'dz' => 'Dzongkha',
		'ee' => 'Ewe',
		'el' => 'Greek',
		'en' => 'English',
		'eo' => 'Esperanto',
		'es' => 'Spanish',
		'et' => 'Estonian',
		'eu' => 'Basque',
		'fa' => 'Persian',
		'ff' => 'Fulah',
		'fi' => 'Finnish',
		'fj' => 'Fijian',
		'fo' => 'Faroese',
		'fr' => 'French',
		'fy' => 'Western Frisian',
		'ga' => 'Irish',
		'gd' => 'Scottish Gaelic',
		'gl' => 'Galician',
		'gn' => 'Guarani',
		'gu' => 'Gujarati',
		'gv' => 'Manx',
		'ha' => 'Hausa',
		'he' => 'Hebrew',
		'hi' => 'Hindi',
		'ho' => 'Hiri Motu',
		'hr' => 'Croatian',
		'ht' => 'Haitian',
		'hu' => 'Hungarian',
		'hy' => 'Armenian',
		'hz' => 'Herero',
		'ia' => 'Interlingua',
		'id' => 'Indonesian',
		'ie' => 'Interlingue',
		'ig' => 'Igbo',
		'ii' => 'Sichuan Yi',
		'ik' => 'Inupiaq',
		'io' => 'Ido',
		'is' => 'Icelandic',
		'it' => 'Italian',
		'iu' => 'Inuktitut',
		'ja' => 'Japanese',
		'jv' => 'Javanese',
		'ka' => 'Georgian',
		'kg' => 'Kongo',
		'ki' => 'Kikuyu',
		'kj' => 'Kwanyama',
		'kk' => 'Kazakh',
		'kl' => 'Kalaallisut',
		'km' => 'Khmer',
		'kn' => 'Kannada',
		'ko' => 'Korean',
		'kr' => 'Kanuri',
		'ks' => 'Kashmiri',
		'ku' => 'Kurdish',
		'kv' => 'Komi',
		'kw' => 'Cornish',
		'ky' => 'Kirghiz',
		'la' => 'Latin',
		'lb' => 'Luxembourgish',
		'lg' => 'Ganda',
		'li' => 'Limburgish',
		'ln' => 'Lingala',
		'lo' => 'Lao',
		'lt' => 'Lithuanian',
		'lu' => 'Luba-Katanga',
		'lv' => 'Latvian',
		'mg' => 'Malagasy',
		'mh' => 'Marshallese',
		'mi' => 'Maori',
		'mk' => 'Macedonian',
		'ml' => 'Malayalam',
		'mn' => 'Mongolian',
		'mr' => 'Marathi',
		'ms' => 'Malay',
		'mt' => 'Maltese',
		'my' => 'Burmese',
		'na' => 'Nauru',
		'nb' => 'Norwegian Bokmal',
		'nd' => 'North Ndebele',
		'ne' => 'Nepali',
		'ng' => 'Ndonga',
		'nl' => 'Dutch',
		'nn' => 'Norwegian Nynorsk',
		'no' => 'Norwegian',
		'nr' => 'South Ndebele',
		'nv' => 'Navajo',
		'ny' => 'Chichewa',
		'oc' => 'Occitan',
		'oj' => 'Ojibwa',
		'om' => 'Oromo',
		'or' => 'Oriya',
		'os' => 'Ossetian',
		'pa' => 'Panjabi',
		'pi' => 'Pali',
		'pl' => 'Polish',
		'ps' => 'Pashto',
		'pt' => 'Portuguese',
		'qu' => 'Quechua',
		'rm' => 'Raeto-Romance',
		'rn' => 'Kirundi',
		'ro' => 'Romanian',
		'ru' => 'Russian',
		'rw' => 'Kinyarwanda',
		'sa' => 'Sanskrit',
		'sc' => 'Sardinian',
		'sd' => 'Sindhi',
		'se' => 'Northern Sami',
		'sg' => 'Sango',
		'si' => 'Sinhala',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'sm' => 'Samoan',
		'sn' => 'Shona',
		'so' => 'Somali',
		'sq' => 'Albanian',
		'sr' => 'Serbian',
		'ss' => 'Swati',
		'st' => 'Southern Sotho',
		'su' => 'Sundanese',
		'sv' => 'Swedish',
		'sw' => 'Swahili',
		'ta' => 'Tamil',
		'te' => 'Telugu',
		'tg' => 'Tajik',
		'th' => 'Thai',
		'ti' => 'Tigrinya',
		'tk' => 'Turkmen',
		'tl' => 'Tagalog',
		'tn' => 'Tswana',
		'to' => 'Tonga',
		'tr' => 'Turkish',
		'ts' => 'Tsonga',
		'tt' => 'Tatar',
		'tw' => 'Twi',
		'ty' => 'Tahitian',
		'ug' => 'Uighur',
		'uk' => 'Ukrainian',
		'ur' => 'Urdu',
		'uz' => 'Uzbek',
		've' => 'Venda',
		'vi' => 'Vietnamese',
		'vo' => 'Volapuk',
		'wa' => 'Walloon',
		'wo' => 'Wolof',
		'xh' => 'Xhosa',
		'yi' => 'Yiddish',
		'yo' => 'Yoruba',
		'za' => 'Zhuang',
		'zh' => 'Chinese',
		'zu' => 'Zulu'
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

		return $number_in_words;
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
