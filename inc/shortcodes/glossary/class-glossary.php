<?php
/**
 * @author   Brad Payne, Alex Paredes
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Glossary;

use PressbooksMix\Assets;

class Glossary {

	/**
	 * @var Glossary
	 */
	static $instance = null;

	/**
	 * @var array
	 */
	static $glossary_terms = [];

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Glossary
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
			self::setGlossaryTerms();
		}

		return self::$instance;
	}

	/**
	 * @param Glossary $obj
	 */
	static public function hooks( Glossary $obj ) {
		add_shortcode( 'pb_glossary', [ $obj, 'shortcodeHandler' ] );
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = 'pb_glossary';

				return $excluded_shortcodes;
			}
		);
		add_action( 'init', [ $obj, 'glossaryButton' ] ); // TinyMCE button
	}

	/**
	 * Gets all glossary terms currently in the database, returns as an array of
	 * key = post_title, id = post ID, content = post_content. Sets an instance variable
	 *
	 * @since 5.5.0
	 *
	 * @return void $glossary_terms
	 */
	private static function setGlossaryTerms() {
		$glossary_terms = [];
		$args = [
			'post_type'      => 'glossary',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		];

		$terms = get_posts( $args );

		foreach ( $terms as $term ) {
			$glossary_terms[ $term->post_title ] = [
				'id' => $term->ID,
				'content' => $term->post_content,
			];
		}

		self::$glossary_terms = $glossary_terms;
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
	function shortcodeHandler( $atts, $content = '' ) {
		$retval = '';

		$a = shortcode_atts(
			[
				'id' => '',
			], $atts
		);

		if ( ! empty( $a['id'] ) ) {
			$retval = $this->glossaryTooltip( $a );
		} else {
			$retval = $this->glossaryTerms();
		}

		return $retval;
	}


	/**
	 * Returns the HTML for a term if the param contains the post id
	 *
	 * @since 5.5.0
	 *
	 * @param $term_id
	 *
	 * @return string
	 */
	function glossaryTooltip( $term_id ) {
		$glossary_terms = '';
		$html = '';

		if ( ! empty( $term_id ) ) {
			//todo: generate appropriate tooltip markup for singular glossary term
		}

		return $html;
	}

	/**
	 * Returns the HTML <dl> description list of all glossary terms
	 *
	 * @since 5.5.0
	 *
	 * @return string
	 */
	function glossaryTerms() {
		$output = '';
		$glossary = '';
		$terms = self::$glossary_terms;

		if ( empty( $terms ) ) {
			return '';
		}

		// make sure they are sorted in alphabetical order
		$ok = ksort( $terms, SORT_LOCALE_STRING );

		if ( true === $ok && count( $terms ) > 0 ) {
			foreach ( $terms as $key => $value ) {
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
							'glossary_terms'     => __( wp_json_encode( self::$glossary_terms ), 'pressbooks' ),
						]
					);
				}
			);

			add_filter( 'mce_external_plugins', [ $this, 'addGlossaryPlugin' ] );
			add_filter( 'mce_buttons_3', [ $this, 'registerGlossaryButtons' ] );
		}

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
}
