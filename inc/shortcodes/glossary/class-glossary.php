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
		// Webbook shortcode
		add_shortcode( self::SHORTCODE, [ $obj, 'webShortcodeHandler' ] );
		add_action( 'pb_pre_export', function () use ( $obj ) {
			// Override webbook shortcode when exporting
			remove_shortcode( self::SHORTCODE );
			add_shortcode( self::SHORTCODE, [ $obj, 'exportShortcodeHandler' ] );
		} );
		add_filter( 'no_texturize_shortcodes', function ( $excluded_shortcodes ) {
			$excluded_shortcodes[] = Glossary::SHORTCODE;
			return $excluded_shortcodes;
		} );
		add_action( 'init', [ $obj, 'addTooltipScripts' ] );
		add_filter( 'wp_insert_post_data', [ $obj, 'sanitizeGlossaryTerm' ] );
		add_filter( 'the_content', [ $obj, 'backMatterAutoDisplay' ] );
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
				'post_status' => [ 'private', 'publish' ],
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
		$terms = $this->getGlossaryTerms();

		if ( empty( $terms ) ) {
			return '';
		}

		// make sure they are sorted in alphabetical order
		$ok = ksort( $terms, SORT_LOCALE_STRING );

		if ( true === $ok && count( $terms ) > 0 ) {
			foreach ( $terms as $key => $value ) {
				if ( $value['status'] !== 'publish' ) {
					continue;
				}
				if ( ! empty( $type ) && ! \Pressbooks\Utility\comma_delimited_string_search( $value['type'], $type ) ) {
					// Type was not found. Skip this glossary term.
					continue;
				}
				$glossary .= sprintf(
					'<dt data-type="glossterm"><dfn id="%1$s">%2$s</dfn></dt><dd data-type="glossdef">%3$s</dd>',
					sprintf( 'dfn-%s', \Pressbooks\Utility\str_lowercase_dash( $key ) ), $key, wp_strip_all_tags( $value['content'] )
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
	 * @param array $term_id
	 * @param string $content
	 *
	 * @return string
	 */
	public function glossaryTooltip( $term_id, $content ) {

		// get the glossary post object the ID belongs to
		$terms = get_post( $term_id['id'] );
		if ( ! $terms ) {
			return $content;
		}
		if ( $terms->post_status === 'trash' ) {
			return $content;
		}

		// use our post instead of the global $post object
		setup_postdata( $terms );

		// setup_postdata() sets up every global for the post except ...drumroll... $post /fail horn
		global $post;
		$old_global_post = $post;
		$post = $terms;

		$html = '<a href="javascript:void(0);" class="tooltip" title="' . $terms->post_content . '">' . $content . '</a>';

		// reset post data
		wp_reset_postdata();
		$post = $old_global_post;

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
			$data['post_content'] = wp_strip_all_tags( $data['post_content'] );
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
