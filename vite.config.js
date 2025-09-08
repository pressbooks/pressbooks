import { createViteConfig } from 'pressbooks-build-tools'
import { resolve } from 'path'

export default createViteConfig({
	input: {
		// Core scripts
		'anchor': resolve(__dirname, 'assets/src/scripts/anchor.js'),
		'applyclass': resolve(__dirname, 'assets/src/scripts/applyclass.js'),
		'book-information': resolve(__dirname, 'assets/src/scripts/book-information.js'),
		'catalog': resolve(__dirname, 'assets/src/scripts/catalog.js'),
		'covergenerator': resolve(__dirname, 'assets/src/scripts/covergenerator.js'),
		'cloner': resolve(__dirname, 'assets/src/scripts/cloner.js'),
		'color-picker': resolve(__dirname, 'assets/src/scripts/color-picker.js'),
		'export': resolve(__dirname, 'assets/src/scripts/export.js'),
		'footnote': resolve(__dirname, 'assets/src/scripts/footnote.js'),
		'ftnref-convert': resolve(__dirname, 'assets/src/scripts/ftnref-convert.js'),
		'latex': resolve(__dirname, 'assets/src/scripts/latex.js'),
		'glossary': resolve(__dirname, 'assets/src/scripts/glossary.js'),
		'glossary-definition': resolve(__dirname, 'assets/src/scripts/glossary-definition.js'),
		'import': resolve(__dirname, 'assets/src/scripts/import.js'),
		'login': resolve(__dirname, 'assets/src/scripts/login.js'),
		'network-managers': resolve(__dirname, 'assets/src/scripts/network-managers.js'),
		'organize': resolve(__dirname, 'assets/src/scripts/organize.js'),
		'post-back-matter': resolve(__dirname, 'assets/src/scripts/post-back-matter.js'),
		'post-visibility': resolve(__dirname, 'assets/src/scripts/post-visibility.js'),
		'post-mathjax': resolve(__dirname, 'assets/src/scripts/post-mathjax.js'),
		'profile': resolve(__dirname, 'assets/src/scripts/profile.js'),
		'quicktags': resolve(__dirname, 'assets/src/scripts/quicktags.js'),
		'search-and-replace': resolve(__dirname, 'assets/src/scripts/search-and-replace.js'),
		'small-menu': resolve(__dirname, 'assets/src/scripts/small-menu.js'),
		'textboxes': resolve(__dirname, 'assets/src/scripts/textboxes.js'),
		'textboxes-legacy': resolve(__dirname, 'assets/src/scripts/textboxes-legacy.js'),
		'theme-lock': resolve(__dirname, 'assets/src/scripts/theme-lock.js'),
		'theme-options': resolve(__dirname, 'assets/src/scripts/theme-options.js'),
		'a11y': resolve(__dirname, 'assets/src/scripts/a11y.js'),
		'export-footnotes': resolve(__dirname, 'assets/src/scripts/export-footnotes.js'),
		'contributors': resolve(__dirname, 'assets/src/scripts/contributors.js'),
		'algolia-search': resolve(__dirname, 'assets/src/scripts/algolia-search.js'),

		// External packages (these were .js() entries in Mix)
		'pressbooks-multiselect': resolve(__dirname, 'node_modules/@pressbooks/multiselect/pressbooks-multiselect.js'),
		'pressbooks-reorderable-multiselect': resolve(__dirname, 'node_modules/@pressbooks/reorderable-multiselect/pressbooks-reorderable-multiselect.js'),

		// SCSS entries - each .sass() call becomes a style entry
		'catalog-styles': resolve(__dirname, 'assets/src/styles/catalog.scss'),
		'colors-pb-styles': resolve(__dirname, 'assets/src/styles/colors-pb.scss'),
		'colors-pb-a11y-styles': resolve(__dirname, 'assets/src/styles/colors-pb-a11y.scss'),
		'covergenerator-styles': resolve(__dirname, 'assets/src/styles/covergenerator.scss'),
		'duet-styles': resolve(__dirname, 'assets/src/styles/duet.css'),
		'export-styles': resolve(__dirname, 'assets/src/styles/export.scss'),
		'export-ui-styles': resolve(__dirname, 'assets/src/styles/admin/export-ui.css'),
		'glossary-definition-styles': resolve(__dirname, 'assets/src/styles/glossary-definition.scss'),
		'login-styles': resolve(__dirname, 'assets/src/styles/login.scss'),
		'metadata-styles': resolve(__dirname, 'assets/src/styles/metadata.scss'),
		'network-managers-styles': resolve(__dirname, 'assets/src/styles/network-managers.scss'),
		'organize-styles': resolve(__dirname, 'assets/src/styles/organize.scss'),
		'pressbooks-styles': resolve(__dirname, 'assets/src/styles/pressbooks.scss'),
		'pressbooks-dashboard-styles': resolve(__dirname, 'assets/src/styles/pressbooks-dashboard.scss'),
		'pressbooks-table-styles': resolve(__dirname, 'assets/src/styles/pressbooks-table.scss'),
		'search-and-replace-styles': resolve(__dirname, 'assets/src/styles/search-and-replace.scss'),
		'style-catalog-styles': resolve(__dirname, 'assets/src/styles/style-catalog.scss'),
		'theme-options-styles': resolve(__dirname, 'assets/src/styles/theme-options.scss'),
		'cloner-styles': resolve(__dirname, 'assets/src/styles/cloner.scss'),
	},

	copyTargets: [
		// Individual file copies
		{
			src: 'node_modules/alpinejs/dist/cdn.min.js',
			dest: 'scripts',
			rename: 'alpine.min.js'
		},
		{
			src: 'node_modules/instantsearch.js/dist/instantsearch.production.min.js',
			dest: 'scripts'
		},
		{
			src: 'node_modules/algoliasearch/dist/algoliasearch-lite.umd.js',
			dest: 'scripts'
		},

		// Directory copies
		{
			src: 'node_modules/@duetds/date-picker/dist/duet/*',
			dest: 'scripts/duet'
		},
		{
			src: 'assets/src/fonts/*',
			dest: 'fonts'
		},
		{
			src: 'assets/src/images/*',
			dest: 'images'
		},
	],

	// Development server configuration (replaces mix.browserSync)
	proxy: {
		'/wp-admin': {
			target: 'https://pressbooks.test',
			changeOrigin: true,
			secure: false
		},
		'/wp-login.php': {
			target: 'https://pressbooks.test',
			changeOrigin: true,
			secure: false
		}
	},
	port: 3100
});
