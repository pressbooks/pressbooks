<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<link rel="icon" href="../../favicon.ico">

		<title>Pressbooks v1 RESTful API Documentation</title>

		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

		<!-- Optional theme -->
		<style>
			/*
 * Base structure
 */

			/* Move down content because we have a fixed navbar that is 50px tall */
			body {
				padding-top: 50px;
			}


			/*
			 * Global add-ons
			 */

			.sub-header {
				padding-bottom: 10px;
				border-bottom: 1px solid #eee;
			}

			.navbar-inverse {
				background-color: #b40026;
				color: #fff;
				border: none;
			}
			.navbar-inverse .navbar-brand{
				color: #fff;
			}
			
			.nav>li.active>a:hover{
				background-color:#b40026;
			}
			/*
			 * Sidebar
			 */

			/* Hide for mobile, show later */
			.sidebar {
				display: none;
			}
			@media (min-width: 768px) {
				.sidebar {
					position: fixed;
					top: 51px;
					bottom: 0;
					left: 0;
					z-index: 1000;
					display: block;
					padding: 20px;
					overflow-x: hidden;
					overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
					background-color: #f5f5f5;
					border-right: 1px solid #eee;
				}
			}

			/* Sidebar navigation */
			.nav-sidebar {
				margin-right: -21px; /* 20px padding + 1px border */
				margin-bottom: 20px;
				margin-left: -20px;
			}
			.nav-sidebar > li > a {
				padding-right: 20px;
				padding-left: 20px;
			}
			.nav-sidebar > .active > a {
				color: #fff;
				background-color: #b40026;
			}


			/*
			 * Main content
			 */

			.main {
				padding: 20px;
			}
			@media (min-width: 768px) {
				.main {
					padding-right: 40px;
					padding-left: 40px;
				}
			}
			.main .page-header {
				margin-top: 0;
			}


		</style>
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>

		<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">Pressbooks v1 RESTful API Documentation</a>
				</div>
			</div>
		</nav>

		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-3 col-md-2 sidebar">
					<ul class="nav nav-sidebar">
						<li class="active"><a href="#overview">Overview</a></li>
						<li><a href="#valid-calls">Valid Calls</a></li>
						<li><a href="#response">Response</a></li>
						<li><a href="#parameters">Parameters</a></li>
						<li><a href="#examples">Examples</a></li>
						<li><a href="#errors">Error Messages</a></li>
					</ul>

				</div>
				<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
					<h1 class="page-header">API Documentation</h1>

					<h2 class="sub-header" id="overview">Overview</h2>
					<ul>	
						<li>Endpoint is <code>/api/v1/</code></li>
						<li>Unless otherwise specified, the default format of the response is <code>application/json</code>.</li>
						<li>Only <b>public</b> book information is returned in a response, including only books and posts that are marked as public/published.</li>
					</ul>
					<h2 id="valid-calls">Valid Calls</h2>
					<h3><code>{http://yourdomain.com}/api/v1/</code></h3>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>GET</th>
									<th>Resource</th>
									<th>Parameters</th>
									<th>Implementation Notes</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td></td>
									<td class="text-info">/books</td>
									<td><a href="#titles">titles</a>, <a href="#subjects">subjects</a>, <a href="#authors">authors</a>, <a href="#licenses">licenses</a>, <a href="#keywords">keywords</a>, <a href="#limit">limit</a>, <a href="#offset">offset</a></td>
									<td>Gets information about a collection of books in a Pressbooks instance. <br>
										Limit of 100 returned unless otherwise specified (ie.<code>?limit=0</code>)
									</td>
								</tr>

								<tr>
									<td></td>
									<td class="text-info">/books/{book_id}</td>
									<td><a href="#titles">titles</a>, <a href="#authors">authors</a>, <a href="#licenses">licenses</a>, <a href="#limit">limit</a>, <a href="#offset">offset</a></td>
									<td>Gets information about a specific book. <br>
										If parameters are passed, it returns information about <b>chapters</b> from that book</td>
								</tr>

								<tr>
									<td colspan="4">
										<h3 id="response">Response:</h3>
										<code><pre>{
"success":true,
"data":{
	"3":{
		"book_id":"3",
		"book_url":"http:\/\/localhost\/pressbooks\/dbdesign\/",
		"book_meta":{
			"pb_cover_image":"http:\/\/localhost\/pressbooks\/dbdesign\/assets\/images\/default-book-cover.jpg",
			"pb_book_copyright":"Brad Payne",
			"pb_title":"Database Design",
			"pb_short_title":"short title",
			"pb_subtitle":"subtitle here",
			"pb_author":"Brad Payne",
			"pb_author_file_as":"Payne, Brad",
			"pb_publisher":"publisher here",
			"pb_publisher_city":"publisher city",
			"pb_publication_date":"1401580800",
			"pb_language":"en",
			"pb_copyright_year":"2013",
			"pb_copyright_holder":"Brad Payne",
			"pb_book_license":"cc-by-sa",
			"pb_custom_copyright":"Custom Copyright Notice here",
			"pb_about_140":"Book Tagline here",
			"pb_about_50":"Short Description here",
			"pb_about_unlimited":"Long Description here",
			"pb_editor":"Editor here",
			"pb_keywords_tags":"database, computers, design",
			"pb_hashtag":"#hashtag here",
			"pb_list_price_print":"$15.00",
			"pb_list_price_pdf":"$0.00",
			"pb_list_price_epub":"$0.00",
			"pb_list_price_web":"$0.00",
			"pb_bisac_subject":"Science &amp; Technology, Teaching Methods &amp; Materials, EDUCATION"
		},
		"book_toc":{
			"front-matter":{
				"4":{
					"post_id":4,
					"post_title":"Introduction",
					"post_link":"http:\/\/localhost\/pressbooks\/dbdesign\/front-matter\/introduction\/",
					"post_license":"cc-by",
					"post_authors":"Jack Black"
				}
			},
			"part":[{
				"post_id":3,
				"post_title":"Main Body",
				"post_link":"http:\/\/localhost\/pressbooks\/dbdesign\/part\/main-body\/",
				"chapters":{
					"18":{
						"post_id":18,
						"post_title":"About the Author",
						"post_link":"http:\/\/localhost\/pressbooks\/dbdesign\/chapter\/about-the-author\/",
						"post_license":"cc-by-nd",
						"post_authors":"Ned Flanders"
					},
					"20":{
						"post_id":20,
						"post_title":"Characteristics and Benefits of a Database",
						"post_link":"http:\/\/localhost\/pressbooks\/dbdesign\/chapter\/c-and-b-of-a-database\/",
						"post_license":"cc0",
						"post_authors":"Christopher Hitchens"
					},
				}],
			"back-matter":{
				"7":{
					"post_id":7,
					"post_title":"Appendix",
					"post_link":"http:\/\/localhost\/pressbooks\/dbdesign\/back-matter\/appendix\/",
					"post_license":"cc-by-sa",
					"post_authors":"David Suzuki"
				}
			}
		}
	}
}
}</pre></code>
									</td>
								</tr>							
							</tbody>
						</table>
					</div>
					<h2 id="parameters">Parameters</h2>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Name</th>
									<th>Format</th>
									<th>Acceptable Values</th>
									<th>Description</th>
								</tr>
							</thead>
							<tbody>
								<tr id="titles">
									<td class="text-info">titles</td>
									<td>string</td>
									<td>comma separated values</td>
									<td>limit of 5<br>
										partial string match<br>
										<code>/books?titles=value1,value2</code> searches <b>book</b> titles within a collection, specified at <code>"pb_title"</code><br>
										<code>/books/{book_id}?titles=value1,value2</code> searches <b>chapter</b> titles within a book, specified at <code>"post_title"</code>
									</td>
								</tr>
								<tr id="subjects">
									<td class="text-info">subjects</td>
									<td>string</td>
									<td>comma separated values</td>
									<td>limit of 5<br>
										partial string match<br>
										<code>/books?subjects=value1,value2</code> searches against values found in <code>"pb_bisac_subject"</code></td>
								</tr>
								<tr id="authors">
									<td class="text-info">authors</td>
									<td>string</td>
									<td>comma separated values</td>
									<td>limit of 5<br>
										partial string match<br>
										<code>/books?authors=brad,jack</code> searches for <b>book</b> authors within a collection, specified at <code>"pb_author"</code><br>
										<code>/books/{book_id}?authors=brad,jack</code> searches for <b>chapter</b> authors within a book, specified at <code>"post_authors"</code></td> 
								</tr>								
								<tr id="licenses">
									<td class="text-info">licenses</td>
									<td>string</td>
									<td>
									cc-by<br> 
									cc-by-sa<br>
									cc-by-nc<br>
									cc-by-nc-sa<br>
									cc-by-nd<br>
									public-domain<br>
									all-rights-reserved</td>
									<td>limit of 5<br>
										exact string match<br>
										<code>/books?licenses=cc-by,cc-by-sa</code> searches for <b>book</b> licenses within a collection, specified at <code>"pb_book_license"</code><br>
										<code>/books/{book_id}?authors=brad,jack</code> searches for <b>chapter</b> licenses within a book, specified at <code>"post_license"</code></td>
								</tr>								
								<tr id="keywords">
									<td class="text-info">keywords</td>
									<td>string</td>
									<td>comma separated values</td>
									<td>limit of 5<br>
									partial string match<br>
									<code>/books?keywords=value1,value2</code> searches against values found in <code>"pb_keywords_tags"</code></td>
								</tr>								
								<tr id="limit">
									<td class="text-info">limit</td>
									<td>integer</td>
									<td>positive or negative integers</td>
									<td>0 = unlimited results<br>
										positive integer returns results starting from the beginning<br>
									negative integer returns results starting from the end</td>
								</tr>								
								<tr id="offset">
									<td class="text-info">offset</td>
									<td>integer</td>
									<td>positive or negative integer</td>
									<td>positive integer returns results, offset from the beginning<br>
									negative integer returns results, offset from the end</td>
								</tr>
								<tr id="json">
									<td class="text-info">json</td>
									<td>integer</td>
									<td>1</td>
									<td>json response is returned whether this parameter is specified, or not<br>
									<code>?json=1</code> returns a json response</td>
								</tr>
								<tr id="xml">
									<td class="text-info">xml</td>
									<td>integer</td>
									<td>1</td>
									<td><code>?xml=1</code> returns an xml response</td>
								</tr>
							</tbody>
						</table>
					</div>
					<h2 id="examples">Examples</h2>
					<ul>
						<li><code>{http://yourdomain}/api/v1/books?subjects=biology,technology&keywords=education&limit=3</code><br>
							returns all <b>books</b> in a collection with either subject 'biology' <b>OR</b> 'technology' <b>AND</b> the keyword 'education', limit the results to 3 starting from the beginning (only return the first 3 results).</li>
						<li><code>{http://yourdomain}/api/v1/books/3?titles=data&licenses=cc-by,cc-by-sa&offset=-1</code><br>
							returns all <b>chapters</b> from the book with id = 3 whose title contains the substring 'data' <b>AND</b> is licensed under 'cc-by' <b>OR</b> 'cc-by-sa', offset from the end (return everything but the last result).</li>
					</ul>
					<h2 id="errors">Error Messages</h2>
					<p>All error messages are returned in json format and refer back to this documentation. Errors will  return <code>success:false</code>. For example: <code><pre>{
"success":false,
"data":{
	"messages":"There are no records that can be returned with the request that was made",
	"documentation":"\/api\/v1\/docs"
	}
}</pre></code></p>
					
					
				</div>
			</div>
		</div>
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script>
		$(document).ready(function () {
			$('.nav-sidebar li a').click(function(e) {
				$('.nav-sidebar li').removeClass('active');

				var $parent = $(this).parent();

				if (!$parent.hasClass('active')) {
					$parent.addClass('active');
				}

			});
		});
		</script>
		<!-- Latest compiled and minified JavaScript -->
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	</body>
</html>
