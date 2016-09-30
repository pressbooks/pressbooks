<?php
$tabs = get_option( 'tabbed_content' );

if ( isset( $tabs ) && in_array( 1, array_values( $tabs ) ) ) {
	$i = 1;
	$html .= '<div id="tabs">';
	$labels .= '<ul>';

	foreach( $tabs as $key=>$tab ){
		$title = \Pressbooks\Sanitize\explode_on_underscores( $key );
		$method = 'pressbooks_tabs_' . $key;
		$labels .= "<li><a href='#tabs-{$i}'>{$title} <span class='dashicons'></span></a></li>";
		$panels .= "<div id='tabs-{$i}'>";
		$panels .= $method($post);
		$panels .= "</div>";
		$i++;
	}
	$labels .= "</ul>";
	$panels .= "</div>";

	$html .= $labels . $panels;

	echo $html;

}
