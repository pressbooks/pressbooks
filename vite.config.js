import { createWpViteConfig } from 'pressbooks-build-tools'
import { resolve } from 'path'

export default createWpViteConfig({
	input: {
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
		'pressbooks': resolve(__dirname, 'assets/src/scripts/pressbooks.js'),
		'pressbooks-select': resolve(__dirname, 'assets/src/scripts/webcomponents/pressbooks-select.js'),
		'pressbooks-reorderable-multiselect': resolve(__dirname, 'assets/src/scripts/webcomponents/pressbooks-reorderable-multiselect.js'),
		'dashboard': resolve(__dirname, 'assets/src/scripts/dashboard.js'),
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
		'login-styles': resolve(__dirname, 'assets/src/styles/login.scss'),
		'colors-pb-styles': resolve(__dirname, 'assets/src/styles/colors-pb.scss'),
		'colors-pb-a11y-styles': resolve(__dirname, 'assets/src/styles/colors-pb-a11y.scss'),
	},
	copyTargets: [
		{
			src: 'assets/src/fonts/*',
			dest: 'fonts',
		},
		{
			src: 'node_modules/tinymce/plugins/table/plugin.js',
			dest: 'scripts',
		},
		{
			src: 'node_modules/block-ui/jquery.blockUI.js',
			dest: 'scripts',
		}
	],
	outDir: 'assets/dist'
});
