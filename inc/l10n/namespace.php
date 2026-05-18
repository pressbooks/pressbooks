<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
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

	$languages = [
		'' => '&nbsp;',
		'af' => __( 'Afrikaans', 'pressbooks' ),
		'sq' => __( 'Albanian', 'pressbooks' ),
		'ar' => __( 'Arabic', 'pressbooks' ),
		'ar-dz' => __( 'Arabic (Algeria)', 'pressbooks' ),
		'ar-bh' => __( 'Arabic (Bahrain)', 'pressbooks' ),
		'ar-eg' => __( 'Arabic (Egypt)', 'pressbooks' ),
		'ar-jo' => __( 'Arabic (Jordan)', 'pressbooks' ),
		'ar-kw' => __( 'Arabic (Kuwait)', 'pressbooks' ),
		'ar-lb' => __( 'Arabic (Lebanon)', 'pressbooks' ),
		'ar-ma' => __( 'Arabic (Morocco)', 'pressbooks' ),
		'ar-om' => __( 'Arabic (Oman)', 'pressbooks' ),
		'ar-qa' => __( 'Arabic (Qatar)', 'pressbooks' ),
		'ar-sa' => __( 'Arabic (Saudi Arabia)', 'pressbooks' ),
		'ar-sy' => __( 'Arabic (Syria)', 'pressbooks' ),
		'ar-tn' => __( 'Arabic (Tunisia)', 'pressbooks' ),
		'ar-ae' => __( 'Arabic (U.A.E.)', 'pressbooks' ),
		'ar-ye' => __( 'Arabic (Yemen)', 'pressbooks' ),
		'hy' => __( 'Armenian', 'pressbooks' ),
		'az' => __( 'Azeri', 'pressbooks' ),
		'eu' => __( 'Basque', 'pressbooks' ),
		'be' => __( 'Belarusian', 'pressbooks' ),
		'bn' => __( 'Bengali', 'pressbooks' ),
		'bg' => __( 'Bulgarian', 'pressbooks' ),
		'ca' => __( 'Catalan', 'pressbooks' ),
		'zh' => __( 'Chinese', 'pressbooks' ),
		'zh-hk' => __( 'Chinese (Hong Kong)', 'pressbooks' ),
		'zh-cn' => __( 'Chinese (PRC)', 'pressbooks' ),
		'zh-sg' => __( 'Chinese (Singapore)', 'pressbooks' ),
		'zh-tw' => __( 'Chinese (Taiwan)', 'pressbooks' ),
		'hr' => __( 'Croatian', 'pressbooks' ),
		'cs' => __( 'Czech', 'pressbooks' ),
		'da' => __( 'Danish', 'pressbooks' ),
		'nl' => __( 'Dutch', 'pressbooks' ),
		'nl-be' => __( 'Dutch (Belgium)', 'pressbooks' ),
		'en' => __( 'English', 'pressbooks' ),
		'en-au' => __( 'English (Australia)', 'pressbooks' ),
		'en-bz' => __( 'English (Belize)', 'pressbooks' ),
		'en-ca' => __( 'English (Canada)', 'pressbooks' ),
		'en-ie' => __( 'English (Ireland)', 'pressbooks' ),
		'en-jm' => __( 'English (Jamaica)', 'pressbooks' ),
		'en-nz' => __( 'English (New Zealand)', 'pressbooks' ),
		'en-ph' => __( 'English (Philippines)', 'pressbooks' ),
		'en-za' => __( 'English (South Africa)', 'pressbooks' ),
		'en-tt' => __( 'English (Trinidad)', 'pressbooks' ),
		'en-gb' => __( 'English (United Kingdom)', 'pressbooks' ),
		'en-us' => __( 'English (United States)', 'pressbooks' ),
		'en-zw' => __( 'English (Zimbabwe)', 'pressbooks' ),
		'et' => __( 'Estonian', 'pressbooks' ),
		'fo' => __( 'Faeroese', 'pressbooks' ),
		'fa' => __( 'Farsi', 'pressbooks' ),
		'fi' => __( 'Finnish', 'pressbooks' ),
		'fr-be' => __( 'French (Belgium)', 'pressbooks' ),
		'fr-ca' => __( 'French (Canada)', 'pressbooks' ),
		'fr' => __( 'French', 'pressbooks' ),
		'fr-lu' => __( 'French (Luxembourg)', 'pressbooks' ),
		'fr-mc' => __( 'French (Monaco)', 'pressbooks' ),
		'fr-ch' => __( 'French (Switzerland)', 'pressbooks' ),
		'ka' => __( 'Georgian', 'pressbooks' ),
		'de' => __( 'German', 'pressbooks' ),
		'de-at' => __( 'German (Austria)', 'pressbooks' ),
		'de-li' => __( 'German (Liechtenstein)', 'pressbooks' ),
		'de-lu' => __( 'German (Luxembourg)', 'pressbooks' ),
		'de-ch' => __( 'German (Switzerland)', 'pressbooks' ),
		'el' => __( 'Greek', 'pressbooks' ),
		'gu' => __( 'Gujarati', 'pressbooks' ),
		'he' => __( 'Hebrew', 'pressbooks' ),
		'hi' => __( 'Hindi', 'pressbooks' ),
		'hu' => __( 'Hungarian', 'pressbooks' ),
		'is' => __( 'Icelandic', 'pressbooks' ),
		'id' => __( 'Indonesian', 'pressbooks' ),
		'it' => __( 'Italian', 'pressbooks' ),
		'it-ch' => __( 'Italian (Switzerland)', 'pressbooks' ),
		'ja' => __( 'Japanese', 'pressbooks' ),
		'kn' => __( 'Kannada', 'pressbooks' ),
		'kk' => __( 'Kazakh', 'pressbooks' ),
		'x-kok' => __( 'Konkani', 'pressbooks' ),
		'ko' => __( 'Korean', 'pressbooks' ),
		'lv' => __( 'Latvian', 'pressbooks' ),
		'lt' => __( 'Lithuanian', 'pressbooks' ),
		'mk' => __( 'Macedonian', 'pressbooks' ),
		'ms' => __( 'Malay', 'pressbooks' ),
		'ml' => __( 'Malayalam', 'pressbooks' ),
		'mt' => __( 'Maltese', 'pressbooks' ),
		'mr' => __( 'Marathi', 'pressbooks' ),
		'ne' => __( 'Nepali', 'pressbooks' ),
		'no' => __( 'Norwegian', 'pressbooks' ),
		'nb' => __( 'Norwegian (Bokm&aring;l)', 'pressbooks' ),
		'nn' => __( 'Norwegian (Nynorsk)', 'pressbooks' ),
		'or' => __( 'Oriya', 'pressbooks' ),
		'pl' => __( 'Polish', 'pressbooks' ),
		'pt' => __( 'Portuguese', 'pressbooks' ),
		'pt-br' => __( 'Portuguese (Brazil)', 'pressbooks' ),
		'pa' => __( 'Punjabi', 'pressbooks' ),
		'rm' => __( 'Rhaeto-Romanic', 'pressbooks' ),
		'ro' => __( 'Romanian', 'pressbooks' ),
		'ro-mo' => __( 'Romanian (Moldova)', 'pressbooks' ),
		'ru' => __( 'Russian', 'pressbooks' ),
		'ru-mo' => __( 'Russian (Moldova)', 'pressbooks' ),
		'sz' => __( 'Sami (Lappish)', 'pressbooks' ),
		'sa' => __( 'Sanskrit', 'pressbooks' ),
		'sr' => __( 'Serbian', 'pressbooks' ),
		'sk' => __( 'Slovak', 'pressbooks' ),
		'sl' => __( 'Slovenian', 'pressbooks' ),
		'sb' => __( 'Sorbian', 'pressbooks' ),
		'es' => __( 'Spanish', 'pressbooks' ),
		'es-ar' => __( 'Spanish (Argentina)', 'pressbooks' ),
		'es-bo' => __( 'Spanish (Bolivia)', 'pressbooks' ),
		'es-cl' => __( 'Spanish (Chile)', 'pressbooks' ),
		'es-co' => __( 'Spanish (Colombia)', 'pressbooks' ),
		'es-cr' => __( 'Spanish (Costa Rica)', 'pressbooks' ),
		'es-do' => __( 'Spanish (Dominican Republic)', 'pressbooks' ),
		'es-ec' => __( 'Spanish (Ecuador)', 'pressbooks' ),
		'es-sv' => __( 'Spanish (El Salvador)', 'pressbooks' ),
		'es-gt' => __( 'Spanish (Guatemala)', 'pressbooks' ),
		'es-hn' => __( 'Spanish (Honduras)', 'pressbooks' ),
		'es-mx' => __( 'Spanish (Mexico)', 'pressbooks' ),
		'es-ni' => __( 'Spanish (Nicaragua)', 'pressbooks' ),
		'es-pa' => __( 'Spanish (Panama)', 'pressbooks' ),
		'es-py' => __( 'Spanish (Paraguay)', 'pressbooks' ),
		'es-pe' => __( 'Spanish (Peru)', 'pressbooks' ),
		'es-pr' => __( 'Spanish (Puerto Rico)', 'pressbooks' ),
		'es-uy' => __( 'Spanish (Uruguay)', 'pressbooks' ),
		'es-ve' => __( 'Spanish (Venezuela)', 'pressbooks' ),
		'sx' => __( 'Sutu', 'pressbooks' ),
		'sw' => __( 'Swahili', 'pressbooks' ),
		'sv' => __( 'Swedish', 'pressbooks' ),
		'sv-fi' => __( 'Swedish (Finland)', 'pressbooks' ),
		'ta' => __( 'Tamil', 'pressbooks' ),
		'tt' => __( 'Tatar', 'pressbooks' ),
		'te' => __( 'Telugu', 'pressbooks' ),
		'th' => __( 'Thai', 'pressbooks' ),
		'ts' => __( 'Tsonga', 'pressbooks' ),
		'tn' => __( 'Tswana', 'pressbooks' ),
		'tr' => __( 'Turkish', 'pressbooks' ),
		'uk' => __( 'Ukranian', 'pressbooks' ),
		'ur' => __( 'Urdu', 'pressbooks' ),
		'uz' => __( 'Uzbek', 'pressbooks' ),
		'vi' => __( 'Vietnamese', 'pressbooks' ),
		'xh' => __( 'Xhosa', 'pressbooks' ),
		'zu' => __( 'Zulu', 'pressbooks' ),
	];

	asort( $languages );

	return $languages;
}

