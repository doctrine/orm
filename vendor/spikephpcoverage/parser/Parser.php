<?php
    /*
    *  $Id: Parser.php 14009 2005-03-16 17:33:33Z npac $
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


    /** 
    * Base class for Parsers. 
    * 
    * @author Nimish Pachapurkar (npac@spikesource.com)
    * @version $Revision: 14009 $
    * @package SpikePHPCoverage_Parser
    */

    define("LINE_TYPE_UNKNOWN", "0");
    define("LINE_TYPE_EXEC", "1");
    define("LINE_TYPE_NOEXEC", "2");
    define("LINE_TYPE_CONT", "3");

    abstract class Parser {
        /*{{{ Members */

        protected $totalLines;
        protected $coveredLines;
        protected $uncoveredLines;
        protected $fileRef;
        protected $filename;

        protected $line;
        protected $logger;

        /*}}}*/
        /*{{{ public function __construct() */
        /** 
        * Constructor 
        * @access public
        */
        public function __construct() {
            global $util;
            $this->totalLines = 0;
            $this->coveredLines = 0;
            $this->uncoveredLines = 0;

            $this->fileRef = false;
            $this->line = false;
            $this->lineType = false;

            $this->logger = $util->getLogger();
        }

        /*}}}*/
        /*{{{ public abstract function parse() */

        /** 
        * Parse a given file 
        * 
        * @param $filename Full path of the file
        * @return FALSE on error.
        * @access public
        */
        public function parse($filename) {
            $this->filename = $filename;
            $ret = $this->openFileReadOnly();
            if(!$ret) {
                die("Error: Cannot open file: $this->filename \n");
            }
        }

        /*}}}*/
        /*{{{ protected abstract function processLine() */

        /** 
        * Process the line and classify it into either
        * covered and uncovered.
        * 
        * @param $line 
        * @return 
        * @access protected
        */
        protected abstract function processLine($line);

        /*}}}*/
        /*{{{ public function getLine() */

        /** 
        * Returns the next line from file. 
        * 
        * @return Next line from file
        * @access public
        */
        public function getLine() {
            if(!feof($this->fileRef)) {
                $this->line = fgets($this->fileRef);
                $this->processLine($this->line);
            }
            else {
                fclose($this->fileRef);
                $this->line = false;
            }
            return $this->line;
        }

        /*}}}*/
        /*{{{ public abstract function getLineType() */

        /** 
        * Returns the type of last line read.
        *
        * The type can be either 
        *  * LINE_TYPE_EXEC Line that can be executed.
        *  * LINE_TYPE_NOEXEC Line that cannot be executed.
        *     This includes the variable and function definitions
        *     (without initialization), blank lines, non-PHP lines,
        *     etc.
        * 
        * @return Type of last line
        * @access public
        */
        public abstract function getLineType();

        /*}}}*/
        /*{{{ public function getLineTypeStr() */

        /** 
        * Returns the string representation of LINE_TYPE 
        * 
        * @param $lineType 
        * @return Type of line
        * @access public
        */
        public function getLineTypeStr($lineType) {
            if($lineType == LINE_TYPE_EXEC) {
                return "LINE_TYPE_EXEC";
            }
            else if($lineType == LINE_TYPE_NOEXEC) {
                return "LINE_TYPE_NOEXEC";
            }
            else if($lineType == LINE_TYPE_CONT) {
                return "LINE_TYPE_CONT";
            }
            else {
                return "LINE_TYPE_UNKNOWN";
            }
        }

        /*}}}*/
        /*{{{ protected function openFileReadOnly() */

        /** 
        * Opens the file to be parsed in Read-only mode 
        * 
        * @return FALSE on failure.
        * @access protected
        */
        protected function openFileReadOnly() {
            $this->fileRef = fopen($this->filename, "r");
            return $this->fileRef !== false;
        }

        /*}}}*/
        /*{{{ public function getTotalLines() */

        /** 
        * Returns the total lines (PHP, non-PHP) from a file 
        * 
        * @return Number of lines
        * @access public
        */
        public function getTotalLines() {
            return $this->totalLines;
        }

        /*}}}*/
        /*{{{ public function getCoveredLines() */

        /** 
        * Returns the number of covered PHP lines
        * 
        * @return Number of covered lines
        * @access public
        */
        public function getCoveredLines() {
            return $this->coveredLines;
        }

        /*}}}*/
        /*{{{ public function getUncoveredLines() */

        /** 
        * Returns the number of uncovered PHP lines 
        *
        * Note that the sum of covered and uncovered
        * lines may not be equal to total lines.
        * 
        * @return Number of uncovered lines
        * @access public
        */
        public function getUncoveredLines() {
            return $this->uncoveredLines;
        }

        /*}}}*/
    }

?>
