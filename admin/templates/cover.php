<?php
$pdf_options = get_option('pressbooks_theme_options_pdf');
foreach ($pdf_options as $key => $value) {
	switch($key) {
		case 'pdf_page_size': ?>
		<h1><?php _e( 'Page size', 'pressbooks' ) ?>: <?php
		$pageclass = 'pdf_size'.$value;
		if ( $value == 1 ) { _e( 'digest', 'pressbooks' ); }
		elseif ( $value == 2 ) { _e( 'US trade', 'pressbooks' ); }
		elseif ( $value == 3 ) { _e( 'US letter', 'pressbooks' ); }
		elseif ( $value == 4 ) { _e( '8.5 x 9.25"', 'pressbooks' ); }
		elseif ( $value == 5 ) { _e( 'duodecimo', 'pressbooks' ); }
		elseif ( $value == 6 ) { _e( 'pocket', 'pressbooks' ); }
		elseif ( $value == 7 ) { _e( 'A4', 'pressbooks' ); }
		elseif ( $value == 8 ) { _e( 'A5', 'pressbooks' ); } ?></h1>
		<?php break;
	}
}
?>
<div id="pagesize_wrapper" class="<?php echo $pageclass ?>">
<div id="pagesize_front_page" class="pagesize_inside"></div>
<div id="pagesize_spine" class="pagesize_inside"></div>
<div id="pagesize_back_page" class="pagesize_inside"></div>
</div>

