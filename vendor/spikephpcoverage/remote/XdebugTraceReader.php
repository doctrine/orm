<?php
    /*
    *  $Id$
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php

    /** 
    * Reader that parses Xdebug Trace data. 
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: $
    * @package SpikePHPCoverage_Parser
    */
    class XdebugTraceReader {
        /*{{{ Members */

        protected $traceFilePath;
        protected $handle;
        protected $coverage = array();

        /*}}}*/
        /*{{{ Constructor */

        /** 
        * Constructor 
        * 
        * @param $traceFilePath Path of the Xdebug trace file
        * @access public
        */
        public function __construct($traceFilePath) {
            $this->traceFilePath = $traceFilePath;
        }

        /*}}}*/
        /*{{{ protected function openTraceFile() */

        /** 
        * Opens the trace file 
        * 
        * @return Boolean True on success, false on failure.
        * @access protected
        */
        protected function openTraceFile() {
            $this->handle = fopen($this->traceFilePath, "r");
            return !empty($this->handle);
        }

        /*}}}*/
        /*{{{ public function parseTraceFile() */

        /** 
        * Parses the trace file 
        * 
        * @return Boolean True on success, false on failure.
        * @access public
        */
        public function parseTraceFile() {
            if(!$this->openTraceFile()) {
                error_log("[XdebugTraceReader::parseTraceFile()] Unable to read trace file.");
                return false;
            }
            while(!feof($this->handle)) {
                $line = fgets($this->handle);
                // echo "Line: " . $line . "\n";
                $this->processTraceLine($line);
            }
            fclose($this->handle);
            return true;
        }

        /*}}}*/
        /*{{{ protected function processTraceLine() */

        /** 
        * Process a give trace line 
        * 
        * @param $line Line from a trace file
        * @return Boolean True on success, false on failure
        * @access protected
        */
        protected function processTraceLine($line) {
            $dataparts = explode("\t", $line);
            // print_r($dataparts);
            $cnt = count($dataparts);
            if($cnt < 2) {
                return false;
            }
            if(!file_exists($dataparts[$cnt-2])) {
                // echo "No file: " . $dataparts[$cnt-2] . "\n";
                return false;
            }
            // Trim the entries
            $dataparts[$cnt-2] = trim($dataparts[$cnt-2]);
            $dataparts[$cnt-1] = trim($dataparts[$cnt-1]);

            if(!isset($this->coverage[$dataparts[$cnt-2]][$dataparts[$cnt-1]])) {
                $this->coverage[$dataparts[$cnt-2]][$dataparts[$cnt-1]] = 1;
            }
            else {
                $this->coverage[$dataparts[$cnt-2]][$dataparts[$cnt-1]] ++;
            }
            return true;
        }

        /*}}}*/
        /*{{{ public function getCoverageData() */

        /** 
        * Returns the coverage array 
        * 
        * @return Array Array of coverage data from parsing.
        * @access public
        */
        public function getCoverageData() {
            return $this->coverage;
        }

        /*}}}*/
    }
?>
