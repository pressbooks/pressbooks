<?php
/*
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
 * register a test post type just for the examples
 *
 * @return void
 */
add_action( 'init', 'x_init_custom_post_types' );
function x_init_custom_post_types() {

	$labels = array(
		'name' => _x( 'Tests', 'post type general name' ),
		'singular_name' => _x( 'Test', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'Test' ),
		'add_new_item' => __( 'Add New Test' ),
		'edit_item' => __( 'Edit Test' ),
		'new_item' => __( 'New Test' ),
		'all_items' => __( 'All Tests' ),
		'view_item' => __( 'View Test' ),
		'search_items' => __( 'Search Tests' ),
		'not_found' =>  __( 'No Tests found' ),
		'not_found_in_trash' => __( 'No Tests found in Trash' ),
		'parent_item_colon' => '',
		'menu_name' => 'Tests'

	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title' )
	);
	register_post_type( 'x_test', $args );
}


/**
 * register custom metadata groups and fields
 * this is the example code that you should use
 * make sure to use the 'admin_init' hook as below
 *
 * @return void
 */
add_action( 'custom_metadata_manager_init_metadata', 'x_init_custom_fields' );
function x_init_custom_fields() {

	// adds a new group to the test post type
	x_add_metadata_group( 'x_metaBox1', 'x_test', array(
			'label' => 'Group with Multiple Fields'
		) );

	// adds another group to the test post type + posts + users
	x_add_metadata_group( 'x_metaBox2', array( 'x_test', 'post', 'user' ), array(
			'label' => 'Group for Post and User',
			'description' => "Here's a group with a description!",
		) );

	// adds a text field to the first group
	x_add_metadata_field( 'x_fieldName1', 'x_test', array(
			'group' => 'x_metaBox1', // the group name
			'description' => 'This is field #1. It\'s a simple text field.', // description for the field
			'label' => 'Text Field', // field label
			'display_column' => true // show this field in the column listings
		) );

	// adds a text field to the 2nd group
	x_add_metadata_field( 'x_fieldName2', 'x_test', array(
			'group' => 'x_metaBox1',
			'display_column' => 'My Column (with Custom Callback)', // show this field in the column listings
			'display_column_callback' => 'x_field_name2_callback', // custom function to display the column results (see below)
			'label' => 'Text with Custom Callback',
		) );


	// adds a cloneable textarea field to the 1st group
	x_add_metadata_field( 'x_fieldTextarea1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'textarea',
			'multiple' => true,
			'label' => 'Repeatable Text Area',
		) );

	// adds a readonly textarea field to the 1st group
	x_add_metadata_field( 'x_fieldTextareaReadOnly1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'textarea',
			'readonly' => true,
			'label' => 'Read Only Text Area',
		) );

	// adds a readonly text field to the 1st group
	x_add_metadata_field( 'x_fieldTextReadOnly1', 'x_test', array(
			'group' => 'x_metaBox1',
			'readonly' => true,
			'label' => 'Read Only Text Area',
		) );

	// adds a wysiwyg (full editor) field to the 2nd group
	x_add_metadata_field( 'x_fieldWysiwyg1', array( 'x_test', 'user' ), array(
			'group' => 'x_metaBox2',
			'field_type' => 'wysiwyg',
			'label' => 'TinyMCE / Wysiwyg field',
		) );

	// adds a datepicker field to the 1st group
	x_add_metadata_field( 'x_fieldDatepicker1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'datepicker',
			'label' => 'Datepicker field',
		) );

	// adds a datetimepicker field to the 1st group
	x_add_metadata_field( 'x_fieldDatetimepicker1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'datetimepicker',
			'label' => 'Datetimepicker field',
		) );

	// adds a timepicker field to the 1st group
	x_add_metadata_field( 'x_fieldTimepicker1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'timepicker',
			'label' => 'Timepicker field',
		) );

	// adds a colorpicker field to the 1st group
	x_add_metadata_field( 'x_fieldColorpicker1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'colorpicker',
			'label' => 'Colorpicker field',
		) );

	// adds an upload field to the 1st group
	x_add_metadata_field( 'x_fieldUpload1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'upload',
			'readonly' => true,
			'label' => 'Upload field',
		) );

	// adds a checkbox field to the first group
	x_add_metadata_field( 'x_fieldCheckbox1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'checkbox',
			'label' => 'Checkbox field',
		) );

	// adds a radio button field to the first group
	x_add_metadata_field( 'x_fieldRadio1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'radio',
			'values' => array(     // set possible value/options
				'option1' => 'Option #1', // key => value pair (key is stored in DB)
				'option2' => 'Option #2',
			),
			'label' => 'Radio field',
		) );

	// adds a select box in the first group
	x_add_metadata_field( 'x_fieldSelect1', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'select',
			'values' => array(     // set possible value/options
				'option1' => 'Option #1', // key => value pair (key is stored in DB)
				'option2' => 'Option #2',
			),
			'label' => 'Select field',
		) );

	// adds a multi-select field in the first group
	x_add_metadata_field( 'x_field_multi_select', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'multi_select',
			'values' => array(     // set possible value/options
				'option1' => 'Option #1', // key => value pair (key is stored in DB)
				'option2' => 'Option #2',
				'option3' => 'Option #3',
				'option4' => 'Option #4',
			),
			'label' => 'Multi Select field',
		) );

	// adds a multi-select field with chosen in the first group
	// note: `select2` and `chosen` args do the exact same (add select2)
	// but for the purposes of testing, we're using chosen here
	x_add_metadata_field( 'x_field_multi_select_chosen', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'multi_select',
			'values' => array(     // set possible value/options
				'option1' => 'Option #1', // key => value pair (key is stored in DB)
				'option2' => 'Option #2',
				'option3' => 'Option #3',
				'option4' => 'Option #4',
			),
			'label' => 'Multi Select field (with chosen)',
			'chosen' => true,
		) );

	// adds a select field with select2 in the first group
	x_add_metadata_field( 'x_field_select_select2', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'select',
			'values' => array(     // set possible value/options
				'option1' => 'Option #1', // key => value pair (key is stored in DB)
				'option2' => 'Option #2',
				'option3' => 'Option #3',
				'option4' => 'Option #4',
			),
			'label' => 'Select field (with select2)',
			'select2' => true,
		) );

	// adds a taxonomy checkbox field in the first group
	x_add_metadata_field( 'x_field_taxonomy_checkbox', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'taxonomy_checkbox',
			'taxonomy' => 'category',
			'label' => 'Category checkbox field',
		) );

	// adds a taxonomy select field in the first group
	x_add_metadata_field( 'x_field_taxonomy_select', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'taxonomy_select',
			'taxonomy' => 'category',
			'label' => 'Category select field',
		) );

	// adds a taxonomy multiselect field in the first group
	x_add_metadata_field( 'x_field_taxonomy_multi_select', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => 'category',
			'label' => 'Category multiselect field',
		) );

	// adds a taxonomy multiselect w/ select2 field in the first group
	x_add_metadata_field( 'x_field_taxonomy_multi_select2', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => 'category',
			'label' => 'Category multiselect w/ select2 field',
			'select2' => true,
		) );

	// adds a number field in the first group (with no min/max)
	x_add_metadata_field( 'x_field_number', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'number',
			'label' => 'Number field',
		) );

	// adds a number field in the first group (with min/max)
	x_add_metadata_field( 'x_field_number_with_min_max', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'number',
			'min' => '-3',
			'max' => '25',
			'multiple' => true,
			'label' => 'Number field (with min/max + cloneable)',
		) );

	// adds an email field in the first group
	x_add_metadata_field( 'x_field_email', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'email',
			'label' => 'Email field',
		) );

	// adds a link field in the first group
	x_add_metadata_field( 'x_field_link', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'link',
			'label' => 'Link field',
		) );

	// adds a telephone field in the first group (with default value)
	x_add_metadata_field( 'x_field_telephone', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'tel',
			'label' => 'Telephone field',
			'default_value' => '123-4567'
		) );

	// adds a text field with a default value
	x_add_metadata_field( 'x_field_text_default', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'text',
			'label' => 'Text field with default value',
			'default_value' => 'lorem ipsum'
		) );

	// adds a text field with placeholder
	x_add_metadata_field( 'x_field_textarea_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'textarea',
			'label' => 'Textarea field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a password field with placeholder
	x_add_metadata_field( 'x_field_password_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'password',
			'label' => 'Password field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a number field with placeholder
	x_add_metadata_field( 'x_field_number_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'number',
			'label' => 'Number field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds an email field with placeholder
	x_add_metadata_field( 'x_field_email_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'email',
			'label' => 'Email field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a link field with placeholder
	x_add_metadata_field( 'x_field_link_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'link',
			'label' => 'Link field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds an telephone field with placeholder
	x_add_metadata_field( 'x_field_telephone_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'tel',
			'label' => 'Telephone field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds an upload field with placeholder
	x_add_metadata_field( 'x_field_upload_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'upload',
			'label' => 'Upload field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds an datepicker field with placeholder
	x_add_metadata_field( 'x_field_datepicker_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'datepicker',
			'label' => 'Datepicker field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a datetimepicker field with placeholder
	x_add_metadata_field( 'x_field_datetimepicker_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'datetimepicker',
			'label' => 'Datetimepicker field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a timepicker field with placeholder
	x_add_metadata_field( 'x_field_timepicker_placeholder', 'x_test', array(
			'group' => 'x_metaBox1',
			'field_type' => 'timepicker',
			'label' => 'Timepicker field with placeholder',
			'placeholder' => 'some placeholder text',
		) );

	// adds a mulitifield
	x_add_metadata_multifield( 'x_test_multifield', 'x_test', array(
			'group' => 'x_metabox1',
			'label' => 'Multifield test',
			'description' => 'This is a great multifield',
		) );

	// adds a text field to the multifield
	x_add_metadata_field( 'multifield_field_1', 'x_test', array(
		'group' => 'x_metabox1', // the group name
		'multifield' => 'x_test_multifield',
		'description' => 'This is field #1 of the multifield.',
		'label' => 'Text Field', // field label
		'field_type' => 'text',
	) );

	// adds an upload text field to the multifield
	x_add_metadata_field( 'multifield_field_2', 'x_test', array(
		'group' => 'x_metabox1', // the group name
		'multifield' => 'x_test_multifield',
		'description' => 'This is field #2 of the multifield.',
		'label' => 'Upload Field', // field label
		'field_type' => 'upload',
	) );


	// adds an upload text field to the multifield
	x_add_metadata_field( 'multifield_field_3', 'x_test', array(
		'group' => 'x_metabox1', // the group name
		'multifield' => 'x_test_multifield',
		'description' => 'This is field #4 of the multifield.',
		'label' => 'Telephone Field', // field label
		'field_type' => 'tel',
	) );

	// adds a text field to the multifield
	x_add_metadata_field( 'multifield_field_4', 'x_test', array(
		'group' => 'x_metabox1', // the group name
		'multifield' => 'x_test_multifield',
		'description' => 'This is field #4 of the multifield.',
		'label' => 'Password Field', // field label
		'field_type' => 'password',
	) );


	// adds a field to posts and users
	x_add_metadata_field( 'x_fieldName2', array( 'post', 'user' ), array(
			'group' => 'x_metaBox2',
			'label' => 'Text field',
		) );

	// adds a field with a custom display callback (see below)
	x_add_metadata_field( 'x_fieldCustomHidden1', 'x_test', array(
			'group' => 'x_metaBox1',
			'display_callback' => 'x_field_customhidden1_callback', // this function is defined below
			'label' => 'Hidden field',
		) );


	// field with capabilities limited
	x_add_metadata_field( 'x_cap-limited-field', 'x_test', array(
			'label' => 'Cap Limited Field (edit_posts)',
			'required_cap' => 'edit_posts' // limit to users who can edit posts
		) );

	// field with role limited
	x_add_metadata_field( 'x_author-cap-limited-field', 'user', array(
			'label' => 'Cap Limited Field (author)',
			'required_cap' => 'author' // limit to authors
		) );

	// comment field
	x_add_metadata_field( 'x_commentField1', 'comment', array(
			'label' => 'Field for Comment',
			'display_column' => true
		) );

	// field that exludes posts
	x_add_metadata_field( 'x_fieldNameExcluded1', 'post', array(
			'description' => 'This field is excluded from Post ID#2476',
			'label' => 'Excluded Field',
			'exclude' => 2476
		) );

	// field that includes certain posts only
	x_add_metadata_field( 'x_fieldNameIncluded1', 'post', array(
			'description' => 'This field is only included on Post ID#2476',
			'label' => 'Included Field',
			'include' => 2476
		) );

	x_add_metadata_field( 'x_fieldExcludeCallback', 'post', array(
			'description' => 'This field is excluded using a custom callback; will be excluded from posts in the "aside" category',
			'label' => 'Excluded Field (with callback)',
			'exclude' => 'x_custom_exclude_callback',
		) );

	/**
	 *
	 *
	 * @param unknown $thing_slug  string Slug of the field or group
	 * @param unknown $thing       object Field or Group args set up when registering
	 * @param unknown $object_type string What type of object (post, comment, user)
	 * @param unknown $object_id   int|string ID of the object
	 * @param unknown $object_slug string
	 */
	function x_custom_exclude_callback( $thing_slug, $thing, $object_type, $object_id, $object_slug ) {
		// exclude from all posts that are in the aside category
		return in_category( 'aside', $object_id );
	}

	x_add_metadata_field( 'x_fieldIncludedCallback', 'post', array(
			'description' => 'This field is included using a custom callback; will only be included for posts that are not published',
			'label' => 'Included Field (with callback)',
			'include' => 'x_custom_include_callback',
		) );

	function x_custom_include_callback( $thing_slug, $thing, $object_type, $object_id, $object_slug ) {
		$post = get_post( $object_id );
		return 'publish' != $post->post_status;
	}
}

/**
 * this is an example of a column callback function
 * it echoes out a bogus description, but it's just so you can see how it works
 *
 * @param string  $field_slug  the slug/id of the field
 * @param object  $field       the field object
 * @param string  $object_type what object type is the field associated with
 * @param int     $object_id   the ID of the current object
 * @param string  $value       the value of the field
 * @return void
 */
function x_field_name2_callback( $field_slug, $field, $object_type, $object_id, $value ) {
	echo sprintf( 'The value of field "%s" is %s. <br /><a href="http://icanhascheezburger.files.wordpress.com/2010/10/04dc84b6-3dde-45db-88ef-f7c242731ce3.jpg">Here\'s a LOLCat</a>', $field_slug, $value ? $value : 'not set' );
}

/**
 * this is another example of a custom callback function
 * we've chosen not to include all of the params this time
 *
 * @param string  $field_slug  the slug/id of the field
 * @param object  $field       the field object
 * @param string  $object_type what object type is the field associated with
 * @return void
 */
function x_field_customhidden1_callback( $field_slug, $field, $value ) {
	if ( ! $value ) $value = 'This is a secret hidden value! Don\'t tell anyone!';
?>
	<hr />
	<p>This is a hidden field rendered with a custom callback. The value is "<?php echo $value; ?>".</p>
	<input type="hidden" name="<?php echo $field_slug; ?>" value="<?php echo $value; ?>" />
	<hr />
	<?php
}