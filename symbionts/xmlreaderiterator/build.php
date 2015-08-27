#!/usr/bin/env php
<?php
/**
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2014, 2015 hakre <http://hakre.wordpress.com>
 *
 * build script
 */

$errors = 0;
$warnings = 0;

$buildDir = __DIR__ . '/build';
$concatenateDir = $buildDir . '/include';
$concatenateFile = $concatenateDir . '/xmlreader-iterators.php';
$autoLoadFile = __DIR__ . '/autoload.php';

### test if composer.json validates ###
built_test_composer_validate_json($errors);

### test if a valid version can be obtained from README.md ###
$readmeVersion = built_test_readme_get_version($errors);
built_validate_version($readmeVersion, $errors);

built_test_git_tag($readmeVersion, $errors);

function built_test_git_tag($version, &$errors)
{
    echo "INFO: Validating git tag version:";

    $command = "git tag --contains HEAD";

    $target  = 'v' . $version;
    $tagName = exec($command, $output, $exitCode);
    $result  = $tagName === $target;

    if (!$result) {
        printf("\nERROR: git tag '%s' does not match '%s'.\n", $tagName, $target);
        $errors++;
    } else {
        echo " ", $tagName, ".\n";
    }
}

### test if autoload.php contains all classes ###
build_test_autoload_file($errors, $autoLoadFile);

### test if tests run clean ###
build_test_tests($errors);

if ($errors) {
    // printf("ERROR: Build (Tests only) had %d errors, quitting.\n", $errors);
    // return;
}

### clean ###
build_make_clean($errors, $buildDir, $concatenateDir);

### create concatenateFile ###
build_create_concatenate_file($errors, $concatenateFile, $autoLoadFile, $readmeVersion);
copy_file_to_dir(__DIR__ . '/README.md', $concatenateDir);

### conditional build target into gist ###
$gistDir = __DIR__ . '/../' . basename(__DIR__) . '-Gist-5147685';
if (is_dir($gistDir)) {
    copy_dir_to_dir($concatenateDir, $gistDir);
} else {
    printf("INFO: Gist build target directory not found.\n");
}

if ($errors) {
    printf("ERROR: Build had %d errors.\n", $errors);
}

/**
 * @param $errors
 *
 * @return string|null
 */
function built_test_readme_get_version(&$errors)
{
    $file    = 'README.md';
    $data    = file($file);
    $version =  null;

    foreach ($data as $index => $line )  {
        if ($line === "### Change Log:\n") {
            $version = preg_match('~`(\d\.\d+\.\d+)`~', $data[$index + 2], $m) ? $m[1] : null;
        }
        if ($index > 10) {
            break;
        }
    }

    if (!strlen($version)) {
        echo "ERROR: Unable to obtain version from README.md.\n";
        $errors++;
        return null;
    }

    echo "INFO: README.md version is $version.\n";

    return $version;
}

function built_validate_version($version, &$errors)
{
    if (!preg_match('~^\d\.\d+\.\d+$~', $version)) {
        echo "ERROR: Unable to validate version '$version'.\n";
        $errors++;
        return false;
    }

    return true;
}


/**
 * @see http://php.net/json_decode
 *
 * @param      $path
 * @param bool $assoc
 * @param int  $depth
 * @param int  $options
 *
 * @return mixed
 */
function json_decode_file($path, $assoc = false, $depth = 512, $options = 0)
{
    return json_decode(file_get_contents($path), $assoc, $depth, $options);
}

/**
 * @param $errors
 */
function built_test_composer_validate_json(&$errors)
{
    echo "INFO: Validating composer.json before building:\n";

    $composer = 'composer';

    $command = "$composer --no-ansi --version";

    exec($command, $output, $exitCode);
    list($versionLine) = $output;
    if (!preg_match('~^Composer version (?:1.0-dev \([0-9a-f]{40}\)|[0-9a-f]{40}) 2\d{3}-(?:0\d|1[0-2])-(?:[0-2]\d|3[0-1]) (?:[0-1]\d|2[0-3]):[0-5]\d:(?:[0-5]\d|60)$~', $versionLine)) {
        echo "ERROR: Unable to invoke Composer.\n";
        $errors++;
        return;
    }

    $command = "$composer --no-ansi validate";
    system($command, $exitCode);
    if ($exitCode !== 0) {
        echo "ERROR: Composer json validation did return exit code $exitCode which is not 0.\n";
        $errors++;

        return;
    }

    echo "INFO: composer.json validation did pass. You might need to review warnings your own.\n";

    return;
}

/**
 * @param $errors
 */
