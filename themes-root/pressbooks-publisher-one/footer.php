
	        	

    </div> <!-- end #canvas -->
         
      <div id="footer">
      	<div class="footer-inner">
		<?php if (is_active_sidebar( 'footer_content')): ?>
	 	    <?php dynamic_sidebar( 'footer_content' ); ?>
	 	<?php endif; ?>
       <p id="copyright">Copyright &copy; <?php echo date('Y');?>  <?php bloginfo('name'); ?> is powered by <a href="http://pressbooks.com/">PressBooks.com</a></p>
      	</div><!-- end .footer-inner -->
      </div><!-- end #footer -->
      
 
        <?php wp_footer(); ?>
  </body>
</html>