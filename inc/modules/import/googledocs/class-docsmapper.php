<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class DocsMapper {

	/** @var array */
	protected array $warnings = [];

	/**
	 * Convert a Google Docs API document array into an array of chapters.
	 *
	 * Each chapter is: ['title' => string, 'body' => string, 'images' => array]
	 *
	 * @param array $doc The full document array from the Google Docs API.
	 * @return array
	 */
	public function toChapters( array $doc ): array {
		$this->warnings = [];
		$content = $doc['body']['content'] ?? [];
		$inline_objects = $doc['inlineObjects'] ?? [];
		$lists = $doc['lists'] ?? [];

		$chapters = [];
		$current_title = '';
		$current_body = '';
		$current_images = [];
		$has_h1 = false;

		foreach ( $content as $element ) {
			if ( isset( $element['sectionBreak'] ) ) {
				continue;
			}

			if ( isset( $element['paragraph'] ) ) {
				$para = $element['paragraph'];
				$style_type = $para['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';

				if ( $style_type === 'HEADING_1' ) {
					$has_h1 = true;
					// Save previous chapter if exists
					if ( $current_title !== '' ) {
						$chapters[] = [
							'title' => $current_title,
							'body' => $this->finalize( $current_body ),
							'images' => $current_images,
						];
					}
					$current_title = $this->extractPlainText( $para['elements'] ?? [] );
					$current_body = '';
					$current_images = [];
					continue;
				}

				if ( ! $has_h1 && $current_title === '' ) {
					// Use doc title as fallback
					$current_title = $doc['title'] ?? 'Untitled';
				}

				// Collect image metadata from inline objects in this paragraph
				$this->collectImageMeta( $para['elements'] ?? [], $inline_objects, $current_images );

				$current_body .= $this->renderParagraph( $para, $style_type, $inline_objects, $lists );
			}

			if ( isset( $element['table'] ) ) {
				if ( ! $has_h1 && $current_title === '' ) {
					$current_title = $doc['title'] ?? 'Untitled';
				}
				// Collect image metadata from table cells
				$this->collectTableImageMeta( $element['table'], $inline_objects, $current_images );
				$current_body .= $this->renderTable( $element['table'], $inline_objects, $lists );
			}
		}

		// Save last chapter
		if ( $current_title !== '' || $current_body !== '' ) {
			if ( $current_title === '' ) {
				$current_title = $doc['title'] ?? 'Untitled';
			}
			$chapters[] = [
				'title' => $current_title,
				'body' => $this->finalize( $current_body ),
				'images' => $current_images,
			];
		}

		return $chapters;
	}

	/**
	 * Get warnings from the last toChapters() call.
	 *
	 * @return array
	 */
	public function getWarnings(): array {
		return $this->warnings;
	}

	/**
	 * Collect image metadata from paragraph elements.
	 */
	protected function collectImageMeta( array $elements, array $inline_objects, array &$images ): void {
		foreach ( $elements as $el ) {
			if ( isset( $el['inlineObjectElement'] ) ) {
				$obj_id = $el['inlineObjectElement']['inlineObjectId'] ?? '';
				if ( $obj_id && isset( $inline_objects[ $obj_id ] ) ) {
					$obj = $inline_objects[ $obj_id ]['inlineObjectProperties']['embeddedObject'] ?? [];
					if ( isset( $obj['imageProperties']['contentUri'] ) ) {
						$images[] = [
							'object_id'   => $obj_id,
							'content_uri' => $obj['imageProperties']['contentUri'],
							'alt'         => $obj['description'] ?? '',
							'title'       => $obj['title'] ?? '',
						];
					}
				}
			}
		}
	}

	/**
	 * Collect image metadata from table cells.
	 */
	protected function collectTableImageMeta( array $table, array $inline_objects, array &$images ): void {
		foreach ( $table['tableRows'] ?? [] as $row ) {
			foreach ( $row['tableCells'] ?? [] as $cell ) {
				foreach ( $cell['content'] ?? [] as $cell_element ) {
					if ( isset( $cell_element['paragraph']['elements'] ) ) {
						$this->collectImageMeta( $cell_element['paragraph']['elements'], $inline_objects, $images );
					}
				}
			}
		}
	}

	/**
	 * Extract plain text from paragraph elements.
	 */
	protected function extractPlainText( array $elements ): string {
		$text = '';
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun']['content'] ) ) {
				$text .= $el['textRun']['content'];
			}
		}
		return trim( $text );
	}

	/**
	 * Render a paragraph element to HTML.
	 */
	protected function renderParagraph( array $para, string $style_type, array $inline_objects, array $lists ): string {
		$is_list_item = isset( $para['bullet'] );

		// Handle list items
		if ( $is_list_item ) {
			$list_id = $para['bullet']['listId'];
			$nesting = $para['bullet']['nestingLevel'] ?? 0;
			$text = $this->renderElements( $para['elements'] ?? [], $inline_objects );
			$list_type = $this->getListType( $list_id, $nesting, $lists );

			return $this->makeListItem( $text, $list_id, $nesting, $list_type );
		}

		$text = $this->renderElements( $para['elements'] ?? [], $inline_objects );

		// Handle heading styles
		$tag = $this->styleToTag( $style_type );
		if ( $tag !== null ) {
			return "<{$tag}>{$text}</{$tag}>\n";
		}

		// Normal text
		if ( trim( $text ) === '' ) {
			return '';
		}

		return "<p>{$text}</p>\n";
	}

	/**
	 * Map named style types to HTML heading tags.
	 */
	protected function styleToTag( string $style_type ): ?string {
		$map = [
			'HEADING_2' => 'h2',
			'HEADING_3' => 'h3',
			'HEADING_4' => 'h4',
			'HEADING_5' => 'h5',
			'HEADING_6' => 'h6',
			'SUBTITLE' => 'h2',
			'TITLE' => 'h1',
		];
		return $map[ $style_type ] ?? null;
	}

	/**
	 * Render a list of elements (text runs, inline objects) to HTML.
	 */
	protected function renderElements( array $elements, array $inline_objects ): string {
		$html = '';
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun'] ) ) {
				$html .= $this->renderTextRun( $el['textRun'] );
			} elseif ( isset( $el['inlineObjectElement'] ) ) {
				$obj_id = $el['inlineObjectElement']['inlineObjectId'] ?? '';
				$html .= $this->renderInlineObject( $obj_id, $inline_objects );
			} elseif ( isset( $el['equation'] ) ) {
				$this->warnings[] = 'Equation element skipped (unsupported).';
			}
		}
		return $html;
	}

	/**
	 * Render a single text run with styling.
	 */
	protected function renderTextRun( array $run ): string {
		$text = $run['content'] ?? '';
		$text = rtrim( $text, "\n" );
		if ( $text === '' ) {
			return '';
		}

		$style = $run['textStyle'] ?? [];

		// Apply link
		if ( ! empty( $style['link']['url'] ) ) {
			$url = $style['link']['url'];
			$text = '<a href="' . htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) . '">' . $text . '</a>';
		}

		// Apply formatting (innermost first)
		if ( ! empty( $style['underline'] ) && empty( $style['link'] ) ) {
			$text = '<u>' . $text . '</u>';
		}
		if ( ! empty( $style['italic'] ) ) {
			$text = '<em>' . $text . '</em>';
		}
		if ( ! empty( $style['bold'] ) ) {
			$text = '<strong>' . $text . '</strong>';
		}

		return $text;
	}

	/**
	 * Render an inline object (image or drawing).
	 */
	protected function renderInlineObject( string $obj_id, array $inline_objects ): string {
		if ( ! isset( $inline_objects[ $obj_id ] ) ) {
			return '';
		}

		$obj = $inline_objects[ $obj_id ]['inlineObjectProperties']['embeddedObject'] ?? [];

		// Skip drawings
		if ( isset( $obj['embeddedDrawingProperties'] ) ) {
			$this->warnings[] = "Drawing element skipped (unsupported): {$obj_id}";
			return '';
		}

		// Handle images
		if ( isset( $obj['imageProperties'] ) ) {
			$alt = $obj['description'] ?? $obj['title'] ?? '';
			$src = '#gdoc-image-' . $obj_id;
			return '<img src="' . htmlspecialchars( $src, ENT_QUOTES, 'UTF-8' ) . '" alt="' . htmlspecialchars( $alt, ENT_QUOTES, 'UTF-8' ) . '" />';
		}

		return '';
	}

	/**
	 * Determine list type (ul or ol).
	 */
	protected function getListType( string $list_id, int $nesting, array $lists ): string {
		$glyph = $lists[ $list_id ]['listProperties']['nestingLevels'][ $nesting ]['glyphType'] ?? 'GLYPH_TYPE_UNSPECIFIED';

		if ( in_array( $glyph, [ 'DECIMAL', 'ALPHA', 'ROMAN', 'ZERO_DECIMAL' ], true ) ) {
			return 'ol';
		}

		return 'ul';
	}

	/**
	 * Build a list item tag with nesting metadata.
	 * Actual list open/close tags are resolved during finalize().
	 */
	protected function makeListItem( string $text, string $list_id, int $nesting, string $list_type ): string {
		return "<!--LIST:{$list_id}:{$nesting}:{$list_type}--><li>{$text}</li>\n";
	}

	/**
	 * Render a table element to HTML.
	 */
	protected function renderTable( array $table, array $inline_objects, array $lists ): string {
		$html = "<table>\n";
		foreach ( $table['tableRows'] ?? [] as $row ) {
			$html .= '<tr>';
			foreach ( $row['tableCells'] ?? [] as $cell ) {
				$cell_content = '';
				foreach ( $cell['content'] ?? [] as $element ) {
					if ( isset( $element['paragraph'] ) ) {
						$text = $this->renderElements( $element['paragraph']['elements'] ?? [], $inline_objects );
						$cell_content .= $text;
					}
				}
				$html .= '<td>' . trim( $cell_content ) . '</td>';
			}
			$html .= "</tr>\n";
		}
		$html .= "</table>\n";
		return $html;
	}

	/**
	 * Finalize body HTML by resolving list markers into proper HTML list tags.
	 */
	protected function finalize( string $body ): string {
		$lines = explode( "\n", trim( $body ) );
		$output = '';
		$open_lists = []; // Stack of [ 'type' => 'ul'|'ol', 'nesting' => int ]

		foreach ( $lines as $line ) {
			if ( preg_match( '/^<!--LIST:([^:]+):(\d+):(\w+)-->(.*)$/', $line, $m ) ) {
				$nesting = (int) $m[2];
				$list_type = $m[3];
				$item_html = $m[4];

				// Close lists to reach correct nesting
				while ( count( $open_lists ) > $nesting + 1 ) {
					$closed = array_pop( $open_lists );
					$output .= "</{$closed['type']}>\n</li>\n";
				}

				if ( count( $open_lists ) === $nesting + 1 ) {
					// Same level — check if same type
					$current = end( $open_lists );
					if ( $current['type'] !== $list_type ) {
						$closed = array_pop( $open_lists );
						$output .= "</{$closed['type']}>\n";
						$output .= "<{$list_type}>\n";
						$open_lists[] = [
							'type' => $list_type,
							'nesting' => $nesting,
						];
					}
					$output .= $item_html . "\n";
				} elseif ( count( $open_lists ) <= $nesting ) {
					// Need to open deeper lists
					while ( count( $open_lists ) < $nesting ) {
						$output .= "<ul>\n<li>\n";
						$open_lists[] = [
							'type' => 'ul',
							'nesting' => count( $open_lists ),
						];
					}
					$output .= "<{$list_type}>\n";
					$open_lists[] = [
						'type' => $list_type,
						'nesting' => $nesting,
					];
					$output .= $item_html . "\n";
				}
			} else {
				// Close all open lists before non-list content
				while ( ! empty( $open_lists ) ) {
					$closed = array_pop( $open_lists );
					$output .= "</{$closed['type']}>\n";
				}
				$output .= $line . "\n";
			}
		}

		// Close any remaining open lists
		while ( ! empty( $open_lists ) ) {
			$closed = array_pop( $open_lists );
			$output .= "</{$closed['type']}>\n";
		}

		return trim( $output );
	}
}
