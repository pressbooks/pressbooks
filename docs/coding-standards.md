---
layout: page
title: Coding Standards
permalink: /coding-standards/
---

## Validating with PHP Code Sniffer

Instead of reading any of this why not just let the computer nag you? From the Pressbooks plugin directory:

1. `composer install --dev`
2. `composer standards`

Bonus: You can sometimes automatically fix errors by running `vendor/bin/phpcbf --standard=phpcs.ruleset.xml /path/to/your/file`

## Pressbooks Coding Standards (Mandatory)

We enforce [Human Made Coding Standards](https://github.com/humanmade/coding-standards) with the following small tweaks.

 + Use camelCase for class methods & properties, uppercase for class constants, snake_case everywhere else.
 + [PHP Sessions](http://php.net/manual/en/book.session.php) are allowed.
 
### Write Classes or Namespaced functions, stay out of global space!

[PHP Namespaces](https://secure.php.net/manual/en/language.namespaces.php) have been available since 2009. Namespaces are not a new concept. We use them.

Our namespace is: `\Pressbooks\`

 * If your Class isn't an Object like `\WP_User`, `\WP_Dependencies`, `\WP_Query` etc., write a library of functions.
 * If your Class is a bunch of Static methods and nothing else, write a library of functions.
 * Afraid of function name collisions? See [Namespaces](https://secure.php.net/manual/en/language.namespaces.php). 
  
## Pressbooks Coding Recommendations (Optional)

Write accurate [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) styled code comments.

Prefix WP Post meta keys with `pb_`.

Prefix WP User meta keys with `pb_`.

Prefix WP Option names with `pressbooks_`.

Files under `themes-book/` and `themes-root/` are exempt from the above rules, but should still make an effort to follow them.
