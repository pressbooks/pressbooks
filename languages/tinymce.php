<?php # -*- coding: utf-8 -*-

$strings = array(
	'classtitle' => __( 'Class', 'pressbooks' ),
	'textboxes' => __( 'Textboxes', 'pressbooks' ),
	'customtextbox' => __( 'Custom Textbox', 'pressbooks' ),
	'standard' => __( 'Standard', 'pressbooks' ),
	'standardsidebar' => __( 'Standard (Sidebar)', 'pressbooks' ),
	'standardplaceholder' => __( 'Type your textbox content here.', 'pressbooks' ),
	'shaded' => __( 'Shaded', 'pressbooks' ),
	'shadedsidebar' => __( 'Shaded (Sidebar)', 'pressbooks' ),
	'learningobjectives' => __( 'Learning Objectives', 'pressbooks' ),
	'learningobjectivessidebar' => __( 'Learning Objectives (Sidebar)', 'pressbooks' ),
	'learningobjectivesplaceholder' => __( 'Type your learning objectives here.', 'pressbooks' ),
	'keytakeaways' => __( 'Key Takeaways', 'pressbooks' ),
	'keytakeawayssidebar' => __( 'Key Takeaways (Sidebar)', 'pressbooks' ),
	'keytakeawaysplaceholder' => __( 'Type your key takeaways here.', 'pressbooks' ),
	'exercises' => __( 'Exercises', 'pressbooks' ),
	'exercisessidebar' => __( 'Exercises (Sidebar)', 'pressbooks' ),
	'exercisesplaceholder' => __( 'Type your exercises here.', 'pressbooks' ),
	'examples' => __( 'Examples', 'pressbooks' ),
	'examplessidebar' => __( 'Examples (Sidebar)', 'pressbooks' ),
	'examplesplaceholder' => __( 'Type your examples here.', 'pressbooks' ),
	'customellipses' => __( 'Custom...', 'pressbooks' ),
	'first' => __( 'First', 'pressbooks' ),
	'second' => __( 'Second', 'pressbooks' ),
	'applyclass' => __( 'Apply Class', 'pressbooks' ),
);

$locale = _WP_Editors::$mce_locale;
$strings = 'tinyMCE.addI18n("' . $locale . '.strings", ' . wp_json_encode( $strings ) . ");\n";
