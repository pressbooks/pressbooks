# Pressbooks
Contributors: Pressbooks <code@pressbooks.com>
Tags: ebooks, publishing, webbooks
Requires at least: 6.1.1
Tested up to: 6.1.1
Stable tag: 6.5.0
Requires PHP: 8.0
License: GPL v3.0 or later
License URI: https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md

Pressbooks is an open source book publishing tool built on a WordPress multisite platform.

## Description 

[![Packagist](https://img.shields.io/packagist/l/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks.svg)](https://github.com/pressbooks/pressbooks/releases/latest/)
[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)

[![Packagist](https://img.shields.io/packagist/dt/pressbooks/pressbooks.svg)](https://packagist.org/packages/pressbooks/pressbooks)
[![Code Coverage](https://codecov.io/gh/pressbooks/pressbooks/branch/dev/graph/badge.svg)](https://codecov.io/gh/pressbooks/pressbooks)
[![Translate Pressbooks](https://img.shields.io/badge/dynamic/json.svg?label=translated&url=https%3A%2F%2Ftenpercent.now.sh%2F%3Forganization%3Dpressbooks%26project%3Dpressbooks&query=%24.status&colorB=e05d44&suffix=%25)](https://www.transifex.com/pressbooks/pressbooks/translate/)

[Pressbooks](https://pressbooks.org) is an open source book publishing tool built on a WordPress multisite platform. Pressbooks outputs books in multiple formats, including PDF, EPUB, web, and a variety of XML flavours, using a theming/templating system, driven by CSS. Pressbooks is free software, released under the [GPL v3.0 or later](https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md) license.

Our webbooks and EPUB/[PDF][pdf] exports are all driven by HTML + CSS. XML outputs have no styling.

[pdf]: https://docraptor.com/prince "Note: we use the non-free software PrinceXML to produce PDF exports."

## Requirements

Pressbooks works with PHP 8.0 and WordPress 6.1.1. Lower versions are not supported.

## Installing the Plugin

Pressbooks is not for use on an existing blog. Instead it should be used with a fresh, [multisite WordPress installation](https://wordpress.org/support/article/glossary/#multisite).

To install Pressbooks on your site, download the [latest release](https://github.com/pressbooks/pressbooks/releases/latest) and follow our [installation instructions](https://pressbooks.org/user-docs/installation/). 

You may want to try [Pressbooks.com](https://pressbooks.com/self-publishers/) before deciding whether or not you wish to host and maintain your own instance of Pressbooks. We can also [host and maintain an instance of Pressbooks for you](https://pressbooks.com/for-educational-institutions/).

## Contributor guidelines

Developers who are interested in contributing to our project should consult our ["Contributing"](.github/CONTRIBUTING.md) guidelines and the developer guides published on our [documentation website](https://pressbooks.org/dev-docs/).

## Disclaimers

The Pressbooks plugin is supplied "as is" and all use is at your own risk.

## Changelog
### 6.5.0
* See: https://github.com/pressbooks/pressbooks/releases/tag/6.5.0
* Full release history available at: https://github.com/pressbooks/pressbooks/releases

## Upgrade Notices
### 6.4.0
* Pressbooks 6.4.o requires PHP >= 8.0
* Pressbooks 6.4.0 requires [WordPress 6.1.1](https://wordpress.org/support/wordpress-version/version-6-1-1/)

### 6.0.0
* Pressbooks 6.0.0 requires [WordPress 6.0.2](https://wordpress.org/support/wordpress-version/version-6-0-2/)

### 5.34.1
* Pressbooks 5.34.1 requires [WordPress 5.9.3](https://wordpress.org/support/wordpress-version/version-5-9-3/)
* Pressbooks 5.34.1 requires [McLuhan >= 2.18.1](https://github.com/pressbooks/pressbooks-book/)

### 5.34.0
* Pressbooks 5.34.0 requires [McLuhan >= 2.18.0](https://github.com/pressbooks/pressbooks-book/)
* Pressbooks 5.34.0 requires PHP >= 7.4

### 5.33.0
* Pressbooks 5.33.0 requires [McLuhan >= 2.17.0](https://github.com/pressbooks/pressbooks-book/)

### 5.32.0
* Pressbooks 5.32.0 requires [WordPress 5.9](https://wordpress.org/support/wordpress-version/version-5-9/)
* Pressbooks 5.32.0 requires [McLuhan >= 2.16.0](https://github.com/pressbooks/pressbooks-book/)

### 5.31.0
* Pressbooks 5.31.0 requires [McLuhan >= 2.15.0](https://github.com/pressbooks/pressbooks-book/)

### 5.30.0
* Pressbooks 5.30.0 requires [WordPress 5.8.2](https://wordpress.org/support/wordpress-version/version-5-8-2/)
* Pressbooks 5.30.0 requires [McLuhan >= 2.14.0](https://github.com/pressbooks/pressbooks-book/)

### 5.27.0
* Pressbooks 5.27.0 requires [WordPress 5.8.1](https://wordpress.org/support/wordpress-version/version-5-8-1/)
* Pressbooks 5.27.0 requires [McLuhan >= 2.13.0](https://github.com/pressbooks/pressbooks-book/)

### 5.25.0
* Pressbooks 5.25.0 requires [WordPress 5.8](https://wordpress.org/support/wordpress-version/version-5-8/)

### 5.21.0
* Pressbooks 5.21.0 requires [WordPress 5.7.2](https://wordpress.org/support/wordpress-version/version-5-7-2/)

### 5.20.1
* Pressbooks 5.20.1 requires [WordPress 5.6.2](https://wordpress.org/support/wordpress-version/version-5-6-2/)

### 5.18.0

* Pressbooks 5.18.0 requires PHP >= 7.3
* Pressbooks 5.18.0 requires [WordPress 5.5.3](https://wordpress.org/support/wordpress-version/version-5-5-3/)

### 5.16.0 
* If you are using the plugin (Lord of the Files)[https://wordpress.org/plugins/blob-mimes/] version <=1.0.0, this upgrade will break your application.
  To fix this, you would need to update Lord of the files plugin to at least 1.1.0.

### 5.15.1
* Pressbooks 5.15.1 requires PHP >= 7.1.
* Pressbooks 5.15.1 requires [WordPress 5.4](https://wordpress.org/support/wordpress-version/version-5-4/)
* Pressbooks 5.15.1 requires [McLuhan >= 2.10.2](https://github.com/pressbooks/pressbooks-book/)
