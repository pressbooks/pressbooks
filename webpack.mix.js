let mix = require('laravel-mix');

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

 mix.setPublicPath('assets/dist')
	.js('assets/src/scripts/anchor.js', 'assets/dist/scripts/anchor.js')
	.js('assets/src/scripts/applyclass.js', 'assets/dist/scripts/applyclass.js')
	.js('node_modules/block-ui/jquery.blockUI.js', 'assets/dist/scripts/blockui.js')
	.js('assets/src/scripts/book-information.js', 'assets/dist/scripts/book-information.js')
	.js('assets/src/scripts/catalog.js', 'assets/dist/scripts/catalog.js')
	.js('assets/src/scripts/color-picker.js', 'assets/dist/scripts/color-picker.js')
	.js('assets/src/scripts/export.js', 'assets/dist/scripts/export.js')
	.js('assets/src/scripts/footnote.js', 'assets/dist/scripts/footnote.js')
	.js('assets/src/scripts/ftnref-convert.js', 'assets/dist/scripts/ftnref-convert.js')
	.js('assets/src/scripts/import.js', 'assets/dist/scripts/import.js')
	.js('node_modules/isotope-layout/dist/isotope.pkgd.js', 'assets/dist/scripts/isotope.js')
	.js('node_modules/jquery-match-height/dist/jquery.matchHeight.js', 'assets/dist/scripts/matchheight.js')
	.js('node_modules/jquery-sticky/jquery.sticky.js', 'assets/dist/scripts/sticky.js')
	.js('node_modules/js-cookie/src/js.cookie.js', 'assets/dist/scripts/js-cookie.js')
	.js('assets/src/scripts/network-managers.js', 'assets/dist/scripts/network-managers.js')
	.js('assets/src/scripts/organize.js', 'assets/dist/scripts/organize.js')
	.js('assets/src/scripts/quicktags.js', 'assets/dist/scripts/quicktags.js')
	.js('assets/src/scripts/search-and-replace.js', 'assets/dist/scripts/search-and-replace.js')
	.js('node_modules/select2/dist/js/select2.js', 'assets/dist/scripts/select2.js')
	.js('node_modules/sidr/dist/jquery.sidr.js', 'assets/dist/scripts/sidr.js')
	.js('assets/src/scripts/small-menu.js', 'assets/dist/scripts/small-menu.js')
	.js('node_modules/tinymce/plugins/table/plugin.js', 'assets/dist/scripts/table.js')
	.js('assets/src/scripts/textboxes.js', 'assets/dist/scripts/textboxes.js')
	.js('assets/src/scripts/theme-options.js', 'assets/dist/scripts/theme-options.js')
	.js('assets/src/scripts/theme-lock.js', 'assets/dist/scripts/theme-lock.js')
	.sass('assets/src/styles/catalog.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/colors-pb.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/custom-css.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/export.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/metadata.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/network-managers.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/organize.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/part.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/pressbooks.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/search-and-replace.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/select2.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/style-catalog.scss', 'assets/dist/styles/')
	.sass('assets/src/styles/theme-options.scss', 'assets/dist/styles/')
	.copyDirectory('assets/src/fonts', 'assets/dist/fonts')
 	.copyDirectory('assets/src/images', 'assets/dist/images')
 	.version()
   .options({
     processCssUrls: false
   });

// Full API
// mix.js(src, output);
// mix.react(src, output); <-- Identical to mix.js(), but registers React Babel compilation.
// mix.extract(vendorLibs);
// mix.sass(src, output);
// mix.standaloneSass('src', output); <-- Faster, but isolated from Webpack.
// mix.fastSass('src', output); <-- Alias for mix.standaloneSass().
// mix.less(src, output);
// mix.stylus(src, output);
// mix.browserSync('my-site.dev');
// mix.combine(files, destination);
// mix.babel(files, destination); <-- Identical to mix.combine(), but also includes Babel compilation.
// mix.copy(from, to);
// mix.copyDirectory(fromDir, toDir);
// mix.minify(file);
// mix.sourceMaps(); // Enable sourcemaps
// mix.version(); // Enable versioning.
// mix.disableNotifications();
// mix.setPublicPath('path/to/public');
// mix.setResourceRoot('prefix/for/resource/locators');
// mix.autoload({}); <-- Will be passed to Webpack's ProvidePlugin.
// mix.webpackConfig({}); <-- Override webpack.config.js, without editing the file directly.
// mix.then(function () {}) <-- Will be triggered each time Webpack finishes building.
// mix.options({
//   extractVueStyles: false, // Extract .vue component styling to file, rather than inline.
//   processCssUrls: true, // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
//   purifyCss: false, // Remove unused CSS selectors.
//   uglify: {}, // Uglify-specific options. https://webpack.github.io/docs/list-of-plugins.html#uglifyjsplugin
//   postCss: [] // Post-CSS options: https://github.com/postcss/postcss/blob/master/docs/plugins.md
// });
