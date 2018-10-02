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
	 * Some JavaScript for our TinyMCE buttons
	 *
	 * @since 5.5.0
	 *
	 * @param $plugin_array
	 *
	 * @return mixed
	 */
	function addGlossaryPlugin( $plugin_array ) {
		$assets = new Assets( 'pressbooks', 'plugin' );
		$plugin_array['glossary'] = $assets->getPath( 'scripts/glossary.js' );

		return $plugin_array;
	}

	/**
	 * Add JavaScript for the tooltip
	 *
	 * @since 5.5.0
	 *
	 */
	function addTooltipScripts() {
		$assets = new Assets( 'pressbooks', 'plugin' );
		wp_enqueue_script( 'glossary-tooltip', $assets->getPath( 'scripts/tooltip.js' ), [ 'jquery-ui-tooltip' ], false, true );
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
	function getGlossaryTerms( $reset = false ) {
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
	 * Register our plugin with TinyMCE
	 *
	 * @since 5.5.0
	 *
	 */
	function glossaryButton() {

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		if ( get_user_option( 'rich_editing' ) ) {

			add_action(
				'admin_enqueue_scripts', function () {
					wp_localize_script(
						'editor', 'PB_GlossaryToken', [
							'nonce'              => wp_create_nonce( 'pb-glossary' ),
							'glossary_title'     => __( 'Insert Glossary Term', 'pressbooks' ),
							'glossary_all_title' => __( 'Insert Glossary List', 'pressbooks' ),
							'glossary_terms'     => wp_json_encode( $this->getGlossaryTerms() ),
						]
					);
				}
			);

			add_filter( 'mce_external_plugins', [ $this, 'addGlossaryPlugin' ] );

			// to avoid 'inception' like glossary within a glossary, restricting
			// glossary buttons means less chance of needing to untangle the labyrinth
			global $typenow;

			if ( empty( $typenow ) && ! empty( $_GET['post'] ) && 'edit' === $_GET['action'] ) {
				$post = get_post( $_GET['post'] );
				$typenow = $post->post_type;
			} elseif ( ! empty( $_GET['post_type'] ) ) {
				$typenow = $_GET['post_type'];
			}

			if ( 'glossary' !== $typenow ) {
				add_filter(
					'mce_buttons_3', [
						$this,
						'registerGlossaryButtons',
					]
				);
			}
		}

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
	function glossaryTerms( $type = '' ) {
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
	function glossaryTooltip( $term_id, $content ) {

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
		add_action( 'init', [ $obj, 'glossaryButton' ] ); // TinyMCE button
		add_action( 'init', [ $obj, 'addTooltipScripts' ] );
	}

	/**
	 * Add buttons to TinyMCE interface
	 *
	 * @since 5.5.0
	 *
	 * @param $buttons
	 *
	 * @return array
	 */
	function registerGlossaryButtons( $buttons ) {
		$buttons[] = 'glossary';
		$buttons[] = 'glossary_all';

		return $buttons;
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
	function shortcodeHandler( $atts, $content ) {
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
