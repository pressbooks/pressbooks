let path = require( 'path' );

let mix = require( 'laravel-mix' );

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

const assets = 'assets';
const dist = `${assets}/dist`;
const templates = 'templates';

// BrowserSync
mix.browserSync( {
	host: 'localhost',
	proxy: 'https://pressbooks.test/wp-login.php',
	port: 3100,
	files: [
		'*.php',
		`${templates}/**/*.php`,
		`${dist}/styles/*.css`,
		`${dist}/scripts/*.js`,
	],
} );

mix
	.version()
	.options( { processCssUrls: false } )
	.setPublicPath( path.join( 'assets', 'dist' ) )
	.js( 'assets/src/scripts/anchor.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/applyclass.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/book-information.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/catalog.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/covergenerator.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/cloner.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/color-picker.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/export.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/footnote.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/ftnref-convert.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/latex.js', 'assets/dist/scripts' )
	.js( 'assets/src/scripts/glossary.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/glossary-tooltip.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/import.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/login.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/network-managers.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/organize.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/post-back-matter.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/post-visibility.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/post-mathjax.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/quicktags.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/search-and-replace.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/small-menu.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/textboxes.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/textboxes-legacy.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/theme-lock.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/theme-options.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/a11y.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/export-footnotes.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/contributors.js', 'assets/dist/scripts/' )
	.scripts(
		'node_modules/event-source-polyfill/src/eventsource.js',
		'assets/dist/scripts/eventsource.polyfill.js'
	)
	.scripts(
		'node_modules/pagedjs/dist/paged.polyfill.js',
		'assets/dist/scripts/paged.polyfill.js'
	)
	.scripts(
		'node_modules/block-ui/jquery.blockUI.js',
		'assets/dist/scripts/blockui.js'
	)
	.scripts(
		'node_modules/isotope-layout/dist/isotope.pkgd.js',
		'assets/dist/scripts/isotope.js'
	)
	.scripts(
		'node_modules/jquery-match-height/dist/jquery.matchHeight.js',
		'assets/dist/scripts/matchheight.js'
	)
	.scripts(
		'node_modules/jquery-sticky/jquery.sticky.js',
		'assets/dist/scripts/sticky.js'
	)
	.scripts(
		'node_modules/js-cookie/dist/js.cookie.js',
		'assets/dist/scripts/js-cookie.js'
	)
	.scripts(
		'node_modules/select2/dist/js/select2.js',
		'assets/dist/scripts/select2.js'
	)
	.scripts(
		'node_modules/sidr/dist/jquery.sidr.js',
		'assets/dist/scripts/sidr.js'
	)
	.scripts(
		'node_modules/tinymce/plugins/table/plugin.js',
		'assets/dist/scripts/table.js'
	)
	.sass( 'assets/src/styles/catalog.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/colors-pb.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/colors-pb-a11y.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/covergenerator.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/export.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/glossary-tooltip.scss', 'assets/dist/styles' )
	.sass( 'assets/src/styles/login.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/metadata.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/network-managers.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/organize.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/pressbooks.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/search-and-replace.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/select2.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/style-catalog.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/theme-options.scss', 'assets/dist/styles/' )
	.copyDirectory( 'assets/src/fonts', 'assets/dist/fonts' )
	.copyDirectory( 'assets/src/images', 'assets/dist/images' );
