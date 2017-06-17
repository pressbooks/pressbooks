---
layout: page
title: Unit Testing
permalink: /unit-testing/
---

The Pressbooks unit testing framework was built (then slightly tweaked) using [WP-CLI][1]. The tests run automatically on commit via [Travis-CI][2], with more in-depth reporting via [Codecov][3].

To run the tests locally, do:
    
    cd /path/to/wordpress/wp-content/plugins/pressbook
    bash bin/install-wp-tests.sh wordpress_test DBUSER DBPASS localhost latest
    phpunit

*   Replace `/path/to` with your path.
*   `wordpress_test` is the name of a new test database (**all data will be deleted!**)
*   `DBUSER` is your MySQL user name
*   `DBPASS` is your MySQL user password
*   `localhost` is your MySQL host
*   `latest` is the WordPress version; could also be `4.5`, `4.6` etc.
*   [PHPUnit is installed...][4]

The bash script installs a copy of WordPress and the WordPress unit testing tools in`/tmp`. It then creates a new tests database to be used while running tests. The bash script can be run multiple times without errors, but it will *not* overwrite previously existing files.

Tests are in `/tests/*.*`

Tests cover the code in `/includes/*.*`

Please [help us improve code coverage!][3]

## More info:

*   [PHPUnit Assertions][5]
*   [WP_UnitTestCase + Object Factories][6]
*   [How to Write Testable Code][7]
*   [Introduction to WordPress Unit Testing][8]
*   [Write Unit Tests For Your WordPress Plugin Using PhpStorm Code Completion][9]

 [1]: https://make.wordpress.org/cli/handbook/plugin-unit-tests/
 [2]: https://travis-ci.org/pressbooks/pressbooks
 [3]: https://codecov.io/gh/pressbooks/pressbooks
 [4]: https://phpunit.de/
 [5]: https://phpunit.de/manual/4.8/en/appendixes.assertions.html
 [6]: http://codesymphony.co/writing-wordpress-plugin-unit-tests/#object-factories
 [7]: http://code.tutsplus.com/tutorials/how-to-write-testable-and-maintainable-code-in-php--net-31726
 [8]: http://carlalexander.ca/introduction-wordpress-unit-testing/
 [9]: http://kizu514.com/blog/write-unit-tests-for-your-wordpress-plugin-using-phpstorm-code-completion/
