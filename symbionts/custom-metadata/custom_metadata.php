<?php
/*
Plugin Name: Custom Metadata Manager
Plugin URI: http://wordpress.org/extend/plugins/custom-metadata/
Description: An easy way to add custom fields to your object types (post, pages, custom post types, users)
Author: Mohammad Jangda, Joachim Kudish & Colin Vernon
Version: 0.7
Author URI: http://digitalize.ca/wordpress-plugins/custom-metadata/

Copyright 2010-2012 Mohammad Jangda, Joachim Kudish, Colin Vernon

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
	include_once( 'custom_metadata_examples.php' );

define( 'CUSTOM_METADATA_MANAGER_CHOSEN_VERSION', '0.9.11' ); // version for included chosen.js

/*
TODO:
- Additional Field types (multi-select, multi-checkboxes)
- Group description field
- Multiple display in the same order as saved

- Limit group based on caps?
- Limit view of custom column based on caps?
- Limit view and save to specific caps based on object type?
-- default for posts: edit_posts
-- default for custom posts: check custom post type object
-- user: edit_user

- validation (pass in array of validation types, or string that references function)
- quick edit
- Links support (?)

*/

// IMPORTANT: Patched by code@pressbooks.com to avoid weird double fields bug.
// If you upgrade this class, make sure you do a diff and keep our changes.

if (!class_exists('custom_metadata_manager')) :

class custom_metadata_manager {

	var $errors = array();

	var $metadata = array();

	var $_non_post_types = array( 'user', 'comment');

	// Object types that come "built-in" with WordPress
	var $_builtin_object_types = array( 'post', 'page', 'user', 'comment' );

	// Column filter names
	var $_column_types = array( 'posts', 'pages', 'users', 'comments' );

	// field types
	var $_field_types = array( 'text', 'textarea', 'password', 'checkbox', 'radio', 'select', 'multi_select', 'upload', 'wysiwyg', 'datepicker', 'taxonomy_select', 'taxonomy_radio',  'taxonomy_checkbox' );

	// field types that are cloneable
	var $_cloneable_field_types = array( 'text', 'textarea', 'upload', 'password');

	// taxonomy types
	var $_taxonomy_fields = array( 'taxonomy_select', 'taxonomy_radio', 'taxonomy_checkbox' );

	// filed types that are saved as multiples but not cloneable
	var $_multiple_not_cloneable = array( 'taxonomy_checkbox' );

	// fields that always save as an array
	var $_always_multiple_fields = array( 'taxonomy_checkbox', 'multi_select' );

	// Object types whose columns are generated through apply_filters instead of do_action
	var $_column_filter_object_types = array( 'user' );

	// Whitelisted pages that get stylesheets and scripts
	var $_pages_whitelist = array( 'edit.php', 'post.php', 'post-new.php', 'users.php', 'profile.php', 'user-edit.php', 'edit-comments.php', 'comment.php');

	// the default args used for the wp_editor function
	var $default_editor_args = array();


	function __construct( ) {

		// filter our vars
		$this->_non_post_types = apply_filters( 'custom_metadata_manager_non_post_types', $this->_non_post_types );
		$this->_builtin_object_types = apply_filters( 'custom_metadata_manager_builtin_object_types', $this->_builtin_object_types );
		$this->_column_types = apply_filters( 'custom_metadata_manager_column_types', $this->_column_types);
		$this->_field_types = apply_filters( 'custom_metadata_manager_field_types', $this->_field_types);
		$this->_cloneable_field_types = apply_filters( 'custom_metadata_manager_cloneable_field_types', $this->_cloneable_field_types);
		$this->_taxonomy_fields = apply_filters( 'custom_metadata_manager_cloneable_field_types', $this->_taxonomy_fields);
		$this->_column_filter_object_types = apply_filters( 'custom_metadata_manager_column_filter_object_types', $this->_column_filter_object_types);
		$this->_pages_whitelist = apply_filters( 'custom_metadata_manager_pages_whitelist', $this->_pages_whitelist);
		$this->default_editor_args = apply_filters( 'custom_metadata_manager_default_editor_args', $this->default_editor_args );


		// We need to run these as late as possible!
		add_action( 'init', array( &$this, 'init' ), 1000, 0 );
		add_action( 'admin_init', array( &$this, 'admin_init' ), 1000, 0 );
	}

