(function($) {

    $(document).ready(function() {

        // Book info page Sharrre buttons
        $('#twitter').sharrre({
			  share: {
			    twitter: true
			  },
			  enableHover: false,
			  enableTracking: true,
			  buttons: { twitter: {via: 'Pressbooks'}},
			  click: function(api, options){
			    api.simulateClick();
			    api.openPopup('twitter');
			  }
			});
		$('#facebook').sharrre({
			  share: {
			    facebook: true
			  },
			  enableHover: false,
			  enableTracking: true,
			  click: function(api, options){
			    api.simulateClick();
			    api.openPopup('facebook');
			  }
			});
		$('#googleplus').sharrre({
			  share: {
			    googlePlus: true
			  },
			  enableHover: false,
			  enableTracking: true,
			  urlCurl: 'wp-content/plugins/pressbooks/themes-book/pressbooks-book/lib/sharrre/sharrre.php',
			  click: function(api, options){
			    api.simulateClick();
			    api.openPopup('googlePlus');
			  }
			});
			
			


    }); //End of $(document).ready()

})(jQuery); //End of ( function( $ ) {