# [Pressbooks](https://pressbooks.org/)

[![Build Status](https://travis-ci.org/pressbooks/pressbooks.svg?branch=dev)](https://travis-ci.org/pressbooks/pressbooks) [![Code Coverage](https://codecov.io/gh/pressbooks/pressbooks/branch/dev/graph/badge.svg)](https://codecov.io/gh/pressbooks/pressbooks) [![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks.svg)](https://github.com/pressbooks/pressbooks/releases/latest/) [![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)

[Pressbooks](http://pressbooks.com) is a book content management system which exports in multiple formats: ebooks, webbooks, print-ready [PDF][], and various XML flavours. [Pressbooks](http://pressbooks.com) is built on top of [WordPress Multisite](http://codex.wordpress.org/Glossary#Multisite), and makes significant changes to:
  * admin interface (customized for books and other structured documents, such as magazines, journals, reports, etc.);
  * web presentation layer (again, customized for books and structured documents); and
  * export routines.

Pressbooks is free software, released under the GPL v.2.0 license.

Our web/ebook and [PDF][] exports are all driven by HTML+CSS. XML outputs have no styling.

  [PDF]: http://pressbooks.com/prince        "Note: we use the non-free software Prince XML for PDF export."


Important!
----------

 * Do ___NOT___ use Pressbooks with an existing WordPress site.
 * Please use with a ___FRESH___ install of [WP ___MULTISITE___](http://codex.wordpress.org/Glossary#Multisite).
 * If this makes you nervous, please use our free site: [Pressbooks](http://pressbooks.com), or contact us.


Try pressbooks.com
------------------

While Pressbooks is open source, we recommend you try [Pressbooks.com](http://pressbooks.com) before deciding whether or not you wish to host and maintain your own instance. We can also host and maintain an instance for you.

Installing the Plugin
---------------------

Pressbooks is not for use on an existing blog. Instead it should be used with a fresh, multisite WordPress install.

To install Pressbooks on your site, download the [latest release](https://github.com/pressbooks/pressbooks/releases/latest). If you need to install Pressbooks for development, please see the ["Contributing"](.github/CONTRIBUTING.md) guide.

Requirements
------------

Pressbooks works with PHP 5.6.X and WordPress 4.7.3. Lower versions are not supported.

Disclaimers
-----------

The Pressbooks plugin is supplied "as is" and all use is at your own risk.

More Details
------------

See [readme.txt](https://github.com/pressbooks/pressbooks/blob/dev/readme.txt) for installation details.
