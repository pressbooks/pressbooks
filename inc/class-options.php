<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

/**
 * @property array booleans
 * @property array strings
 * @property array urls
 * @property array integers
 * @property array floats
 * @property array predefined
 */
abstract class Options {

	/**
	 * @var int
	 */
	const VERSION = null;

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
	 *
	 * @return array $defaults
	 */
	abstract static function filterDefaults( $defaults );

	/**
	 * Sanitize various options (boolean, string, integer, float).
	 *
	 * @param array $input
	 *
	 * @return array $options
	 */
	function sanitize( $input ) {
		$options = [];

		if ( ! is_array( $input ) ) {
			$input = [];
		}

		if ( property_exists( $this, 'booleans' ) ) {
			foreach ( $this->booleans as $key ) {
				if ( empty( $input[ $key ] ) ) {
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
	 * @param array $args {
	 *     Arguments to render the input.
	 *
	 * @type string $id The id which will be assigned to the rendered field.
	 * @type string $name The name of the field.
	 * @type string $option The name of the option that the field is within.
	 * @type string $value The stored value of the field as retrieved from the database.
	 * @type string $description A description which will be displayed below the field.
	 * @type string $append A string which will be appended to the field (e.g. 'px').
	 * @type string $type The type property of the input. Default 'text'.
	 * @type string $class The class(es) which will be assigned to the rendered input. Default 'regular-text'.
	 * @type bool $disabled Is the field disabled?
	 * }
	 */
	static function renderField( $args ) {
		$defaults = [
			'id' => null,
			'name' => null,
			'option' => null,
			'value' => '',
			'description' => null,
			'append' => null,
			'type' => 'text',
			'class' => 'regular-text',
			'disabled' => false,
		];

		$args = wp_parse_args( $args, $defaults );

		printf(
			'<input id="%s" class="%s" name="%s[%s]" type="%s" value="%s" %s/>',
			$args['id'],
			$args['class'],
			$args['name'],
			$args['option'],
			$args['type'],
			$args['value'],
			( ! empty( $args['disabled'] ) ) ? ' disabled' : ''
		);
		if ( isset( $args['append'] ) ) {
			echo ' ' . $args['append'];
		}
		if ( isset( $args['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				$args['description']
			);
		}
	}

	/**
	 * Render a checkbox.
	 *
	 * @param array $args
	 */
	static function renderCheckbox( $args ) {
		$defaults = [
			'id' => null,
			'name' => null,
			'option' => null,
			'value' => '',
			'label' => null,
			'disabled' => false,
			'description' => null,
		];

		$args = wp_parse_args( $args, $defaults );

		printf(
			'<input id="%s" name="%s[%s]" type="checkbox" value="1" %s%s/><label for="%s">%s</label>',
			$args['id'],
			$args['name'],
			$args['option'],
			checked( 1, $args['value'], false ),
			( ! empty( $args['disabled'] ) ) ? ' disabled' : '',
			$args['id'],
			$args['label']
		);
		if ( isset( $args['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				$args['description']
			);
		}
	}

	/**
	 * Render radio buttons.
	 *
	 * @param array $args
	 */
	static function renderRadioButtons( $args ) {
		$defaults = [
			'id' => null,
			'name' => null,
			'option' => null,
			'value' => '',
			'choices' => [],
			'custom' => false,
			'disabled' => false,
		];

		$args = wp_parse_args( $args, $defaults );

		$is_custom = false;
		if ( ! array_key_exists( $args['value'], $args['choices'] ) ) {
			$is_custom = true;
		}
		foreach ( $args['choices'] as $key => $label ) {
			printf(
				'<label for="%s"><input type="radio" id="%s" name="%s[%s]" value="%s" %s%s/>%s</label><br />',
				$args['id'] . '_' . sanitize_key( $key ),
				$args['id'] . '_' . sanitize_key( $key ),
				$args['name'],
				$args['option'],
				$key,
				( $args['custom'] && $is_custom && empty( $key ) ) ? 'checked' : checked( $key, $args['value'], false ),
				( ! empty( $args['disabled'] ) ) ? ' disabled' : '',
				$label
			);
		}
	}

	/**
	 * Render a select element.
	 *
	 * @param array $args
	 */
	static function renderSelect( $args ) {
		$defaults = [
			'id' => null,
			'name' => null,
			'option' => null,
			'value' => '',
			'choices' => [],
			'multiple' => false,
			'disabled' => false,
		];

		$args = wp_parse_args( $args, $defaults );

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
			'<select name="%s[%s]" id="%s" %s%s>%s</select>',
			$args['name'],
			$args['option'],
			$args['id'],
			( $args['multiple'] ) ? ' multiple' : '',
			( ! empty( $args['disabled'] ) ) ? ' disabled' : '',
			$options
		);
	}

	/**
	 * Render a custom select element.
	 *
	 * @param array $args
	 */
	static function renderCustomSelect( $args ) {
		$defaults = [
			'id' => null,
			'name' => null,
			'value' => '',
			'choices' => [],
			'multiple' => false,
			'disabled' => false,
		];

		$args = wp_parse_args( $args, $defaults );

		$is_custom = false;
		if ( ! array_key_exists( $args['value'], $args['choices'] ) ) {
			$is_custom = true;
		}
		$options = '';
		foreach ( $args['choices'] as $key => $label ) {
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$key,
				( empty( $key ) && $is_custom ) ? ' selected' : selected( $key, $args['value'], false ),
				$label
			);
		}
		printf(
			'<select name="%s" id="%s" %s%s>%s</select><br />',
			$args['name'],
			$args['id'],
			( $args['multiple'] ) ? ' multiple' : '',
			( ! empty( $args['disabled'] ) ) ? ' disabled' : '',
			$options
		);
	}
}