/**
 * This helps us convert KindleGen language codes to WordPress-compatible ones and vice versa.
 *
 * @return array
 */
function wplang_codes() {

	$languages = [
		'af' => 'af', // Afrikaans
		'sq' => 'sq', // Albanian
		'ar' => 'ar', // Arabic
		'ar-dz' => 'ar', // Arabic (Algeria)
		'ar-bh' => 'ar', // Arabic (Bahrain)
		'ar-eg' => 'ar', // Arabic (Egypt)
		'ar-jo' => 'ar', // Arabic (Jordan)
		'ar-kw' => 'ar', // Arabic (Kuwait)
		'ar-lb' => 'ar', // Arabic (Lebanon)
		'ar-ma' => 'ary', // Arabic (Morocco)
		'ar-om' => 'ar', // Arabic (Oman)
		'ar-qa' => 'ar', // Arabic (Qatar)
		'ar-sa' => 'ar', // Arabic (Saudi Aria)
		'ar-sy' => 'ar', // Arabic (Syria)
		'ar-tn' => 'ar', // Arabic (Tunisia)
		'ar-ae' => 'ar', // Arabic (U.A.E.)
		'ar-ye' => 'ar', // Arabic (Yemen)
		'hy' => 'hy', // Armenian
		'az' => 'az', // Azerbaijani
		'eu' => 'eu', // Basque
		'be' => '', // Belarusian
		'bn' => 'bn_BD', // Bengali
		'bg' => 'bg_BG', // Bulgarian
		'ca' => 'ca', // Catalan
		'zh' => 'zh_CN', // Chinese
		'zh-hk' => 'zh_HK', // Chinese (Hong Kong)
		'zh-cn' => 'zh_CN', // Chinese (PRC)
		'zh-sg' => 'zh_CN', // Chinese (Singapore)
		'zh-tw' => 'zh_TW', // Chinese (Taiwan)
		'hr' => 'hr', // Croatian
		'cs' => 'cs_CZ', // Czech
		'da' => 'da_DK', // Danish
		'nl' => 'nl_NL', // Dutch
		'nl-be' => 'nl_NL', // Dutch (Belgium)
		'en' => 'en_US', // English (United States)
		'en-au' => 'en_AU', // English (Australia)
		'en-bz' => 'en_US', // English (Belize)
		'en-ca' => 'en_CA', // English (Canada)
		'en-ie' => 'en_UK', // English (Ireland)
		'en-jm' => 'en_US', // English (Jamaica)
		'en-nz' => 'en_NZ', // English (Aotearoa New Zealand)
		'en-ph' => 'en_US', // English (Philippines)
		'en-za' => 'en_ZA', // English (South Africa)
		'en-tt' => 'en_US', // English (Trinidad)
		'en-gb' => 'en_GB', // English (United Kingdom)
		'en-us' => 'en_US', // English (United States)
		'en-zw' => 'en_US', // English (Zimbabwe)
		'et' => 'et', // Estonian
		'fo' => '', // Faeroese
		'fa' => 'fa_IR', // Farsi
		'fi' => 'fi', // Finnish
		'fr' => 'fr_FR', // French
		'fr-ca' => 'fr_CA', // French (Canada)
		'fr-be' => 'fr_FR', // French (Belgium)
		'fr-lu' => 'fr_FR', // French (Luxembourg)
		'fr-mc' => 'fr_FR', // French (Monaco)
		'fr-ch' => 'fr_FR', // French (Switzerland)
		'ka' => 'ka_GE', // Georgian
		'de' => 'de_DE', // German
		'de-at' => 'de_DE', // German (Austria)
		'de-li' => 'de_DE', // German (Liechtenstein)
		'de-lu' => 'de_DE', // German (Luxembourg)
		'de-ch' => 'de_CH', // German (Switzerland)
		'el' => 'el', // Greek
		'gu' => 'gu', // Gujarati
		'he' => 'he_IL', // Hebrew
		'hi' => 'hi_IN', // Hindi
		'hu' => 'hu_HU', // Hungarian
		'is' => 'is_IS', // Icelandic
		'id' => 'id_ID', // Indonesian
		'it' => 'it_IT', // Italian
		'it-ch' => 'it_IT', // Italian (Switzerland)
		'ja' => 'ja', // Japanese
		'kn' => '', // Kannada
		'kk' => '', // Kazakh
		'x-kok' => '', // Konkani
		'ko' => 'ko_KR', // Korean
		'lv' => 'lv', // Latvian
		'lt' => 'lt_LT', // Lithuanian
		'mk' => 'mk_MK', // Macedonian
		'ms' => 'ms_MY', // Malay
		'ml' => '', // Malayalam
		'mt' => '', // Maltese
		'mr' => 'mr', // Marathi
		'ne' => '', // Nepali
		'no' => 'nb_NO', // Norwegian (Bokmal)
		'nb' => 'nb_NO', // Norwegian (Bokmal)
		'nn' => 'nn_NO', // Norwegian (Nynorsk)
		'or' => 'Oriya',
		'pl' => 'pl_PL', // Polish
		'pt' => 'pt_PT', // Portuguese (Portugal)
		'pt-br' => 'pt_BR', // Portuguese (Brazil)
		'pa' => '', // Punjabi
		'rm' => '', // Rhaeto-Romanic
		'ro' => 'ro_RO', // Romanian
		'ro-mo' => 'ro_RO', // Romanian (Moldova)
		'ru' => 'ru_RU', // Russian
		'ru-mo' => 'ru_RU', // Russian (Moldova)
		'sz' => '', // Sami (Lappish)
		'sa' => '', // Sanskrit
		'sr' => 'sr_RS', // Serbian
		'sk' => 'sk_SK', // Slovak
		'sl' => 'sl_SI', // Slovenian
		'sb' => '', // Sorbian
		'es' => 'es_ES', // Spanish
		'es-ar' => 'es_AR', // Spanish (Argentina)
		'es-bo' => '', // Spanish (Bolivia)
		'es-cl' => 'es_CL', // Spanish (Chile)
		'es-co' => 'es_CO', // Spanish (Colombia)
		'es-cr' => '', // Spanish (Costa Rica)
		'es-do' => '', // Spanish (Dominican Republic)
		'es-ec' => '', // Spanish (Ecuador)
		'es-sv' => '', // Spanish (El Salvador)
		'es-gt' => 'es_GT', // Spanish (Guatemala)
		'es-hn' => '', // Spanish (Honduras)
		'es-mx' => 'es_MX', // Spanish (Mexico)
		'es-ni' => '', // Spanish (Nicaragua)
		'es-pa' => '', // Spanish (Panama)
		'es-py' => '', // Spanish (Paraguay)
		'es-pe' => 'es_PE', // Spanish (Peru)
		'es-pr' => '', // Spanish (Puerto Rico)
		'es-uy' => '', // Spanish (Uruguay)
		'es-ve' => 'es_VE', // Spanish (Venezuela)
		'sx' => '', // Sutu
		'sw' => '', // Swahili
		'sv' => 'sv_SE', // Swedish
		'sv-fi' => 'sv_SE', // Swedish (Finland)
		'ta' => '', // Tamil
		'tt' => '', // Tatar
		'te' => '', // Telugu
		'th' => 'th', // Thai
		'ts' => '', // Tsonga
		'tn' => '', // Tswana
		'tr' => 'tr_TR', // Turkish
		'uk' => 'uk', // Ukrainian
		'ur' => '', // Urdu
		'uz' => '', // Uzbek
		'vi' => 'vi', // Vietnamese
		'xh' => '', // Xhosa
		'zu' => '', // Zulu
	];

	return $languages;
}

