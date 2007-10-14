<?php
    /*
    *  $Id: Utility.php 14663 2005-03-23 19:27:27Z npac $
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php

    if(!defined("__PHPCOVERAGE_HOME")) {
        define("__PHPCOVERAGE_HOME", dirname(dirname(__FILE__)));
    }
    require_once __PHPCOVERAGE_HOME . "/conf/phpcoverage.conf.php";

    // include our dummy implementation
    require_once 'CoverageLogger.php';


    /** 
    * Utility functions 
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: $
    * @package SpikePHPCoverage_Util
    */
    class Utility {

        public static $logger;

        /*{{{ public function getTimeStamp() */

        /** 
        * Return the current timestamp in human readable format.
        * Thursday March 17, 2005 19:10:47
        * 
        * @return Readable timestamp
        * @access public
        */
        public function getTimeStamp() {
            $ts = getdate();
            return $ts["weekday"] . " " . $ts["month"] . " " . $ts["mday"] 
            . ", " . $ts["year"] . " " . sprintf("%02d:%02d:%02d", $ts["hours"], $ts["minutes"], $ts["seconds"]);
        }

        /*}}}*/
        /*{{{ public function shortenFilename() */

        /** 
        * Shorten the filename to some maximum characters 
        * 
        * @param $filename Complete file path
        * @param $maxlength=150 Maximum allowable length of the shortened 
        * filepath
        * @return Shortened file path
        * @access public
        */
        public function shortenFilename($filename, $maxlength=80) {
            $length = strlen($filename);
            if($length < $maxlength) {
                return $filename;
            }

            // trim the first few characters
            $filename = substr($filename, $length-$maxlength);
            // If there is a path separator slash in first n characters,
            // trim upto that point.
            $n = 20;
            $firstSlash = strpos($filename, "/");
            if($firstSlash === false || $firstSlash > $n) {
                $firstSlash = strpos($filename, "\\");
                if($firstSlash === false || $firstSlash > $n) {
                    return "..." . $filename;
                }
                return "..." . substr($filename, $firstSlash);
            }
            return "..." . substr($filename, $firstSlash);
        }

        /*}}}*/
        /*{{{ public function writeError() */

        /** 
        * Write error log if debug is on 
        * 
        * @param $str Error string 
        * @access public
        */
        public function writeError($str) {
            if(__PHPCOVERAGE_DEBUG) {
                error_log($str);
            }
        }
        /*}}}*/
        /*{{{ public function unixifyPath() */

        /** 
        * Convert Windows paths to Unix paths 
        * 
        * @param $path File path
        * @return String Unixified file path
        * @access public
        */
        public function unixifyPath($path) {
            // Remove the drive-letter:
            if(strpos($path, ":") == 1) {
                $path = substr($path, 2);
            }
            $path = $this->replaceBackslashes($path);
            return $path;
        }

        /*}}}*/
        /*{{{ public function replaceBackslashes() */

        /** 
        * Convert the back slash path separators with forward slashes. 
        * 
        * @param $path Windows path with backslash path separators
        * @return String Path with back slashes replaced with forward slashes.
        * @access public
        */
        public function replaceBackslashes($path) {
            $path = str_replace("\\", "/", $path);
            return $this->capitalizeDriveLetter($path);
        }
        /*}}}*/
        /*{{{ public function capitalizeDriveLetter() */

        /** 
         * Convert the drive letter to upper case
         * 
         * @param $path Windows path with "c:<blah>"
         * @return String Path with driver letter capitalized.
         * @access public
        */
        public function capitalizeDriveLetter($path) {
            if(strpos($path, ":") === 1) {
                $path = strtoupper(substr($path, 0, 1)) . substr($path, 1);
            }
            return $path;
        }

        /*}}}*/
        /*{{{ public function makeDirRecursive() */
        /** 
         * Make directory recursively. 
         * (Taken from: http://aidan.dotgeek.org/lib/?file=function.mkdirr.php)
         * 
         * @param $dir Directory path to create
         * @param $mode=0755 
         * @return True on success, False on failure
         * @access public
        */
        public function makeDirRecursive($dir, $mode=0755) {
            // Check if directory already exists
            if (is_dir($dir) || empty($dir)) {
                return true;
            }

            // Ensure a file does not already exist with the same name
            if (is_file($dir)) {
                $this->getLogger()->debug("File already exists: " . $dir,
                    __FILE__, __LINE__);
                return false;
            }

            $dir = $this->replaceBackslashes($dir);

            // Crawl up the directory tree
            $next_pathname = substr($dir, 0, strrpos($dir, "/"));
            if ($this->makeDirRecursive($next_pathname, $mode)) {
                if (!file_exists($dir)) {
                    return mkdir($dir, $mode);
                }
            }

            return false;
        }
        /*}}}*/
        /*{{{ public function getOS() */
        /** 
         * Returns the current OS code  
         * WIN - Windows, LIN -Linux, etc. 
         *
         * @return String 3 letter code for current OS
         * @access public
         * @since  0.6.6
        */
        public function getOS() {
            return strtoupper(substr(PHP_OS, 0, 3));
        }
        /*}}}*/
        /*{{{ public function getTmpDir() */

        public function getTmpDir() {
            global $spc_config;
            $OS = $this->getOS();
            switch($OS) {
            case "WIN":
                return $spc_config['windows_tmpdir'];
            default:
                return $spc_config['tmpdir'];
            }
        }

        /*}}}*/
        /*{{{ public function getLogger() */

        public function getLogger($package=false) {
            global $spc_config;
            if(!isset($this->logger) || $this->logger == NULL) {
                $this->logger =& new CoverageLogger();
                $this->logger->setLevel($spc_config["log_level"]);
            }
            return $this->logger;
        }

        /*}}}*/
    }
    $util = new Utility();
    global $util;
?>
