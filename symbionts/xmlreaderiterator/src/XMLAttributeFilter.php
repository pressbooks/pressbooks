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
 * Class XMLAttributeFilter
 *
 * FilterIterator for attribute value(s)
 */
class XMLAttributeFilter extends XMLAttributeFilterBase
{
    private $compare;
    private $invert;

    /**
     * @param XMLElementIterator $elements
     * @param string $attr name of the attribute, '*' for every attribute
     * @param string|array $compare value(s) to compare against
     * @param bool $invert
     */
    public function __construct(XMLElementIterator $elements, $attr, $compare, $invert = false)
    {

        parent::__construct($elements, $attr);

        $this->compare = (array) $compare;
        $this->invert  = (bool) $invert;
    }

    public function accept()
    {
        $result = $this->search($this->getAttributeValues(), $this->compare);

        return $this->invert ? !$result : $result;
    }

    private function search($values, $compares)
    {
        foreach ($compares as $compare) {
            if (in_array($compare, $values)) {
                return true;
            }
        }

        return false;
    }
}