/**
 * Override get_locale
 * For performance reasons, we only want functions in this namespace to call WP get_locale once.
 * (avoid triggering `apply_filters( 'locale', $locale )` ad nausea)
 *
 * @return string
 */
function get_locale() {
	// If the user has set a locale, use it
	if ( function_exists( 'wp_get_current_user' ) && is_admin() ) {
		$user = wp_get_current_user();
		if ( $user->locale ) {
			return $user->locale;
		}
	}
	// Else, use the global locale
	global $locale;
	if ( ! empty( $locale ) ) {
		return $locale;
	}
	return \get_locale();
}

/**
 * When multiple mo-files are loaded for the same domain, the first found translation will be used. To allow for easier
 * customization we load from the WordPress languages directory by default then fallback on our own, if any.
 *
 * @see \load_plugin_textdomain
 * @see \Translations::merge_with
 *
 * @param string $locale (optional)
 */
function load_plugin_textdomain( $locale = '' ) {
	if ( empty( $locale ) ) {
		$locale = get_locale();
	}
	$domain = 'pressbooks';
	$locale = apply_filters( 'plugin_locale', $locale, $domain );
	$mofile = $domain . '-' . $locale . '.mo';

	// Start by unloading all translations
	unload_textdomain( $domain );

	// Find, merge the translations we want
	$path = WP_LANG_DIR . '/pressbooks/' . $mofile;
	load_textdomain( $domain, $path );

	$path = WP_LANG_DIR . '/plugins/' . $mofile;
	load_textdomain( $domain, $path );

	$path = WP_PLUGIN_DIR . '/pressbooks/languages/' . $mofile;
	if ( ! load_textdomain( $domain, $path ) ) {
		$path = __DIR__ . '/../../languages/' . $mofile;
		load_textdomain( $domain, $path );
	}
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
	if ( $original === 'put your unique phrase here' ) {
		return $original;
	}

	$overrides = include_core_overrides();

	if ( isset( $overrides[ $original ] ) ) {
		$translations = get_translations_for_domain( $domain );
		$translated = $translations->translate( $overrides[ $original ] ); // @codingStandardsIgnoreLine
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
	static $_overrides = [];

	$locale = apply_filters( 'plugin_locale', get_locale(), 'pressbooks' );
	$filename = 'core-' . strtolower( str_replace( '_', '-', $locale ) ) . '.php';
	$filepath = PB_PLUGIN_DIR . 'languages/' . $filename;

	if ( ! isset( $_overrides[ $locale ] ) ) {
		$_overrides[ $locale ] = [];
		if ( file_exists( $filepath ) ) {
			$_overrides[ $locale ] = include( $filepath );
		}
	}

	return $_overrides[ $locale ];
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

	if ( '__UNSET__' === $loc ) {
		$book_lang = get_book_language();
		if ( is_admin() ) {
			// If user locale isn't set, use the book information value.
			if ( function_exists( 'wp_get_current_user' ) && ! get_user_option( 'locale' ) ) {
				$locations = \Pressbooks\L10n\wplang_codes();
				$loc = $locations[ $book_lang ];
			}
		} elseif ( isset( $GLOBALS['pagenow'] ) && 'wp-signup.php' === $GLOBALS['pagenow'] ) {
			// If we're on the registration page, use the global setting.
			$loc = get_site_option( 'WPLANG' );
		} else {
			// Use the book information value.
			$locations = \Pressbooks\L10n\wplang_codes();
			$loc = $locations[ $book_lang ];
		}
	}

	// Return the language
	if ( '__UNSET__' === $loc ) {
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
	// Try to retrieve the network setting
	$loc = get_site_option( 'WPLANG' );
	return ( $loc ? $loc : $lang );
}

/**
 * When a user changes their book's language, try to install the corresponding language pack.
 *
 * @since 3.9.6
 *
 * @param int $meta_id The metadata ID
 * @param int $post_id The book information post ID
 * @param string $meta_key The metadata key
 * @param string $meta_value The metadata value
 *
 * @return string|bool Returns the language code if successfully downloaded
 *                     (or already installed), or false on failure.
 */
function install_book_locale( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( 'pb_language' !== $meta_key ) {
		return false;
	}

	$languages = wplang_codes();
	$locale = $languages[ $meta_value ];
	if ( '' !== $locale && 'en_US' !== $locale ) {
		require_once( ABSPATH . '/wp-admin/includes/translation-install.php' );
		$result = \wp_download_language_pack( $locale );
		if ( $result ) {
			if ( ! empty( $GLOBALS['wp_locale_switcher'] ) ) {
				// We have a new language, reset locale switcher so that it knows the new language is available
				// @see wp-settings.php
				$GLOBALS['wp_locale_switcher'] = new \WP_Locale_Switcher();
				$GLOBALS['wp_locale_switcher']->init();
			}
			return $result;
		} else {
			$supported_languages = supported_languages();
			$_SESSION['pb_errors'][] = sprintf( __( 'Please contact your system administrator if you would like them to install extended %s language support for the Pressbooks interface.', 'pressbooks' ), $supported_languages[ $meta_value ] );
		}
	}

	return false;
}

/**
 * Update previous user interface language meta value to WP 4.7 user locale, try to install the corresponding language pack.
 *
 * @since 3.9.6
 */
function update_user_locale() {
	if ( function_exists( 'get_user_meta' ) ) {
		$locale = get_user_meta( get_current_user_id(), 'user_interface_lang', true );
		if ( $locale && 'en_US' !== $locale ) {
			update_user_meta( get_current_user_id(), 'locale', $locale );
			require_once( ABSPATH . '/wp-admin/includes/translation-install.php' );
			$result = \wp_download_language_pack( $locale );
			if ( false === $result ) {
				$wplang_codes = wplang_codes();
				$supported_languages = supported_languages();
				$lang = array_search( $locale, $wplang_codes, true );
				$_SESSION['pb_errors'][] = sprintf( __( 'Please contact your system administrator if you would like them to install extended %s language support for the Pressbooks interface.', 'pressbooks' ), $supported_languages[ $lang ] );
			}
		}
		delete_user_meta( get_current_user_id(), 'user_interface_lang' );
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

	$table = [
		'M' => 1000,
		'CM' => 900,
		'D' => 500,
		'CD' => 400,
		'C' => 100,
		'XC' => 90,
		'L' => 50,
		'XL' => 40,
		'X' => 10,
		'IX' => 9,
		'V' => 5,
		'IV' => 4,
		'I' => 1,
	];
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
 * Get book language
 *
 * We used to get `pb_language` by calling \Pressbooks\Book::getBookInformation()
 * When we introduced the Pressbooks Five data model, and if called before init, then that function would go into infinite recursion.
 *
 * @since 5.0.0
 *
 * @return string
 */
function get_book_language() {
	// Book Language
	$meta_post_id = ( new \Pressbooks\Metadata() )->getMetaPostId();
	if ( $meta_post_id ) {
		$book_lang = get_post_meta( $meta_post_id, 'pb_language', true );
	}
	if ( empty( $book_lang ) ) {
		$book_lang = 'en';
	}
	return $book_lang;
}

