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
 * Class XMLSequenceStream
 *
 * @since 0.1.3
 */
class XMLSequenceStream
{
    const DECL_POS_PATTERN = '(<\?xml\s+version\s*=\s*(["\'])(1\.\d+)\1(?:\s*\?>|\s+encoding\s*=\s*(["\'])(((?!\3).)*)\3))';
    /**
     * @var resource
     */
    public $context;

    /**
     * @var string
     */
    private $file;

    /**
     * @var BufferedFileRead
     */
    private $reader;

    /**
     * this wrapper keeps a multi-singleton based on the filename
     * for read buffers to allow multiple stream operations
     * after another.
     *
     * @var BufferedFileReaders
     */
    public static $readers;

    /**
     * @var bool
     */
    private $flagEof;

    private $declFound = 0;

    /**
     * clear reader buffers, close open files if any.
     */
    public static function clean()
    {
        self::$readers && self::$readers->close();
    }

    /**
     * @param string $path filename of the buffer to close, complete with wrapper prefix
     *
     * @return bool
     */
    public static function closeBuffer($path)
    {
        if (!self::$readers) {
            return false;
        }

        $path = new XMLSequenceStreamPath($path);
        $file = $path->getFile();

        return self::$readers->removeReaderForFile($file);
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public static function notAtEndOfSequence($path)
    {
        if (!self::$readers) {
            return true;
        }

        try {
            $path = new XMLSequenceStreamPath($path);
        } catch (UnexpectedValueException $e) {
            return true;
        }

        $file = $path->getFile();

        return !self::$readers->isFileConsumed($file);
    }

    public function __construct()
    {
        # fputs(STDOUT, sprintf('<construct>'));
        self::$readers || self::$readers = new BufferedFileReaders();
    }

    /**
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     *
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        # fputs(STDOUT, sprintf('<open: %s - raise errors: %d - use path: %d >', var_export($path, 1), $options & STREAM_REPORT_ERRORS, $options & STREAM_USE_PATH));
        $path = new XMLSequenceStreamPath($path);

        $file         = $path->getFile();
        $reader       = self::$readers->getReaderForFile($file, $mode, null, $this->context);
        $this->file   = $file;
        $this->reader = $reader;

        if (!$reader) {
            return false;
        }

        $reader->setReadAhead(256);
        if ($reader->feof() && !strlen($reader->buffer)) {
            $message = sprintf('Concatenated XML Stream: Resource %s at the end of stream', var_export($file, true));
            trigger_error($message);
            return false;
        }

        return true;
    }

    public function stream_stat()
    {
        return false;
    }

    /**
     * @param string $path
     * @param int    $flags
     *
     * @return bool
     */
    public function url_stat($path, $flags)
    {
        # fputs(STDOUT, sprintf('<url stat: %s - Link: %d - Quiet: %d>', var_export($path, 1), $flags & STREAM_URL_STAT_LINK, $flags | STREAM_URL_STAT_QUIET));

        return array();
    }

    public function stream_read($count)
    {
        $reader = $this->reader;

        # fputs(STDOUT, sprintf('<read: %d - buffer: %d - eof: %d>', $count, strlen($reader->buffer), $this->flagEof));

        if ($this->flagEof) {
            return false;
        }

        $bufferLen = $reader->append($count);
        # fputs(STDOUT, sprintf('<buffer: %d>', $bufferLen));

        $pos = $this->declPos();
        if (!$this->declFound && $pos !== false) {
            $this->declFound++;
            if ($pos !== 0) {
                throw new UnexpectedValueException(sprintf('First XML declaration expected at offset 0, found at %d', $pos));
            }
            $pos = $this->declPos(5);
        }

        if ($pos === false) {
            $returnLen = min($bufferLen, $count);
        } else {
            $returnLen = min($pos, $count);
            if ($returnLen >= $pos) {
                $this->flagEof = true;
            }
            $this->declFound++;
        }

        $return = $reader->shift($returnLen);

        return $return;
    }

    private function declPos($offset = 0)
    {
        $result      = preg_match(self::DECL_POS_PATTERN, $this->reader->buffer, $matches, PREG_OFFSET_CAPTURE, $offset);
        if ($result === FALSE) {
            throw new UnexpectedValueException('Regex failed.');
        }

        return $result ? $matches[0][1] : false;
    }

    public function stream_eof()
    {
        return $this->flagEof;
    }
}
