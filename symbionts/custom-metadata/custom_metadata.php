<?php
/*
Plugin Name: Custom Metadata Manager
Plugin URI: http://wordpress.org/extend/plugins/custom-metadata/
Description: An easy way to add custom fields to your object types (post, pages, custom post types, users)
Author: Automattic, Stresslimit & Contributors
Version: 0.8-dev
Author URI: https://github.com/Automattic/custom-metadata/

Copyright 2010-2013 The Contributors

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * set this to true in your wp-config.php file to enable debug/test mode
 */
if ( ! defined( 'CUSTOM_METADATA_MANAGER_DEBUG' ) )
	define( 'CUSTOM_METADATA_MANAGER_DEBUG', false );

if ( CUSTOM_METADATA_MANAGER_DEBUG )
	include_once 'custom_metadata_examples.php';

class custom_metadata_manager {

	var $errors = array();

	var $metadata = array();

	var $_non_post_types = array( 'user', 'comment' );

	// Object types that come "built-in" with WordPress
	var $_builtin_object_types = array( 'post', 'page', 'user', 'comment' );

	// Column filter names
	var $_column_types = array( 'posts', 'pages', 'users', 'comments' );

	// field types
	var $_field_types = array( 'text', 'textarea', 'password', 'number', 'email', 'telephone', 'checkbox', 'radio', 'select', 'multi_select', 'upload', 'wysiwyg', 'datepicker', 'datetimepicker', 'timepicker', 'colorpicker', 'taxonomy_select', 'taxonomy_radio',  'taxonomy_checkbox', 'link' );

	// field types that are cloneable
	var $_cloneable_field_types = array( 'text', 'textarea', 'upload', 'password', 'number', 'email', 'tel' );

	// field types that support a default value
	var $_field_types_that_support_default_value = array( 'text', 'textarea', 'password', 'number', 'email', 'telephone', 'upload', 'wysiwyg', 'datepicker', 'datetimepicker', 'timepicker', 'link' );

	// field types that support the placeholder attribute
	var $_field_types_that_support_placeholder = array( 'text', 'textarea', 'password', 'number', 'email', 'tel', 'upload', 'datepicker', 'datetimepicker', 'timepicker', 'link' );

	// field types that are read only by default
	var $_field_types_that_are_read_only = array( 'upload', 'link', 'datepicker', 'datetimepicker', 'timepicker' );

	// field types that support being part of a multifield group
	// @todo: workarounds needed for other field types
	var $_field_types_that_support_multifield = array( 'text', 'textarea', 'password', 'number', 'email', 'tel', 'select' );

	// taxonomy types
	var $_taxonomy_fields = array( 'taxonomy_select', 'taxonomy_radio', 'taxonomy_checkbox', 'taxonomy_multi_select' );

	// filed types that are saved as multiples but not cloneable
	var $_multiple_not_cloneable = array( 'taxonomy_checkbox' );

	// fields that always save as an array
	var $_always_multiple_fields = array( 'taxonomy_checkbox', 'multi_select', 'taxonomy_multi_select' );

	// Object types whose columns are generated through apply_filters instead of do_action
	var $_column_filter_object_types = array( 'user' );

	// Whitelisted pages that get stylesheets and scripts
	var $_pages_whitelist = array( 'edit.php', 'post.php', 'post-new.php', 'users.php', 'profile.php', 'user-edit.php', 'edit-comments.php', 'comment.php' );

	// the default args used for the wp_editor function
	var $default_editor_args = array();

	// singleton instance
	private static $instance;

	public static function instance() {
		if ( isset( self::$instance ) )
			return self::$instance;

		self::$instance = new custom_metadata_manager;
		self::$instance->run_initial_hooks();
		return self::$instance;
	}

	// do nothing on construct
	function __construct() {}

