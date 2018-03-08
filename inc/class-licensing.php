<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function \Pressbooks\Utility\debug_error_log;

/**
 * TODO: Refactor
 * Custom Licenses don't work with the Creative Commons API. For now we fallback to 'all-rights-reserved'. Instead, the Creative Commons API should be gutted.
 * An admin can delete Creative Commons taxonomies. Should we let them?
 */
class Licensing {

	const TAXONOMY = 'license';

	/**
	 * Wheee!
	 */
	public function __construct() {
	}

	/**
	 * Returns supported license types in array that looks like:
	 *
	 *     slug =>
	 *        api[],
	 *        url,
	 *        desc,
	 *
	 * @param bool $disable_translation (optional)
	 * @param bool $disable_custom (optional)
	 *
	 * @return array
	 */
	public function getSupportedTypes( $disable_translation = false, $disable_custom = false ) {

		if ( $disable_translation ) {
			add_filter( 'gettext', [ $this, 'disableTranslation' ], 999, 3 );
		}

		// Supported
		$supported = [
			'public-domain' => [
				'api' => [
					'license' => 'zero',
					'commercial' => 'y',
					'derivatives' => 'y',
				],
				'url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
				'desc' => __( 'Public Domain (No Rights Reserved)', 'pressbooks' ),
			],
			'cc-by' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'y',
					'derivatives' => 'y',
				],
				'url' => 'https://creativecommons.org/licenses/by/4.0/',
				'desc' => __( 'CC BY (Attribution)', 'pressbooks' ),
			],
			'cc-by-sa' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'y',
					'derivatives' => 'sa',
				],
				'url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
				'desc' => __( 'CC BY-SA (Attribution ShareAlike)', 'pressbooks' ),
			],
			'cc-by-nd' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'y',
					'derivatives' => 'n',
				],
				'url' => 'https://creativecommons.org/licenses/by-nd/4.0/',
				'desc' => __( 'CC BY-ND (Attribution NoDerivatives)', 'pressbooks' ),
			],
			'cc-by-nc' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'n',
					'derivatives' => 'y',
				],
				'url' => 'https://creativecommons.org/licenses/by-nc/4.0/',
				'desc' => __( 'CC BY-NC (Attribution NonCommercial)', 'pressbooks' ),
			],
			'cc-by-nc-sa' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'n',
					'derivatives' => 'sa',
				],
				'url' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
				'desc' => __( 'CC BY-NC-SA (Attribution NonCommercial ShareAlike)', 'pressbooks' ),
			],
			'cc-by-nc-nd' => [
				'api' => [
					'license' => 'standard',
					'commercial' => 'n',
					'derivatives' => 'n',
				],
				'url' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
				'desc' => __( 'CC BY-NC-ND (Attribution NonCommercial NoDerivatives)', 'pressbooks' ),
			],
			'all-rights-reserved' => [
				'api' => [], // Not supported
				'url' => 'https://choosealicense.com/no-license/',
				'desc' => __( 'All Rights Reserved', 'pressbooks' ),
			],
		];

		// Custom
		if ( ! $disable_custom ) {
			$custom = get_terms(
				[
					'taxonomy' => self::TAXONOMY,
					'hide_empty' => false,
				]
			);
			if ( is_array( $custom ) ) {
				foreach ( $custom as $custom_term ) {
					if ( ! isset( $supported[ $custom_term->slug ] ) ) {
						$supported[ $custom_term->slug ] = [
							'api' => [], // Not supported
							'url' => "https://choosealicense.com/no-license/#{$custom_term->slug}",
							'desc' => $custom_term->name,
						];
					}
				}
			}
		}

		if ( $disable_translation ) {
			remove_filter( 'gettext', [ $this, 'disableTranslation' ], 999 );
		}

		return $supported;
	}

	/**
	 * For gettext filter
	 *
	 * @param $translated
	 * @param $original
	 * @param $domain
	 *
	 * @return string
	 */
	public function disableTranslation( $translated, $original, $domain ) {
		return $original;
	}

	/**
	 * @param string $license
	 *
	 * @return bool
	 */
	public function isSupportedType( $license ) {
		return isset( $this->getSupportedTypes()[ $license ] );
	}

	/**
	 * Will create an html blob of copyright information, returns empty string
	 * if license not supported
	 *
	 * @param array $metadata \Pressbooks\Book::getBookInformation
	 * @param int $post_id (optional)
	 * @param string $title (optional)
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function doLicense( $metadata, $post_id = 0, $title = '' ) {

		$transient_id = "license-inf-{$post_id}";
		$lang = ! empty( $metadata['pb_language'] ) ? $metadata['pb_language'] : 'en';

		// if no post $id given, we default to book copyright
		if ( empty( $post_id ) ) {
			$section_license = '';
			$section_author = '';
			$link = get_bloginfo( 'url' );
		} else {
			$section_license = get_post_meta( $post_id, 'pb_section_license', true );
			$section_author = ( new Contributors() )->get( $post_id, 'pb_authors' );
			$link = get_permalink( $post_id );
		}

		// Copyright license, set in order of precedence
		if ( ! empty( $section_license ) ) {
			// section copyright higher priority than book
			$license = $section_license;
		} elseif ( isset( $metadata['pb_book_license'] ) ) {
			// book is the fallback, default
			$license = $metadata['pb_book_license'];
		} else {
			$license = 'all-rights-reserved';
		}
		if ( ! $this->isSupportedType( $license ) ) {
			// License not supported, bail
			return '';
		}

		// Title
		if ( empty( $title ) ) {
			$title = empty( $post_id ) ? get_bloginfo( 'name' ) : get_post( $post_id )->post_title;
		}

		// Copyright holder, set in order of precedence
		if ( ! empty( $section_author ) ) {
			// section author higher priority than book author, copyrightholder
			$copyright_holder = $section_author;
		} elseif ( isset( $metadata['pb_copyright_holder'] ) ) {
			// book copyright holder higher priority than book author
			$copyright_holder = $metadata['pb_copyright_holder'];
		} elseif ( isset( $metadata['pb_authors'] ) ) {
			// book author is the fallback, default
			$copyright_holder = $metadata['pb_authors'];
		} else {
			$copyright_holder = '';
		}

		if ( ! empty( $metadata['pb_copyright_year'] ) ) {
			$copyright_year = $metadata['pb_copyright_year'];
		} elseif ( ! empty( $metadata['pb_publication_date'] ) ) {
			$copyright_year = strftime( '%Y', absint( $metadata['pb_publication_date'] ) );
		} else {
			$copyright_year = 0;
		}

		// Check if the user has changed anything about the license
		$transient = get_transient( $transient_id );
		$changed = false;
		if ( is_array( $transient ) ) {
			foreach ( [ $license, $copyright_holder, $title, $lang, $copyright_year ] as $val ) {
				if ( ! array_key_exists( $val, $transient ) ) {
					$changed = true;
				}
			}
		}

		// if the cache has expired, or the user changed something about the license
		if ( false === $transient || true === $changed ) {
			// get xml response from API
			$response = $this->getLicenseXml( $license, $copyright_holder, $link, $title, $lang, $copyright_year );

			// convert to object
			$result = simplexml_load_string( $response );

			if ( ! false === $result || ! isset( $result->html ) ) {
				throw new \Exception( 'Creative Commons license API not returning expected results' );
			} else {
				// process the response, return html
				$except_where_otherwise_noted = in_array( $license, [ 'all-rights-reserved' ], true ) ? false : true;
				$html = $this->getLicenseHtml( $result->html[0], $except_where_otherwise_noted );
			}

			set_transient(
				$transient_id,
				[
					$license => $html,
					$copyright_holder => 1,
					$title => 1,
					$lang => 1,
					$copyright_year => 1,
				]
			);

		} else {
			$html = $transient[ $license ];
		}

		return $html;
	}

	/**
	 * Takes a known string from metadata, builds a url to hit an api which returns an xml response
	 *
	 * @see https://api.creativecommons.org/docs/readme_15.html
	 *
	 * @param string $type license type
	 * @param string $copyright_holder of the page
	 * @param string $src_url of the page
	 * @param string $title of the page
	 * @param string $lang (optional)
	 * @param int $year (optional)
	 *
	 * @return string $xml response
	 */
	public function getLicenseXml( $type, $copyright_holder, $src_url, $title, $lang = '', $year = 0 ) {

		$endpoint = 'https://api.creativecommons.org/rest/1.5/';
		$lang = ( ! empty( $lang ) ) ? substr( $lang, 0, 2 ) : '';
		$expected = $this->getSupportedTypes();

		if ( ! array_key_exists( $type, $expected ) ) {
			// nothing meaningful to hit the api with, so bail
			return '';
		}
		if ( $type !== 'all-rights-reserved' && empty( $expected[ $type ]['api'] ) ) {
			// We don't know what to do with a custom license, use "all-rights-reserved" for now
			$type = 'all-rights-reserved';
		}

		switch ( $type ) {
			// api doesn't have an 'all-rights-reserved' endpoint, so manual build necessary
			case 'all-rights-reserved':
				$xml =
					'<result><html>' .
					"<span property='dct:title'>" . Sanitize\sanitize_xml_attribute( $title ) . '</span> ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
				if ( $year ) {
					$xml .= Sanitize\sanitize_xml_attribute( $year ) . ' ' . __( 'by', 'pressbooks' ) . ' ';
				}
				$xml .= Sanitize\sanitize_xml_attribute( $copyright_holder );
				$xml .= '. ' . __( 'All Rights Reserved', 'pressbooks' ) . '.</html></result>';
				break;

			default:
				$key = array_keys( $expected[ $type ]['api'] );
				$val = array_values( $expected[ $type ]['api'] );

				$url =
					$endpoint . $key[0] . '/' . $val[0] . '/get?' . $key[1] . '=' . $val[1] . '&' . $key[2] . '=' . $val[2] .
					'&creator=' . rawurlencode( $copyright_holder ) . '&attribution_url=' . rawurlencode( $src_url ) . '&title=' . rawurlencode( $title ) . '&locale=' . $lang;
				if ( $year ) {
					$url .= '&year=' . (int) $year;
				}

				$xml = wp_remote_get( $url );
				$ok = wp_remote_retrieve_response_code( $xml );

				if ( absint( $ok ) === 200 && is_wp_error( $xml ) === false ) {
					$xml = $xml['body'];
				} else {
					// Something went wrong, try to log it
					if ( is_wp_error( $xml ) ) {
						$error_message = $xml->get_error_message();
					} elseif ( is_array( $xml ) && ! empty( $xml['body'] ) ) {
						$error_message = wp_strip_all_tags( $xml['body'] );
					} else {
						$error_message = 'An unknown error occurred';
					}
					debug_error_log( '\Pressbooks\Licensing::getLicenseXml() error: ' . $error_message );
					$xml = ''; // Set empty string
				}
				break;
		}

		return $xml;
	}

	/**
	 * Returns an HTML blob if given an XML object
	 *
	 * @param \SimpleXMLElement $response
	 * @param $except_where_otherwise_noted bool (optional)
	 *
	 * @return string $html blob of copyright information
	 */
	public function getLicenseHtml( \SimpleXMLElement $response, $except_where_otherwise_noted = true ) {

		$content = $response->asXML();
		$content = trim( str_replace( [ '<p xmlns:dct="http://purl.org/dc/terms/">', '</p>', '<html>', '</html>' ], '', $content ) );
		$content = preg_replace( '/http:\/\/i.creativecommons/iU', 'https://i.creativecommons', $content );

		$html = '<div class="license-attribution" xmlns:cc="http://creativecommons.org/ns#"><p xmlns:dct="http://purl.org/dc/terms/">';
		if ( $except_where_otherwise_noted ) {
			$html .= rtrim( $content, '.' ) . ', ' . __( 'except where otherwise noted.', 'pressbooks' );
		} else {
			$html .= $content;
		}
		$html .= '</p></div>';

		return html_entity_decode( $html, ENT_XHTML, 'UTF-8' );
	}

	/**
	 * Returns URL for saved license value.
	 *
	 * @since 4.1.0
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function getUrlForLicense( $license ) {
		if ( $this->isSupportedType( $license ) ) {
			return $this->getSupportedTypes()[ $license ]['url'];
		} else {
			return 'https://choosealicense.com/no-license/';
		}
	}

	/**
	 * Returns Book Information-compatible license value from URL.
	 *
	 * @since 4.1.0
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function getLicenseFromUrl( $url ) {
		$licenses = $this->getSupportedTypes();
		foreach ( $licenses as $license => $v ) {
			if ( $url === $v['url'] ) {
				return $license;
			}
		}

		return 'all-rights-reserved';
	}
}
