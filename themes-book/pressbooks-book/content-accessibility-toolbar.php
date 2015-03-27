<?php
$accessibility = get_option( 'pressbooks_theme_options_web' );
?>
<!-- a11y toolbar -->
<div class="a11y-toolbar">
	<?php
            if ( true == $accessibility['accessibility_fontsize'] ) {
                    echo '<ul><li><a href="#" role="button" class="a11y-toggle-fontsize toggle-fontsize" id="is_normal_fontsize" title="Toggle Font size"><span class="dashicons dashicons-visibility"></span></a></li></ul>';
            }
	?>
</div>
<!-- // a11y toolbar -->

