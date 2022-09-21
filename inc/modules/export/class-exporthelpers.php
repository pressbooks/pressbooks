<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export;

use Pressbooks\Book;
use Pressbooks\Sanitize;

/**
 * Reusable code between export routines
 */
trait ExportHelpers {

	/**
	 * @param $post_type_identifier
	 * @param $id
	 * @return mixed
	 */
	public function getPostSubClass( $post_type_identifier, $id ) {
		$method = studly_case( $post_type_identifier );
		$taxonomy_method = "get{$method}Type";

		return $this->taxonomy->{$taxonomy_method}( $id );
	}

	/**
	 * Map Book contents
	 * This trait should be used in classes that are ExportGenerators (black magic traits stuff)
	 * @param array $post_data
	 * @param array $metadata
	 * @param int $post_number
	 * @param array $options post_type,needs_sanitization,endnotes,footnotes
	 * @return array
	 */
	public function mapBookDataAndContent( array $post_data, array $metadata, int $post_number, array $options = [] ) {
		$post_type_identifier = $options['type'] ?? 'post';
		$needs_tidy_html = $options['needs_tidy_html'] ?? false;
		$endnotes = $options['endnotes'] ?? false;
		$footnotes = $options['footnotes'] ?? false;
		$is_epub = $options['is_epub'] ?? false;

		$data = [
			'id' => $post_data['ID'],
		];
		$data['post_type_class'] = str_replace( '_', '-', $post_type_identifier ); // This class is used to map with the SCSS class in buckram Ex: front-matter
		$data['subclass'] = $this->getPostSubClass( $post_type_identifier, $post_data['ID'] );
		$data['slug'] = $is_epub ? $post_data['post_name'] : "{$data['post_type_class']}-{$post_data['post_name']}";
		$data['title'] = get_post_meta( $post_data['ID'], 'pb_show_title', true ) ? Sanitize\decode( $post_data['post_title'] ) : '';

		if ( ! $is_epub ) {
			$data['title'] = empty( $data['title'] ) ? '<span class="display-none">' . $post_data['post_title'] . '</span>' : $data['title'];
		}

		$data['content'] = $post_data['post_content'];
		$data['append_post_content'] = apply_filters( "pb_append_{$post_type_identifier}_content", '', $post_data['ID'] );
		$data['short_title'] = trim( get_post_meta( $post_data['ID'], 'pb_short_title', true ) );
		$section_license = $this->doSectionLevelLicense( $metadata, $post_data['ID'] );

		if ( $needs_tidy_html ) {
			$data['content'] = $this->kneadHtml( $data['content'], $post_type_identifier, $post_number );

			if ( $section_license ) {
				$data['append_post_content'] .= $this->kneadHtml($this->tidy( $section_license ), $post_type_identifier,
				$post_number);
			}
		} else {
			$data['append_post_content'] .= $this->removeAttributionLink( $section_license );
		}
		$data['short_title'] = ( $data['short_title'] ) ?: wp_strip_all_tags( Sanitize\decode( $post_data['post_title'] ) ); //Sanitize to pass this to the blade template as the Title attr
		$data['subtitle'] = trim( get_post_meta( $post_data['ID'], 'pb_subtitle', true ) );
		$data['author'] = $this->contributors->get( $post_data['ID'], 'pb_authors' );
		$data['post_number'] = $post_number;

		if ( $endnotes ) {
			$data['endnotes'] = $this->doEndnotes( $post_data['ID'] );
		}
		if ( $footnotes ) {
			$data['footnotes'] = $this->doFootnotes( $post_data['ID'] );
		}

		if ( ( Export::shouldParseSubsections() === true ) && Book::getSubsections( $post_data['ID'] ) !== false ) {
			$data['subsection_class'] = 'with-subsections';
			$data['content'] = $this->html5ToXhtml( Book::tagSubsections( $data['content'], $post_data['ID'] ) );
		}

		if ( ! $is_epub ) { // Print contributors in PDF after the content
			$data['content'] .= $this->displayAboutTheAuthors ? \Pressbooks\Modules\Export\get_contributors_section( $post_data['ID'] ) : '';
		}

		$data['is_new_buckram'] = $this->wrapHeaderElements;
		$data['output_short_title'] = property_exists( $this, 'outputShortTitle' ) ? $this->outputShortTitle : false;
		$data['post_type_identifier'] = $post_type_identifier;

		return $data;
	}

	/**
	 * @param  array  $book_contents
	 * @return int
	 */
	public function countPartsAndChapters( $book_contents ) {
		$ticks = count( $book_contents['part'] );

		foreach ( $book_contents['part'] as $part ) {
			$ticks += count( $part['chapters'] );
		}

		return $ticks;
	}

	/**
	 * getPostInformation
	 *
	 * @param $post_type
	 * @param $post
	 * @param  null  $alias
	 * @return array
	 */
	public function getPostInformation( $post_type, $post, $alias = null ) {
		$prefix = $alias ?? $post_type;
		return [
			'ID' => $post['ID'],
			'post_type' => $post_type,
			'subclass' => $this->getPostSubClass( $post_type, $post['ID'] ),
			'href' => $post['href'] ?? "{$prefix}-{$post['post_name']}",
			'title' => Sanitize\strip_br( $post['post_title'] ),
			'content' => $post['post_content'] ?? '',
		];
	}

	/**
	 * @param $post_type
	 * @param $post
	 * @return array
	 */
	public function getExtendedPostInformation( $post_type, $post ) {
		$data = $this->getPostInformation( $post_type, $post );
		$data['subtitle'] = trim( get_post_meta( $post['ID'], 'pb_subtitle', true ) );
		$data['author'] = $this->contributors->get( $post['ID'], 'pb_authors' );
		$data['license'] = $this->doTocLicense( $post['ID'] );

		return $data;
	}

	/**
	 * @param $post_type
	 * @param $data
	 * @param  bool  $is_slug
	 * @return string
	 */
	public function renderTocItem( $post_type, $data, $is_slug = true ) {

		$subsections = [];

		if ( Export::shouldParseSubsections() === true ) {

			$sections = \Pressbooks\Book::getSubsections( $data['ID'] );

			if ( $sections ) {
				foreach ( $sections as $id => $subsection ) {
					$subsections[] = [
						'slug' => $is_slug ? "#{$id}" : "${data['href']}#{$id}",
						'title' => Sanitize\decode( $subsection ),
					];
				}
			}
		}

		return $this->blade->render('export/bullet-toc-item', array_merge(
			$data,
			[
				'title' => Sanitize\decode( $data['title'] ),
				'subclass' => trim( $data['subclass'] ) !== '' ? ' ' . $data['subclass'] : '', //css class space between toc item and subclasses
				'post_type' => $post_type,
				'href' => $is_slug ? '#' . $data['href'] : $data['href'],
				'subsections' => $subsections,
			]
		));
	}
}