	function init() {
		$this->init_object_types();
	}

	function admin_init() {
		global $pagenow;

		define( 'CUSTOM_METADATA_MANAGER_VERSION', '0.7' );
		define( 'CUSTOM_METADATA_MANAGER_URL' , apply_filters('custom_metadata_manager_url', trailingslashit(plugins_url('', __FILE__))) );

		// Hook into load to initialize custom columns
		if( in_array( $pagenow, $this->_pages_whitelist ) ) {
			add_action( 'load-' . $pagenow, array( &$this, 'init_metadata' ) );
		}

		// Hook into admin_notices to show errors
		if( current_user_can( 'manage_options' ) )
			add_action( 'admin_notices', array( &$this, '_display_registration_errors' ) );

	}

	function init_object_types() {
		foreach( array_merge( get_post_types(), $this->_builtin_object_types ) as $object_type )
			$this->metadata[$object_type] = array();
	}

	function init_metadata() {
		$object_type = $this->_get_object_type_context();

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		$this->init_columns();

		// Handle actions related to users
		if( $object_type == 'user' ) {
			// Editing another user's profile
			add_action( 'edit_user_profile', array( &$this, 'add_user_metadata_groups' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'save_user_metadata' ) );
			// Allow user-editable fields on "Your Profile"
			add_action( 'show_user_profile', array( &$this, 'add_user_metadata_groups' ) );
			add_action( 'personal_options_update', array( &$this, 'save_user_metadata' ) );

		} else {

			// Hook in to metaboxes
			add_action( 'add_meta_boxes', array( &$this, "add_post_metadata_groups" ) );

			// Hook in to save
			add_action( 'save_post', array( &$this, 'save_post_metadata' ) );
			add_action( 'edit_comment', array( &$this, 'save_comment_metadata' ) );
		}
	}

	function init_columns() {

		$object_type = $this->_get_object_type_context();

		// This is not really that clean, but it works. Damn inconsistencies!
		if( post_type_exists( $object_type ) ) {
			$column_header_name = sprintf( '%s_posts', $object_type );
			$column_content_name = ( 'page' != $object_type ) ? 'posts' : 'pages';
		} elseif( $object_type == 'comment' ) {
			$column_header_name = 'edit-comments';
			$column_content_name = 'comments';
		} else {
			// users
			$column_header_name = $column_content_name = $object_type . 's';
		}

		// Hook into Column Headers
		add_filter( "manage_{$column_header_name}_columns", array( &$this, 'add_metadata_column_headers' ) );

		// User and Posts have different functions
		$custom_column_content_function = array( &$this, "add_{$object_type}_metadata_column_content" );
		if( ! is_callable( $custom_column_content_function ) )
			$custom_column_content_function = array( &$this, 'add_metadata_column_content' );

		// Hook into Column Content. Users get filtered, others get actioned.
		if( ! in_array( $object_type, $this->_column_filter_object_types ) )
			add_action( "manage_{$column_content_name}_custom_column", $custom_column_content_function, 10, 3 );
		else
			add_filter( "manage_{$column_content_name}_custom_column", $custom_column_content_function, 10, 3 );

	}

	function enqueue_scripts() {		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('custom-metadata-manager-js', apply_filters( 'custom-metadata-manager-default-js', CUSTOM_METADATA_MANAGER_URL .'js/custom-metadata-manager.js' ), array( 'jquery' ), CUSTOM_METADATA_MANAGER_VERSION, true);
		wp_enqueue_script('chosen-js', apply_filters( 'custom-metadata-manager-chosen-js', CUSTOM_METADATA_MANAGER_URL .'js/chosen.jquery.min.js' ), array( 'jquery' ), CUSTOM_METADATA_MANAGER_CHOSEN_VERSION, true);
		// wp_enqueue_script('jquery-ui-datepicker', apply_filters('custom-metadata-manager-datepicker-js', CUSTOM_METADATA_MANAGER_URL .'js/jquery-ui-datepicker.min.js'), array('jquery', 'jquery-ui-core'));
	}

