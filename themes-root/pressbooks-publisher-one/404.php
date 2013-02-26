<?php get_header(); ?>

<div id="content" class="error-page">
 <h2>Error 404</h2>
 <p><strong>Sorry what you are looking for doesn't exist.</strong><br />
</p>
<dl>
	<dt>You can try the following:</dt>
    	<dd> Going <a href="<?php echo home_url(); ?>">HOME</a> 
 		or try doing a Search.</dd>
 </dl>

 
     <form id="searchform" method="get" action="<?php echo home_url();  ?>">
	    <div>
		    <input type="text" name="s" id="s" size="25" />
		    <input type="submit" value="<?php _e('Search', 'pressbooks' ); ?>" id="error-search" />
	    </div>
	    </form>

 
</div> <!-- end #content -->


				


<?php get_footer(); ?>