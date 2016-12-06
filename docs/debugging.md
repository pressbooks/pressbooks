---
layout: page
title: Debugging
permalink: /debugging/
---

A few things you can try:

1. Add this line to `wp-config.php`: `define( 'WP_ENV', 'development' );` (this will enable some debugging features and outputs that are not enabled in production environments).
2. Network disable all plugins other than Pressbooks, then see if the problem persists.
3. Switch your book to the “Luther” book theme (the Pressbooks default), then see if the problem persists.