	function enqueue_styles() {
		wp_enqueue_style( 'custom-metadata-manager-css', apply_filters( 'custom-metadata-manager-default-css', CUSTOM_METADATA_MANAGER_URL .'css/custom-metadata-manager.css' ), array(), CUSTOM_METADATA_MANAGER_VERSION );
		wp_enqueue_style( 'jquery-ui-css', apply_filters( 'custom-metadata-manager-jquery-ui-css', CUSTOM_METADATA_MANAGER_URL .'css/jquery-ui-smoothness.css' ), array(), CUSTOM_METADATA_MANAGER_VERSION );
		wp_enqueue_style( 'chosen-css', apply_filters( 'custom-metadata-manager-chosen-css', CUSTOM_METADATA_MANAGER_URL .'css/chosen.css' ), array(), CUSTOM_METADATA_MANAGER_CHOSEN_VERSION );
	}

	function add_metadata_column_headers( $columns ) {

		$object_type = $this->_get_object_type_context();

		if( $object_type ) {
			$fields = $this->get_fields_in_object_type( $object_type );

			foreach( $fields as $field_slug => $field ) {
				if( $this->is_field_addable_to_columns( $field_slug, $field ) ) {
					$columns[$field_slug] = is_string( $field->display_column ) ? $field->display_column : $field->label;
				}
			}
		}
		return $columns;
	}

	function add_user_metadata_column_content( $param, $name, $object_id ) {
		return $this->add_metadata_column_content( $name, $object_id );
	}

	function add_metadata_column_content( $name, $object_id ) {

		$object_type = $this->_get_object_type_context();
		$field_slug = $name;

		$column_content = '';

		if( $this->is_registered_object_type( $object_type ) && $this->is_registered_field( $field_slug, null, $object_type ) ) {
			$field = $this->get_field( $field_slug, null, $object_type );
			$column_content = $this->_metadata_column_content( $field_slug, $field, $object_type, $object_id );
		}

		if( $column_content && ! in_array( $object_type, $this->_column_filter_object_types ) )
			echo $column_content;
		else
			return $column_content;
	}

	function add_metadata_field( $field_slug, $object_types = array( 'post' ), $args = array() ) {

		$defaults = array(
			'group' => '', // To which meta_box the field should be added
			'field_type' => 'text', // The type of field; possibly values: text, checkbox, radio, select, image
			'label' => $field_slug, // Label for the field
			'description' => '', // Description of the field, displayed below the input
			'values' => array(), // values for select, checkbox, radio buttons
			'display_callback' => '', // function to custom render the input
			'sanitize_callback' => '',
			'display_column' => false, // Add the field to the columns when viewing all posts
			'display_column_callback' => '',
			'add_to_quick_edit' => false, // (post only) Add the field to Quick edit
			'required_cap' => '', // the cap required to view and edit the field
			'multiple' => false, // can the field be duplicated with a click of a button
			'readonly' => false, // makes the field be readonly
			'chosen' => false, // applies chosen.js (only when 'field_type' => 'multi_select')
		);

		// Merge defaults with args
		$field = wp_parse_args( $args, $defaults );
		$field = (object) $field;

		// Sanitize slug
		$field_slug = sanitize_key( $field_slug );
		$group_slug = sanitize_key( $field->group );

		// Check to see if the user should see this field
		if( $field->required_cap && ! current_user_can( $field->required_cap ) )
			return;

		if( ! $this->_validate_metadata_field( $field_slug, $field, $group_slug, $object_types ) )
			return;

		// Add to group
		$this->add_field_to_group( $field_slug, $field, $group_slug, $object_types );

	}

	function add_metadata_group( $group_slug, $object_types, $args = array() ) {

		$defaults = array(
			'label' => $group_slug, // Label for the group
			'description' => '', // Description of the group
			'context' => 'normal', // (post only)
			'priority' => 'default', // (post only)
			'autosave' => false, // (post only) Should the group be saved in autosave?
		);

		// Merge defaults with args
		$group = wp_parse_args( $args, $defaults );
		$group = (object) $group;

		// Sanitize slug
		$group_slug = sanitize_key( $group_slug );

		if( !$this->_validate_metadata_group( $group_slug, $group, $object_types ) )
			return;

		$this->add_group_to_object_type( $group_slug, $group, $object_types );
	}


	function add_field_to_group( $field_slug, $field, $group_slug, $object_types ) {
		$object_types = (array) $object_types;

		foreach( $object_types as $object_type ) {
			if( ! $group_slug ) {
				$group_slug = sprintf( 'single-group-%1$s-%2$s', $object_type, $field_slug );
			}

			// If group doesn't exist, create group
			if( ! $this->is_registered_group( $group_slug, $object_type ) ) {
				$this->add_metadata_group( $group_slug, $object_type, array( 'label' => ( ! empty( $field->label ) ) ? $field->label : $field_slug ) );
				$field->group = $group_slug;
			}

			$this->_push_field( $field_slug, $field, $group_slug, $object_type );
		}
	}