function build_test_tests(&$errors)
{
    echo "INFO: Running phpunit testuite before building:\n";

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $phpunit = '.\vendor\bin\phpunit.bat';
    } else {
        $phpunit = './vendor/bin/phpunit';
    }

    $command = "$phpunit --version";

    exec($command, $output, $exitCode);
    list($versionLine) = $output + array(null);
    if (!preg_match('~^PHPUnit \d\.\d\.\d+ by Sebastian Bergmann\.$~', $versionLine)) {
        echo "ERROR: Unable to invoke PHPUnit.\n";
        $errors++;

        return;
    }

    $command = "$phpunit --stop-on-failure tests";

    $result = system($command, $exitCode);

    if ($result === false) {
        echo "ERROR: Unable to invoke PHPUnit tests.\n";
        $errors++;

        return;
    }

    if ($exitCode !== 0) {
        echo "ERROR: PHPUnit did return exit code $exitCode which is not 0.\n";
        $errors++;

        return;
    }

    echo "INFO: phpunit testuite did pass.\n";

    return;
}

/**
 * @param $errors
 * @param $autoLoadFile
 */
function build_test_autoload_file(&$errors, $autoLoadFile)
{
    require_once($autoLoadFile);

    foreach (glob('src/*.php') as $file) {
        $class = basename($file, '.php');


        if (!class_exists($class) && !interface_exists($class)) {
            echo "ERROR: ", $class, " does not exists.\n";
            $errors++;
        }
    }
}

/**
 * @param int    $errors
 * @param string $concatenateFile
 * @param string $autoLoadFile
 * @param string $version
 *
 * @internal param $buildDir
 * @internal param $concatenateFileHandle
 */
function build_create_concatenate_file(&$errors, $concatenateFile, $autoLoadFile, $version)
{
    if (!is_dir(dirname($concatenateFile))) {
        echo "ERROR: target dir '", dirname($concatenateFile), "' missing.\n";
        $errors++;

        return;
    } else {
        $concatenateFileHandle = fopen($concatenateFile, 'c+');
        if (!$concatenateFileHandle) {
            echo "ERROR: concatenateFile '$concatenateFile' can not be created.\n";
            $errors++;

            return;
        }
    }

    ### write concatenateFile based on autoload.php ###
    $pattern = '~^require .*\'/([^.]*\.php)\';$~';
    $lines   = preg_grep($pattern, file($autoLoadFile));
    if (!$lines) {
        echo "ERROR: Problem parsing file.\n";
    }
    $count = 0;
    foreach ($lines as $line) {
        $result = preg_match($pattern, $line, $matches);
        if (!$result) {
            echo "ERROR: Problem parsing file.\n";
            continue;
        }
        $file   = sprintf('src/%s', $matches[1]);
        $handle = fopen($file, 'r');

        if (!$handle) {
            echo "ERROR: Can not open file '$file' for reading.\n";
            continue;
        }

        if (!isset($concatenateFileHandle)) {
            fclose($handle);
            continue;
        }

        if ($count !== 0 && false === fseek_first_empty_line($handle)) { // first file is complete copy
            echo "ERROR: Problem reading file until first empty line.\n";
            continue;
        }

        stream_copy_to_stream($handle, $concatenateFileHandle);
        fclose($handle);
        $count++;
    }
    printf("INFO: concatenated %d files into %s.\n", $count, cwdname($concatenateFile));

    ### change the part of line ###
    do {

        $buffer  = stream_get_contents($concatenateFileHandle, 512, 0);
        $search  = ' * This file is part of the XMLReaderIterator package.';
        $replace = ' * XMLReaderIterator <http://git.io/xmlreaderiterator>';

        $length = strlen($search);

        if ($length !== strlen($replace)) {
            echo "ERROR: Search and replace must have the same length.\n";
            $errors++;
            break;
        }

        $pos = strpos($buffer, $search);
        if (!$pos) {
            echo "ERROR: Unable to find search string in first 512 bytes.\n";
            $errors++;
            break;
        }

        $buffer = substr_replace($buffer, $replace, $pos, $length);

        $bytesWritten = stream_put_contents($concatenateFileHandle, $buffer, 0, $pos + $length);
        if (false === $bytesWritten) {
            echo "ERROR: Failed to put first 512 bytes into stream.\n";
            $errors++;
            break;
        }

    } while (false);

    fclose($concatenateFileHandle);

    $buffer = file_get_contents($concatenateFile);

    $search  = " * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>\n */";
    $replace = " * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>\n * @version $version\n */";

    $pos = strpos($buffer, $search);
    if (!$pos) {
        echo "ERROR: Unable to find search string in buffer.\n";
        $errors++;
        return;
    }

    $buffer = substr_replace($buffer, $replace, $pos, strlen($search));

    if (!is_string($buffer)) {
        echo "ERROR: Failed to replace in buffer.\n";
        $errors++;
        return;
    }

    $bytesWritten = file_put_contents($concatenateFile, $buffer);

    if (false === $bytesWritten) {
        echo "ERROR: Failed to write back to file.\n";
        $errors++;
        return;
    }
}

