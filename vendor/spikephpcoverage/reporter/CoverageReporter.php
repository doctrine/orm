<?php
    /*
    *  $Id: CoverageReporter.php 14665 2005-03-23 19:37:50Z npac $
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
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";

    /*{{{ Defines */

    define("TOTAL_FILES_EXPLAIN", "count of included source code files");
    define("TOTAL_LINES_EXPLAIN", "includes comments and whitespaces");
    define("TOTAL_COVERED_LINES_EXPLAIN", "lines of code that were executed");
    define("TOTAL_UNCOVERED_LINES_EXPLAIN", "lines of executable code that were not executed");
    define ("TOTAL_LINES_OF_CODE_EXPLAIN", "lines of executable code");

    /*}}}*/

    /** 
    * The base class for reporting coverage. This is an abstract as it does not 
    * implement the generateReport() function. Every concrete subclass must
    * implement this method to generate a report.
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: 14665 $
    * @package SpikePHPCoverage_Reporter
    */
    abstract class CoverageReporter {
        // {{{ Members

        protected $logger;

        // Report heading - will be displayed as the title of the main page.
        protected $heading;
        // CSS file path to be used.
        protected $style;
        // Directory where the report file(s) are written.
        protected $outputDir;

        // Total number of lines in all the source files.
        protected $grandTotalLines;
        // Total number of lines covered in code coverage measurement.
        protected $grandTotalCoveredLines;
        // Total number of executable code lines that were left untouched.
        protected $grandTotalUncoveredLines;
        // Total number of files included
        protected $grandTotalFiles;
        protected $fileCoverage = array();
        protected $recorder = false;

        // }}}
        /*{{{ public function __construct()*/

        /** 
        * The constructor (PHP5 compatible)  
        * 
        * @param $heading
        * @param $style
        * @param $dir 
        * @access public
        */
        public function __construct(
            $heading="Coverage Report",
            $style="",
            $dir="report"
        ) {

            global $util;
            echo get_class($util);
            $this->heading = $heading;
            $this->style = $style;
            $this->outputDir = $util->replaceBackslashes($dir);
            // Create the directory if not there
            $this->createReportDir();
            $this->grandTotalFiles = 0;
            $this->grandTotalLines = 0;
            $this->grandTotalCoveredLines = 0;
            $this->grandTotalUncoveredLines = 0;

            // Configure
            $this->logger = $util->getLogger();
        }

        /*}}}*/
        /*{{{ protected function createReportDir() */

        /** 
        * Create the report directory if it does not exists 
        * 
        * @access protected
        */
        protected function createReportDir() {
            global $util;
            if(!file_exists($this->outputDir)) {
                $util->makeDirRecursive($this->outputDir, 0755);
            }
            if(file_exists($this->outputDir)) {
                $this->outputDir = $util->replaceBackslashes(realpath($this->outputDir));
            }
        }

        /*}}}*/
        /*{{{ protected function updateGrandTotals() */

        /** 
        * Update the grand totals
        * 
        * @param &$coverageCounts Coverage counts for a file 
        * @access protected
        */
        protected function updateGrandTotals(&$coverageCounts) {
            $this->grandTotalLines += $coverageCounts['total'];
            $this->grandTotalCoveredLines += $coverageCounts['covered'];
            $this->grandTotalUncoveredLines += $coverageCounts['uncovered'];

            $this->recordFileCoverageInfo($coverageCounts);
        }

        /*}}}*/
        /*{{{ public function getGrandCodeCoveragePercentage()*/

        /** 
        * Returns Overall Code Coverage percentage
        * 
        * @return double Code Coverage percentage rounded to two decimals
        * @access public
        */
        public function getGrandCodeCoveragePercentage() {
            if($this->grandTotalCoveredLines+$this->grandTotalUncoveredLines == 0) {
                return round(0, 2);
            }
            return round(((double)$this->grandTotalCoveredLines/((double)$this->grandTotalCoveredLines + (double)$this->grandTotalUncoveredLines)) * 100.0, 2);
        }

        /*}}}*/
        /*{{{ public function getFileCoverageInfo() */

        /** 
        * Return the array containing file coverage information.
        *
        * The array returned contains following fields
        *   * filename: Name of the file
        *   * total: Total number of lines in that file
        *   * covered: Total number of executed lines in that file
        *   * uncovered: Total number of executable lines that were not executed.
        * 
        * @return array Array of file coverage information
        * @access public
        */
        public function getFileCoverageInfo() {
            return $this->fileCoverage;
        }

        /*}}}*/
        /*{{{ public function recordFileCoverageInfo() */

        /** 
        * Record the file coverage information for a file.
        * 
        * @param &$fileCoverage Coverage information for a file
        * @access protected
        */
        protected function recordFileCoverageInfo(&$fileCoverage) {
            $this->fileCoverage[] = $fileCoverage;
        }

        /*}}}*/
        /*{{{ public function printTextSummary() */

        /** 
        * Print the coverage summary to filename (if specified) or stderr 
        * 
        * @param $filename=false Filename to write the log to
        * @access public
        */
        public function printTextSummary($filename=false) {
            global $util;
            $str = "\n";
            $str .= "##############################################\n";
            $str .= " Code Coverage Summary: " . $this->heading . "\n";
            $str .= "   Total Files: " . $this->grandTotalFiles . "\n";
            $str .= "   Total Lines: " . $this->grandTotalLines . "\n";
            $str .= "   Total Covered Lines of Code: " . $this->grandTotalCoveredLines . "\n";
            $str .= "   Total Missed Lines of Code: " . $this->grandTotalUncoveredLines . "\n";
            $str .= "   Total Lines of Code: " . ($this->grandTotalCoveredLines + $this->grandTotalUncoveredLines) . "\n";
            $str .= "   Code Coverage: " . $this->getGrandCodeCoveragePercentage() . "%\n";
            $str .= "##############################################\n";

            if(empty($filename)) {
                file_put_contents("php://stdout", $str);
            }
            else {
                $filename = $util->replaceBackslashes($filename);
                if(!file_exists(dirname($filename))) {
                    $ret = $util->makeDirRecursive(dirname($filename), 0755);
                    if(!$ret) {
                        die ("Cannot create directory " . dirname($filename) . "\n");
                    }
                }
                file_put_contents($filename, $str);
            }
        }

        /*}}}*/
/*{{{ protected function makeRelative() */

        /** 
         * Convert the absolute path to PHP file markup to a path relative
         * to the report dir.
         * 
         * @param $filepath PHP markup file path
         * @return Relative file path
         * @access protected
         */
        protected function makeRelative($filepath) {
            $dirPath = realpath($this->outputDir);
            $absFilePath = realpath($filepath);

            if(strpos($absFilePath, $dirPath) === 0) {
                $relPath = substr($absFilePath, strlen($dirPath)+1);
                return $relPath;
            }
            return $absFilePath;
        }

/*}}}*/
/*{{{ protected function getRelativeOutputDirPath() */


        /** 
         * Get the relative path of report directory with respect to the given
         * filepath
         * 
         * @param $filepath Path of the file (relative to the report dir)
         * @return String Relative path of report directory w.r.t. filepath
         * @access protected
         */
        protected function getRelativeOutputDirPath($filepath) {
            $relPath = "";
            $filepath = dirname($filepath);
            while($filepath !== false && $filepath != ".") {
                $relPath = "../" . $relPath;
                $filepath = dirname($filepath);
            }
            return $relPath;
        }

/*}}}*/
        /*{{{ public abstract function generateReport() */

        /**
        *
        * This function generates report using one of the concrete subclasses.
        *
        * @param &$data Coverage Data recorded by coverage recorder.
        * @access public
        */
        public abstract function generateReport(&$data);

        /*}}}*/
        /*{{{ Getters and Setters */

        public function setHeading($heading) {
            $this->heading = $heading;
        }

        public function getHeading() {
            return $this->heading;
        }

        public function setStyle($style) {
            $this->style = $style;
        }

        public function getStyle() {
            return $this->style;
        }

        public function setOutputDir($dir) {
            $this->outputDir = $dir;
        }

        public function getOutputDir() {
            return $this->outputDir;
        }

        public function setCoverageRecorder(&$recorder) {
            $this->recorder = $recorder;
        }

        /*}}}*/
    }
?>
