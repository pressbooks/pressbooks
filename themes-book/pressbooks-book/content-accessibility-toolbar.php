<?php 
	$accessibility = get_option( 'pressbooks_theme_options_web' );
?>
<!-- a11y toolbar -->
<div class="a11y-toolbar">
	<ul>
		<li>it works!</li>
		<?php
		if ( true == $accessibility['accessibility_fontsize'] ) {
			echo '<li><a href="#" role="button" class="a11y-toggle-fontsize toggle-fontsize" id="is_normal_fontsize" title="Toggle Font size" style="text-decoration: underline;"><span class="offscreen">Toggle Font size</span><span class="aticon aticon-font" aria-hidden="true"></span></a></li>';
		}
		?>
	</ul>
</div>
<!-- // a11y toolbar -->

