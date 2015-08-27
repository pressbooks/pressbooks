<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2014 hakre <http://hakre.wordpress.com>
 *
 * Example: Write XML with XMLWriter while reading from XMLReader with XMLWriterIteration
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlInputFile  = 'data/dobs-items.xml';
$xmlOutputFile = 'php://output';

$reader = new XMLReader();
$reader->open($xmlInputFile);

$writer = new XMLWriter();
$writer->openUri($xmlOutputFile);

$iterator = new XMLWritingIteration($writer, $reader);

$writer->startDocument();

$itemsCount = 0;
$itemCount  = 0;
foreach ($iterator as $node) {
    $isElement = $node->nodeType === XMLReader::ELEMENT;


    if ($isElement && $node->name === 'ITEMS') {
        // increase counter for <ITEMS> elements and reset <ITEM> counter
        $itemsCount++;
        $itemCount = 0;
    }

    if ($isElement && $node->name === 'ITEM') {
        // increase <ITEM> counter and insert "id" attribute
        $itemCount++;
        $writer->startElement($node->name);
        $writer->writeAttribute('id', $itemsCount . "-" . $itemCount);
        if ($node->isEmptyElement) {
            $writer->endElement();
        }
    } else {
        // handle everything else
        $iterator->write();
    }
}

$writer->endDocument();
