<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

// var_dump( $catalog->get() );

$src = PB_PLUGIN_URL . 'themes-root/pressbooks-publisher-one/';

?>
<!DOCTYPE html>

<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js">
</script>
<![endif]-->

<head>
	<meta charset='UTF-8'>
	<title>Catalog Page</title>
	<link rel="stylesheet" type="text/css" href="<?php echo $src; ?>style-catalog.css" />
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,400italic' rel='stylesheet' type='text/css'>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js" type="text/javascript"></script>
	<script src="<?php echo $src; ?>js/jquery.equalizer.min.js" type="text/javascript"></script>

	<script type="text/javascript">
		// <![CDATA[
		$(function () {
			$('#catalog-content').equalizer({ columns: '> div', min: 165 });
		});
		// ]]>
	</script>
</head>
<body>

<div class="catalog-wrap">

	<div class="catalog-sidebar">
		<img class="catalog-logo" src="<?php echo $src; ?>images/tascha-logo.png" alt="tascha-logo" width="100" height="99"/>
		<p class="about-blurb">The Technology & Social Change Group at the University of Washington Information School explores the design, use, and effects
			of information and communication technologies in communities facing social and economic challenges.</p>
		<a class="link-more" href="#">Learn more about TASCHA &raquo;  </a>

		<h3>Research by Area</h3>
		<ul>
			<li><a href="#" class="active">Accessibility & Technology</a></li>
			<li><a href="#" >Civil Society 2.0</a></li>
			<li><a href="#" >Crisis Informatics</a></li>
			<li><a href="#" >Digital Inclusion</a></li>
			<li><a href="#" >Employability</a></li>
			<li><a href="#" >Future of Libraries</a></li>
		</ul>

		<h3>Research By Project</h3>
		<ul>
			<li><a href="#" class="active">Cost Benefit Analysis in Chile</a></li>
			<li><a href="#" >e-Inclusion actors in the European Union: Theories and frameworks</a></li>
			<li><a href="#" >Inclusion in the European Union | Mapping actors</a></li>
			<li><a href="#" >Employability Evidence Narratives</a></li>
		</ul>


	</div><!-- end catalog-sidebar -->

	<!-- Books! -->
	<div class="catalog-content" id="catalog-content">

		<h1>Catalog</h1>


		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->

		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/default-book-cover.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>This is a really long book title, for those times when you need it</h2>
				<p><a href="#">Longauthorfirst Longauthorlastname</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->


		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->


		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/default-book-cover.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->

		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/short-book.jpg" alt="book-cover" width="225" height="150" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->

		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/short-book.jpg" alt="book-cover" width="225" height="150" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>This is a really long book title, for those times when you need it. This one is reaaaaaaaaaaaaaallllly long..........</h2>
				<p><a href="#">Longauthorfirst Longauthorlastname</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->


		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->

		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->
		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->
		<div class="book-data">
			<div class="book">
				<p class="book-description"><a href="#">They were sparing of the Heat-Ray that night, either because they had but a limited supply of material for its production or because they did not wish to destroy the country but only to crush and overawe the opposition they had aroused.  In the latter aim they certainly succeeded.  Sunday night was the end of the organized opposition. <span href="#" class="book-link">&rarr;</span></a></p>
				<img src="<?php echo $src; ?>images/fake-book.jpg" alt="book-cover" width="225" height="300" />
			</div><!-- end .book -->

			<div class="book-info">
				<h2>Book Title</h2>
				<p><a href="#">Author Name</a></p> <!-- I'm assuming here we are linking to Author's about page -->
			</div><!-- end book-info -->
		</div><!-- end .book-data -->


	</div>	<!-- end .catalog -->

</div><!-- end .catalog-wrap -->

</body>
</html>