## Iterators for [PHP `XMLReader`](http://php.net/XMLReader) for ease of parsing

### Change Log:

 - `0.1.10` maintenance release with fixes.

 - `0.1.9` maintenance release with fixes. added XMLReaderNode::expand().

 - `0.1.8` maintenance release with fixes.

 - `0.1.7` maintenance release with fixes.

 - `0.1.6` maintenance release with fixes. added xml-file-scanner command-line tool example.

 - `0.1.5` maintenance release with tests and new `XMLReaderNextIteration` to iterate in `XMLReader::next()` fashion.

 - `0.1.4` maintenance release with fixes.

 - `0.1.3` added `XMLSequenceStream`, a PHP stream wrapper to read XML from files which are a sequence of XML
  documents. Works transparently with `XMLReader`.

 - `0.1.2` added `XMLWritingIteration`, an iteration to write with `XMLWriter` from `XMLReader`.

 - `0.1.0` composer support has been added.

 - `0.0.23` first try of a compatibility layer for PHP installs with a libxml version below version 2.6.20.
  Functions with compatibility checks are `XMLReaderNode::readOuterXml()` and `XMLReaderNode::readString()`.

 - `0.0.21` moved library into new repository and added `XMLReaderAggregate`.

 - `0.0.19` added `XMLElementXpathFilter`, a `FilterIterator` for `XMLReaderIterator` by an Xpath
 expression.

        $reader = new XMLReader();
        $reader->open($xmlFile);
        $it = new XMLElementIterator($reader);
        $list = new XMLElementXpathFilter($it, '//user[@id = "1" or @id = "6"]//message');

        foreach($list as $message) {
            echo " * ",  $message->readString(), "\n";
        }

### Code examples for the XMLReader Iterators (latest on top):

- [How to distinguish between empty element and null-size string in DOMDocument?](http://stackoverflow.com/a/24109776/367456)
- [PHP XML parser: How to read only part of the XML document?](http://stackoverflow.com/a/15443517/367456)
- [Parse XML with PHP and XMLReader](http://stackoverflow.com/a/15351723/367456)
- [Getting XML Attribute with XMLReader and PHP](http://stackoverflow.com/a/15399491/367456)
