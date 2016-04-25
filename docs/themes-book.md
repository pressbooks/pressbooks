# Book Themes

In addition to being WordPress compatible, a book theme must have the following directories and files. These files are
used by our PDF & EPUB export modules. The files can be empty (`script.js`, for example), but must exist.

We use the SCSS variant of [SASS](http://sass-lang.com) for our stylesheets, which allows us dynamically modify font
stacks and other variables based on user preferences.

	├── _fonts-epub.scss
	├── _fonts-prince.scss
	├── _fonts-web.scss
	├── export
	│   ├── epub
	│   │   ├── images
	│   │   │   └── *
	│   │   └── style.scss
	│   └── prince
	│       ├── images
	│       │   └── *
	│       ├── script.js
	│       └── style.scss
	├── style.scss


## Fonts

`_fonts-epub.scss`, `_fonts-prince.scss`, `_fonts-web.scss` are SCSS font stack definitions for EPUB, PDF and WEB.

Example code in `_fonts-prince.scss`:

```
@import '_font-stack-prince'; // Dynamically generated

$serif-prince: serif !default;
$sans-serif-prince: sans-serif !default;

$font-1: "Tinos", Georgia, $serif-prince;
$font-2: 'Lato', Helvetica, Arial, $sans-serif-prince;

@import 'LatoFont', 'TinosFont';
```

Most themes have at least two font stacks. `$font-1` must be the body font, and `$font-2` must be the header font.

The `@import` rule loads a SCSS `_font-stack-{TYPE}` file which is dynamically generated based on the Global Typography
theme option. The `_font-stack-{TYPE}` is built from partials found in ./pressbooks/assets/scss/fonts/*.* - These files,
in combination with the `$serif-epub`, `$sans-serif-epub`, `$serif-prince`, `$sans-serif-prince`, `$serif-web` and
`$sans-serif-web` variables, allows us to dynamically add support for non-Latin character sets.

Each `style.scss` file must import the appropriate font stack(s). Example for ./export/prince/style.scss:

```
@import 'fonts-prince';
```

Font files are located in `./themes-book/pressbooks-book/fonts/`

The paths for all such fonts in your Custom CSS need to be as follows:

```
@font-face {
  // ...
  src: url(themes-book/pressbooks-book/fonts/YourFont.ttf) format("truetype");
}
```


## PDF (Prince) Best Practices

1. Use CSS and JavaScript as outlined in the [Prince user guide](http://www.princexml.com/doc/).

## Ebook Best Practices

1. CSS must validate as CSS 2.01 with absolutely **no** errors. See:

	+ [W3C CSS Validation Service](http://jigsaw.w3.org/css-validator/)
	+ [Open Publication Structure (OPS) Style Sheets](http://idpf.org/epub/20/spec/OPS_2.0.1_draft.htm#Section3.0)

	Why? Adobe Digital Editions (v1.7.2), a licensed technology used by other vendors, will not render CSS with errors.

2. Avoid nested styles.

	Why? Some CSS styles which are declared in nested form do not work well with Mobi7.

3. More Mobi7 superstition.

	Newer Kindles (KF8) work pretty good! Unfortunately there are no CSS standards for old Kindles (Mobi7). Mobi7
	styling is all done inline. The problems you will encounter are when kindlegen converts CSS to inline HTML for
	backwards compatibility.

	`font-size`, `font-weight`, and `font-*` on `.someclass p`, `.someclass h5`, and `.someclass *random*` will mess up
	Mobi7 ouput very badly. You can solve this by making valid CSS 2.1 too complicated for the Mobi7 converter to
	figure  out. For example, defining a CSS selector that only applies to a node that has multi-class CSS inheritance,
	e.g. `.ugc.ugc-chapter h5`, or by using the `>` symbol.

	This is "throw salt over your shoulder" and "don't walk on sidewalk cracks" advice. The bugs you will encounter
	with  Mobi7 conversion are time consuming and arbitrary. Expect a ton of exports, a ton of trial and error. You can
	save  some time by unpacking MOBI files and looking directly at the Mobi7 HTML with a tool like:
	[Mobi Unpack](http://www.mobileread.com/forums/showthread.php?t=61986).


4. We test in:

	 + Calibre
	 + Firefox Epub reader
	 + iBooks
	 + Adobe Digital Editions 1.7.2
	 + Nook Simple Touch
	 + Kindle Preview
	 + $69 Kindle


## Theme Options

Certain style elements can be overridden by the user under My Books → __YOUR_BOOK__ → Appearance → Theme Options.

These options are in the book theme, not in the plugin. As a book theme designer you are required to created the
following functions in your functions.php file:

+ `pressbooks_theme_options_display()`
+ `pressbooks_theme_options_summary()`

When it comes time to exporting, the following WordPress filters are available:

 + `pb_pdf_css_override`
 + `pb_epub_css_override`
 + `pb_pdf_hacks`
 + `pb_epub_hacks`

The rule of thumb is that *all* PDF styling should be done using CSS.

In contrast, the HTML piped to the Epub exporter may include HTML hacks to work around bugs found in older Ebook
readers. As older hardware is deprecated we expect this situation to improve.

Examples:

```
	function pressbooks_theme_pdf_css_override( $css ) {
		return $css; // string
	}
	add_filter( 'pb_pdf_css_override', 'pressbooks_theme_pdf_css_override' );

	function pressbooks_theme_ebook_css_override( $css ) {
	  return $css; // string
	}
	add_filter( 'pb_epub_css_override', 'pressbooks_theme_ebook_css_override' );

	function pressbooks_theme_pdf_hacks( $hacks ) {
	  return $hacks; // array of options passed back to the export module
	}
	add_filter( 'pb_pdf_hacks', 'pressbooks_theme_pdf_hacks' );
	
	function pressbooks_theme_ebook_hacks( $hacks ) {
	  return $hacks; // array of options passed back to the export module
	}
	add_filter( 'pb_epub_hacks', 'pressbooks_theme_ebook_hacks' );
```

More functions available to book designers. See: pressbooks/functions.php

 + `pb_get_book_information()`
 + `pb_get_book_structure()`
 + `pb_get_prev()`
 + `pb_get_next()`
 + `pb_get_first()`
 + `pb_decode()`
 + `pb_is_custom_theme()`
 + `pb_get_custom_stylesheet_url()`
 + `pb_get_chapter_number( $post_name )`
 + `pb_thumbify( $thumb, $path )`

### The HTML

Top level elements (i.e. children nodes of <body>):

`<div id="X">` where X can be:

 + cover-image
 + half-title-page
 + title-page
 + copyright-page
 + toc

`<div class="X" id="post_name">` where X can be:

 + front-matter
 + part
 + chapter
 + back-matter


### Typical structure for _front-matter_ (note: "ugc" stands for "User Generated Content")

```
<div class="front-matter subclass" id="post_name">
	  <div class="front-matter-title-wrap">
	    <h3 class="front-matter-number">123</h3>
	    <h1 class="front-matter-title">Title</h1>
	  </div>
	  <div class="ugc front-matter-ugc">
	    <!-- Optional -->
	    <h2 class="chapter-author"></h2>
	    <h2 class="chapter-subtitle"></h2>
	    <h6 class="short-title"></h2>
	    <!-- WordPress, post_content -->
	  </div>
	  <div class="endnotes"><!-- h3, ol --></div>
	</div>
```

### Typical structure for _part_

```
	<div class="part" id="post_name">
	  <div class="part-title-wrap">
	    <h3 class="part-number">123</h3>
	    <h1 class="part-title">Title</h1>
	  </div>
	</div>
```

### Typical structure for _chapter_

```
	<div class="chapter" id="post_name">
	  <div class="chapter-title-wrap">
	    <h3 class="chapter-number">123</h3>
	    <h2 class="chapter-title">Title</h2>
	  </div>
	  <div class="ugc chapter-ugc">
	    <!-- Optional -->
	    <h2 class="chapter-author"></h2>
	    <h2 class="chapter-subtitle"></h2>
	    <h6 class="short-title"></h2>
	    <!-- WordPress, post_content -->
	  </div>
	  <div class="endnotes"><!-- h3, ol--></div>
	</div>
```

### Typical structure for _back-matter_

```
<div class="back-matter subclass" id="post_name">
	  <div class="back-matter-title-wrap">
	    <h3 class="back-matter-number">123</h3>
	    <h1 class="back-matter-title">Title</h1>
	  </div>
	  <div class="ugc back-matter-ugc">
	    <!-- WordPress, post_content -->
	  </div>
	  <div class="endnotes"><!-- h3, ol --></div>
	</div>
```