	function add_group_to_object_type( $group_slug, $group, $object_types ) {
		$object_types = (array) $object_types;

		foreach( $object_types as $object_type ) {
			if( ($this->is_registered_object_type( $object_type ) && ! $this->is_group_in_object_type( $group_slug, $object_type )) ) {
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

		if( isset( $post ) ) {
			$object_id = $post->ID;
		} elseif( isset( $comment ) ) {
			$object_id = $comment->comment_ID;
		}
		$object_type = $this->_get_object_type_context();

		$groups = $this->get_groups_in_object_type( $object_type );

		if( $object_id && !empty( $groups ) ) {
			foreach( $groups as $group_slug => $group ) {
				$this->add_post_metadata_group( $group_slug, $group, $object_type, $object_id );
			}
		}
	}

	function add_post_metadata_group( $group_slug, $group, $object_type, $object_id ) {

		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		if( ! empty( $fields ) && $this->is_thing_added_to_object( $group_slug, $group, $object_type, $object_id ) ) {
			add_meta_box( $group_slug, $group->label, array( &$this, '_display_post_metadata_box' ), $object_type, $group->context, $group->priority, array( 'group' => $group, 'fields' => $fields));
		}
	}

	function add_user_metadata_groups() {
		global $user_id;

		if( !$user_id ) return;

		$object_type = 'user';

		$groups = $this->get_groups_in_object_type( $object_type );

		if( !empty( $groups ) ) {
			foreach( $groups as $group_slug => $group ) {
				$this->add_user_metadata_group( $group_slug, $group, $object_type, $user_id );
			}
		}
	}

	function add_user_metadata_group( $group_slug, $group, $object_type, $user_id ) {
		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		if( ! empty( $fields ) && $this->is_thing_added_to_object( $group_slug, $group, $object_type, $user_id ) )
			$this->_display_user_metadata_box( $group_slug, $group, $object_type, $fields );
	}


	function _display_user_metadata_box( $group_slug, $group, $object_type, $fields ) {
		global $user_id;
		?>
		<h3><?php echo $group->label; ?></h3>

		<table class="form-table user-metadata-group">
			<?php foreach( $fields as $field_slug => $field ) : ?>
				<?php if( $this->is_thing_added_to_object( $field_slug, $field, $object_type, $user_id ) ) : ?>
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
		if( isset( $object->{$object_type . '_ID'} ) ) {
			$object_id =  $object->{$object_type . '_ID'};
		} elseif ( isset( $object->ID ) ) {
			$object_id = $object->ID;
		} else {
			_e( 'Uh oh, something went wrong!', 'custom-metadata-manager' );
			return;
		}

		foreach( $fields as $field_slug => $field ) {
			if( $this->is_thing_added_to_object( $field_slug, $field, $object_type, $object_id ) ) {
				$this->_display_metadata_field( $field_slug, $field, $object_type, $object_id );
			}
		}

		// Each group gets its own nonce
		$this->_display_group_nonce( $group_slug, $object_type );
	}

	function _display_group_nonce( $group_slug, $object_type ) {
		$nonce_key = $this->build_nonce_key( $group_slug, $object_type );
		wp_nonce_field( 'save-metadata', $nonce_key, false );
	}

	function verify_group_nonce( $group_slug, $object_type ) {
		$nonce_key = $this->build_nonce_key( $group_slug, $object_type );
		if( isset( $_POST[$nonce_key] ) )
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

		foreach( $groups as $group_slug => $group ) {
			$this->save_metadata_group( $group_slug, $group, $object_type, $user_id );
		}
	}

	function save_post_metadata( $post_id ) {
		$post_type = $this->_get_object_type_context();
		$groups = $this->get_groups_in_object_type( $post_type );

		foreach( $groups as $group_slug => $group ) {
			// TODO: Allow hook into autosave
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE && !$group->autosave )
				return $post_id;

			$this->save_metadata_group( $group_slug, $group, $post_type, $post_id );
		}
	}

	function save_comment_metadata( $comment_id ) {
		$object_type = 'comment';
		$groups = $this->get_groups_in_object_type( $object_type );

		foreach( $groups as $group_slug => $group ) {
			$this->save_metadata_group( $group_slug, $group, $object_type, $comment_id );
		}
	}

	function save_metadata_group( $group_slug, $group, $object_type, $object_id ) {
		if ( !$this->verify_group_nonce( $group_slug, $object_type ) ) {
			return $object_id;
		}

		$fields = $this->get_fields_in_group( $group_slug, $object_type );

		foreach( $fields as $field_slug => $field ) {
			$this->save_metadata_field( $field_slug, $field, $object_type, $object_id );
		}
	}

	function save_metadata_field( $field_slug, $field, $object_type, $object_id ) {
		if( isset( $_POST[$field_slug] ) ) {
			$value = $this->_sanitize_field_value( $field_slug, $field, $object_type, $object_id, $_POST[$field_slug] );
			$this->_save_field_value( $field_slug, $field, $object_type, $object_id, $value );
		} else {
			$this->_delete_field_value( $field_slug, $field, $object_type, $object_id );
		}
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
		if( $group_slug )
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
		if( $this->is_registered_field( $field_slug, $group_slug, $object_type ) ) {
			if( $group_slug ) {
				return $this->get_single_field_in_group( $field_slug, $group_slug, $object_type );
			} else {
				return $this->get_single_field_in_object_type( $field_slug, $object_type );
			}
		}
		return null;
	}

	function get_group( $group_slug, $object_type ) {
		if( $this->is_registered_group( $group_slug, $object_type ) ) {
			$groups = $this->get_groups_in_object_type( $object_type );
			return $groups[$group_slug];
		}
		return null;
	}

	function get_object_types() {
		return array_keys( $this->metadata );
	}

	function get_groups_in_object_type( $object_type ) {
		if( $this->is_registered_object_type( $object_type ) )
			return $this->metadata[$object_type];
		return array();
	}

	function get_single_field_in_group( $field_slug, $group_slug, $object_slug ) {
		$fields = $this->get_fields_in_group( $group_slug, $object_type );
		return isset( $fields[$field_slug] ) ? $fields[$field_slug] : null;
	}

	function get_fields_in_group( $group_slug, $object_type ) {
		$group = $this->get_group( $group_slug, $object_type );
		if( $group ) return (array) $group->fields;
		return array();
	}

	function get_single_field_in_object_type( $field_slug, $object_type ) {
		$fields = $this->get_fields_in_object_type( $object_type );
		return isset( $fields[$field_slug] ) ? $fields[$field_slug] : null;
	}

	function get_fields_in_object_type( $object_type ) {
		$fields = array();
		foreach( $this->get_groups_in_object_type( $object_type ) as $group_slug => $group ) {
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

	function is_thing_added_to_object( $thing_slug, $thing, $object_type, $object_id, $object_slug = '' ) {

		if( isset( $thing->exclude ) ) {
			return ! $this->does_id_array_match_object( $thing->exclude, $object_type, $object_id, $object_slug );
		}

		if( isset( $thing->include ) ) {
			return $this->does_id_array_match_object( $thing->include, $object_type,  $object_id, $object_slug );
		}

		return true;
	}

	function does_id_array_match_object( $id_array, $object_type, $object_id, $object_slug = '' ) {
		if( is_array( $id_array ) ) {
			if( isset( $id_array[$object_type] ) ) {
				if( is_array( $id_array[$object_type] ) ) {
					// array( 'user' => array( 123, 'postname' ) )
					return $this->does_id_array_match_object( $id_array[$object_type], $object_type, $object_id, $object_slug );
				} else {
					// array( 'post' => 123 )
					return $this->does_id_match_object( $id_array[$object_type], $object_id, $object_slug );
				}
			} else {
				// array( 123, 456, 'postname' )
				$match = false;
				foreach( $id_array as $id ) {
					if( $this->does_id_match_object( $id, $object_id, $object_slug ) ) {
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
		if( is_int( $id ) ) {
			// 123
			return $id == $object_id;
		} else {
			// 'postname' || 'username' || 'comment-name' ??
			return $id == $object_slug;
		}
	}

	function is_restricted_field( $field_slug, $object_type ) {
		// TODO: Build this out
		$post_restricted = array( 'post_title', 'post_author' );
		$page_restricted = array( );
		$user_restricted = array( );

		switch( $object_type ) {
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

		if( $pagenow == 'profile.php' || $pagenow == 'user-edit.php' || $pagenow == 'users.php' ) {
			return 'user';
		}

		if( isset( $current_screen->post_type ) ) {
			$object_type = $current_screen->post_type;
		} elseif( isset( $current_screen->base ) ) {
			foreach( $this->_builtin_object_types as $builtin_type ) {
				if( strpos( $current_screen->base, $builtin_type ) !== false ) {
					$object_type = $builtin_type;
					break;
				}
			}
		}

		return $object_type;
	}

	function _get_value_callback( $field ) {
		$callback = isset( $field->value_callback ) ? $field->value_callback : '';
		if( $callback && is_callable( $callback ) )
			return $callback;
		return '';
	}

	function _get_save_callback( $field ) {
		$callback = isset( $field->save_callback ) ? $field->save_callback : '';
		if( $callback && is_callable( $callback ) )
			return $callback;
		return '';
	}

	function get_sanitize_callback( $field ) {
		$callback = $field->sanitize_callback;
		if( $callback && is_callable( $callback ) )
			return $callback;
		return '';
	}

	function get_display_column_callback( $field ) {
		$callback = $field->display_column_callback;
		if( $callback && is_callable( $callback ) )
			return $callback;
		return '';
	}

	// Changed by code@pressbooks.com
	function _get_field_value( $field_slug, $field, $object_type, $object_id ) {

		$get_value_callback = $this->_get_value_callback( $field );
		if( $get_value_callback )
			return call_user_func( $get_value_callback, $object_type, $object_id, $field_slug );

		if ( !in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$value = get_metadata( $object_type, $object_id, $field_slug, false );

		if ( is_array( $value ) && ( in_array( $field->field_type, $this->_always_multiple_fields ) || $field->multiple ) ) {
			// Do nothing
		} else {
			// Pop out the last value
			$value = array( 0 => array_pop( $value ) );
		}

		return $value;
	}

	// Changed by code@pressbooks.com
	function _save_field_value( $field_slug, $field, $object_type, $object_id, $value ) {

		$save_callback = $this->_get_save_callback( $field );

		if( $save_callback )
			return call_user_func( $save_callback, $object_type, $object_id, $field_slug, $value );

		if( ! in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$field_slug = sanitize_key( $field_slug );

		// save the taxonomy as a taxonomy [as well as a custom field]
		if ( in_array($field->field_type, $this->_taxonomy_fields) && !in_array( $object_type, $this->_non_post_types ) )	{
			wp_set_object_terms($object_id, $value, $field->taxonomy);
		}

		delete_metadata( $object_type, $object_id, $field_slug ); // Delete first

		if ( is_array( $value ) && $field->multiple ) {
			// multiple values
			$value = array_reverse($value);
			foreach ($value as $v) {
				add_metadata( $object_type, $object_id, $field_slug, $v, false );
			}
		} elseif ( ! empty( $value ) ) {
			// single value
			add_metadata( $object_type, $object_id, $field_slug, $value, true );
		}

	}

	function _delete_field_value( $field_slug, $field, $object_type, $object_id, $value = false ) {
		if( ! in_array( $object_type, $this->_non_post_types ) )
			$object_type = 'post';

		$field_slug = sanitize_key( $field_slug );

		delete_metadata( $object_type, $object_id, $field_slug, $value );
	}

	function _sanitize_field_value( $field_slug, $field, $object_type, $object_id, $value ) {

		$sanitize_callback = $this->get_sanitize_callback( $field );

		if( $sanitize_callback )
			return call_user_func( $sanitize_callback, $field_slug, $field, $object_type, $object_id, $value );

		// convert date to unix timestamp
		if ($field->field_type == 'datepicker')	{
			$value = strtotime($value);
		}

		return $value;
	}

	function _metadata_column_content( $field_slug, $field, $object_type, $object_id ) {
		$value = $this->get_metadata_field_value( $field_slug, $field, $object_type, $object_id );

		$display_column_callback = $this->get_display_column_callback( $field );

		if( $display_column_callback )
			return call_user_func( $display_column_callback, $field_slug, $field, $object_type, $object_id, $value );

		if( is_array( $value ) )
			return implode( ', ', $value );
		return $value;
	}

	function _display_metadata_field( $field_slug, $field, $object_type, $object_id ) {

		$value = $this->get_metadata_field_value( $field_slug, $field, $object_type, $object_id );

		if (isset($field->display_callback) && function_exists($field->display_callback)) :

			call_user_func($field->display_callback, $field_slug, $field, $object_type, $object_id, $value);

		else :
		?>
		<div class="custom-metadata-field <?php echo $field->field_type ?>">
			<?php
			if (!in_array($object_type, $this->_non_post_types)) global $post;
			if (isset($field->multiple) && $field->multiple && @!in_array($field->field_type, $this->_cloneable_field_types)) {
				$field->multiple = false;
				echo '<p class="error"><strong>Note:</strong> this field type cannot be multiplied</p>';
			}

			if ( (isset($field->multiple) && $field->multiple) || in_array($field->field_type, $this->_always_multiple_fields) ) $field_id = $field_slug.'[]';
			else $field_id = $field_slug;

			$cloneable = (isset($field->multiple) && $field->multiple);

			$readonly_str = ($field->readonly) ? 'readonly="readonly" ' : '';

			if (get_post_type()) $numb = $post->ID; else $numb = 1; ?>
			<script>var numb = '<?php echo $numb ?>'; </script>

			<label for="<?php echo $field_slug; ?>"><?php echo $field->label; ?></label>
			<?php
			// make sure $value is an array
				if (!$value) $value = ''; // if empty, give it an empty string instead
				$value = (array)$value;
				$count = 1;
				foreach( $value as $v ) :	?>

				<div class="<?php echo $field_slug ?><?php echo ( $cloneable ) ? ' cloneable' : ''; ?>" id="<?php echo $field_slug ?>-<?php echo $count;?>">

					<?php switch ($field->field_type) :
							case 'text': ?>
							<input type="text" id="<?php echo $field_slug; ?>" name="<?php echo $field_id; ?>" value="<?php echo esc_attr($v); ?>" <?php echo $readonly_str ?>/>
						<?php break; ?>

						<?php case 'textarea': ?>
							<textarea id="<?php echo $field_slug; ?>" name="<?php echo $field_id; ?>" <?php echo $readonly_str ?>><?php echo esc_attr($v); ?></textarea>
						<?php break; ?>

						<?php case 'password': ?>
							<input type="password" id="<?php echo $field_slug; ?>" name="<?php echo $field_id; ?>" value="<?php echo esc_attr($v); ?>" <?php echo $readonly_str ?>/>
						<?php break; ?>

						<?php case 'checkbox': ?>
							<input type="checkbox" id="<?php echo $field_slug; ?>" name="<?php echo $field_id; ?>" <?php checked($checked = $v, 'on' ); ?> />
						<?php break; ?>

						<?php case 'radio': ?>
							<?php foreach( $field->values as $value_slug => $value_label ) : ?>
								<?php
								$value_id = sprintf( '%s_%s', $field_slug, $value_slug );
								?>
								<label for="<?php echo $value_id; ?>" class="selectit">
									<input type="radio" id="<?php echo $value_id; ?>" name="<?php echo $field_id; ?>" id="<?php echo $value_id; ?>" value="<?php echo $value_slug ?>" <?php checked($checked = $v ); ?> />
									<?php echo $value_label; ?>
								</label>
							<?php endforeach; ?>
						<?php break; ?>

						<?php case 'select': ?>
							<select id="<?php echo $field_slug; ?>" name="<?php echo $field_id; ?>">
								<?php foreach( $field->values as $value_slug => $value_label ) : ?>
									<?php
									$value_id = $field_slug . $value_slug;
									?>
									<option value="<?php echo $value_slug ?>" <?php selected($v == $value_slug); ?>>
										<?php echo $value_label; ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php break; ?>

						<?php case 'datepicker': ?>
							<input type="text" name="<?php echo $field_id; ?>" value="<?php echo (isset($v)) ? date('Y-d-m', $v) : ''; ?>" <?php echo $readonly_str ?>/>
						<?php break; ?>

						<?php case 'wysiwyg': ?>
							<?php
								$args = apply_filters('custom_metadata_manager_wysiwyg_args_field_'.$field_id, $this->default_editor_args, $field_slug, $field, $object_type, $object_id );
							 	wp_editor($v, $field_id, $args);
							?>
						<?php break; ?>

						<?php case 'upload': ?>
							<input type="text" name="<?php echo $field_id; ?>" value="<?php echo $v; ?>" class="upload_field" <?php echo $readonly_str ?>/>
							<input type="button" title="<?php echo $post->ID ?>" class="button upload_button" value="Upload" />
						<?php break; ?>

						<?php case 'taxonomy_select': ?>
							<select name="<?php echo $field_id; ?>" id="<?php echo $field_slug; ?>">
							<?php
							$terms = get_terms( $field->taxonomy, array('hide_empty' => false));
							foreach ( $terms as $term ) : ?>
								<option value="<?php echo $term->slug ?>"<?php selected($term->slug == $v) ?>><?php echo $term->name ?></option>
							<?php endforeach; ?>
							</select>
						<?php break; ?>

						<?php case 'taxonomy_radio':
							$terms = get_terms( $field->taxonomy, array('hide_empty' => false) );
							foreach ( $terms as $term ) : ?>
								<label for="<?php echo $term->slug; ?>" class="selectit">
									<input type="radio" name="<?php echo $field_id ?>" value="<?php echo $term->slug ?>" id="<?php echo $term->slug ?>"<?php checked($term->slug == $v) ?>>
									<?php echo $term->name ?>
								</label>
						<?php endforeach; ?>
						<?php break; ?>

					<?php endswitch; ?>

					<?php if ( $cloneable && $count > 1) : ?>
						<a href="#" class="del-multiple hide-if-no-js" style="color:red;">Delete</a>
					<?php endif; $count++ ?>

				</div>

			<?php endforeach; ?>

			<?php if( 'multi_select' == $field->field_type ) : ?>
				<select id="<?php echo $field_slug; ?>" <?php if( true == $field->chosen ) { echo( 'class="chosen" ' ); } ?>name="<?php echo $field_id; ?>" multiple>
					<?php foreach( $field->values as $value_slug => $value_label ) : ?>
						<option value="<?php echo esc_attr( $value_slug ); ?>" <?php selected( in_array($value_slug, $value) ) ?>>
							<?php echo $value_label; ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<?php if ( 'taxonomy_checkbox' == $field->field_type ) :
				$terms = get_terms( $field->taxonomy, array('hide_empty' => false) );
				foreach ( $terms as $term ) : ?>
					<label for="<?php echo $term->slug; ?>" class="selectit">
						<input type="checkbox" name="<?php echo $field_id ?>" value="<?php echo $term->slug ?>" id="<?php echo $term->slug ?>"<?php checked(in_array($term->slug, $value)) ?>>
						<?php echo $term->name ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>

		<?php if ($cloneable) : ?>
			<p><a href="#" class="add-multiple hide-if-no-js" id="add-<?php echo $field_slug ?>">+ Add New</a></p>
		<?php endif ?>

		<?php $this->_display_field_description( $field_slug, $field, $object_type, $object_id, $value ); ?>

		</div>

	<?php
		endif;
	}

	function _display_field_description( $field_slug, $field, $object_type, $object_id, $value ) {
		?>
		<?php if( $field->description ) : ?>
			<span class="description"><?php echo $field->description; ?></span>
		<?php endif; ?>
		<?php
	}

	function _display_registration_errors( ) {
		if( !empty( $this->errors ) ) {
			?>
			<div class="message error">
				<?php foreach( $this->errors as $error => $error_message ) : ?>
					<li><?php echo $error_message; ?></li>
				<?php endforeach; ?>
			</div>
			<?php
		}
	}

	function debug($msg, $object) {
		if( CUSTOM_METADATA_MANAGER_DEBUG ) {
			echo '<hr />';
			echo sprintf('<p>%s</p>', $msg);
			echo '<pre>';
			var_dump($object);
			echo '</pre>';
		}
	}
}

global $custom_metadata_manager;
$custom_metadata_manager = new custom_metadata_manager();

endif; // !class_exists

function x_add_metadata_field( $slug, $object_types = 'post', $args = array() ) {
	global $custom_metadata_manager;
	$custom_metadata_manager->add_metadata_field( $slug, $object_types, $args );
}

function x_add_metadata_group( $slug, $object_types, $args = array() ) {
	global $custom_metadata_manager;
	$custom_metadata_manager->add_metadata_group( $slug, $object_types, $args );
}
