# [Pressbooks](https://pressbooks.org/)

[![Packagist](https://img.shields.io/packagist/l/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks.svg)](https://github.com/pressbooks/pressbooks/releases/latest/)
[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)

[![Packagist](https://img.shields.io/packagist/dt/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Build Status](https://img.shields.io/github/checks-status/pressbooks/pressbooks/dev)](https://travis-ci.org/pressbooks/pressbooks)
[![Code Coverage](https://codecov.io/gh/pressbooks/pressbooks/branch/dev/graph/badge.svg)](https://codecov.io/gh/pressbooks/pressbooks)
[![Translate Pressbooks](https://img.shields.io/badge/dynamic/json.svg?label=translated&url=https%3A%2F%2Ftenpercent.now.sh%2F%3Forganization%3Dpressbooks%26project%3Dpressbooks&query=%24.status&colorB=e05d44&suffix=%25)](https://www.transifex.com/pressbooks/pressbooks/translate/)

[Pressbooks](https://pressbooks.org) is a book content management system which allows people to publish webbooks and produce exports in a variety of formats, including EPUB, accessible PDF, print-ready [PDF][pdf], and various XML flavours. [Pressbooks](https://pressbooks.org) is built on top of [WordPress Multisite](https://wordpress.org/support/article/glossary/#multisite), and makes significant changes to:

* admin interface (customized for books and other structured documents, like
  magazines, journals, reports, etc.);
* web presentation layer (again, customized for books and structured documents);
  and
* export routines.

Pressbooks is free software, released under the [GPL v3.0 or later](https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md) license.

Our webbooks and EPUB/[PDF][pdf] exports are all driven by HTML + CSS. XML outputs have no styling.

[pdf]: https://docraptor.com/blog/docraptor-vs-princexml/ "Note: we use the non-free software PrinceXML to produce PDF exports."

## Requirements

Pressbooks works with PHP 7.3 and WordPress 5.9. Lower versions are not supported.

## Installing the Plugin

Pressbooks is not for use on an existing blog. Instead it should be used with a fresh, [multisite WordPress installation](https://wordpress.org/support/article/glossary/#multisite).

To install Pressbooks on your site, download the [latest release](https://github.com/pressbooks/pressbooks/releases/latest) and follow our [installation instructions](https://docs.pressbooks.org/installation). 

You may want to try [Pressbooks.com](https://pressbooks.com/self-publishers/) before deciding whether or not you wish to host and maintain your own instance of Pressbooks. We can also [host and maintain an instance of Pressbooks for you](https://pressbooks.com/for-educational-institutions/).

## Contributor guidelines

Developers who are interested in contributing to our project should consult our ["Contributing"](.github/CONTRIBUTING.md) guidelines and the developer guides published on our [documentation website](https://docs.pressbooks.org).

## Disclaimers

The Pressbooks plugin is supplied "as is" and all use is at your own risk.
