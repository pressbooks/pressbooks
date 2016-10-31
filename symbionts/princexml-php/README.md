PrinceXMLPhp
============

[![Build Status](https://api.travis-ci.org/gridonic/PrinceXMLPhp.svg?branch=master)](https://travis-ci.org/gridonic/PrinceXMLPhp) [![Latest Stable Version](https://poser.pugx.org/gridonic/princexml-php/v/stable.svg)](https://packagist.org/packages/gridonic/princexml-php) [![Total Downloads](https://poser.pugx.org/gridonic/princexml-php/downloads.svg)](https://packagist.org/packages/gridonic/princexml-php) [![License](https://poser.pugx.org/gridonic/princexml-php/license.svg)](https://packagist.org/packages/gridonic/princexml-php)

[PrinceXML PHP5 wrapper](http://www.princexml.com/download/wrappers), converted to follow PSR-0 conventions.

Fetch
-----

The recommended way to install PrinceXMLPhp is [through composer](http://packagist.org).

Just create a composer.json file for your project:

```JSON
{
    "require": {
        "gridonic/princexml-php": "*"
    }
}
```

And run these two commands to install it:

    $ wget http://getcomposer.org/composer.phar
    $ php composer.phar install

Now you can add the autoloader, and you will have access to the library:

```php
<?php
require 'vendor/autoload.php';
```

Usage
-----

```php
<?php
use PrinceXMLPhp\PrinceWrapper;

$prince = new PrinceWrapper('/path/to/prince/binary');

// for instance methods, see the original library documentation
// @see lib/readme.html
```

License
-------

The PrinceXMLPhp wrapper is licensed under the MIT license. The original library from is
taken from the [Prince Website](http://www.princexml.com/download/wrappers/) and subject to the license by YesLogic Pty. Ltd.