/**
 * @param $errors
 * @param $buildDir
 * @param $concatenateDir
 */
function build_make_clean(&$errors, $buildDir, $concatenateDir)
{
    if (is_dir($buildDir)) {
        deltree($buildDir);
    }
    if (is_dir($buildDir)) {
        printf("ERROR: cannot clean buildDir %s .\n", cwdname($buildDir));
        $errors++;
    } else {
        mkdir($buildDir);
        mkdir($concatenateDir);
    }
}


/**
 * @param $handle
 *
 * @return bool
 */
function fseek_first_empty_line($handle)
{
    $lastLine = 0;
    while (false !== $line = fgets($handle)) {
        if ('' === rtrim($line, "\r\n")) {
            break;
        }
        $lastLine += strlen($line);
    }
    if ($line === false) {
        return false;
    }

    return !fseek($handle, $lastLine);
}


/**
 * shorten pathname realtive to cwd
 *
 * @param $path
 *
 * @return string
 */
function cwdname($path)
{
    static $base;
    isset($base) || $base = realpath('.');

    $baseLen = strlen($base);
    if (substr($path, 0 , $baseLen) !== $base or !strpos(' ' . '\\/', $path[$baseLen])) {
        echo "INFO: File '$path' not relative to cwd. Please verify.\n";
        $relative = realpath($path);
    } else {
        $relative = ltrim(substr($path, $baseLen), '\\/');
    }

    return strtr($relative, '\\', '/');
}

/**
 * copy files from one directory into another.
 *
 * @param string $sourceDir
 * @param string $targetDir
 */
function copy_dir_to_dir($sourceDir, $targetDir)
{
    foreach (new DirectoryIterator($sourceDir) as $file) {
        if (!$file->isFile()) {
            continue;
        }

        copy_file_to_dir($file->getPathname(), $targetDir);
    }
}

/**
 * copy file into directory
 *
 * @param string $file
 * @param string $targetDir
 *
 * @return bool
 */
function copy_file_to_dir($file, $targetDir)
{
    $target = rtrim($targetDir, '/\\') . '/' . basename($file);
    if (realpath($file) === realpath($target)) {
        echo "INFO: source and target in copy_to_dir() are the same.\n";

        return true; // already copied
    }
    $result = copy($file, $target);

    if ($result) {
        printf("INFO: copied %s to %s.\n", cwdname($file), cwdname($target));
    }

    return $result;
}

/**
 * deltree()  - delete a directory incl. subdirectories and files therein.
 *
 * implemented as a stack so that no recursion is necessar and
 * traversal is fast.
 *
 * @param $path
 */
function deltree($path)
{
    if (!is_dir($path) || is_link($path)) {
        echo "ERROR: given path rejected by deltree.\n";

        return;
    }

    $stack      = array($path);
    $rmdirStack = array();
    while ($stack) {
        $path = array_pop($stack);
        $it   = new DirectoryIterator($path);
        foreach ($it as $file) {
            /* @var $file DirectoryIterator */
            if ($file->isDot()) {
                continue;
            }
            $localPath = $path . '/' . $file->getBasename();
            if ($file->isDir()) {
                $stack[] = $localPath;
            } elseif ($file->isLink() || $file->isFile()) {
                $result = unlink($localPath);
                if (!$result) {
                    echo "ERROR: Failed to delete file '$localPath'. Expecting more problems.\n";
                }
            } else {
                printf(
                    "ERROR: Unknown processing for %s [%s] (%s) isDot: %d\n",
                    $file,
                    get_class($file),
                    $localPath,
                    $file->isDir()
                );
            }
        }
        unset($file, $it);
        array_unshift($rmdirStack, $path);
    }

    clearstatcache(true);
    foreach ($rmdirStack as $path) {
        chmod($path, 0777);
        clearstatcache(true, $path);
        $result = @rmdir($path);
        if (!$result) {
            echo "ERROR: Failed to delete directory '$path'. Skipping rest.\n";
            break;
        }
    }
}

/**
 * @param resource $handle    destination stream
 * @param string   $data      data to write
 * @param int      $offset    offset in destination stream if specified
 * @param int      $maxlength specify bytes to write if specified
 *
 * @return bool|int
 * @internal param string $string string to write
 */
function stream_put_contents($handle, $data, $offset = null, $maxlength = null)
{
    if (!is_resource($handle) or 'stream' !== get_resource_type($handle)) {
        trigger_error('Destination is not a stream resource type.');

        return false;
    }


    $length = strlen($data);
    if (null !== $maxlength) {
        $length = max(0, (int) $maxlength);
    }

    if (null !== $offset) {
        if (-1 === fseek($handle, $offset)) {
            trigger_error('Unable to seek.');

            return false;
        }
    }

    return fwrite($handle, $data, $length);
}
