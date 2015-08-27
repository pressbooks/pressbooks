<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 */


/**
 * Class DOMReadingIteration
 *
 * @since 0.1.0
 */
class DOMReadingIteration extends IteratorIterator
{
    private $rootNode;

    private $reader;
    const XMLNS = 'xmlns';

    /**
     * @var array|DOMNode[]
     */
    private $stack;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var int
     */
    private $lastDepth;

    /**
     * @var DOMNode
     */
    private $node;

    /**
     * @var DOMNode
     */
    private $lastNode;

    public function __construct(DOMNode $node, XMLReader $reader)
    {
        $this->rootNode = $node;
        $this->reader   = $reader;
        parent::__construct(new XMLReaderIteration($reader));
    }

    /**
     * The element by marked by type XMLReader::END_ELEMENT
     * is empty (has no children) but not self-closing.
     *
     * @return bool
     */
    public function isEndElementOfEmptyElement()
    {
        return
            $this->reader->nodeType === XMLReader::END_ELEMENT
            && $this->lastDepth === $this->reader->depth
            && $this->lastNode instanceof DOMElement
            && !$this->reader->isEmptyElement;
    }

    public function rewind()
    {
        $this->stack = array($this->rootNode);
        parent::rewind();
        $this->build();
    }

    private function build()
    {
        if (!$this->valid()) {
            $this->depth     = NULL;
            $this->lastDepth = NULL;
            $this->node      = NULL;
            $this->lastNode  = NULL;
            $this->stack     = NULL;
            return;
        }

        $depth = $this->reader->depth;

        switch ($this->reader->nodeType) {
            case XMLReader::ELEMENT:
                $parent = $this->stack[$depth];
                $prefix = $this->reader->prefix;
                /* @var $node DOMElement */
                if ($prefix) {
                    $uri = $parent->lookupNamespaceURI($prefix) ?: $this->nsUriSelfLookup($prefix);
                    if ($uri === NULL) {
                        trigger_error(sprintf('Unable to lookup NS URI for element prefix "%s"', $prefix));
                    }
                    /* @var $doc DOMDocument */
                    $doc  = ($parent->ownerDocument?:$parent);
                    $node = $doc->createElementNS($uri, $this->reader->name);
                    $node = $parent->appendChild($node);
                } else {
                    $node = $parent->appendChild(new DOMElement($this->reader->name));
                }
                $this->stack[$depth + 1] = $node;
                if ($this->reader->moveToFirstAttribute()) {
                    $nsUris = array();
                    do {
                        if ($this->reader->prefix === self::XMLNS) {
                            $nsUris[$this->reader->localName] = $this->reader->value;
                        }
                    } while ($this->reader->moveToNextAttribute());

                    $this->reader->moveToFirstAttribute();
                    do {
                        $prefix = $this->reader->prefix;
                        if ($prefix === self::XMLNS) {
                            $node->setAttributeNS('http://www.w3.org/2000/xmlns/', $this->reader->name, $this->reader->value);
                        } elseif ($prefix) {
                            $uri = $parent->lookupNamespaceUri($prefix) ?: @$nsUris[$prefix];
                            if ($uri === NULL) {
                                trigger_error(sprintf('Unable to lookup NS URI for attribute prefix "%s"', $prefix));
                            }
                            $node->setAttributeNS($uri, $this->reader->name, $this->reader->value);
                        } else {
                            $node->appendChild(new DOMAttr($this->reader->name, $this->reader->value));
                        }
                    } while ($this->reader->moveToNextAttribute());
                }
                break;

            case XMLReader::END_ELEMENT:
                $node = NULL;
                break;

            case XMLReader::COMMENT:
                $node = $this->stack[$depth]->appendChild(new DOMComment($this->reader->value));
                break;

            case XMLReader::SIGNIFICANT_WHITESPACE:
            case XMLReader::TEXT:
            case XMLReader::WHITESPACE:
                $node = $this->stack[$depth]->appendChild(new DOMText($this->reader->value));
                break;

            case XMLReader::PI:
                $node = $this->stack[$depth]->appendChild(new DOMProcessingInstruction($this->reader->name, $this->reader->value));
                break;

            default:
                $node    = NULL;
                $message = sprintf('Unhandled XMLReader node type %s', XMLReaderNode::dump($this->reader, TRUE));
                trigger_error($message);
        }

        $this->depth = $depth;
        $this->node  = $node;
    }

    private function nsUriSelfLookup($prefix) {
        $uri = NULL;

        if ($this->reader->moveToFirstAttribute()) {
            do {
                if ($this->reader->prefix === self::XMLNS && $this->reader->localName === $prefix) {
                    $uri = $this->reader->value;
                    break;
                }
            } while ($this->reader->moveToNextAttribute());
            $this->reader->moveToElement();
        }

        return $uri;
    }

    public function next()
    {
        parent::next();
        $this->lastDepth = $this->depth;
        $this->lastNode  = $this->node;
        $this->build();
    }

    /**
     * @return \DOMNode
     */
    public function getLastNode()
    {
        return $this->lastNode;
    }
}
