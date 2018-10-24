<?php
/**
 * @author   Brad Payne, Alex Paredes
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Glossary;

use PressbooksMix\Assets;

class Glossary {

	const SHORTCODE = 'pb_glossary';

	/**
	 * @var Glossary
	 */
	static $instance = null;

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Glossary
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * @param Glossary $obj
	 */
	static public function hooks( Glossary $obj ) {
		add_shortcode( self::SHORTCODE, [ $obj, 'shortcodeHandler' ] );
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = Glossary::SHORTCODE;

				return $excluded_shortcodes;
			}
		);
		add_action( 'init', [ $obj, 'addTooltipScripts' ] );
	}

	/**
	 * Add JavaScript for the tooltip
	 *
	 * @since 5.5.0
	 */
	public function addTooltipScripts() {
		if ( ! is_admin() ) {
			$assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_script( 'glossary-tooltip', $assets->getPath( 'scripts/glossary-tooltip.js' ), [ 'jquery-ui-tooltip' ], false, true );
		}
	}

	/**
	 * Gets the instance variable of glossary terms, returns as an array of
	 * key = post_title, id = post ID, content = post_content. Sets an instance variable
	 *
	 * @param bool $reset (optional, default is false)
	 *
	 * @since 5.5.0
	 *
	 * @return array
	 */
	public function getGlossaryTerms( $reset = false ) {
		// Cheap cache
		static $glossary_terms = null;
		if ( $reset || $glossary_terms === null ) {
			$glossary_terms = [];
			$args = [
				'post_type' => 'glossary',
				'posts_per_page' => -1, // @codingStandardsIgnoreLine
				'post_status' => 'publish',
			];
			$posts = get_posts( $args );
			foreach ( $posts as $post ) {
				$type = '';
				$terms = get_the_terms( $post->ID, 'glossary-type' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$type .= "{$term->slug},";
					}
				}
				$glossary_terms[ $post->post_title ] = [
					'id' => $post->ID,
					'content' => $post->post_content,
					'type' => rtrim( $type, ',' ),
				];
			}
		}
		return $glossary_terms;
	}

	/**
	 * For tiny mce
	 *
	 * @param bool $reset (optional, default is false)
	 *
	 * @return string
	 */
	public function getGlossaryTermsListbox( $reset = false ) {
		$values[] = [ 'text' => '-- ' . __( 'Select', 'pressbooks' ) . ' --', 'value' => '' ];
		$terms = $this->getGlossaryTerms( $reset );
		foreach ( $terms as $title => $term ) {
			$values[] = [ 'text' => \Pressbooks\Sanitize\decode( $title ), 'value' => (int) $term['id'] ];
		}
		return wp_json_encode( $values );
	}

	/**
	 * Returns the HTML <dl> description list of all glossary terms
	 *
	 * @since 5.5.0
	 * @see \Pressbooks\HTMLBook\Component\Glossary
	 *
	 * @param string $type The slug of an entry in the Glossary Types taxonomy
	 *
	 * @return string
	 */
	public function glossaryTerms( $type = '' ) {
		$output = '';
		$glossary = '';
		$terms = $this->getGlossaryTerms();

		if ( empty( $terms ) ) {
			return '';
		}

		// make sure they are sorted in alphabetical order
		$ok = ksort( $terms, SORT_LOCALE_STRING );

		if ( true === $ok && count( $terms ) > 0 ) {
			foreach ( $terms as $key => $value ) {
				if ( ! empty( $type ) && ! \Pressbooks\Utility\comma_delimited_string_search( $value['type'], $type ) ) {
					// Type was not found. Skip this glossary term.
					continue;
				}
				$glossary .= sprintf(
					'<dt data-type="glossterm"><dfn id="%1$s">%2$s</dfn></dt><dd data-type="glossdef">%3$s</dd>',
					sprintf( 'dfn-%s', \Pressbooks\Utility\str_lowercase_dash( $key ) ), $key, trim( $value['content'] )
				);
			}
		}
		if ( ! empty( $glossary ) ) {
			$output = sprintf( '<section data-type="glossary"><header><h2>%1$s</h2></header><dl data-type="glossary">%2$s</dl></section>', __( 'Glossary Terms', 'pressbooks' ), $glossary );
		}

		return $output;
	}

	/**
	 * Returns the tooltip markup and content
	 *
	 * @since 5.5.0
	 *
	 * @param array $term_id
	 * @param string $content
	 *
	 * @return string
	 */
	public function glossaryTooltip( $term_id, $content ) {

		// get the glossary post object the ID belongs to
		$terms = get_post( $term_id['id'] );

		// use our post instead of the global $post object
		setup_postdata( $terms );

		$html = '<a href="javascript:void(0);" class="tooltip" title="' . get_the_excerpt( $term_id['id'] ) . '">' . $content . '</a>';

		// reset post data
		wp_reset_postdata();

		return $html;
	}

	/**
	 * Gets the tooltip if the param contains the post id,
	 * or a list of terms if it's just the short-code
	 *
	 * @since 5.5.0
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function shortcodeHandler( $atts, $content ) {
		$a = shortcode_atts(
			[
				'id' => '',
				'type' => '',
			], $atts
		);

		if ( ! empty( $content ) ) {
			// This is a tooltip
			if ( $a['id'] ) {
				return $this->glossaryTooltip( $a, $content );
			}
		} else {
			// This is a list of glossary terms
			return $this->glossaryTerms( $a['type'] );
		}

		return $content;
	}

}
