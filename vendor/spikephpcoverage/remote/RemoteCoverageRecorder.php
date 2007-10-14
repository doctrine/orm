<?php
    /*
    *  $Id: RemoteCoverageRecorder.php 14665 2005-03-23 19:37:50Z npac $
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
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";
    require_once __PHPCOVERAGE_HOME . "/CoverageRecorder.php";
    require_once __PHPCOVERAGE_HOME . "/remote/XdebugTraceReader.php";
    require_once __PHPCOVERAGE_HOME . "/parser/CoverageXmlParser.php";

    /** 
    * A Coverage recorder extension for remote Coverage measurement. 
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: $
    * @package SpikePHPCoverage_Remote
    */
    class RemoteCoverageRecorder extends CoverageRecorder {
        /*{{{ Members */

        protected $traceFilePath;
        protected $xdebugTraceReader;
        protected $tmpDir;
        protected $tmpTraceFilename = "phpcoverage.xdebug.trace";
        protected $coverageFileName = "phpcoverage.coverage.xml";

        protected $xmlStart = "<?xml version=\"1.0\" encoding=\"utf-8\" ?><spike-phpcoverage>";
        protected $xmlEnd = "</spike-phpcoverage>";

        /*}}}*/
        /*{{{ public function __construct() */

        /** 
        * Constructor 
        * 
        * @access public
        */
        public function __construct(
            $includePaths=array("."),
            $excludePaths=array(),
            $reporter="new HtmlCoverageReporter()"
        ) {
            global $util;
            parent::__construct($includePaths, $excludePaths, $reporter);
            $this->isRemote = true;
            $this->phpCoverageFiles[] = "phpcoverage.remote.inc.php";
            $this->phpCoverageFiles[] = "phpcoverage.remote.top.inc.php";
            $this->phpCoverageFiles[] = "phpcoverage.remote.bottom.inc.php";

            // configuration
            $this->tmpDir = $util->getTmpDir();
        }

        /*}}}*/
        /*{{{ Getters and Setters */

        public function getTraceFilePath() {
            return $this->traceFilePath;
        }

        public function setTraceFilePath($traceFilePath) {
            $this->traceFilePath = $traceFilePath;
        }

        public function getTmpDir() {
            return $this->tmpDir;
        }

        public function setTmpDir($tmpTraceDir) {
            $this->tmpDir = $tmpTraceDir;
        }

        public function getCoverageFileName() {
            return $this->coverageFileName;
        }

        public function setCoverageFileName($covFileName) {
            $this->coverageFileName = $covFileName;
        }

        /*}}}*/
        /*{{{ public function cleanCoverageFile() */

        /** 
        * Deletes a coverage data file if one exists. 
        * 
        * @return Boolean True on success, False on failure.
        * @access public
        */
        public function cleanCoverageFile() {
            $filepath = $this->tmpDir . "/" . $this->coverageFileName;
            if(file_exists($filepath)) {
                if(is_writable($filepath)) {
                    unlink($filepath);
                }
                else {
                    $this->logger->error("[RemoteCoverageRecorder::cleanCoverageFile()] "
                    . "ERROR: Cannot delete $filepath.", __FILE__, __LINE__);
                    return false;
                }
            }
            return true;
        }

        /*}}}*/
        /*{{{ protected function prepareCoverageXml() */

        /** 
        * Convert the Coverage data into an XML. 
        * 
        * @return String XML generated from Coverage data
        * @access protected
        */
        protected function prepareCoverageXml() {
            global $util;
            $xmlString = "";
            $xmlBody = "";
            if(!empty($this->coverageData)) {
                foreach($this->coverageData as $file => &$lines) {
                    $xmlBody .= "<file path=\"". $util->replaceBackslashes($file) . "\">";
                    foreach($lines as $linenum => &$frequency) {
                        $xmlBody .= "<line line-number=\"" . $linenum . "\"";
                        $xmlBody .= " frequency=\"" . $frequency . "\"/>";
                    }
                    $xmlBody .= "</file>\n";
                }
                unset($this->coverageData);
            }
            else {
                $this->logger->info("[RemoteCoverageRecorder::prepareCoverageXml()] Coverage data is empty.",
                    __FILE__, __LINE__);
            }
            $xmlString .= $xmlBody;
            $this->logger->debug("[RemoteCoverageRecorder::prepareCoverageXml()] Xml: " . $xmlString, __FILE__, __LINE__);
            return $xmlString;
        }

        /*}}}*/
        /*{{{ protected function parseCoverageXml() */

        /** 
        * Parse coverage XML to regenerate the Coverage data array. 
        * 
        * @param $xml XML String or URL of the coverage data
         * @param $stream=false Is the input a stream?
         * @return 
         * @access protected
        */
        protected function parseCoverageXml(&$xml, $stream=false) {
            // Need to handle multiple xml files.
            if(!is_array($xml)) {
                $xml = array($xml);
            }
            for($i = 0; $i < count($xml); $i++) {
                $xmlParser = new CoverageXmlParser();
                if($stream) {
                    $xmlParser->setInput($xml[$i]);
                }
                else {
                    $xmlParser->setInputString($xml[$i]);
                }
                $xmlParser->parse();
                $data =& $xmlParser->getCoverageData();
                if(empty($this->coverageData)) {
                    $this->coverageData = $data;
                }
                else {
                    $data2 = array_merge_recursive($this->coverageData, $data);
                    $this->coverageData = $data2;
                }
                $this->logger->debug("[RemoteCoverageRecorder::prepareCoverageXml()] " . "Coverage data intermediate: " . print_r($this->coverageData, true));
            }
        }

        /*}}}*/
        /*{{{ public function getCoverageXml() */

        /** 
        * Dumps the coverage data in XML format
        * 
        * @access public
        */
        public function getCoverageXml() {
            $filepath = $this->tmpDir . "/" . $this->coverageFileName;
            if(file_exists($filepath) && is_readable($filepath)) {
                $fp = fopen($filepath, "r");
                if($fp) {
                    while(!feof($fp)) {
                        $xml = fread($fp, 4096);
                        echo $xml;
                    }
                    fclose($fp);
                    return true;
                }
                else {
                    $this->logger->error("Could not read coverage data file.",
                        __FILE__, __LINE__);
                }
            }
            else {
                $this->logger->error("[RemoteCoverageRecorder::getCoverageXml()] " 
                . "ERROR: Cannot read file " . $filepath, __FILE__, __LINE__);
            }
            return false;
        }

        /*}}} */
        /*{{{ protected function appendDataToFile() */

        /** 
         * Append coverage data to xml file 
         * 
         * @param $newXml New xml recorded
         * @return True on success; false otherwise
         * @access protected
         */
        protected function appendDataToFile($newXml) {
            $filepath = $this->tmpDir . "/" . $this->coverageFileName;
            if(!file_exists($filepath)) {
                // If new file, write the xml start and end tags
                $bytes = file_put_contents($filepath, $this->xmlStart . "\n" . $this->xmlEnd);
                if(!$bytes) {
                    $this->logger->critical("[RemoteCoverageRecorder::appendDataToFile()] Could not create file: " . $filepath, __FILE__, __LINE__);
                    return false;
                }
            }
            if(file_exists($filepath) && is_readable($filepath)) {
                $res = fopen($filepath, "r+");
                if($res) {
                    fseek($res, -1 * strlen($this->xmlEnd), SEEK_END);
                    $ret = fwrite($res, $newXml);
                    if(!$ret) {
                        $this->logger->error("[RemoteCoverageRecorder::appendDataToFile()] Could not append data to file.",
                            __FILE__, __LINE__);
                        fclose($res);
                        return false;
                    }
                    fwrite($res, $this->xmlEnd);
                    fclose($res);
                }
                else {
                    $this->logger->error("[RemoteCoverageRecorder::appendDataToFile()] Error opening file for writing: " . $filepath,
                        __FILE__, __LINE__);
                    return false;
                }
            }
            return true;
        }

        /*}}}*/
        /*{{{ public function saveCoverageXml() */

        /** 
         * Append coverage xml to a xml data file. 
         * 
         * @return Boolean True on success, False on error
         * @access public
         */
        public function saveCoverageXml() {
            $filepath = $this->tmpDir . "/" . $this->coverageFileName;
            if($this->stopInstrumentation()) {
                $xml = $this->prepareCoverageXml();
                $ret = $this->appendDataToFile($xml);
                if(!$ret) {
                    $this->logger->warn("[RemoteCoverageRecorder::saveCoverageXml()] "
                    . "ERROR: Nothing was written to " . $filepath,
                    __FILE__, __LINE__);
                    return false;
                }
                $this->logger->info("[RemoteCoverageRecorder::saveCoverageXml()] "
                . "Saved XML to $filepath; size: [" . filesize($filepath) 
                . "]", __FILE__, __LINE__);
                return true;
            }
            return false;
        }

        /*}}}*/
        /*{{{ public function generateReport() */

        /** 
         * Generate report from the xml coverage data
         * The preferred method for usage of this function is 
         * passing a stream of the XML data in. This is much more
         * efficient and consumes less memory. 
         * 
         * @param $xmlUrl Url where XML data is available or string
         * @param $stream=false Is the xml available as stream? 
         * @access public
        */
        public function generateReport($xmlUrl, $stream=false) {
            $this->logger->debug("XML Url: " . $xmlUrl, __FILE__, __LINE__);
            $this->parseCoverageXml($xmlUrl, true);
            $this->logger->debug("Coverage Data final: " . print_r($this->coverageData, true));
            parent::generateReport();
        }

        /*}}}*/
    }
?>
