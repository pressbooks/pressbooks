<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
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
 * Class XMLTypeFilter
 *
 * FilterIterator to only accept one or more specific XMLReader nodeTypes
 *
 */
class XMLNodeTypeFilter extends XMLReaderFilterBase
{
    private $allowed;
    private $reader;
    private $invert;

    /**
     * @param XMLReaderIterator $iterator
     * @param int|int[] $nodeType one or more type constants  <http://php.net/class.xmlreader>
     *      XMLReader::NONE            XMLReader::ELEMENT         XMLReader::ATTRIBUTE       XMLReader::TEXT
     *      XMLReader::CDATA           XMLReader::ENTITY_REF      XMLReader::ENTITY          XMLReader::PI
     *      XMLReader::COMMENT         XMLReader::DOC             XMLReader::DOC_TYPE        XMLReader::DOC_FRAGMENT
     *      XMLReader::NOTATION        XMLReader::WHITESPACE      XMLReader::SIGNIFICANT_WHITESPACE
     *      XMLReader::END_ELEMENT     XMLReader::END_ENTITY      XMLReader::XML_DECLARATION
     * @param bool $invert
     */
    public function __construct(XMLReaderIterator $iterator, $nodeType, $invert = false)
    {
        parent::__construct($iterator);
        $this->allowed = (array) $nodeType;
        $this->reader  = $iterator->getReader();
        $this->invert  = $invert;
    }

    public function accept()
    {
        $result = in_array($this->reader->nodeType, $this->allowed);

        return $this->invert ? !$result : $result;
    }
}
