<?php
/**
 * @author   Brad Payne, Alex Paredes
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Glossary;

use PressbooksMix\Assets;
use Pressbooks\PostType\BackMatter;
use Pressbooks\Utility\AutoDisplayable;
use WP_Post;

class Glossary implements BackMatter {

	use AutoDisplayable;

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
	static public function init(): Glossary {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * @param Glossary $obj
	 */
	static public function hooks( Glossary $obj ): void {
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
		add_filter( 'the_content', [ $obj, 'overrideDisplay' ] );
		// do_shortcode() is registered as a default filter on 'the_content' with a priority of 11.
		// We need to run $this->tooltipContent() after this, and after footnotes and attributions which are set to 12 and 13 respectively
		add_filter( 'the_content', [ $obj, 'tooltipContent' ], 13 );
	}

	/**
	 * Add JavaScript for the tooltip
	 *
	 * @since 5.5.0
	 */
	public function addTooltipScripts(): void {
		if ( ! is_admin() ) {
			$assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_script( 'glossary-definition', $assets->getPath( 'scripts/glossary-definition.js' ), false, null, true );
			wp_enqueue_style( 'glossary-definition', $assets->getPath( 'styles/glossary-definition.css' ), false, null );
		}
	}

	/**
	 * Gets the instance variable of glossary terms, returns as an array of
	 * key = post_title, id = post ID, content = post_content. Sets an instance variable
	 *
	 * @param bool $reset (optional, default is false)
	 *
	 * @return array
	 *@since 5.5.0
	 *
	 */
	public function getGlossaryTerms( bool $reset = false ): ?array {
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
	public function getGlossaryTermsListbox( bool $reset = false ): string {
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
	 * @param string $type The slug of an entry in the Glossary Types taxonomy
	 *
	 * @return string
	 */
	public function glossaryTerms( string $type = '' ): string {
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
				$gContent = apply_shortcodes( $glossary_term['content'] );

				$glossary .= sprintf(
					'<dt data-type="glossterm"><dfn id="%1$s">%2$s</dfn></dt><dd data-type="glossdef">%3$s</dd>',
					sprintf(
						'dfn-%s',
						\Pressbooks\Sanitize\sanitize_xml_id( \Pressbooks\Utility\str_lowercase_dash( $glossary_term_id ) )
					),
					$glossary_term_id,
					wpautop( $gContent )
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
	 * @param int $glossary_term_id
	 * @param string $content
	 *
	 * @return string
	 *@since 5.5.0
	 *
	 */
	public function glossaryTooltip( int $glossary_term_id, string $content ): string {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		// Get the glossary post object the glossary term ID belongs to
		$glossary_term = get_post( $glossary_term_id );

		if ( ! $glossary_term || $glossary_term->post_status === 'trash' ) {
			return $content;
		}

		$html = '<a class="glossary-term" aria-haspopup="dialog" aria-describedby="definition" href="#term_' . $id . '_' . $glossary_term_id . '">' . $content . '</a>';

		return $html;
	}

	/**
	 * Webbook shortcode
	 * Gets the tooltip if the param contains the post id,
	 * or a list of terms if it's just the short-code
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 *@since 5.5.0
	 *
	 */
	public function webShortcodeHandler( array $atts, string $content ): string {

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
	 * @param string $content
	 *
	 * @return string
	 */
	function tooltipContent( string $content ): string {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		if ( ! empty( $this->glossaryTerms ) && isset( $this->glossaryTerms[ $id ] ) ) {
			$glossary_terms = $this->glossaryTerms[ $id ];
		} else {
			return $content;
		}

		$content .= '<div class="glossary"><span class="screen-reader-text" id="definition">' . __( 'definition', 'pressbooks' ) . '</span>';

		foreach ( $glossary_terms as $glossary_term_id => $glossary_term ) {
			$identifier = 'term_' . $id . '_' . $glossary_term_id;
			$content .= '<template id="' . $identifier . '"><div class="glossary__definition" role="dialog" data-id="' . $identifier . '"><div tabindex="-1">' . wpautop( apply_shortcodes( $glossary_term ) ) . '</div><button><span aria-hidden="true">&times;</span><span class="screen-reader-text">' . __( 'Close definition', 'pressbooks' ) . '</span></button></div></template>';
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
	public function exportShortcodeHandler( array $atts, string $content ): string {
		return "<span class='glossary-term'>" . $content . '</span>';
	}

	/**
	 * @param array $data An array of slashed post data.
	 *
	 * @return mixed
	 */
	public function sanitizeGlossaryTerm( array $data ): mixed {
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
	public function overrideDisplay( $content ): string {

		return $this->display(
			$content, function() {
				return $this->glossaryTerms();
			}
		);

	}

	public static function isGlossaryPost( ?WP_Post $post ): bool {
		$post = $post ?? get_post();

		$is_glossary_type = ! empty( array_filter(
			wp_get_post_terms( $post->ID, 'back-matter-type' ),
			function( $term ) {
				return $term->slug === 'glossary';
			}
		) );

		return $post->post_type === 'back-matter' && $is_glossary_type;
	}
}
