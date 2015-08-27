<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
 *
 * Example: Filter XML elements by xpath expression
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/posts.xml';

$reader = new XMLReader();
$reader->open($xmlFile);

/** @var XMLElementIterator|XMLReaderNode[] $it */
$it = new XMLElementIterator($reader);

/** @var XMLElementXpathFilter|XMLReaderNode[] $list */
$list = new XMLElementXpathFilter($it, '//user[@id = "1" or @id = "6"]//message');

foreach($list as $message) {
    echo " * ",  $message->readString(), "\n";
}
