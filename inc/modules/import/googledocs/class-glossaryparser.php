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

	/**
	 * Parse "Glossary" H3 sections out of a set of chapter bodies into entries.
	 *
	 * @param array<int,string> $bodies chapter HTML strings
	 * @return array<string, array{title:string, definition:string}>
	 */
	public function parseGlossaryEntries( array $bodies ): array {
		$entries = [];
		foreach ( $bodies as $html ) {
			if ( '' === $html || false === stripos( $html, 'glossary' ) ) {
				continue;
			}
			$dom = $this->loadDom( $html );
			if ( null === $dom ) {
				continue;
			}
			$wrap = $dom->getElementsByTagName( 'div' )->item( 0 );
			if ( null === $wrap ) {
				continue;
			}
			$section = $this->findGlossarySection( $wrap );
			if ( null === $section['heading'] ) {
				continue;
			}
			$lines = $this->nodesToLines( $section['nodes'] );
			foreach ( $this->entriesFromLines( $lines ) as $key => $entry ) {
				if ( ! isset( $entries[ $key ] ) ) {
					$entries[ $key ] = $entry;
				}
			}
		}
		return $entries;
	}

	/**
	 * Remove the "Glossary" H3 heading and its entry nodes (up to the next
	 * h1/h2/h3) from a chapter body, returning the remaining HTML trimmed.
	 * The input is returned unchanged when no Glossary section is present.
	 */
	public function stripGlossarySection( string $html ): string {
		if ( '' === $html || false === stripos( $html, 'glossary' ) ) {
			return $html;
		}
		$dom = $this->loadDom( $html );
		if ( null === $dom ) {
			return $html;
		}
		$wrap = $dom->getElementsByTagName( 'div' )->item( 0 );
		if ( null === $wrap ) {
			return $html;
		}
		$section = $this->findGlossarySection( $wrap );
		if ( null === $section['heading'] ) {
			return $html;
		}
		foreach ( array_merge( [ $section['heading'] ], $section['nodes'] ) as $node ) {
			if ( $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
		return trim( $this->innerHtml( $wrap ) );
	}

	/**
	 * Replace [GT]term[/GT] markers with [pb_glossary id="N"]term[/pb_glossary].
	 *
	 * @param array<string,int> $idMap normalizedKey => glossary post ID
	 */
	public function replaceMarkers( string $html, array $idMap ): string {
		return preg_replace_callback(
			self::MARKER_REGEX,
			function ( array $match ) use ( $idMap ): string {
				$inner = $match[1];
				$key = self::normalizeKey( $inner );
				if ( isset( $idMap[ $key ] ) ) {
					return '[pb_glossary id="' . (int) $idMap[ $key ] . '"]' . $inner . '[/pb_glossary]';
				}
				return $inner;
			},
			$html
		);
	}

	/**
	 * Load an HTML fragment into a DOMDocument wrapped in a single <div>.
	 */
	protected function loadDom( string $html ): ?\DOMDocument {
		$dom = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$ok = $dom->loadHTML(
			'<?xml encoding="UTF-8"?><div>' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		return $ok ? $dom : null;
	}

	/**
	 * Find the first "Glossary" H3 and the nodes that follow it up to the next
	 * h1/h2/h3 (or end of the wrapper).
	 *
	 * @return array{heading: ?\DOMNode, nodes: array<int,\DOMNode>}
	 */
	protected function findGlossarySection( \DOMElement $wrap ): array {
		$heading = null;
		$nodes = [];
		$collecting = false;
		foreach ( iterator_to_array( $wrap->childNodes ) as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				if ( $collecting ) {
					$nodes[] = $node;
				}
				continue;
			}
			$tag = strtolower( $node->nodeName );
			if ( ! $collecting ) {
				$heading_text = strtolower( trim( preg_replace( '/\s+/u', ' ', str_replace( "\xc2\xa0", ' ', $node->textContent ) ) ) );
				if ( 'h3' === $tag && 'glossary' === $heading_text ) {
					$heading = $node;
					$collecting = true;
				}
				continue;
			}
			if ( in_array( $tag, [ 'h1', 'h2', 'h3' ], true ) ) {
				break;
			}
			$nodes[] = $node;
		}
		return [ 'heading' => $heading, 'nodes' => $nodes ];
	}

	/**
	 * Convert section nodes into lines, each carrying a plain-text projection
	 * (for boundary detection) and sanitized inline HTML (for the definition).
	 *
	 * @param array<int,\DOMNode> $nodes
	 * @return array<int, array{text:string, html:string}>
	 */
	protected function nodesToLines( array $nodes ): array {
		$lines = [];
		foreach ( $nodes as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				$text = trim( $node->textContent ?? '' );
				if ( '' !== $text ) {
					$lines[] = [ 'text' => $text, 'html' => $text ];
				}
				continue;
			}
			$inner = $this->innerHtml( $node );
			foreach ( preg_split( '/<br\s*\/?>/i', $inner ) as $part ) {
				$text = trim( html_entity_decode( strip_tags( $part ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				if ( '' === $text ) {
					continue;
				}
				$lines[] = [ 'text' => $text, 'html' => trim( $this->sanitizeInline( $part ) ) ];
			}
		}
		return $lines;
	}

	/**
	 * Keep only the inline tags allowed in glossary definitions. Attribute
	 * scrubbing happens later via wp_kses on save (sanitizeGlossaryTerm()).
	 */
	protected function sanitizeInline( string $html ): string {
		return strip_tags( $html, '<a><em><strong><sub><sup>' );
	}

	/**
	 * Serialize a node's inner HTML.
	 */
	protected function innerHtml( \DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return $html;
	}

	/**
	 * Build entries from lines using the boundary heuristic. Titles are plain
	 * text; definitions preserve sanitized inline HTML.
	 *
	 * @param array<int, array{text:string, html:string}> $lines
	 * @return array<string, array{title:string, definition:string}>
	 */
	protected function entriesFromLines( array $lines ): array {
		$entries = [];
		$current = null;
		foreach ( $lines as $line ) {
			if ( $this->startsNewEntry( $line['text'] ) ) {
				$pos = mb_strpos( $line['text'], ':' );
				$title = trim( mb_substr( $line['text'], 0, $pos ) );
				$definition = trim( ltrim( preg_replace( '/^.*?:/s', '', $line['html'], 1 ) ) );
				$key = self::normalizeKey( $title );
				if ( ! isset( $entries[ $key ] ) ) {
					$entries[ $key ] = [ 'title' => $title, 'definition' => $definition ];
				}
				$current = $key;
			} elseif ( null !== $current ) {
				$entries[ $current ]['definition'] = trim(
					$entries[ $current ]['definition'] . '<br>' . $line['html']
				);
			}
		}
		return $entries;
	}

	/**
	 * Whether a line begins a new entry: colon + a plausible short key.
	 */
	protected function startsNewEntry( string $line ): bool {
		$pos = mb_strpos( $line, ':' );
		if ( false === $pos || 0 === $pos ) {
			return false;
		}
		$key = trim( mb_substr( $line, 0, $pos ) );
		if ( '' === $key || mb_strlen( $key ) > 60 ) {
			return false;
		}
		if ( str_word_count( $key ) > 6 ) {
			return false;
		}
		if ( preg_match( '/[.?!]$/', $key ) ) {
			return false;
		}
		return true;
	}
}
