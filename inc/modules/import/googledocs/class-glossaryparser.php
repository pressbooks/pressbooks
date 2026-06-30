<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

/**
 * Parses [GT]term[/GT] markers and a dedicated "Glossary" H3 section out of
 * converted Google Docs chapter HTML. Pure PHP: no WordPress calls.
 */
class GlossaryParser {

	const MARKER_REGEX = '/\[GT\](.+?)\[\/GT\]/s';

	/**
	 * Normalize a term for case-insensitive matching and de-dupe.
	 */
	public static function normalizeKey( string $term ): string {
		return mb_strtolower( trim( strip_tags( $term ) ) );
	}

	/**
	 * Extract unique [GT] marker terms from a chapter body.
	 *
	 * @return array<string,string> normalizedKey => display term (first wins)
	 */
	public function extractMarkerTerms( string $html ): array {
		$terms = [];
		if ( preg_match_all( self::MARKER_REGEX, $html, $matches ) ) {
			foreach ( $matches[1] as $inner ) {
				$key = self::normalizeKey( $inner );
				if ( '' !== $key && ! isset( $terms[ $key ] ) ) {
					$terms[ $key ] = trim( $inner );
				}
			}
		}
		return $terms;
	}
}
