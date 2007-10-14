<?php
    /*
    *  $Id: instrument.php 14672 2005-03-23 21:37:47Z npac $
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php
    #!/bin/php

    if(!defined("__PHPCOVERAGE_HOME")) {
        define("__PHPCOVERAGE_HOME", dirname(dirname(__FILE__)));
    }
    require_once __PHPCOVERAGE_HOME . "/conf/phpcoverage.conf.php";
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";

    ## Instruments the PHP Source files

    /** 
     * Print help message and exit
     * 
     * @access public
     */
    function help() {
        echo "Usage: " . basename(__FILE__) . " -b <application-base-path> [-p <phpcoverage-home>] [-r] [-u] [-e <exclude-file-list>]"
         . "[-v] [-h] [path1 [path2 [...]]]\n";
        echo "\n";
        echo "   Options: \n";
        echo "       -b <application-base-path>       Application directory accessible via HTTP "
            . "where PHPCoverage files should be copied.\n";
        echo "       -p <phpcoverage-home>            Path to PHPCoverage Home.\n";
        echo "       -r                               Recursively instrument PHP files.\n";
        echo "       -u                               Undo instrumentation.\n";
        echo "       -e <file1,file2,...>             Execlude files in the file list.\n";
        echo "       -v                               Be verbose.\n";
        echo "       -h                               Print this help and exit.\n";
        echo "\n";
        exit(0);
    }

    /** 
     * Print error message and exit 
     * 
     * @param $msg Message to write to console.
     * @access public
     */
    function error($msg) {
        echo basename(__FILE__) . ": [ERROR] " . $msg . "\n";
        exit(1);
    }

    /** 
     * Write a information message 
     * 
     * @param $msg Message to write to console.
     * @access public
     */
    function writeMsg($msg) {
        global $VERBOSE;
        if($VERBOSE) {
            echo basename(__FILE__) . ": [INFO] " . $msg . "\n";
        }
    }

    /** 
     * Instrument the PHP file. 
     * 
     * @param $file File path
     * @access public
     */
    function instrument($file) {
        global $LOCAL_PHPCOVERAGE_LOCATION, $top, $bottom;
        $tmpfile = "$file.tmp";
        $contents = file_get_contents($file);
        $len = strlen($contents);
        if(strpos($contents, $top) === 0 && strrpos($contents, $bottom) === ($len - strlen($bottom))) {
            writeMsg("Skipping $file.");
            return;
        }

        $fp = fopen($tmpfile, "w");
        if(!$fp) {
            error("Cannot write to file: $tmpfile");
        }
        fputs($fp, $top);
        fwrite($fp, $contents);
        fputs($fp, $bottom);
        fclose($fp);
        // Delete if already exists - 'rename()' on Windows will return false otherwise
        if(file_exists($file)) {
            unlink($file);
        }
        $ret = rename($tmpfile, $file);
        if(!$ret) {
            error("Cannot save file: $file");
        }
        writeMsg("Instrumented: $file.");
    }

    /** 
     * Uninstrument the PHP file 
     * 
     * @param $file File path
     * @access public
     */
    function uninstrument($file) {
        global $LOCAL_PHPCOVERAGE_LOCATION, $top, $bottom;
        $tmpfile = "$file.tmp";

        $contents = file_get_contents($file);
        $len = strlen($contents);
        if(strpos($contents, $top) !== 0 && strrpos($contents, $bottom) !== ($len - strlen($bottom))) {
            writeMsg("Skipping $file.");
            return;
        }

        $fr = fopen($file, "r");
        $fw = fopen($tmpfile, "w");
        if(!$fr) {
            error("Cannot read file: $file");
        }
        if(!$fr) {
            error("Cannot write to file: $tmpfile");
        }
        while(!feof($fr)) {
            $line = fgets($fr);
            if(strpos($line, $top) === false && strpos($line, $bottom) === false) {
                fputs($fw, $line);
            }
        }
        fclose($fr);
        fclose($fw);

        // Delete if already exists - 'rename()' on Windows will return false otherwise
        if(file_exists($file)) {
            unlink($file);
        }
        $ret = rename($tmpfile, $file);
        if(!$ret) {
            error("Cannot save file: $file");
        }
        writeMsg("Uninstrumented: $file");
    }

    /** 
     * Retrive a list of all PHP files in the given directory
     * 
     * @param $dir Directory to scan
     * @param $recursive True is directory is scanned recursively
     * @return Array List of PHP files
     * @access public
     */
    function get_all_php_files($dir, &$excludeFiles, $recursive) {
        global $spc_config;
        $phpExtensions = $spc_config["extensions"];
        $dirs[] = $dir;
        while(count($dirs) > 0) {
            $currDir = realpath(array_pop($dirs));
            if(!is_readable($currDir)) {
                continue;
            }
            $currFiles = scandir($currDir);
            for($j = 0; $j < count($currFiles); $j++) {
                if($currFiles[$j] == "." || $currFiles[$j] == "..") {
                    continue;
                }
                $currFiles[$j] = $currDir . "/" . $currFiles[$j];
                if(is_file($currFiles[$j])) {
                    $pathParts = pathinfo($currFiles[$j]);
                    // Ignore phpcoverage bottom and top stubs
                    if(strpos($pathParts['basename'], "phpcoverage.remote.") !== false) {
                        continue;
                    }
                    // Ignore files specified in the exclude list
                    if(in_array(realpath($currFiles[$j]), $excludeFiles) !== false) {
                        continue;
                    }
                    if(isset($pathParts['extension'])
                        && in_array($pathParts['extension'], $phpExtensions)) {
                        $files[] = $currFiles[$j];
                    }
                }
                else if(is_dir($currFiles[$j]) && $recursive) {
                    $dirs[] = $currFiles[$j];
                }
            }
        }
        return $files;
    }

    // Initialize

    $RECURSIVE = false;
    $UNDO = false;

    $top_file = "/phpcoverage.remote.top.inc.php";
    $bottom_file = "/phpcoverage.remote.bottom.inc.php";

    //print_r($argv);
    for($i = 1; $i < $argc; $i++) {
        switch($argv[$i]) {
        case "-r":
            $RECURSIVE = true;
            break;

        case "-p":
            $PHPCOVERAGE_HOME = $argv[++$i];
            break;

        case "-b":
            $LOCAL_PHPCOVERAGE_LOCATION = $argv[++$i];
            break;

        case "-u":
            $UNDO = true;
            break;

        case "-e":
            $EXCLUDE_FILES = explode(",", $argv[++$i]);
            break;

        case "-v":
            $VERBOSE = true;
            break;

        case "-h":
            help();
            break;

        default:
            $paths[] = $argv[$i];
            break;
        }
    }


    if(!is_dir($LOCAL_PHPCOVERAGE_LOCATION)) {
        error("LOCAL_PHPCOVERAGE_LOCATION [$LOCAL_PHPCOVERAGE_LOCATION] not found.");
    }
    if(empty($PHPCOVERAGE_HOME) || !is_dir($PHPCOVERAGE_HOME)) {
        $PHPCOVERAGE_HOME = __PHPCOVERAGE_HOME;
        if(empty($PHPCOVERAGE_HOME) || !is_dir($PHPCOVERAGE_HOME)) {
            error("PHPCOVERAGE_HOME does not exist. [" . $PHPCOVERAGE_HOME . "]");
        }
    }

    $LOCAL_PHPCOVERAGE_LOCATION = realpath($LOCAL_PHPCOVERAGE_LOCATION);
    if(file_exists($LOCAL_PHPCOVERAGE_LOCATION . $top_file)) {
        unlink($LOCAL_PHPCOVERAGE_LOCATION . $top_file);
    }
    $ret = copy($PHPCOVERAGE_HOME . $top_file, $LOCAL_PHPCOVERAGE_LOCATION . $top_file);
    if(!$ret) {
        error("Cannot copy to $LOCAL_PHPCOVERAGE_LOCATION");
    }
    if(file_exists($LOCAL_PHPCOVERAGE_LOCATION . $bottom_file)) {
        unlink($LOCAL_PHPCOVERAGE_LOCATION . $bottom_file);
    }
    $ret = copy($PHPCOVERAGE_HOME . $bottom_file, $LOCAL_PHPCOVERAGE_LOCATION . $bottom_file);
    if(!$ret) {
        error("Cannot copy to $LOCAL_PHPCOVERAGE_LOCATION");
    }
    $top="<?php require_once \"" . $LOCAL_PHPCOVERAGE_LOCATION . $top_file ."\"; ?>\n";
    $bottom="<?php require \"" . $LOCAL_PHPCOVERAGE_LOCATION . $bottom_file . "\"; ?>\n";

    if(empty($paths)) {
        $paths[] = getcwd();
    }
    if(!isset($EXCLUDE_FILES) || empty($EXCLUDE_FILES)) {
        $EXCLUDE_FILES = array();
    }
    for($i = 0; $i < count($EXCLUDE_FILES); $i++) {
        // Remove a file from the array if it does not exist
        if(!file_exists($EXCLUDE_FILES[$i])) {
            array_splice($EXCLUDE_FILES, $i, 1);
            $i --;
            continue;
        } 
        $EXCLUDE_FILES[$i] = realpath($EXCLUDE_FILES[$i]);
    }

    //print_r($paths);
    foreach($paths as $path) {
        unset($files);
        if(is_dir($path)) {
            $files = get_all_php_files($path, $EXCLUDE_FILES, $RECURSIVE);
        }
        else if(is_file($path)) {
            $files[] = $path;
        }
        else {
            error("Unknown entity: $path");
        }
        //print_r($files);
        foreach($files as $file) {
            if($UNDO) {
                uninstrument($file);
            }
            else {
                instrument($file);
            }
        }
    }
?>
