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
 * Class XMLReaderElement
 *
 * This node is used in the elementStack
 *
 * @since 0.0.19
 */
class XMLReaderElement extends XMLReaderNode
{
    private $name_;
    private $attributes_;

    public function __construct(XMLReader $reader)
    {
        parent::__construct($reader);
        $this->initializeFrom($reader);
    }

    public function getXMLElementAround($innerXML = '')
    {
        return XMLBuild::wrapTag($this->name_, $this->attributes_, $innerXML);
    }

    public function getAttributes()
    {
        return $this->attributes_;
    }

    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes_[$name])
            ? $this->attributes_[$name] : $default;
    }

    public function __toString()
    {
        return $this->name_;
    }

    private function initializeFrom(XMLReader $reader)
    {
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            $node = new XMLReaderNode($reader);
            throw new RuntimeException(sprintf(
                'Reader must be at an XMLReader::ELEMENT, is XMLReader::%s given.',
                $node->getNodeTypeName()
            ));
        }
        $this->name_       = $reader->name;
        $this->attributes_ = parent::getAttributes()->getArrayCopy();
    }
}
