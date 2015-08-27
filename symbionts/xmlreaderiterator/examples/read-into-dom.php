<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2014 hakre <http://hakre.wordpress.com>
 *
 * Example: Build a DOMDocument with DOMReadingIteration
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/features-basic.xml';

$reader = new XMLReader();
$reader->open($xmlFile);

$doc = new DOMDocument();

$iterator = new DOMReadingIteration($doc, $reader);

foreach ($iterator as $index => $value) {
    // Preserve empty elements as non-self-closing by making them non-empty with a single text-node
    // children that has zero-length text
    if ($iterator->isEndElementOfEmptyElement()) {
        $iterator->getLastNode()->appendChild(new DOMText(''));
    }
}

echo $doc->saveXML();
