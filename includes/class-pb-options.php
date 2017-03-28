<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

abstract class Options {

	/**
	 * Configure the options page or tab using the settings API.
	 */
	abstract function init();

	/**
	 * Display the options page or tab description.
	 */
	abstract function display();

	/**
	 * Render the options page or tab.
	 */
	abstract function render();

	/**
	* Upgrade handler for the options page or tab.
	 *
	 * @param int $version
	 */
	abstract function upgrade( $version );

	/**
	 * Get the slug for this options page or tab.
	 *
	 * @return string $slug
	 */
	abstract static function getSlug();

	/**
	 * Get the localized title of this options page or tab.
	 *
	 * @return string $title
	 */
	abstract static function getTitle();

	/**
	 * Get an array of default values for this set of options
	 *
	 * @return array $defaults
	 */
	abstract static function getDefaults();

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults
	 * @return array $defaults
	 */
	abstract static function filterDefaults( $defaults );

	/**
	* Sanitize various options (boolean, string, integer, float).
	*
	* @param array $input
	 * @return array $options
	*/
	function sanitize( $input ) {
		$options = array();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		if ( property_exists( $this, 'booleans' ) ) {
			foreach ( $this->booleans as $key ) {
				if ( ! isset( $input[ $key ] ) || 1 != @$input[ $key ] ) {
					$options[ $key ] = 0;
				} else {
					$options[ $key ] = 1;
				}
			}
		}

		if ( property_exists( $this, 'strings' ) ) {
			foreach ( $this->strings as $key ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $options[ $key ] );
				} else {
					$options[ $key ] = sanitize_text_field( $input[ $key ] );
				}
			}
		}

		if ( property_exists( $this, 'urls' ) ) {
			foreach ( $this->urls as $key ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $options[ $key ] );
				} else {
					$value = trim( strip_tags( stripslashes( $input[ $key ] ) ) );
					if ( $value ) {
						$options[ $key ] = \Pressbooks\Sanitize\canonicalize_url( $value );
					} else {
						unset( $options[ $key ] );
					}
				}
			}
		}

		if ( property_exists( $this, 'integers' ) ) {
			foreach ( $this->integers as $key ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $options[ $key ] );
				} else {
					$options[ $key ] = absint( $input[ $key ] );
				}
			}
		}

		if ( property_exists( $this, 'floats' ) ) {
			foreach ( $this->floats as $key ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $options[ $key ] );
				} else {
					$options[ $key ] = filter_var( $input[ $key ], FILTER_VALIDATE_FLOAT );
				}
			}
		}

		if ( property_exists( $this, 'predefined' ) ) {
			foreach ( $this->predefined as $key ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $options[ $key ] );
				} else {
					$options[ $key ] = $input[ $key ];
				}
			}
		}

		return $options;
	}

	/**
	 * Render an input.
	 *
	 * @param array $args
	 */
	static function renderField( $args = array( 'id' => null, 'name' => null, 'option' => null, 'value' => '', 'description' => '', 'append' => '', 'type' => 'text', 'class' => 'regular-text', 'disabled' => false ) ) {
		printf(
			'<input id="%s" class="%s" name="%s[%s]" type="%s" value="%s" %s/>',
			$args['id'],
			$args['class'],
			$args['name'],
			$args['option'],
			$args['type'],
			$args['value'],
			( $args['disabled'] ) ? ' disabled' : ''
		);
		if ( $args['append'] ) {
			echo ' ' . $args['append'];
		}
		printf(
			'<p class="description">%s</p>',
			$args['description']
		);
	}

	/**
	 * Render a checkbox.
	 *
	 * @param array $args
	 */
	static function renderCheckbox( $args = array( 'id' => null, 'name' => null, 'option' => null, 'value' => '', 'description' => '' ) ) {
		printf(
			'<input id="%s" name="%s[%s]" type="checkbox" value="1" %s/><label for="%s">%s</label>',
			$args['id'],
			$args['name'],
			$args['option'],
			checked( 1, $args['value'], false ),
			$args['id'],
			$args['description']
		);
	}

	/**
	 * Render radio buttons.
	 *
	 * @param array $args
	 */
	static function renderRadioButtons( $args = array( 'id' => null, 'name' => null, 'option' => null, 'value' => '', 'choices' => array(), 'custom' => false ) ) {
		$is_custom = false;
		if ( ! array_key_exists( $args['value'], $args['choices'] ) ) {
			$is_custom = true;
		}
		foreach ( $args['choices'] as $key => $label ) {
			printf(
				'<label for="%s"><input type="radio" id="%s" name="%s[%s]" value="%s" %s/>%s</label><br />',
				$args['id'] . '_' . sanitize_key( $key ),
				$args['id'] . '_' . sanitize_key( $key ),
				$args['name'],
				$args['option'],
				$key,
				( $args['custom'] && $is_custom && '' == $key ) ? 'checked' : checked( $key, $args['value'], false ),
				$label
			);
		}
	}

	/**
	 * Render a select element.
	 *
	 * @param array $args
	 */
	static function renderSelect( $args = array( 'id' => null, 'name' => null, 'option' => null, 'value' => '', 'choices' => array(), 'multiple' => false ) ) {
		$options = '';
		foreach ( $args['choices'] as $key => $label ) {
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$key,
				selected( $key, $args['value'], false ),
				$label
			);
		}
		printf(
			'<select name="%s[%s]" id="%s"%s>%s</select>',
			$args['name'],
			$args['option'],
			$args['id'],
			( $args['multiple'] ) ? ' multiple' : '',
			$options
		);
	}

	/**
	 * Render a custom select element.
	 *
	 * @param array $args
	 */
	static function renderCustomSelect( $args = array( 'id' => null, 'name' => null, 'value' => '', 'choices' => array(), 'multiple' => false ) ) {
		$is_custom = false;
		if ( ! array_key_exists( $args['value'], $args['choices'] ) ) {
			$is_custom = true;
		}
		$options = '';
		foreach ( $args['choices'] as $key => $label ) {
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$key,
				( '' == $key && $is_custom ) ? ' selected' : selected( $key, $args['value'], false ),
				$label
			);
		}
		printf(
			'<select name="%s" id="%s"%s>%s</select><br />',
			$args['name'],
			$args['id'],
			( $args['multiple'] ) ? ' multiple' : '',
			$options
		);
	}
}
