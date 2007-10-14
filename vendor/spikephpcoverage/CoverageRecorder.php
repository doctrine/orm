<?php
    /*
    *  $Id: CoverageRecorder.php 14665 2005-03-23 19:37:50Z npac $
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php

    if(!defined("__PHPCOVERAGE_HOME")) {
        define("__PHPCOVERAGE_HOME", dirname(__FILE__));
    }
    require_once __PHPCOVERAGE_HOME . "/conf/phpcoverage.conf.php";
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";
    require_once __PHPCOVERAGE_HOME . "/reporter/CoverageReporter.php";

    /**
    *
    * The Coverage Recorder utility
    *
    * This is the main class for the CoverageRecorder. User should 
    * instantiate this class and set various parameters of it.
    * The startInstrumentation and stopInstrumentation methods will
    * switch code coverage recording on and off respectively. 
    *
    * The code coverage is recorded using XDebug Zend Extension. Therefore,
    * it is required to install that extension on the system where
    * code coverage measurement is going to take place. See
    * {@link http://www.xdebug.org www.xdebug.org} for more
    * information.
    *
    * @author		Nimish Pachapurkar <npac@spikesource.com>
    * @version		$Revision: 14665 $
    */
    class CoverageRecorder {

        // {{{ Members

        protected $includePaths;
        protected $excludePaths;
        protected $reporter;
        protected $coverageData;
        protected $isRemote = false;
        protected $stripped = false;
        protected $phpCoverageFiles = array("phpcoverage.inc.php");
        protected $version;
        protected $logger;

        /** 
        * What extensions are treated as php files. 
        * 
        * @param "php" Array of extension strings
        */
        protected $phpExtensions;

        // }}}
        // {{{ Constructor

        /** 
        * Constructor (PHP5 only) 
        * 
        * @param $includePaths Directories to be included in code coverage report
        * @param $excludePaths Directories to be excluded from code coverage report
        * @param $reporter Instance of a Reporter subclass
        * @access public
        */
        public function __construct(
            $includePaths=array("."),
            $excludePaths=array(), 
            $reporter="new HtmlCoverageReporter()"
        ) {

            $this->includePaths = $includePaths;
            $this->excludePaths = $excludePaths;
            $this->reporter = $reporter;
            // Set back reference
            $this->reporter->setCoverageRecorder($this);
            $this->excludeCoverageDir();
            $this->version = "0.8";

            // Configuration
            global $spc_config;
            $this->phpExtensions = $spc_config['extensions'];
            global $util;
            $this->logger = $util->getLogger();
        }

        // }}}
        // {{{ public function startInstrumentation()

        /** 
        * Starts the code coverage recording 
        * 
        * @access public
        */
        public function startInstrumentation() {
            if(extension_loaded("xdebug")) {
                xdebug_start_code_coverage();
                return true;
            }
            $this->logger->critical("[CoverageRecorder::startInstrumentation()] " 
            . "ERROR: Xdebug not loaded.", __FILE__, __LINE__);
            return false;
        }

        // }}}
        // {{{ public function stopInstrumentation()

        /** 
        * Stops code coverage recording 
        * 
        * @access public
        */
        public function stopInstrumentation() {
            if(extension_loaded("xdebug")) {
                $this->coverageData = xdebug_get_code_coverage();
                xdebug_stop_code_coverage();
                $this->logger->debug("[CoverageRecorder::stopInstrumentation()] Code coverage: " . print_r($this->coverageData, true),
                    __FILE__, __LINE__);
                return true;
            }
            else {
                $this->logger->critical("[CoverageRecorder::stopInstrumentation()] Xdebug not loaded.", __FILE__, __LINE__);
            }
            return false;
        }

        // }}}
        // {{{ public function generateReport()

        /** 
        * Generate the code coverage report 
        * 
        * @access public
        */
        public function generateReport() {
            if($this->isRemote) {
                $this->logger->info("[CoverageRecorder::generateReport()] "
                ."Writing report.", __FILE__, __LINE__);
            }
            else {
                $this->logger->info("[CoverageRecorder::generateReport()] "
                . "Writing report:\t\t", __FILE__, __LINE__);
            }
            $this->logger->debug("[CoverageRecoder::generateReport()] " . print_r($this->coverageData, true),
                __FILE__, __LINE__);
            $this->unixifyCoverageData();
            $this->coverageData = $this->stripCoverageData();
            $this->reporter->generateReport($this->coverageData);
            if($this->isRemote) {
                $this->logger->info("[CoverageRecorder::generateReport()] [done]", __FILE__, __LINE__);
            }
            else {
                $this->logger->info("[done]", __FILE__, __LINE__);
            }
        }

        // }}}
        /*{{{ protected function removeAbsentPaths() */

        /** 
        * Remove the directories that do not exist from the input array 
        * 
        * @param &$dirs Array of directory names
        * @access protected
        */
        protected function removeAbsentPaths(&$dirs) {
            for($i = 0; $i < count($dirs); $i++) {
                if(! file_exists($dirs[$i])) {
                    // echo "Not found: " . $dirs[$i] . "\n";
                    $this->errors[] = "Not found: " . $dirs[$i] 
                    . ". Removing ...";
                    array_splice($dirs, $i, 1);
                    $i--;
                }
                else {
                    $dirs[$i] = realpath($dirs[$i]);
                }
            }
        }

        /*}}}*/
        // {{{ protected function processSourcePaths()

        /** 
        * Processes and validates the source directories 
        * 
        * @access protected
        */
        protected function processSourcePaths() {
            $this->removeAbsentPaths($this->includePaths);
            $this->removeAbsentPaths($this->excludePaths);

            sort($this->includePaths, SORT_STRING);
        }

        // }}}
        /*{{{ protected function getFilesAndDirs() */

        /** 
        * Get the list of files that match the extensions in $this->phpExtensions 
        * 
        * @param $dir Root directory
        * @param &$files Array of filenames to append to
        * @access protected
        */
        protected function getFilesAndDirs($dir, &$files) {
            global $util;
            $dirs[] = $dir;
            while(count($dirs) > 0) {
                $currDir = realpath(array_pop($dirs));
                if(!is_readable($currDir)) {
                    continue;
                }
                //echo "Current Dir: $currDir \n";
                $currFiles = scandir($currDir);
                //print_r($currFiles);
                for($j = 0; $j < count($currFiles); $j++) {
                    if($currFiles[$j] == "." || $currFiles[$j] == "..") {
                        continue;
                    }
                    $currFiles[$j] = $currDir . "/" . $currFiles[$j];
                    //echo "Current File: " . $currFiles[$j] . "\n";
                    if(is_file($currFiles[$j])) {
                        $pathParts = pathinfo($currFiles[$j]);
                        if(isset($pathParts['extension']) && in_array($pathParts['extension'], $this->phpExtensions)) {
                            $files[] = $util->replaceBackslashes($currFiles[$j]);
                        }
                    }
                    if(is_dir($currFiles[$j])) {
                        $dirs[] = $currFiles[$j];
                    }
                }
            }
        }

        /*}}}*/
        /*{{{ protected function addFiles() */

        /** 
        * Add all source files to the list of files that need to be parsed. 
        * 
        * @access protected
        */
        protected function addFiles() {
            global $util;
            $files = array();
            for($i = 0; $i < count($this->includePaths); $i++) {
                $this->includePaths[$i] = $util->replaceBackslashes($this->includePaths[$i]);
                if(is_dir($this->includePaths[$i])) {
                    //echo "Calling getFilesAndDirs with " . $this->includePaths[$i] . "\n";
                    $this->getFilesAndDirs($this->includePaths[$i], $files);
                }
                else if(is_file($this->includePaths[$i])) {
                    $files[] = $this->includePaths[$i];
                }
            }

            $this->logger->debug("Found files:" . print_r($files, true),
                __FILE__, __LINE__);
            for($i = 0; $i < count($this->excludePaths); $i++) {
                $this->excludePaths[$i] = $util->replaceBackslashes($this->excludePaths[$i]);
            }

            for($i = 0; $i < count($files); $i++) {
                for($j = 0; $j < count($this->excludePaths); $j++) {
                    $this->logger->debug($files[$i] . "\t" . $this->excludePaths[$j] . "\n", __FILE__, __LINE__);
                    if(strpos($files[$i], $this->excludePaths[$j]) === 0) {
                        continue;
                    }
                }
                if(!array_key_exists($files[$i], $this->coverageData)) {
                    $this->coverageData[$files[$i]] =  array();
                }
            }
        }

        /*}}}*/
        // {{{ protected function stripCoverageData()

        /** 
        * Removes the unwanted coverage data from the recordings 
        * 
        * @return Processed coverage data
        * @access protected
        */
        protected function stripCoverageData() {
            if($this->stripped) {
                $this->logger->debug("[CoverageRecorder::stripCoverageData()] Already stripped!", __FILE__, __LINE__);
                return $this->coverageData;
            }
            $this->stripped = true;
            if(empty($this->coverageData)) {
                $this->logger->warn("[CoverageRecorder::stripCoverageData()] No coverage data found.", __FILE__, __LINE__);
                return $this->coverageData;
            }
            $this->processSourcePaths();
            $this->logger->debug("!!!!!!!!!!!!! Source Paths !!!!!!!!!!!!!!",
                __FILE__, __LINE__);
            $this->logger->debug(print_r($this->includePaths, true),
                __FILE__, __LINE__);
            $this->logger->debug(print_r($this->excludePaths, true),
                __FILE__, __LINE__);
            $this->logger->debug("!!!!!!!!!!!!! Source Paths !!!!!!!!!!!!!!",
                __FILE__, __LINE__);
            $this->addFiles();
            $altCoverageData = array();
            foreach ($this->coverageData as $filename => &$lines) {
                $preserve = false;
                $realFile = $filename;
                for($i = 0; $i < count($this->includePaths); $i++) {
                    if(strpos($realFile, $this->includePaths[$i]) === 0) {
                        $preserve = true;
                    }
                    else {
                        $this->logger->debug("File: " . $realFile 
                        . "\nDoes not match: " . $this->includePaths[$i],
                        __FILE__, __LINE__);
                    }
                }
                // Exclude dirs have a precedence over includes.
                for($i = 0; $i < count($this->excludePaths); $i++) {
                    if(strpos($realFile, $this->excludePaths[$i]) === 0) {
                        $preserve = false;
                    }
                    else if(in_array(basename($realFile), $this->phpCoverageFiles)) {
                        $preserve = false;
                    }
                }
                if($preserve) {
                    // Should be preserved
                    $altCoverageData[$filename] = $lines;
                }
            }

            array_multisort($altCoverageData, SORT_STRING);
            return $altCoverageData;
        }

        // }}}
        /*{{{ protected function unixifyCoverageData() */

        /** 
        * Convert filepaths in coverage data to forward slash separated
        * paths.
        * 
        * @access protected
        */
        protected function unixifyCoverageData() {
            global $util;
            $tmpCoverageData = array();
            foreach($this->coverageData as $file => &$lines) {
                $tmpCoverageData[$util->replaceBackslashes(realpath($file))] = $lines;
            }
            $this->coverageData = $tmpCoverageData;
        }

        /*}}}*/
        // {{{ public function getErrors()

        /** 
        * Returns the errors array containing all error encountered so far. 
        * 
        * @return Array of error messages
        * @access public
        */
        public function getErrors() {
            return $this->errors;
        }

        // }}}
        // {{{ public function logErrors()

        /** 
        * Writes all error messages to error log 
        * 
        * @access public
        */
        public function logErrors() {
            $this->logger->error(print_r($this->errors, true),
                __FILE__, __LINE__);
        }

        // }}}
        /*{{{ Getters and Setters */

        public function getIncludePaths() {
            return $this->includePaths;
        }

        public function setIncludePaths($includePaths) {
            $this->includePaths = $includePaths;
        }

        public function getExcludePaths() {
            return $this->excludePaths;
        }

        public function setExcludePaths($excludePaths) {
            $this->excludePaths = $excludePaths;
            $this->excludeCoverageDir();
        }

        public function getReporter() {
            return $this->reporter;
        }

        public function setReporter(&$reporter) {
            $this->reporter = $reporter;
        }

        public function getPhpExtensions() {
            return $this->phpExtensions;
        }

        public function setPhpExtensions(&$extensions) {
            $this->phpExtensions = $extensions;
        }

        public function getVersion() {
            return $this->version;
        }

        /*}}}*/
        /*{{{ public function excludeCoverageDir() */

        /** 
        * Exclude the directory containing the coverage measurement code. 
        *
        * @access public
        */
        public function excludeCoverageDir() {
            $f = __FILE__;
            if(is_link($f)) {
                $f = readlink($f);
            }
            $this->excludePaths[] = realpath(dirname($f));
        }
        /*}}}*/
    }
?>
