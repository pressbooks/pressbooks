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
 * Example: Inspect a large data-dump by scanning the file and display XML element structure as tree
 *
 * Note: use as PHP CLI command
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

stream_wrapper_register('xmlseq', 'XMLSequenceStream');

$elementsToScan = 5000;
$timeLimit      = 10;
$file           = 'data/movies.xml';
$outfile        = 'php://stdout';

$process = isset($argv) ? $argv : array();

$self = array_shift($process);

function print_usage()
{
    global $self;
    $name = basename($self, '.php');
    echo "\n";
    echo "usage: $name [-t <timelimit>] [-l <elementlimit>] [-o <outfile>] <infile>\n";
    echo "\n";
    echo "       <timelimit>   limit seconds to use for scan, 0 for no limit\n";
    echo "       <elementlmit> maximum number of elements to scan, 0 for no limit\n";
    echo "       <outfile>     output file for the summary tree, default is\n";
    echo "                     standard output (php://stdout)\n";
    echo "       <infile>      input file\n";
    echo "\n";
}

if (!$process) {
    if (!is_readable($file)) {
        print_usage();
        exit(1);
    }
}

while ($process) {
    $cmd = array_shift($process);
    if ($cmd === '--help' or $cmd === '-h') {
        print_usage();
        exit(0);
    } elseif ($cmd === '-t') {
        $in = array_shift($process);
        if ($in !== trim((int)$in) || $in < 0) {
            echo "invalid -t time limit in seconds: $in\n";
            print_usage();
            exit(1);
        }
        $timeLimit = (int)$in;
        continue;
    } elseif ($cmd === '-l') {
        $in = array_shift($process);
        if ($in !== trim((int)$in) || $in < 0) {
            echo "invalid -l elements to scan limit: $in\n";
            print_usage();
            exit(1);
        }
        $elementsToScan = (int)$in;
        continue;
    } elseif ($cmd === '-o') {
        $in = array_shift($process);
        if (!strlen($in)) {
            echo "invalid -o output file.\n";
            print_usage();
            exit(1);
        }
        $outfile = $in;
        continue;
    }
    $file = $cmd;
    break;
};


$timeLimit = (int)max(0, $timeLimit);


printf("input.: %s\n", $file);
printf("output: %s\n", $outfile);
printf(
    "limits: %s elements with %s time-limit\n", $elementsToScan ? : 'all', $timeLimit ? "$timeLimit seconds" : 'no'
);

$indexLimit = (int)max(0, $elementsToScan - 2);

$levels = array();

do {
    $saved  = libxml_use_internal_errors(true);
    $reader = new XMLReader();
    $result = $reader->open($file);
    if (!$result) {
        echo "unable to open input file.\n";
        foreach (libxml_get_errors() as $error) {
            print_r($error);
        }
        libxml_use_internal_errors($saved);
        exit(1);
    }

    $start       = microtime(true);
    $lastCount   = 0;
    $lastRuntime = 0;
    $messageLastLen = 0;

    /** @var XMLChildElementIterator|XMLReaderNode[] $children */
    $children = new XMLChildElementIterator($reader, null, true);
    foreach ($children as $index => $child) {
        $path  = $children->getNodePath();
        $level = $reader->depth;

        $runtime = microtime(true) - $start;
        if ($index % 1000 === 0) {
            if ($lastRuntime) {
                $step   = $index - $lastCount;
                $perSec = $step / ($runtime - $lastRuntime);
            } else {
                $perSec = '?';
            }
            $spacer         = '';
            $message        = sprintf("%05d %' -48s (%.2f secs; %.2f per second)", $index, $path, $runtime, $perSec);
            ($messageLastLen)
                && ($need = max(0, $messageLastLen - strlen($message)))
                && $spacer = str_repeat(' ', $need);
            ;
            $messageLastLen = strlen($message);

            echo("\r$message$spacer");
            $lastCount   = $index;
            $lastRuntime = $runtime;
        }

        $index || $levels[0][dirname($path)] = 1;
        isset($levels[$level][$path]) || $levels[$level][$path] = 0;
        $levels[$level][$path]++;

        if (
            ($indexLimit && $index > $indexLimit)
            or ($timeLimit && $runtime > $timeLimit)
        ) {
            break;
        }
    }
    echo "\n";

    isset($index) || $index = -1;
    isset($path) || $path = '(parse error)';

    $summary = sprintf(
        "scanning done. scanned %d elements in %d seconds. last element was %s.\n", $index + 1,
        microtime(true) - $start, $path
    );
    echo $summary;
    $errors = libxml_get_errors();
    if ($errors) {
        printf("had %d parse error(s):\n", count($errors));
        foreach ($errors as $error) {
            printf(
                "[level: %d; code: %d; line: %d column: %d]\n%s ", $error->level, $error->code, $error->line,
                $error->column, $error->message
            );
        }
    }
    libxml_use_internal_errors($saved);
} while (substr($file, 0, 9) === 'xmlseq://' && XMLSequenceStream::notAtEndOfSequence($file));
XMLSequenceStream::clean();
stream_wrapper_unregister('xmlseq');

printf("creating scan summary in %s.\n", $outfile);

class Levels
{
    protected $levels;

    public function __construct(array $levels)
    {
        $this->levels = $levels;
    }

    public function getChildrenOfAtLevel($prefix, $level)
    {
        $children = array();

        if (isset($this->levels[$level])) {
            foreach ($this->levels[$level] as $path => $count) {
                if (substr($path, 0, strlen($prefix) + 1) === "$prefix/") {
                    $children[$path] = $count;
                }
            }
        }

        return $children;
    }
}

class LevelsTree implements RecursiveIterator
{
    /**
     * @var Levels
     */
    private $levels;

    /**
     * @var int
     */
    private $level;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $index;

    /**
     * @var array
     */
    private $toConsume;


    /**
     * @var array
     */
    private $childCache;
    private $childCachePrefix;


    function __construct(Levels $levels, $level = 0, $prefix = '')
    {
        $this->levels = $levels;
        $this->level  = $level;
        $this->prefix = $prefix;
    }

    public function rewind()
    {
        $this->toConsume = $this->levels->getChildrenOfAtLevel($this->prefix, $this->level);
        $this->index     = 0;
    }

    public function valid()
    {
        return (bool)$this->toConsume;
    }

    public function current()
    {
        reset($this->toConsume);
        list($key, $value) = each($this->toConsume);
        return sprintf("%s (%d)", basename($key), $value);
    }

    public function next()
    {
        if ($this->toConsume) {
            $this->index++;
            array_shift($this->toConsume);
        }
    }

    public function key()
    {
        return $this->index;
    }


    public function hasChildren()
    {
        reset($this->toConsume);
        list($key) = each($this->toConsume);

        $this->childCachePrefix = $key;
        $this->childCache       = $this->levels->getChildrenOfAtLevel($key, $this->level + 1);

        return (bool)$this->childCache;
    }

    public function getChildren()
    {
        if (!$this->childCache) {
            return null;
        }

        return new self($this->levels, $this->level + 1, $this->childCachePrefix);
    }
}

$data  = new Levels($levels);
$tree  = new LevelsTree($data);
$lines = new RecursiveTreeIterator($tree);

if ($outfile === 'php://stdout') {
    // phpunit needs the output buffer so stdout needs some fake
    foreach ($lines as $line) {
        echo "$line\n";
    }
} else {
    $out = new SplFileObject($outfile, 'w');
    foreach ($lines as $line) {
        $out->fwrite("$line\n");
    }
}
