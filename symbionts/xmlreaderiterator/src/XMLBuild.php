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
 * Module XMLBuild
 *
 * Some string functions helping to create XML
 *
 * @since 0.0.23
 */
abstract class XMLBuild
{

    /**
     * indentLines()
     *
     * this will add a line-separator at the end of the last line because if it was
     * empty it is not any longer and deserves one.
     *
     * @param string $lines
     * @param string $indent (optional)
     *
     * @return string
     */
    public static function indentLines($lines, $indent = '  ')
    {
        $lineSeparator = "\n";
        $buffer        = '';
        $line          = strtok($lines, $lineSeparator);
        while ($line) {
            $buffer .= $indent . $line . $lineSeparator;
            $line = strtok($lineSeparator);
        }
        strtok(null, null);

        return $buffer;
    }

    /**
     * @param string            $name
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     * @param bool              $emptyTag    create an empty element tag (commonly known as short tags)
     *
     * @return string
     */
    public static function startTag($name, $attributes, $emptyTag = false)
    {
        $buffer = '<' . $name;
        $buffer .= static::attributes($attributes);
        $buffer .= $emptyTag ? '/>' : '>';

        return $buffer;
    }

    /**
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     *
     * @return string
     */
    public static function attributes($attributes)
    {
        $buffer = '';

        foreach ($attributes as $name => $value) {
            $buffer .= ' ' . $name . '="' . static::attributeValue($value) . '"';
        }

        return $buffer;
    }

    /**
     * @param string $value
     * @see XMLBuild::numericEntitiesSingleByte
     *
     * @return string
     */
    public static function attributeValue($value)
    {
        $buffer = $value;

        // REC-xml/#AVNormalize - preserve
        // REC-xml/#sec-line-ends - preserve
        $buffer = preg_replace_callback('~\r\n|\r(?!\n)|\t~', array('self', 'numericEntitiesSingleByte'), $buffer);

        return htmlspecialchars($buffer, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * @param string            $name
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     * @param string            $innerXML
     *
     * @return string
     */
    public static function wrapTag($name, $attributes, $innerXML)
    {
        if (!strlen($innerXML)) {
            return XMLBuild::startTag($name, $attributes, true);
        }

        return
            XMLBuild::startTag($name, $attributes)
            . "\n"
            . XMLBuild::indentLines($innerXML)
            . "</$name>";
    }

    /**
     * @param XMLReader $reader
     *
     * @return string
     */
    public static function readerNode(XMLReader $reader)
    {
        switch ($reader->nodeType) {
            case XMLREADER::NONE:
                return '%(0)%';

            case XMLReader::ELEMENT:
                return XMLBuild::startTag($reader->name, new XMLAttributeIterator($reader));

            default:
                $node = new XMLReaderNode($reader);
                $nodeTypeName = $node->getNodeTypeName();
                $nodeType = $reader->nodeType;
                return sprintf('%%%s (%d)%%', $nodeTypeName, $nodeType);
        }
    }

    /**
     * @param array $matches
     *
     * @return string
     * @see attributeValue()
     */
    private static function numericEntitiesSingleByte($matches)
    {
        $buffer = str_split($matches[0]);
        foreach ($buffer as &$char) {
            $char = sprintf('&#%d;', ord($char));
        }

        return implode('', $buffer);
    }
}
