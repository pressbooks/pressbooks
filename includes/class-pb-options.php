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
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	abstract static function getBooleanOptions();

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	abstract static function getStringOptions();

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	abstract static function getIntegerOptions();

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	abstract static function getFloatOptions();

	/**
	 * Get an array of options which return predefined values (e.g. selects)
	 *
	 * @return array $options
	 */
	abstract static function getPredefinedOptions();

	/**
   * Sanitize various options (boolean, string, integer, float).
   *
   * @param array $input
	 * @return array $options
   */
	function sanitize( $input ) {
		$options = array();

		if ( !is_array( $input ) ) {
			$input = array();
		}

		foreach ( $this->booleans as $key ) {
			if ( ! isset( $input[ $key ] ) || @$input[ $key ] != 1 ) {
				$options[$key] = 0;
			} else {
				$options[$key] = 1;
			}
		}

		foreach ( $this->strings as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $options[ $key ] );
			} else {
				$options[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		foreach ( $this->integers as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $options[ $key ] );
			} else {
				$options[ $key ] = absint( $input[ $key ] );
			}
		}

		foreach ( $this->floats as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $options[ $key ] );
			} else {
				$options[ $key ] = filter_var( $input[ $key ], FILTER_VALIDATE_FLOAT );
			}
		}

		foreach ( $this->predefined as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $options[ $key ] );
			} else {
				$options[ $key ] = $input[ $key ];
			}
		}

		return $options;
	}

	/**
   * Render an input.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $description
	 * @param string $append
	 * @param string $type
	 * @param string $size
	 * @param bool $disabled
   */
  protected function renderField($id, $name, $option, $value = '', $description = '', $append = '', $type = 'text', $class = 'regular-text', $disabled = false) { ?>
  	<input id="<?= $id; ?>" class="<?= $class; ?>" name="<?= $name; ?>[<?= $option; ?>]" type="<?= $type; ?>" value="<?= $value; ?>" <?php if ( $disabled ) : ?> disabled<?php endif; ?>/><?php if ( $append ) : ?> <?= $append; ?><?php endif; ?>
		<?php if ( $description ) : ?><p class="description"><?= $description; ?><?php endif; ?>
  <?php }

	/**
   * Render a checkbox.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $description
   */
	protected function renderCheckbox($id, $name, $option, $value = '', $description) { ?>
		<input id="<?= $id; ?>" name="<?= $name; ?>[<?= $option; ?>]" type="checkbox" value="1" <?= checked( 1, $value, false ); ?>/>
		<label for="<?= $id; ?>"><?= $description; ?></label>
  <?php }

	/**
   * Render radio buttons.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $args
	 * @param bool $custom
   */
	protected function renderRadioButtons($id, $name, $option, $value = '', $args, $custom = false) {
		$is_custom = false;
		if ( !array_key_exists( $value, $args ) ) {
			$is_custom = true;
		}
		foreach ( $args as $key => $label ) { ?>
			<label for="<?= $id . '_' . sanitize_key( $key ); ?>">
				<input type="radio" id="<?= $id . '_' . sanitize_key( $key ); ?>" name="<?= $name; ?>[<?= $option; ?>]" value="<?= $key; ?>" <?php if ( $custom && $is_custom ) {
					if ( $key == '' ) {
						echo('checked');
					}
				} else {
					checked( $key, $value );
				} ?>/><?= $label; ?>
			</label><br />
		<?php }
	}

	/**
   * Render a select element.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $args
	 * @param boolean $multiple
   */
	protected function renderSelect($id, $name, $option, $value = '', $args, $multiple = false) { ?>
		<select name='<?= $name; ?>[<?= $option; ?>]' id='<?= $id; ?>'<?php if ( $multiple ) : ?>multiple<?php endif; ?>>
		<?php foreach ( $args as $key => $label ) { ?>
			<option value='<?= $key; ?>' <?php selected( $key, $value ); ?>><?= $label; ?></option>
		<?php } ?>
		</select>
	<?php }

	/**
	 * Render a custom select element.
	 *
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param string $args
	 * @param boolean $multiple
	 */
	protected function renderCustomSelect($id, $name, $value = '', $args, $multiple = false) {
		$is_custom = false;
		if ( !array_key_exists( $value, $args ) ) {
			$is_custom = true;
		} ?>
		<select name='<?= $name; ?>' id='<?= $id; ?>'>
		<?php foreach ( $args as $key => $label ) { ?>
			<option value='<?= $key; ?>' <?php if ( $key == '' && $is_custom ) { echo 'selected'; } else { selected( $key, $value ); } ?>><?= $label; ?></option>
		<?php } ?>
		</select><br />
	<?php }
}
