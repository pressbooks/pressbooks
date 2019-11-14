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
	 * @var array
	 */
	var $glossaryTerms = [];

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
		// Webbook shortcode
		add_shortcode( self::SHORTCODE, [ $obj, 'webShortcodeHandler' ] );
		add_action(
			'pb_pre_export', function () use ( $obj ) {
				// Override webbook shortcode when exporting
				remove_shortcode( self::SHORTCODE );
				add_shortcode( self::SHORTCODE, [ $obj, 'exportShortcodeHandler' ] );
				remove_filter( 'the_content', [ $obj, 'tooltipContent' ], 13 ); // Only for the webbook!

			}
		);
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = Glossary::SHORTCODE;
				return $excluded_shortcodes;
			}
		);
		add_action( 'init', [ $obj, 'addTooltipScripts' ] );
		add_filter( 'wp_insert_post_data', [ $obj, 'sanitizeGlossaryTerm' ] );
		add_filter( 'the_content', [ $obj, 'backMatterAutoDisplay' ] );
		// do_shortcode() is registered as a default filter on 'the_content' with a priority of 11.
		// We need to run $this->tooltipContent() after this, and after footnotes and attributions which are set to 12 and 13 respectively
		add_filter( 'the_content', [ $obj, 'tooltipContent' ], 13 );
	}

	/**
	 * Add JavaScript for the tooltip
	 *
	 * @since 5.5.0
	 */
	public function addTooltipScripts() {
		if ( ! is_admin() ) {
			$assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_script( 'glossary-tooltip', $assets->getPath( 'scripts/glossary-tooltip.js' ), false, null, true );
			wp_enqueue_style( 'glossary-tooltip', $assets->getPath( 'styles/glossary-tooltip.css' ), false, null );
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
				'post_status' => [ 'private', 'publish' ],
				'order' => 'ASC',
				'orderby' => 'title',
			];
			$posts = get_posts( $args );
			/** @var \WP_Post $post */
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
					'status' => $post->post_status,
				];
			}
		}
		return $glossary_terms;
	}

	/**
	 * For tiny mce
	 * Get both published and private terms
	 *
	 * @param bool $reset (optional, default is false)
	 *
	 * @return string
	 */
	public function getGlossaryTermsListbox( $reset = false ) {
		$values[] = [
			'text' => '-- ' . __( 'Select', 'pressbooks' ) . ' --',
			'value' => '',
		];
		$terms = $this->getGlossaryTerms( $reset );
		foreach ( $terms as $title => $term ) {
			$values[] = [
				'text' => \Pressbooks\Sanitize\decode( $title ),
				'value' => (int) $term['id'],
			];
		}
		return wp_json_encode( $values );
	}

	/**
	 * Returns the HTML <dl> description list of all !published! glossary terms
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
		$glossary_terms = $this->getGlossaryTerms();

		if ( empty( $glossary_terms ) ) {
			return '';
		}

		$ok = uksort(
			$glossary_terms, function ( $a, $b ) {
				// Case insensitive, special accented characters, sort
				$at = iconv( 'UTF-8', 'ASCII//TRANSLIT', $a );
				$bt = iconv( 'UTF-8', 'ASCII//TRANSLIT', $b );
				return strcmp( strtolower( $at ), strtolower( $bt ) );
			}
		);

		if ( true === $ok && count( $glossary_terms ) > 0 ) {
			foreach ( $glossary_terms as $glossary_term_id => $glossary_term ) {
				if ( $glossary_term['status'] !== 'publish' ) {
					continue;
				}
				if ( ! empty( $type ) && ! \Pressbooks\Utility\comma_delimited_string_search( $glossary_term['type'], $type ) ) {
					// Type was not found. Skip this glossary term.
					continue;
				}
				$glossary .= sprintf(
					'<dt data-type="glossterm"><dfn id="%1$s">%2$s</dfn></dt><dd data-type="glossdef">%3$s</dd>',
					sprintf(
						'dfn-%s',
						\Pressbooks\Sanitize\sanitize_xml_id( \Pressbooks\Utility\str_lowercase_dash( $glossary_term_id ) )
					),
					$glossary_term_id,
					wpautop( $glossary_term['content'] )
				);
			}
		}
		if ( ! empty( $glossary ) ) {
			$output = sprintf( '<dl data-type="glossary">%1$s</dl>', $glossary );
		}

		return $output;
	}

	/**
	 * Returns the tooltip markup and content
	 *
	 * @since 5.5.0
	 *
	 * @param int $glossary_term_id
	 * @param string $content
	 *
	 * @return string
	 */
	public function glossaryTooltip( $glossary_term_id, $content ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		// Get the glossary post object the glossary term ID belongs to
		$glossary_term = get_post( $glossary_term_id );
		if ( ! $glossary_term ) {
			return $content . 'no post';
		}
		if ( $glossary_term->post_status === 'trash' ) {
			return $content;
		}

		$html = '<button class="glossary-term" aria-describedby="' . $id . '-' . $glossary_term_id . '">' . $content . '</button>';

		return $html;
	}

	/**
	 * Webbook shortcode
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
	public function webShortcodeHandler( $atts, $content ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$a = shortcode_atts(
			[
				'id' => '',
				'type' => '',
			], $atts
		);

		if ( ! empty( $content ) ) {
			// This is a tooltip
			if ( $a['id'] ) {
				if ( ! isset( $this->glossaryTerms[ $id ] ) ) {
					$this->glossaryTerms[ $id ] = [];
				}

				if ( ! isset( $this->glossaryTerms[ $id ][ $a['id'] ] ) ) {
					$this->glossaryTerms[ $id ][ $a['id'] ] = get_post_field( 'post_content', $a['id'] );
				}
				return $this->glossaryTooltip( $a['id'], $content );
			}
		} else {
			// This is a list of glossary terms
			return $this->glossaryTerms( $a['type'] );
		}

		return $content;
	}

	/**
	 * Post-process glossary shortcode, creating content for tooltips
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function tooltipContent( $content ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		if ( ! empty( $this->glossaryTerms ) && isset( $this->glossaryTerms[ $id ] ) ) {
			$glossary_terms = $this->glossaryTerms[ $id ];
		} else {
			return $content;
		}

		$content .= '<div class="glossary">';

		foreach ( $glossary_terms as $glossary_term_id => $glossary_term ) {
			$identifier = "$id-$glossary_term_id";
			$content .= '<div class="glossary__tooltip" id="' . $identifier . '" hidden>' . wpautop( do_shortcode( $glossary_term ) ) . '</div>';
		}

		$content .= '</div>';

		return $content;
	}

	/**
	 * Export shortcode
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function exportShortcodeHandler( $atts, $content ) {
		return $content;
	}

	/**
	 * @param array $data An array of slashed post data.
	 *
	 * @return mixed
	 */
	public function sanitizeGlossaryTerm( $data ) {
		if ( isset( $data['post_type'], $data['post_content'] ) && $data['post_type'] === 'glossary' ) {
			$data['post_content'] = wp_kses(
				$data['post_content'],
				[
					'a' => [
						'href' => [],
						'target' => [],
					],
					'br' => [],
					'em' => [],
					'p' => [],
					'strong' => [],

				]
			);
		}
		return $data;
	}

	/**
	 * Automatically display shortcode list in Glossary back matter if content is empty
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function backMatterAutoDisplay( $content ) {
		$post = get_post();
		if ( ! $post ) {
			// Try to find using deprecated means
			global $id;
			$post = get_post( $id );
		}
		if ( ! $post ) {
			// Unknown post
			return $content;
		}
		if ( $post->post_type !== 'back-matter' ) {
			// Post is not a back-matter
			return $content;
		}
		$taxonomy = \Pressbooks\Taxonomy::init();
		if ( $taxonomy->getBackMatterType( $post->ID ) !== 'glossary' ) {
			// Post is not a glossary
			return $content;
		}

		if ( ! \Pressbooks\Utility\empty_space( \Pressbooks\Sanitize\decode( str_replace( '&nbsp;', '', $content ) ) ) ) {
			// Content is not empty
			return $content;
		}

		return $this->glossaryTerms();
	}

}