	function run_initial_hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ), 1000, 0 );
	}

	function admin_init() {
		global $pagenow;

		// filter our vars
		$this->_non_post_types = apply_filters( 'custom_metadata_manager_non_post_types', $this->_non_post_types );
		$this->_builtin_object_types = apply_filters( 'custom_metadata_manager_builtin_object_types', $this->_builtin_object_types );
		$this->_column_types = apply_filters( 'custom_metadata_manager_column_types', $this->_column_types );
		$this->_field_types = apply_filters( 'custom_metadata_manager_field_types', $this->_field_types );
		$this->_cloneable_field_types = apply_filters( 'custom_metadata_manager_cloneable_field_types', $this->_cloneable_field_types );
		$this->_field_types_that_support_default_value = apply_filters( 'custom_metadata_manager_field_types_that_support_default_value', $this->_field_types_that_support_default_value );
		$this->_field_types_that_support_placeholder = apply_filters( 'custom_metadata_manager_field_types_that_support_placeholder', $this->_field_types_that_support_placeholder );
		$this->_field_types_that_are_read_only = apply_filters( 'custom_metadata_manager_field_types_that_are_read_only', $this->_field_types_that_are_read_only );
		$this->_field_types_that_support_multifield = apply_filters( 'custom_metadata_manager_field_types_that_support_multifield', $this->_field_types_that_support_multifield );
		$this->_taxonomy_fields = apply_filters( 'custom_metadata_manager_cloneable_field_types', $this->_taxonomy_fields );
		$this->_column_filter_object_types = apply_filters( 'custom_metadata_manager_column_filter_object_types', $this->_column_filter_object_types );
		$this->_pages_whitelist = apply_filters( 'custom_metadata_manager_pages_whitelist', $this->_pages_whitelist );
		$this->default_editor_args = apply_filters( 'custom_metadata_manager_default_editor_args', $this->default_editor_args );

		define( 'CUSTOM_METADATA_MANAGER_SELECT2_VERSION', '3.2' ); // version for included select2.js
		define( 'CUSTOM_METADATA_MANAGER_TIMEPICKER_VERSION', '1.2' ); // version for included timepicker
		define( 'CUSTOM_METADATA_MANAGER_VERSION', '0.8-dev' );
		define( 'CUSTOM_METADATA_MANAGER_URL' , apply_filters( 'custom_metadata_manager_url', trailingslashit( plugins_url( '', __FILE__ ) ) ) );

		$this->init_object_types();

		// Hook into load to initialize custom columns
		if ( in_array( $pagenow, $this->_pages_whitelist ) ) {
			add_action( 'load-' . $pagenow, array( $this, 'init_metadata' ) );
		}

		// Hook into admin_notices to show errors
		if ( current_user_can( 'manage_options' ) )
			add_action( 'admin_notices', array( $this, '_display_registration_errors' ) );

		do_action( 'custom_metadata_manager_init' );
		do_action( 'custom_metadata_manager_admin_init' );
	}

	function init_object_types() {
		foreach ( array_merge( get_post_types(), $this->_builtin_object_types ) as $object_type )
			$this->metadata[$object_type] = array();
	}

	function init_metadata() {
		$object_type = $this->_get_object_type_context();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		$this->init_columns();

		// Handle actions related to users
		if ( $object_type == 'user' ) {
			global $user_id;

			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			// Editing another user's profile
			add_action( 'edit_user_profile', array( $this, 'add_user_metadata_groups' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_user_metadata' ) );
			// Allow user-editable fields on "Your Profile"
			add_action( 'show_user_profile', array( $this, 'add_user_metadata_groups' ) );
			add_action( 'personal_options_update', array( $this, 'save_user_metadata' ) );

		} else {

			// Hook in to metaboxes
			add_action( 'add_meta_boxes', array( $this, "add_post_metadata_groups" ) );

			// Hook in to save
			add_action( 'save_post', array( $this, 'save_post_metadata' ) );
			add_action( 'edit_comment', array( $this, 'save_comment_metadata' ) );
		}

		do_action( 'custom_metadata_manager_init_metadata', $object_type );

		add_action( 'admin_footer', array( $this, '_display_wp_link_dialog' ) );
	}

	function init_columns() {

		$object_type = $this->_get_object_type_context();

		// This is not really that clean, but it works. Damn inconsistencies!
		if ( post_type_exists( $object_type ) ) {
			$column_header_name = sprintf( '%s_posts', $object_type );
			$column_content_name = ( 'page' != $object_type ) ? 'posts' : 'pages';
		} elseif ( $object_type == 'comment' ) {
			$column_header_name = 'edit-comments';
			$column_content_name = 'comments';
		} else {
			// users
			$column_header_name = $column_content_name = $object_type . 's';
		}

		// Hook into Column Headers
		add_filter( "manage_{$column_header_name}_columns", array( $this, 'add_metadata_column_headers' ) );

		// User and Posts have different functions
		$custom_column_content_function = array( $this, "add_{$object_type}_metadata_column_content" );
		if ( ! is_callable( $custom_column_content_function ) )
			$custom_column_content_function = array( $this, 'add_metadata_column_content' );

		// Hook into Column Content. Users get filtered, others get actioned.
		if ( ! in_array( $object_type, $this->_column_filter_object_types ) )
			add_action( "manage_{$column_content_name}_custom_column", $custom_column_content_function, 10, 3 );
		else
			add_filter( "manage_{$column_content_name}_custom_column", $custom_column_content_function, 10, 3 );

	}

	function enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script( 'wplink' );
		wp_enqueue_script( 'wpdialogs-popup' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'select2', apply_filters( 'custom_metadata_manager_select2_js', CUSTOM_METADATA_MANAGER_URL .'js/select2.min.js' ), array( 'jquery' ), CUSTOM_METADATA_MANAGER_SELECT2_VERSION, true );
		wp_enqueue_script( 'timepicker', apply_filters( 'custom_metadata_manager_timepicker_js', CUSTOM_METADATA_MANAGER_URL .'js/jquery-ui-timepicker.min.js' ), array( 'jquery', 'jquery-ui-datepicker' ), CUSTOM_METADATA_MANAGER_TIMEPICKER_VERSION, true );
		wp_enqueue_script( 'custom-metadata-manager-js', apply_filters( 'custom_metadata_manager_default_js', CUSTOM_METADATA_MANAGER_URL .'js/custom-metadata-manager.js' ), array( 'jquery', 'jquery-ui-datepicker', 'select2' ), CUSTOM_METADATA_MANAGER_VERSION, true );
		wp_enqueue_script( 'wp-color-picker' );
	}

	function enqueue_styles() {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style( 'editor-buttons' );
		wp_enqueue_style( 'custom-metadata-manager-css', apply_filters( 'custom_metadata_manager_default_css', CUSTOM_METADATA_MANAGER_URL .'css/custom-metadata-manager.css' ), array(), CUSTOM_METADATA_MANAGER_VERSION );
		wp_enqueue_style( 'jquery-ui-datepicker', apply_filters( 'custom_metadata_manager_jquery_ui_css', CUSTOM_METADATA_MANAGER_URL .'css/jquery-ui-smoothness.css' ), array(), CUSTOM_METADATA_MANAGER_VERSION );
		wp_enqueue_style( 'select2', apply_filters( 'custom_metadata_manager_select2_css', CUSTOM_METADATA_MANAGER_URL .'css/select2.css' ), array(), CUSTOM_METADATA_MANAGER_SELECT2_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
	}

	function add_metadata_column_headers( $columns ) {

		$object_type = $this->_get_object_type_context();

		if ( $object_type ) {
			$fields = $this->get_fields_in_object_type( $object_type );

			foreach ( $fields as $field_slug => $field ) {
				if ( $this->is_field_addable_to_columns( $field_slug, $field ) ) {
					$columns[$field_slug] = is_string( $field->display_column ) ? $field->display_column : $field->label;
				}
			}
		}
		return $columns;
	}

	function add_user_metadata_column_content( $param, $name, $object_id ) {
		return $this->add_metadata_column_content( $name, $object_id, $param );
	}

	function add_metadata_column_content( $name, $object_id, $column_content = '' ) {

		$object_type = $this->_get_object_type_context();
		$field_slug = $name;

		if ( $this->is_registered_object_type( $object_type ) && $this->is_registered_field( $field_slug, null, $object_type ) ) {
			$field = $this->get_field( $field_slug, null, $object_type );
			$column_content = $this->_metadata_column_content( $field_slug, $field, $object_type, $object_id );
		}

		if ( $column_content && ! in_array( $object_type, $this->_column_filter_object_types ) )
			echo $column_content;
		else
			return $column_content;
	}

	function add_metadata_field( $field_slug, $object_types = array( 'post' ), $args = array() ) {
		static $localized_strings;

		if ( ! $localized_strings ) {
			$localized_strings = (object) array(
				'upload_modal_title' => __( 'Choose a file', 'custom-metadata' ), // upload modal title (for upload field only)
				'upload_modal_button_text' => __( 'Select this file', 'custom-metadata' ), // upload modal button text (for upload field only)
				'upload_clear_button_text' => __( 'Clear', 'custom-metadata' ), // upload clear field text (for upload field only)
				'link_modal_button_text' => __( 'Select', 'custom-metadata' ), // link field button text
			);
		}

		$defaults = array(
			'group' => '', // To which meta_box the field should be added
			'multifield' => false, // which multifield does this field belong to, if any
			'field_type' => 'text', // The type of field; possibly values: text, checkbox, radio, select, image
			'label' => $field_slug, // Label for the field
			'slug' => $field_slug, // Slug for the field
			'description' => '', // Description of the field, displayed below the input
			'values' => array(), // values for select, checkbox, radio buttons
			'default_value' => '', // default value
			'placeholder' => '',
			'display_callback' => '', // function to custom render the input
			'sanitize_callback' => '',
			'display_column' => false, // Add the field to the columns when viewing all posts
			'display_column_callback' => '',
			'add_to_quick_edit' => false, // (post only) Add the field to Quick edit
			'required_cap' => false, // the cap required to view and edit the field
			'multiple' => false, // can the field be duplicated with a click of a button
			'readonly' => false, // makes the field be readonly
			'select2' => false, // applies select2.js (work on select and multi select field types)
			'min' => false, // a minimum value (for number field only)
			'max' => false, // a maximum value (for number field only)
			'upload_modal_title' => $localized_strings->upload_modal_title,
			'upload_modal_button_text' => $localized_strings->upload_modal_button_text,
			'upload_clear_button_text' => $localized_strings->upload_clear_button_text,
			'link_modal_button_text' => $localized_strings->link_modal_button_text,
		);

		// upload field is readonly by default (can be set explicitly to false though)
		if ( ! empty( $args['field_type'] ) && in_array( $args['field_type'], $this->_field_types_that_are_read_only ) )
			$defaults['readonly'] = true;

		// `chosen` arg is the same as `select2` arg
		if ( isset( $args['chosen'] ) ) {
			$args['select2'] = $args['chosen'];
			unset( $args['chosen'] );
		}

		// Merge defaults with args
		$field = wp_parse_args( $args, $defaults );
		$field = (object) $field;

		// Sanitize slug
		$field_slug = sanitize_key( $field_slug );
		$group_slug = sanitize_key( $field->group );

		// Check to see if the user should see this field
		if ( ! empty( $field->required_cap ) && ! current_user_can( $field->required_cap ) )
			return;

		$field = apply_filters( 'custom_metadata_manager_add_metadata_field', $field, $field_slug, $group_slug, $object_types );

		if ( ! $this->_validate_metadata_field( $field_slug, $field, $group_slug, $object_types ) )
			return;

//		$object_types = (array) $object_type;
//		if ( $field->multifield && $this->_multifield_exists_for_group_object( $field->multifield, $group_slug, array_shift( $object_types ) ) ) {
//			$this->add_field_to_multifield( $field_slug, $field, $group_slug, $object_types );
//		} else {
			// add to group
			$this->add_field_to_group( $field_slug, $field, $group_slug, $object_types );
//		}

	}

	function add_multifield( $slug, $object_types = array( 'post' ), $args = array() ) {

		$defaults = array(
			'group' => '', // To which meta_box the multifield should be added
			'label' => $slug, // Label for the multifield
			'description' => '', // Description of the multifield, displayed below all the fields
			'required_cap' => false, // the cap required to view and edit the multifield
		);

		// Merge defaults with args
		$multifield = wp_parse_args( $args, $defaults );
		$multifield['multifield'] = true; // force it
		$multifield = (object) $multifield;

		// Sanitize slug
		$slug = sanitize_key( $slug );
		$group_slug = sanitize_key( $multifield->group );

		// Check to see if the user should see this field
		if ( ! empty( $multifield->required_cap ) && ! current_user_can( $multifield->required_cap ) )
			return;

		$multifield = apply_filters( 'custom_metadata_manager_add_multifield', $multifield, $slug, $group_slug, $object_types );

		if ( ! $this->_validate_metadata_field( $slug, $multifield, $group_slug, $object_types ) )
			return;

		// Add to group
		$this->add_multifield_to_group( $slug, $multifield, $group_slug, $object_types );

	}

	function add_metadata_group( $group_slug, $object_types, $args = array() ) {

		$defaults = array(
			'label' => $group_slug, // Label for the group
			'description' => '', // Description of the group
			'context' => 'normal', // (post only)
			'priority' => 'default', // (post only)
			'autosave' => false, // (post only) Should the group be saved in autosave?
			'required_cap' => false, // the cap required to view and edit the group
		);

		// Merge defaults with args
		$group = wp_parse_args( $args, $defaults );
		$group = (object) $group;

		// Sanitize slug
		$group_slug = sanitize_key( $group_slug );

		$group = apply_filters( 'custom_metadata_manager_add_metadata_group', $group, $group_slug, $object_types );

		// Check to see if the user has caps to view/edit this group
		if ( ! empty( $group->required_cap ) && ! current_user_can( $group->required_cap ) )
			return;

		if ( !$this->_validate_metadata_group( $group_slug, $group, $object_types ) )
			return;

		$this->add_group_to_object_type( $group_slug, $group, $object_types );
	}


	function add_field_to_group( $field_slug, $field, $group_slug, $object_types ) {
		$object_types = (array) $object_types;

		foreach ( $object_types as $object_type ) {
			if ( ! $group_slug ) {
				$group_slug = sprintf( 'single-group-%1$s-%2$s', $object_type, $field_slug );
			}

			// If group doesn't exist, create group
			if ( ! $this->is_registered_group( $group_slug, $object_type ) ) {
				$this->add_metadata_group( $group_slug, $object_type, array( 'label' => ( ! empty( $field->label ) ) ? $field->label : $field_slug ) );
				$field->group = $group_slug;
			}

			$this->_push_field( $field_slug, $field, $group_slug, $object_type );
		}
	}

	function add_multifield_to_group( $slug, $multifield, $group_slug, $object_types ) {
		$object_types = (array) $object_types;

		foreach ( $object_types as $object_type ) {
			if ( ! $group_slug ) {
				$group_slug = sprintf( 'single-group-%1$s-%2$s', $object_type, $slug );
			}

			// If group doesn't exist, create group
			if ( ! $this->is_registered_group( $group_slug, $object_type ) ) {
				$this->add_metadata_group( $group_slug, $object_type, array( 'label' => ( ! empty( $multifield->label ) ) ? $multifield->label : $slug ) );
				$multifield->group = $group_slug;
			}

			$this->_push_multifield( $slug, $multifield, $group_slug, $object_type );
		}
	}

	function add_group_to_object_type( $group_slug, $group, $object_types ) {
		$object_types = (array) $object_types;

		foreach ( $object_types as $object_type ) {
			if ( ( $this->is_registered_object_type( $object_type ) && ! $this->is_group_in_object_type( $group_slug, $object_type ) ) ) {
				$group->fields = array();
				$this->_push_group( $group_slug, $group, $object_type );
			}
		}
	}

	function _validate_metadata_group( $group_slug, $group, $object_type ) {
		$valid = true;

		// TODO: only validate when DEBUG is on?
		/*
		if( ! $group_slug ) {

		} elseif ( $this->is_registered_group( $group_slug, $object_type ) ) {
			// TODO: check that it hasn't been registered already
		} elseif ( $this->is_restricted_group( $group_slug, $object_type ) ) {
			// TODO: check that it isn't restricted
		}
		*/
		return $valid;
	}

	function _validate_metadata_field( $field_slug, $field, $group_slug, $object_types ) {

		// TODO: only validate when DEBUG is on?

		$valid = true;
		/*
		if( !$field_slug ) {
			// Check that
			$this->_add_registration_error( $field_slug, __( 'You entered an empty slug name for this field!', 'custom-metadata-manager' ) );
			$valid = false;
		} else if( $this->is_registered_field( $field_slug, $group_slug, $object_type ) ) {
			// does field name already exists
			$this->_add_registration_error( $field_slug, __( 'This field already exists. Check to see that you\'re not registering the field twice, or use a different slug.', 'custom-metadata-manager' ) );
			$valid = false;
		} else if( $this->is_restricted_field( $field_slug, $object_type ) ) {
			// is field restricted
			$this->_add_registration_error( $field_slug, __( 'This field is restricted. Please use a different slug.', 'custom-metadata-manager' ) );
			$valid = false;
		}
		// if display_callback not defined
				// show admin_notices error
				// show as text field (?)

		*/
		// TODO: valid object_type?

		return $valid;
	}

	function _add_registration_error( $field_slug, $error_message ) {
		$this->errors[] = sprintf( __( '<strong>%1$s:</strong> %2$s', 'custom-metadata-manager' ), $field_slug, $error_message );
	}

	function add_post_metadata_groups() {
		global $post, $comment;

		$object_id = 0;

		if ( isset( $post ) ) {
			$object_id = $post->ID;
		} elseif ( isset( $comment ) ) {
			$object_id = $comment->comment_ID;
		}
		$object_type = $this->_get_object_type_context();

		$groups = $this->get_groups_in_object_type( $object_type );

		if ( $object_id && !empty( $groups ) ) {
			foreach ( $groups as $group_slug => $group ) {
				$this->add_post_metadata_group( $group_slug, $group, $object_type, $object_id );
			}
		}
	}

	function add_post_metadata_group( $group_slug, $group, $object_type, $object_id ) {

		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		if ( ! empty( $fields ) && $this->is_thing_added_to_object( $group_slug, $group, $object_type, $object_id ) ) {
			add_meta_box( $group_slug, $group->label, array( $this, '_display_post_metadata_box' ), $object_type, $group->context, $group->priority, array( 'group' => $group, 'fields' => $fields ) );
		}
	}

	function add_user_metadata_groups() {
		global $user_id;

		if ( !$user_id ) return;

		$object_type = 'user';

		$groups = $this->get_groups_in_object_type( $object_type );

		if ( !empty( $groups ) ) {
			foreach ( $groups as $group_slug => $group ) {
				$this->add_user_metadata_group( $group_slug, $group, $object_type, $user_id );
			}
		}
	}

	function add_user_metadata_group( $group_slug, $group, $object_type, $user_id ) {
		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		if ( ! empty( $fields ) && $this->is_thing_added_to_object( $group_slug, $group, $object_type, $user_id ) )
			$this->_display_user_metadata_box( $group_slug, $group, $object_type, $fields );
	}


	function _display_user_metadata_box( $group_slug, $group, $object_type, $fields ) {
		global $user_id;
?>
		<h3><?php echo $group->label; ?></h3>

		<table class="form-table user-metadata-group">
			<?php foreach ( $fields as $field_slug => $field ) : ?>
				<?php if ( $this->is_thing_added_to_object( $field_slug, $field, $object_type, $user_id ) ) : ?>
					<tr valign="top">
						<td scope="row">
							<?php $this->_display_metadata_field( $field_slug, $field, $object_type, $user_id ); ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</table>
		<?php

		$this->_display_group_nonce( $group_slug, $object_type );
	}

	function _display_post_metadata_box( $object, $meta_box ) {

		$group_slug = $meta_box['id'];
		$group = $meta_box['args']['group'];
		$fields = $meta_box['args']['fields'];
		$object_type = $this->_get_object_type_context();

		// I really don't like using variable variables, but this is the path of least resistence.
		if ( isset( $object->{$object_type . '_ID'} ) ) {
			$object_id =  $object->{$object_type . '_ID'};
		} elseif ( isset( $object->ID ) ) {
			$object_id = $object->ID;
		} else {
			_e( 'Uh oh, something went wrong!', 'custom-metadata-manager' );
			return;
		}

		$this->_display_group_description( $group );

		foreach ( $fields as $field_slug => $field ) {
			if ( $this->is_thing_added_to_object( $field_slug, $field, $object_type, $object_id ) ) {

				if ( $this->_is_multifield( $field_slug ) ) {
					$this->_display_metadata_multifield( $field_slug, $field, $object_type, $object_id );
				} elseif ( empty( $field->multifield ) ) {
					$this->_display_metadata_field( $field_slug, $field, $object_type, $object_id );
				}
			}
		}

		// Each group gets its own nonce
		$this->_display_group_nonce( $group_slug, $object_type );
	}

	function _display_group_description( $group ) {
		if ( ! empty( $group->description ) )
			printf( '<div class="custom-metadata-group-description description">%s</div>', $group->description );
	}

	function _display_group_nonce( $group_slug, $object_type ) {
		$nonce_key = $this->build_nonce_key( $group_slug, $object_type );
		wp_nonce_field( 'save-metadata', $nonce_key, false );
	}

	function verify_group_nonce( $group_slug, $object_type ) {
		$nonce_key = $this->build_nonce_key( $group_slug, $object_type );
		if ( isset( $_POST[$nonce_key] ) )
			return wp_verify_nonce( $_POST[$nonce_key], 'save-metadata' );
		else
			return false;
	}

	function build_nonce_key( $group_slug, $object_type ) {
		return sprintf( 'metadata-%1$s-%2$s', $object_type, $group_slug );
	}

	function save_user_metadata( $user_id ) {
		$object_type = 'user';
		$groups = $this->get_groups_in_object_type( $object_type );

		foreach ( $groups as $group_slug => $group ) {
			$this->save_metadata_group( $group_slug, $group, $object_type, $user_id );
		}
	}

	function save_post_metadata( $post_id ) {
		$post_type = $this->_get_object_type_context();
		$groups = $this->get_groups_in_object_type( $post_type );

		foreach ( $groups as $group_slug => $group ) {
			// TODO: Allow hook into autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE && !$group->autosave )
				return $post_id;

			$this->save_metadata_group( $group_slug, $group, $post_type, $post_id );
		}
	}

	function save_comment_metadata( $comment_id ) {
		$object_type = 'comment';
		$groups = $this->get_groups_in_object_type( $object_type );

		foreach ( $groups as $group_slug => $group ) {
			$this->save_metadata_group( $group_slug, $group, $object_type, $comment_id );
		}
	}

	function save_metadata_group( $group_slug, $group, $object_type, $object_id ) {
		if ( !$this->verify_group_nonce( $group_slug, $object_type ) ) {
			return $object_id;
		}

		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		foreach ( $fields as $field_slug => $field ) {
			if ( true === $field->multifield ) {
				$this->save_metadata_multifield( $field_slug, $field, $object_type, $object_id );
			} elseif ( ! $field->multifield ) {
				$this->save_metadata_field( $field_slug, $field, $object_type, $object_id );
			}
		}

	}

	function save_metadata_multifield( $slug, $multifield, $object_type, $object_id ) {

		if ( isset( $_POST[$slug] ) ) {
			$multifield_value = array();
			$groupings = $_POST[$slug];
			$fields = $this->get_fields_in_multifield( $multifield->group, $slug, $object_type );
			foreach ( $groupings as $grouping ) {
				$grouping_values = array();
				foreach ( $fields as $field_slug => $field ) {
					if ( ! empty( $grouping[$field_slug] ) ) {
						$grouping_values[$field_slug] = $this->_sanitize_field_value( $field_slug, $field, $object_type, $object_id, $grouping[$field_slug] );
					} else {
						$grouping_values[$field_slug] = '';
					}
				}
				$multifield_value[] = $grouping_values;
			}

			$slug = sanitize_key( $slug );

			if ( ! in_array( $object_type, $this->_non_post_types ) )
				$object_type = 'post';

			update_metadata( $object_type, $object_id, $slug, $multifield_value );
		} else {
			$slug = sanitize_key( $slug );

			if ( ! in_array( $object_type, $this->_non_post_types ) )
				$object_type = 'post';

			delete_metadata( $object_type, $object_id, $slug );
		}
	}

	function save_metadata_field( $field_slug, $field, $object_type, $object_id ) {
		if ( isset( $_POST[$field_slug] ) ) {
			$value = $this->_sanitize_field_value( $field_slug, $field, $object_type, $object_id, $_POST[$field_slug] );
			$this->_save_field_value( $field_slug, $field, $object_type, $object_id, $value );


			// save the attachment ID of the upload field as well
			if ( $field->field_type == 'upload' && isset( $_POST[$field_slug . '_attachment_id'] ) )
				$this->_save_field_value( $field_slug . '_attachment_id', $field, $object_type, $object_id, absint( $_POST[$field_slug . '_attachment_id'] ) );
		} else {
			$this->_delete_field_value( $field_slug, $field, $object_type, $object_id );

			// delete the attachment ID of the upload field as well
			if ( $field->field_type == 'upload' && isset( $_POST[$field_slug . '_attachment_id'] ) )
				$this->_delete_field_value( $field_slug . '_attachment_id', $field, $object_type, $object_id );
		}
	}

	function get_metadata_mulitifield_value( $slug, $multifield, $object_type, $object_id ) {
		return $this->_get_field_value( $slug, $multifield, $object_type, $object_id, true );
	}

	function get_metadata_field_value( $field_slug, $field, $object_type, $object_id ) {
		return $this->_get_field_value( $field_slug, $field, $object_type, $object_id );
	}

	function is_registered_object_type( $object_type ) {
		return array_key_exists( $object_type, $this->metadata ) /*&& is_array( $this->metadata[$object_type] )*/;
	}

	function is_registered_group( $group_slug, $object_type ) {
		return $this->is_registered_object_type( $object_type ) && array_key_exists( $group_slug, $this->get_groups_in_object_type( $object_type ) );
	}

	function is_registered_field( $field_slug, $group_slug = '', $object_type ) {
		if ( $group_slug )
			return $this->is_registered_group( $group_slug, $object_type ) && array_key_exists( $field_slug, $this->get_fields_in_group( $group_slug, $object_type ) );
		else
			return array_key_exists( $field_slug, $this->get_fields_in_object_type( $object_type ) );
	}

	function is_field_in_group( $field_slug, $group_slug, $object_type ) {
		return in_array( $field_slug, $this->get_fields_in_group( $group_slug, $object_type ) );
	}

	function is_group_in_object_type( $group_slug, $object_type ) {
		return array_key_exists( $group_slug, $this->get_groups_in_object_type( $object_type ) );
	}

	function is_field_addable_to_columns( $field_slug, $field ) {
		return is_string( $field->display_column ) || ( is_bool( $field->display_column ) && $field->display_column );
	}

	function get_field( $field_slug, $group_slug, $object_type ) {
		if ( $this->is_registered_field( $field_slug, $group_slug, $object_type ) ) {
			if ( $group_slug ) {
				return $this->get_single_field_in_group( $field_slug, $group_slug, $object_type );
			} else {
				return $this->get_single_field_in_object_type( $field_slug, $object_type );
			}
		}
		return null;
	}

	function get_group( $group_slug, $object_type ) {
		if ( $this->is_registered_group( $group_slug, $object_type ) ) {
			$groups = $this->get_groups_in_object_type( $object_type );
			$group = $groups[$group_slug];
			return $group;
		}
		return null;
	}

	function get_object_types() {
		return array_keys( $this->metadata );
	}

	function get_groups_in_object_type( $object_type ) {
		if ( $this->is_registered_object_type( $object_type ) )
			return $this->metadata[$object_type];
		return array();
	}

	function get_single_field_in_group( $field_slug, $group_slug, $object_type ) {
		$fields = $this->get_fields_in_group( $group_slug, $object_type );
		return isset( $fields[$field_slug] ) ? $fields[$field_slug] : null;
	}

	function get_fields_in_group( $group_slug, $object_type ) {
		$group = $this->get_group( $group_slug, $object_type );
		if ( $group ) return (array) $group->fields;
		return array();
	}

	function get_fields_in_multifield( $group_slug, $multifield_slug, $object_type ) {
		$group = $this->get_group( $group_slug, $object_type );
		$fields_in_multifield = array();
		if ( empty( $group ) || empty( $group->fields ) || empty( $group->fields[$multifield_slug] ) )
			return $fields_in_multifield;

		$_multifields = wp_list_pluck( $group->fields, 'multifield' );
		foreach ( $_multifields as $_field_key => $_multifield ) {
			if ( empty( $_multifield ) || true === $_multifield )
				continue;

			if ( $multifield_slug == $_multifield || $multifield_slug == '_x_multifield_' . $_multifield )
				$fields_in_multifield[$_field_key] = $group->fields[$_field_key];
		}

		return $fields_in_multifield;
	}

	function get_single_field_in_object_type( $field_slug, $object_type ) {
		$fields = $this->get_fields_in_object_type( $object_type );
		return isset( $fields[$field_slug] ) ? $fields[$field_slug] : null;
	}

	function get_fields_in_object_type( $object_type ) {
		$fields = array();
		foreach ( $this->get_groups_in_object_type( $object_type ) as $group_slug => $group ) {
			$fields = array_merge( $fields, $this->get_fields_in_group( $group_slug, $object_type ) );
		}
		return $fields;
	}

	function _push_group( $group_slug, $group, $object_type ) {
		$this->metadata[$object_type][$group_slug] = $group;
	}

	function _push_field( $field_slug, $field, $group_slug, $object_type ) {
		$this->metadata[$object_type][$group_slug]->fields[$field_slug] = $field;
	}

	function _push_multifield( $slug, $multifield, $group_slug, $object_type ) {
		$this->metadata[$object_type][$group_slug]->fields['_x_multifield_' . $slug] = $multifield;
	}

	function _multifield_exists_for_group_object( $slug, $group_slug, $object_type ) {
		$slug = '_x_multifield_' . $slug;
		return (
			! empty( $this->metadata[$object_type] ) &&
			! empty( $this->metadata[$object_type][$group_slug] ) &&
			! empty( $this->metadata[$object_type][$group_slug]->fields ) &&
			array_key_exists( $slug, $this->metadata[$object_type][$group_slug]->fields )
		);
	}

	function _is_multifield( $slug ) {
		return ( 0 === strpos( $slug, '_x_multifield' ) );
	}

	function is_thing_added_to_object( $thing_slug, $thing, $object_type, $object_id, $object_slug = '' ) {

		if ( isset( $thing->exclude ) ) {
			if ( is_callable( $thing->exclude ) )
				return ! (bool) call_user_func( $thing->exclude, $thing_slug, $thing, $object_type, $object_id, $object_slug );
			return ! $this->does_id_array_match_object( $thing->exclude, $object_type, $object_id, $object_slug );
		}

		if ( isset( $thing->include ) ) {
			if ( is_callable( $thing->include ) )
				return (bool) call_user_func( $thing->include, $thing_slug, $thing, $object_type, $object_id, $object_slug );
			return $this->does_id_array_match_object( $thing->include, $object_type,  $object_id, $object_slug );
		}

		return true;
	}

	function does_id_array_match_object( $id_array, $object_type, $object_id, $object_slug = '' ) {
		if ( is_array( $id_array ) ) {
			if ( isset( $id_array[$object_type] ) ) {
				if ( is_array( $id_array[$object_type] ) ) {
					// array( 'user' => array( 123, 'postname' ) )
					return $this->does_id_array_match_object( $id_array[$object_type], $object_type, $object_id, $object_slug );
				} else {
					// array( 'post' => 123 )
					return $this->does_id_match_object( $id_array[$object_type], $object_id, $object_slug );
				}
			} else {
				// array( 123, 456, 'postname' )
				$match = false;
				foreach ( $id_array as $id ) {
					if ( $this->does_id_match_object( $id, $object_id, $object_slug ) ) {
						$match = true;
						break;
					}
				}
				return $match;
			}
		} else {
			// 123 || 'postname' || 'username' || 'comment-name'(?)
			return $this->does_id_match_object( $id_array, $object_id, $object_slug );
		}
	}

	function does_id_match_object( $id, $object_id, $object_slug = '' ) {
		if ( is_int( $id ) ) {
			// 123
			return $id == $object_id;
		} elseif ( is_string( $id ) ) {
			// 'postname' || 'username' || 'comment-name' ??
			return $id == $object_slug;
		}
		return false;
	}

	function is_restricted_field( $field_slug, $object_type ) {
		// TODO: Build this out
		$post_restricted = array( 'post_title', 'post_author' );
		$page_restricted = array( );
		$user_restricted = array( );

		switch ( $object_type ) {
		case 'user':
			return in_array( $field_slug, $user_restricted );
		case 'page':
			return in_array( $field_slug, $page_restricted ) || in_array( $field_slug, $post_restricted );
		case 'post':
		default:
			return in_array( $field_slug, $post_restricted );
		}
		return false;
	}

	function is_restricted_group( $group_slug, $object_type ) {
		// TODO: Build this out
		// Built-in metaboxes: title, custom-fields, revisions, author, etc.
		return false;
	}

	function _get_object_type_context() {
		global $current_screen, $pagenow;

		$object_type = '';

		if ( $pagenow == 'profile.php' || $pagenow == 'user-edit.php' || $pagenow == 'users.php' ) {
			return 'user';
		}

		if ( isset( $current_screen->post_type ) ) {
			$object_type = $current_screen->post_type;
		} elseif ( isset( $current_screen->base ) ) {
			foreach ( $this->_builtin_object_types as $builtin_type ) {
				if ( strpos( $current_screen->base, $builtin_type ) !== false ) {
					$object_type = $builtin_type;
					break;
				}
			}
		}

		return $object_type;
	}

	function _get_value_callback( $field, $object_type ) {
		$callback = isset( $field->value_callback ) ? $field->value_callback : '';

		if ( ! ( $callback && is_callable( $callback ) ) )
			$callback = '';

		return apply_filters( 'custom_metadata_manager_get_value_callback', $callback, $field, $object_type );
	}

	function _get_save_callback( $field, $object_type ) {
		$callback = isset( $field->save_callback ) ? $field->save_callback : '';

		if ( ! ( $callback && is_callable( $callback ) ) )
			$callback = '';

		return apply_filters( 'custom_metadata_manager_get_save_callback', $callback, $field, $object_type );
	}

	function get_sanitize_callback( $field, $object_type ) {
		$callback = $field->sanitize_callback;

		if ( ! ( $callback && is_callable( $callback ) ) )
			$callback = '';

		return apply_filters( 'custom_metadata_manager_get_sanitize_callback', $callback, $field, $object_type );
	}

	function get_display_column_callback( $field, $object_type ) {
		$callback = $field->display_column_callback;

		if ( ! ( $callback && is_callable( $callback ) ) )
			$callback = '';

		return apply_filters( 'custom_metadata_manager_get_display_column_callback', $callback, $field, $object_type );
	}

	function _get_field_value( $field_slug, $field, $object_type, $object_id, $single = false ) {

		$get_value_callback = $this->_get_value_callback( $field, $object_type );

		if ( $get_value_callback )
			return call_user_func( $get_value_callback, $object_type, $object_id, $field_slug );

		if ( !in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$value = get_metadata( $object_type, $object_id, $field_slug, $single );

		return $value;
	}

	function _save_field_value( $field_slug, $field, $object_type, $object_id, $value ) {

		$save_callback = $this->_get_save_callback( $field, $object_type );

		if ( $save_callback )
			return call_user_func( $save_callback, $object_type, $object_id, $field_slug, $value );

		if ( ! in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$field_slug = sanitize_key( $field_slug );

		// save the taxonomy as a taxonomy [as well as a custom field]
		if ( in_array( $field->field_type, $this->_taxonomy_fields ) && !in_array( $object_type, $this->_non_post_types ) ) {
			wp_set_object_terms( $object_id, $value, $field->taxonomy );
		}

		if ( is_array( $value ) ) {
			// multiple values
			delete_metadata( $object_type, $object_id, $field_slug ); // delete the old values and add the new ones
			foreach ( $value as $v ) {
				add_metadata( $object_type, $object_id, $field_slug, $v, false );
			}
		} else {
			// single value
			update_metadata( $object_type, $object_id, $field_slug, $value );
		}

		// delete metadata entries if empty
		if ( empty( $value ) ) {
			delete_metadata( $object_type, $object_id, $field_slug );
		}
	}

	function _delete_field_value( $field_slug, $field, $object_type, $object_id, $value = false ) {
		if ( ! in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$field_slug = sanitize_key( $field_slug );

		delete_metadata( $object_type, $object_id, $field_slug, $value );
	}

	function _sanitize_field_value( $field_slug, $field, $object_type, $object_id, $value ) {

		$sanitize_callback = $this->get_sanitize_callback( $field, $object_type );

		// convert date to unix timestamp
		if ( in_array( $field->field_type, array( 'datepicker', 'datetimepicker', 'timepicker' ) ) ) {
			$value = strtotime( $value );
		}

		if ( $sanitize_callback )
			return call_user_func( $sanitize_callback, $field_slug, $field, $object_type, $object_id, $value );

		return $value;
	}

	function _metadata_column_content( $field_slug, $field, $object_type, $object_id ) {
		$value = $this->get_metadata_field_value( $field_slug, $field, $object_type, $object_id );

		$display_column_callback = $this->get_display_column_callback( $field, $object_type );

		if ( $display_column_callback )
			return call_user_func( $display_column_callback, $field_slug, $field, $object_type, $object_id, $value );

		if ( is_array( $value ) )
			return implode( ', ', $value );
		return $value;
	}

	function _display_metadata_multifield( $slug, $multifield, $object_type, $object_id ) {
		echo '<div class="custom-metadata-multifield" data-slug="' . esc_attr( $slug ) . '" id="' . esc_attr( 'custom-metadata-multifield-' . str_replace( '_', '-', str_replace( '_x_multifield_', '', $slug ) ) ) . '">';

		if ( ! empty( $multifield->label ) ) {
			printf( '<h2>%s</h2>', esc_html( $multifield->label ) );
		}

		if ( ! empty( $multifield->description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $multifield->description ) );
		}

		$fields = $this->get_fields_in_multifield( $multifield->group, $slug, $object_type );

		// validate/weed out the fields that can't be part of mulitified
		foreach ( $fields as $field_slug => $field ) {
			if ( ! in_array( $field->field_type, $this->_field_types_that_support_multifield ) ) {
				unset( $fields[$field_slug] );
			}
		}

		$_values = $this->get_metadata_mulitifield_value( $slug, $multifield, $object_type, $object_id );
		$_values = ( ! empty( $_values ) ) ? $_values : array( array() );
		$grouping_count = 0;

		foreach ( $_values as $grouping_of_values ) {
			$grouping_count++;
			$grouping_id = $slug . '-' . $grouping_count;
			printf( '<div id="%s" class="custom-metadata-multifield-grouping">', esc_attr( $grouping_id ) );
				foreach ( $fields as $field_slug => $field ) {
					$value = ( isset( $grouping_of_values[$field_slug] ) ) ? $grouping_of_values[$field_slug] : false;
					$field_id = $slug . '[' . ( $grouping_count - 1 ) . ']' . '[' . $field_slug . ']';
					$display_field_slug = $field_slug . '-' . $grouping_count;
					$this->_display_metadata_field( $display_field_slug, $field, $object_type, $object_id, $field_id, $value );
				}
			echo '<div class="clear"></div>';
			printf( '<a title="%s" class="custom-metadata-multifield-clone hide-if-no-js" href="#">+</a>', __( 'duplicate this set of fields' ) );

			if ( $grouping_count > 1 ) {
				printf( '<a title="%s" class="custom-metadata-multifield-delete hide-if-no-js" href="#">-</a>', __( 'remove this set of fields' ) );
			}

			echo '</div>';
		}

		echo '</div>';

	}

	function _display_metadata_field( $field_slug, $field, $object_type, $object_id, $field_id = null, $value = null ) {

		// this is a safety to prevent multifields from being displayed as a field
		if ( true === $field->multifield )
			return;

		if ( null === $value )
			$value = $this->get_metadata_field_value( $field_slug, $field, $object_type, $object_id );

		$callback = $field->display_callback;

		if ( $callback && is_callable( $callback ) ) {
			call_user_func( $callback, $field_slug, $field, $object_type, $object_id, $value );
			return;
		}

		echo '<div class="custom-metadata-field ' . sanitize_html_class( $field->field_type ) .'" data-slug="' . esc_attr( $field->slug ) . '">';
		if ( ! in_array( $object_type, $this->_non_post_types ) )
			global $post;

		if ( ! empty ($field->multiple ) && ( empty( $this->_cloneable_field_types ) || ! in_array( $field->field_type, $this->_cloneable_field_types ) ) ) {
			$field->multiple = false;
			printf( '<p class="error">%s</p>', __( '<strong>Note:</strong> this field type cannot be multiplied', 'custom-metadata-manager' ) );
		}

		if ( empty( $field_id ) ) {
			$field_id = ( ! empty( $field->multiple ) || in_array( $field->field_type, $this->_always_multiple_fields ) ) ? $field_slug . '[]' : $field_slug;
		}

		$cloneable = ( ! empty( $field->multiple ) ) ? true : false;
		$readonly_str = ( ! empty( $field->readonly ) ) ? ' readonly="readonly"' : '';
		$placeholder_str = ( in_array( $field->field_type, $this->_field_types_that_support_placeholder ) && ! empty( $field->placeholder ) ) ? ' placeholder="' . esc_attr( $field->placeholder ) . '"' : '';

		printf( '<label for="%s">%s</label>', esc_attr( $field_slug ), esc_html( $field->label ) );

		// check if there is a default value and set it if no value currently set
		if ( empty( $value ) && in_array( $field->field_type, $this->_field_types_that_support_default_value ) && ! empty( $field->default_value ) )
			$value = sanitize_text_field( $field->default_value );


		// if value is empty set to an empty string
		if ( empty( $value ) )
			$value = '';

		// make sure $value is an array
		$value = (array) $value;

		$count = 1;
		$container_class = sanitize_html_class( $field_slug );
		$container_class .= ( $cloneable ) ? ' cloneable' : '';
		foreach ( $value as $v ) :
			$container_id = $field_slug . '-' . $count;
			printf( '<div class="%s" id="%s">', esc_attr( $container_class ), esc_attr( $container_id ) );

			switch ( $field->field_type ) :
				case 'text' :
					printf( '<input type="text" id="%s" name="%s" value="%s"%s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					break;
				case 'password' :
					printf( '<input type="password" id="%s" name="%s" value="%s"%s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					break;
				case 'email' :
					printf( '<input type="email" id="%s" name="%s" value="%s"%s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					break;
				case 'tel' :
					printf( '<input type="tel" id="%s" name="%s" value="%s"%s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					break;
				case 'link' :
					printf( '<input type="text" id="%s" name="%s" value="%s" %s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					printf( '<input type="button" class="button custom-metadata-link-button" value="%s"/>', esc_attr( $field->link_modal_button_text ) );
					break;
				case 'number' :
					$min = ( ! empty( $field->min ) ) ? ' min="' . (int) $field->min . '"': '';
					$max = ( ! empty( $field->max ) ) ? ' max="' . (int) $field->max . '"': '';
					printf( '<input type="number" id="%s" name="%s" value="%s"%s%s%s%s/>', esc_attr( $field_slug ), esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str, $min, $max );
					break;
				case 'textarea' :
					printf( '<textarea id="%s" name="%s"%s%s>%s</textarea>', esc_attr( $field_slug ), esc_attr( $field_id ), $readonly_str, $placeholder_str, esc_textarea( $v ) );
					break;
				case 'checkbox' :
					printf( '<input type="checkbox" id="%s" name="%s" %s/>', esc_attr( $field_slug ), esc_attr( $field_id ), checked( $v, 'on', false ) );
					break;
				case 'radio' :
					foreach ( $field->values as $value_slug => $value_label ) {
						$value_id = sprintf( '%s_%s', $field_slug, $value_slug );
						printf( '<label for="%s" class="selectit">', esc_attr( $value_id ) );
						printf( '<input type="radio" id="%s" name="%s" id="%s" value="%s"%s/>', esc_attr( $value_id ), esc_attr( $field_id ), esc_attr( $value_id ), esc_attr( $value_slug ), checked( $v, $value_slug, false ) );
						echo esc_html( $value_label );
						echo '</label>';
					}
					break;
				case 'select' :
					$select2 = ( $field->select2 ) ? ' class="custom-metadata-select2" ' : ' ';
					$select2 .= ( $field->placeholder ) ? ' data-placeholder="'. esc_attr( $field->placeholder ) . '" ' : ' ';
					printf( '<select id="%s" name="%s"%s>', esc_attr( $field_slug ), esc_attr( $field_id ), $select2 );
					foreach ( $field->values as $value_slug => $value_label ) {
						printf( '<option value="%s"%s>', esc_attr( $value_slug ), selected( $v, $value_slug, false ) );
						echo esc_html( $value_label );
						echo '</option>';
					}
					echo '</select>';
					break;
				case 'datepicker' :
					$datepicker_value = ! empty( $v ) ? esc_attr( date( 'm/d/Y', $v ) ) : '';
					printf( '<input type="text" name="%s" value="%s"%s%s/>', esc_attr( $field_id ), $datepicker_value, $readonly_str, $placeholder_str );
					break;
				case 'colorpicker':
					printf( '<input type="text" name="%s" value="%s"%s%s/>', esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					break;
				case 'datetimepicker' :
					$datetimepicker_value = ! empty( $v ) ? esc_attr( date( 'm/d/Y G:i', $v ) ) : '';
					printf( '<input type="text" name="%s" value="%s"%s%s/>', esc_attr( $field_id ), $datetimepicker_value, $readonly_str, $placeholder_str );
					break;
				case 'timepicker' :
					$timepicker = ! empty( $v ) ? esc_attr( date( 'G:i', $v ) ) : '';
					printf( '<input type="text" name="%s" value="%s"%s%s/>', esc_attr( $field_id ), $timepicker, $readonly_str, $placeholder_str );
					break;
				case 'wysiwyg' :
					$wysiwyg_args = apply_filters( 'custom_metadata_manager_wysiwyg_args_field_' . $field_id, $this->default_editor_args, $field_slug, $field, $object_type, $object_id );
					wp_editor( $v, $field_id, $wysiwyg_args );
					break;
				case 'upload' :
					$_attachment_id = $this->get_metadata_field_value( $field_slug . '_attachment_id', $field, $object_type, $object_id );
					$attachment_id = array_shift( array_values( $_attachment_id ) ); // get the first value in the array
					printf( '<input type="text" name="%s" value="%s" class="custom-metadata-upload-url"%s%s/>', esc_attr( $field_id ), esc_attr( $v ), $readonly_str, $placeholder_str );
					printf( '<input type="button" data-uploader-title="%s" data-uploader-button-text="%s" class="button custom-metadata-upload-button" value="%s"/>', esc_attr( $field->upload_modal_title ), esc_attr( $field->upload_modal_button_text ), esc_attr( $field->upload_modal_title ) );
					printf( '<input type="button" class="button custom-metadata-clear-button" value="%s"/>', $field->upload_clear_button_text );
					printf( '<input type="hidden" name="%s" value="%s" class="custom-metadata-upload-id"/>', esc_attr( $field_id . '_attachment_id' ), esc_attr( $attachment_id ) );
					break;
				case 'taxonomy_select' :
					$terms = get_terms( $field->taxonomy, array( 'hide_empty' => false ) );
					if ( empty( $terms ) ) {
						printf( __( 'There are no %s to select from yet.', $field->taxonomy ) );
						break;
					}
					$select2 = ( $field->select2 ) ? ' class="custom-metadata-select2" ' : ' ';
					$select2 .= ( $field->placeholder ) ? ' data-placeholder="'. esc_attr( $field->placeholder ) . '" ' : ' ';
					printf( '<select name="%s" id="%s"%s>', esc_attr( $field_id ), esc_attr( $field_slug ), $select2 );
						echo '<option value=""></option>';
					foreach ( $terms as $term ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $term->slug ), selected( $v, $term->slug, false ), esc_html( $term->name ) );
					}
					echo '</select>';
					break;
				case 'taxonomy_radio' :
					$terms = get_terms( $field->taxonomy, array( 'hide_empty' => false ) );
					if ( empty( $terms ) ) {
						printf( __( 'There are no %s to select from yet.', $field->taxonomy ) );
						break;
					}
					foreach ( $terms as $term ) {
						printf( '<label for="%s" class="selectit">', esc_attr( $term->slug ) );
						printf( '<input type="radio" name="%s" value="%s" id="%s"%s>', esc_attr( $field_id ), esc_attr( $term->slug ), esc_attr( $term->slug ), checked( $v, $term->slug, false ) );
						echo esc_html( $term->name );
						echo '</label>';
					}
					break;
			endswitch;

			if ( $cloneable && $count > 1 )
					echo '<a href="#" class="del-multiple hide-if-no-js">' . __( 'Delete', 'custom-metadata-manager' ) . '</a>';

			$count++;

			echo '</div>';

		endforeach;


		if ( in_array( $field->field_type, $this->_always_multiple_fields ) ) :
			$container_id = $field_slug . '-' . 1;
			printf( '<div class="%s" id="%s">', esc_attr( $container_class ), esc_attr( $container_id ) );


			// fields that save as arrays are not part of the foreach, otherwise they would display for each value, which is not the desired behaviour
			switch ( $field->field_type ) :
				case 'multi_select' :
					$select2 = ( $field->select2 ) ? ' class="custom-metadata-select2" ' : ' ';
					$select2 .= ( $field->placeholder ) ? ' data-placeholder="'. esc_attr( $field->placeholder ) . '" ' : ' ';
					printf( '<select id="%s" name="%s"%smultiple>', esc_attr( $field_slug ), esc_attr( $field_id ), $select2 );
					foreach ( $field->values as $value_slug => $value_label ) {
						printf( '<option value="%s"%s>', esc_attr( $value_slug ), selected( in_array( $value_slug, $value ), true, false ) );
						echo esc_html( $value_label );
						echo '</option>';
					}
					echo '</select>';
					break;
				case 'taxonomy_checkbox' :
					$terms = get_terms( $field->taxonomy, array( 'hide_empty' => false ) );
					if ( empty( $terms ) ) {
						printf( __( 'There are no %s to select from yet.', $field->taxonomy ) );
						break;
					}
					foreach ( $terms as $term ) {
						printf( ' <label for="%s" class="selectit">', esc_attr( $term->slug ) );
						printf( '<input type="checkbox" name="%s" value="%s" id="%s"%s>', esc_attr( $field_id ), esc_attr( $term->slug ), esc_attr( $term->slug ), checked( in_array( $term->slug, $value ), true, false ) );
						echo esc_html( $term->name );
						echo '</label>';
					}
					break;
				case 'taxonomy_multi_select' :
					$terms = get_terms( $field->taxonomy, array( 'hide_empty' => false ) );
					if ( empty( $terms ) ) {
						printf( __( 'There are no %s to select from yet.', $field->taxonomy ) );
						break;
					}
					$select2 = ( $field->select2 ) ? ' class="custom-metadata-select2" ' : ' ';
					$select2 .= ( $field->placeholder ) ? ' data-placeholder="'. esc_attr( $field->placeholder ) . '" ' : ' ';
					printf( '<select name="%s" id="%s"%smultiple>', esc_attr( $field_id ), esc_attr( $field_slug ), $select2 );
					foreach ( $terms as $term ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $term->slug ), selected( in_array( $term->slug, $value ), true, false ), esc_html( $term->name ) );
					}
					echo '</select>';
					break;
			endswitch;

			echo '</div>';
		endif;

		if ( $cloneable )
			printf( '<p><a href="#" class="add-multiple hide-if-no-js" id="%s">%s</a></p>', esc_attr( 'add-' . $field_slug ), __( '+ Add New', 'custom-metadata-manager' ) );

		$this->_display_field_description( $field_slug, $field, $object_type, $object_id, $value );

		echo '</div>';
	}

	function _display_field_description( $field_slug, $field, $object_type, $object_id, $value ) {
		if ( $field->description )
			echo '<span class="description">' . $field->description . '</span>';
	}

	function _display_registration_errors() {
		if ( empty( $this->errors ) )
			return;

		echo '<div class="message error">';
		foreach ( $this->errors as $error => $error_message )
			printf( '<li>%s</li>', esc_html( $error_message ) );
		echo '</div>';
	}

	function _display_wp_link_dialog() {
		if ( ! class_exists( '_WP_Editors' ) )
			require( ABSPATH . WPINC . '/class-wp-editor.php' );

		if ( ! has_action( 'admin_footer', array( '_WP_Editors', 'enqueue_scripts' ) ) )
			_WP_Editors::wp_link_dialog();
	}
}

global $custom_metadata_manager; // for backwards-compatibility we keep the global around, but it shouldn't be used
$custom_metadata_manager = custom_metadata_manager::instance();

function x_add_metadata_field( $slug, $object_types = 'post', $args = array() ) {
	custom_metadata_manager::instance()->add_metadata_field( $slug, $object_types, $args );
}

function x_add_metadata_multifield( $slug, $object_types = 'post', $args = array() ) {
	custom_metadata_manager::instance()->add_multifield( $slug, $object_types, $args );
}

function x_add_metadata_group( $slug, $object_types, $args = array() ) {
	custom_metadata_manager::instance()->add_metadata_group( $slug, $object_types, $args );
}
