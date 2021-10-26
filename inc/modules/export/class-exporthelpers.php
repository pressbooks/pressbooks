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
	 * Map Book contents
	 * This trait should be used in classes that are ExportGenerators (black magic traits stuff)
	 * @param $post_data
	 * @param $metadata
	 * @param $post_number
	 * @param  array  $options post_type,needs_sanitization,endnotes,footnotes
	 * @return array
	 */
	public function mapBookDataAndContent( $post_data, $metadata, $post_number, $options = [] ) {

		$post_type_identifier = $options['type'] ?? 'post';
		$needs_tidy_html = $options['needs_tidy_html'] ?? false;
		$endnotes = $options['endnotes'] ?? false;
		$footnotes = $options['footnotes'] ?? false;

		$data = [
			'id' => $post_data['ID'],
		];
		$data['post_type_class'] = str_replace( '_','-', $post_type_identifier ); // This class is used to map with the SCSS class in buckram Ex: front-matter
		$method = studly_case( $post_type_identifier );
		$taxonomy_method = "get{$method}Type";
		$data['subclass'] = $this->taxonomy->{$taxonomy_method}( $post_data['ID'] );
		$data['slug'] = "{$data['post_type_class']}-{$post_data['post_name']}";
		$data['title'] = ( get_post_meta( $post_data['ID'], 'pb_show_title', true ) ? $post_data['post_title'] : '<span class="display-none">' . $post_data['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
		$data['content'] = $post_data['post_content'];
		$data['append_post_content'] = apply_filters( "pb_append_{$post_type_identifier}_content", '', $post_data['ID'] );
		$data['short_title'] = trim( get_post_meta( $post_data['ID'], 'pb_short_title', true ) );
		$section_license = $this->doSectionLevelLicense( $metadata, $post_data['ID'] );

		if ( $needs_tidy_html ) {
			$data['content'] = $this->kneadHtml( $data['content'], $post_type_identifier, $post_type_identifier );
			$data['append_post_content'] = $this->kneadHtml( apply_filters( "pb_append_{$post_type_identifier}_content", '', $post_data['ID'] ), $post_type_identifier, $post_number );
			if ( $section_license ) {
				$data['append_post_content'] .= $this->kneadHtml( $this->tidy( $section_license ), $post_type_identifier, $post_number );
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
			$data['footnotes'] = $this->doEndnotes( $post_data['ID'] );
		}

		if ( ( Export::shouldParseSubsections() === true ) && Book::getSubsections( $post_data['ID'] ) !== false ) {
			$data['content'] = $this->html5ToXhtml( Book::tagSubsections( $data['content'], $post_data['ID'] ) );
		}

		$data['content'] .= $this->displayAboutTheAuthors ? \Pressbooks\Modules\Export\get_contributors_section( $post_data['ID'] ) : '';
		$data['is_new_buckram'] = $this->wrapHeaderElements;
		$data['output_short_title'] = property_exists( $this, 'outputShortTitle' ) ? $this->outputShortTitle : false;
		$data['post_type_identifier'] = $post_type_identifier;

		return $data;
	}
}
