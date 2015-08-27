<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
 *
 * Example: Iterate over all elements
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/movies.xml';

$reader = new XMLReader();
$reader->open($xmlFile);

/** @var XMLElementIterator|XMLReaderNode[] $it */
$it = new XMLElementIterator($reader);

foreach($it as $index => $element) {
    printf("#%02d: %s\n", $index, XMLBuild::readerNode($reader));
}
