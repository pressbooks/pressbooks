<!-- tabs start -->
<div id="tabs">
	<ul>
		<li><a href="#tabs-1">Read <span class="dashicons"></span></a></li>
		<li><a href="#tabs-2">History <span class="dashicons"></span></a></li>
	</ul>

	<div id="tabs-1">
		<p>some content</p>
	</div>

	<div id="tabs-2">
		<?php
		echo pressbooks_post_revision_display($post);
		?>
	</div>

</div>
