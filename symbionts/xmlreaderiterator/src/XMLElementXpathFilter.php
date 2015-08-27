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
 * Class XMLElementXpathFilter
 *
 * Filter an XMLReaderIterator with an Xpath expression
 *
 * @since 0.0.19
 *
 * @method XMLElementIterator getInnerIterator()
 */
class XMLElementXpathFilter extends XMLReaderFilterBase
{
    private $expression;

    public function __construct(XMLElementIterator $iterator, $expression)
    {
        parent::__construct($iterator);
        $this->expression = $expression;
    }

    public function accept()
    {
        $buffer = $this->getInnerIterator()->getNodeTree();
        $result = simplexml_load_string($buffer)->xpath($this->expression);
        $count  = count($result);
        if ($count !== 1) {
            return false;
        }

        return !($result[0]->children()->count());
    }
}
