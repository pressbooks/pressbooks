# [Pressbooks](https://pressbooks.org/)

[![Packagist](https://img.shields.io/packagist/l/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks.svg)](https://github.com/pressbooks/pressbooks/releases/latest/)
[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)

[![Packagist](https://img.shields.io/packagist/dt/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Build Status](https://travis-ci.org/pressbooks/pressbooks.svg?branch=dev)](https://travis-ci.org/pressbooks/pressbooks)
[![Code Coverage](https://codecov.io/gh/pressbooks/pressbooks/branch/dev/graph/badge.svg)](https://codecov.io/gh/pressbooks/pressbooks)
[![Translate Pressbooks](https://img.shields.io/badge/dynamic/json.svg?label=translated&url=https%3A%2F%2Ftenpercent.now.sh%2F%3Forganization%3Dpressbooks%26project%3Dpressbooks&query=%24.status&colorB=e05d44&suffix=%25)](https://www.transifex.com/pressbooks/pressbooks/translate/)


[Pressbooks](http://pressbooks.org) is a book content management system which
produces multiple formats: ebooks, webbooks, print-ready [PDF][pdf], and various
XML flavours. [Pressbooks](http://pressbooks.org) is built on top of
[WordPress Multisite](http://codex.wordpress.org/Glossary#Multisite), and makes
significant changes to:

* admin interface (customized for books and other structured documents, such as
  magazines, journals, reports, etc.);
* web presentation layer (again, customized for books and structured documents);
  and
* export routines.

Pressbooks is free software, released under the
[GPL v3.0 or later](https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md) license.

Our web/ebook and [PDF][pdf] exports are all driven by HTML + CSS. XML outputs have
no styling.

[pdf]: https://docraptor.com/blog/docraptor-vs-princexml/ "Note: we use the non-free software PrinceXML for PDF export."

## Important!

* Do **_NOT_** use Pressbooks with an existing WordPress site.
* Please use with a **_FRESH_** install of
  [WP **_MULTISITE_**](http://codex.wordpress.org/Glossary#Multisite).
* If this makes you nervous, please use our free site:
  [Pressbooks](http://pressbooks.com), or contact us.

## Try pressbooks.com

While Pressbooks is open source, we recommend you try
[Pressbooks.com](http://pressbooks.com) before deciding whether or not you wish
to host and maintain your own instance. We can also host and maintain an
instance for you.

## Installing the Plugin

Pressbooks is not for use on an existing blog. Instead it should be used with a
fresh, multisite WordPress install.

To install Pressbooks on your site, download the
[latest release](https://github.com/pressbooks/pressbooks/releases/latest). If
you need to install Pressbooks for development, please see the
["Contributing"](.github/CONTRIBUTING.md) guide.

## Requirements

Pressbooks works with PHP 7.1 and WordPress 5.2.3. Lower versions are not
supported.

## Disclaimers

The Pressbooks plugin is supplied "as is" and all use is at your own risk.

## More Details

Visit our [documentation website](https://docs.pressbooks.org) for
[installation instructions](https://docs.pressbooks.org/installation) etc.
