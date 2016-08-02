<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

abstract class Options {

	abstract function init();

	abstract function display();

	abstract protected function getSlug();

	abstract protected function getTitle();

	abstract static function getDefaults();

	abstract static function getBooleanOptions();

	abstract static function getStringOptions();

	abstract static function getIntegerOptions();

	abstract static function getFloatOptions();

	abstract static function getPredefinedOptions();

	function sanitize( $input ) {
		if ( !is_array( $input ) ) {
			$input = array();
		}

		foreach ( $this->booleans as $key ) {
			if ( ! isset( $input[ $key ] ) || $input[ $key ] !== '1' ) {
				$this->options[$key] = 0;
			} else {
				$this->options[$key] = 1;
			}
		}

		foreach ( $this->strings as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $this->options[ $key ] );
			} else {
				$this->options[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		foreach ( $this->integers as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $this->options[ $key ] );
			} else {
				$this->options[ $key ] = absint( $input[ $key ] );
			}
		}

		foreach ( $this->floats as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $this->options[ $key ] );
			} else {
				$this->options[ $key ] = filter_var( $input[ $key ], FILTER_VALIDATE_FLOAT );
			}
		}

		foreach ( $this->predefined as $key ) {
			if ( empty( $input[ $key ] ) ) {
				unset( $this->options[ $key ] );
			} else {
				$this->options[ $key ] = $input[ $key ];
			}
		}

		return $this->options;
	}

	/**
   * Render a form field.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $description
   * @param string $type
	 * @param string $append
   */
  protected function renderField($id, $name, $option, $value = '', $description = '', $append = '', $type = 'text', $size = '3') { ?>
  	<input id="<?= $id; ?>" name="<?= $name; ?>[<?= $option; ?>]" type="<?= $type; ?>" value="<?= $value; ?>" size="<?= $size; ?>" /><?php if ( $append ) : ?> <?= $append; ?><?php endif; ?>
		<?php if ( $description ) : ?><p class="description"><?= $description; ?><?php endif; ?>
  <?php }

	/**
   * Render a form checkbox.
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
   * Render a form checkbox.
   *
   * @param string $id
   * @param string $name
	 * @param string $option
   * @param string $value
	 * @param string $args
   */

	protected function renderRadioButtons($id, $name, $option, $value = '', $args) {
		foreach ( $args as $key => $label ) { ?>
			<label for="<?= $id . '_' . $key; ?>">
				<input type="radio" id="<?= $id . '_' . $key; ?>" name="<?= $name; ?>[<?= $option; ?>]" value="<?= $key; ?>" <?php checked( $key, $value ); ?>/><?= $label; ?>
			</label><br />
		<?php }
	}

	protected function renderSelect($id, $name, $option, $value = '', $args, $multiple) { ?>
		<select name='<?= $name; ?>[<?= $option; ?>]' id='<?= $id; ?>' >";
		<?php foreach ( $args as $key => $label ) { ?>
			<option value='<?= $key; ?>' <?php selected( $key, $value ); ?>><?= $label; ?></option>
		<?php } ?>
		<select>
	<?php }
}
