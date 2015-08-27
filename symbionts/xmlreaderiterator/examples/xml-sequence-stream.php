<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
 *
 * Example: Read XML from a file that contains a sequence of XML documents
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

stream_wrapper_register('xmlseq', 'XMLSequenceStream');

// file is an excerpt from https://www.google.com/googlebooks/uspto-patents-grants-text.html - ipg140107.zip
$path = 'xmlseq://compress.bzip2://data/sequence.xml.bz2';

printf("XMLReader over '%s':\n", basename($path));

$iteration = 1;
while (XMLSequenceStream::notAtEndOfSequence($path)) {
    $reader = new XMLReader();
    $reader->open($path);

    /** @var XMLElementIterator|XMLReaderNode $elements */
    $elements = new XMLElementIterator($reader);
    $rootName = $elements->getName();

    $names = array();
    $elements->setName('name');
    foreach ($elements as $index => $nameElement) {
        $name = preg_replace('~ et al.$~', '', ucfirst($nameElement));
        $names[$name] = $index;
    }

    $names = array_flip($names);
    $count = count($names);
    sort($names);

    printf("- xml #%d: %s: names (%d): %s\n", $iteration++, $rootName, $count, $names ? implode('; ', $names) : '%');
}

XMLSequenceStream::clean();
stream_wrapper_unregister('xmlseq');
