<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
 *
 * Example: XML Reader standard iteration
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xml = <<<XML
<movies>
    <movie>
        <title>PHP: Behind the Parser</title>
    </movie>
    <movie>
        <title>Whitespace</title>
    </movie>
    <movie>
        <title>Whitespace - Tabs Revenge</title>
    </movie>
    <movie>
        <title>Whitespace - The Return of the Whitespace</title>
    </movie>
</movies>
XML;

$reader = new XMLReader();
$reader->open('data://text/plain,' . urlencode($xml));
while ($reader->read()) XMLReaderNode::dump($reader);
