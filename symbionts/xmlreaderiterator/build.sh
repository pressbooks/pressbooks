#!/usr/bin/env bash
#
# build script on clean checkout
#

if [ -n "TRAVIS" ]; then
    echo "build on travis with php ${TRAVIS_PHP_VERSION}"
    if [ "${TRAVIS_PHP_VERSION}" == "5.2" ]; then
        echo "5.2 is not a composer version, can't build, exiting."
        exit 0
    fi
fi

if [ ! -d vendor ]; then
    echo "installing build dependencies..."
    composer install
fi

if [ ! -d vendor ]; then
    echo "build dependencies not installed!"
    exit 1
fi

php -f build.php
