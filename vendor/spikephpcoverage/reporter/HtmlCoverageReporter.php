<?php
    /*
    *  $Id: HtmlCoverageReporter.php 14665 2005-03-23 19:37:50Z npac $
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
    require_once __PHPCOVERAGE_HOME . "/reporter/CoverageReporter.php";
    require_once __PHPCOVERAGE_HOME . "/parser/PHPParser.php";
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";

    /** 
    * Class that implements HTML Coverage Reporter. 
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: 14665 $
    * @package SpikePHPCoverage_Reporter
    */
    class HtmlCoverageReporter extends CoverageReporter {

        /*{{{ Members */

        private $coverageData;
        private $htmlFile;
        private $body;
        private $header = "html/header.html";
        private $footer = "html/footer.html";
        private $indexHeader = "html/indexheader.html"; 
        private $indexFooter = "html/indexfooter.html";

        /*}}}*/
        /*{{{ public function __construct() */

        /** 
        * Constructor method (PHP5 only) 
        * 
        * @param $heading Heading of the report (shown as title)
        * @param $style Name of the stylesheet file
        * @param $dir Directory where the report files should be dumped
        * @access public
        */
        public function __construct(
            $heading="Coverage Report",
            $style="",
            $dir="report"
        ) {
            parent::__construct($heading, $style, $dir);
        }

        /*}}}*/
        /*{{{ public function generateReport() */

        /** 
        * Implementaion of generateReport abstract function.
        * This is the only function that will be called 
        * by the instrumentor.
        * 
        * @param &$data  Reference to Coverage Data
        * @access public
        */
        public function generateReport(&$data) {
            if(!file_exists($this->outputDir)) {
                mkdir($this->outputDir);
            }
            $this->coverageData =& $data;
            $this->grandTotalFiles = count($this->coverageData);
            $ret = $this->writeIndexFile();
            if($ret === FALSE) {
                $this->logger->error("Error occured!!!", __FILE__, __LINE__);
            }
            $this->logger->debug(print_r($data, true), __FILE__, __LINE__);
        }

        /*}}}*/
        /*{{{ private function writeIndexFileHeader() */

        /** 
        * Write the index file header to a string
        * 
        * @return string String containing HTML code for the index file header
        * @access private
        */
        private function writeIndexFileHeader() {
            $str = false;
            $dir = realpath(dirname(__FILE__));
            if($dir !== false) {
                $str = file_get_contents($dir . "/" . $this->indexHeader);
                if($str == false) {
                    return $str;
                }
                $str = str_replace("%%heading%%", $this->heading, $str);
                $str = str_replace("%%style%%", $this->style, $str);
            }
            return $str;
        }

        /*}}}*/
        /*{{{ private function writeIndexFileFooter() */

        /** 
        * Write the index file footer to a string
        * 
        * @return string String containing HTML code for the index file footer.
        * @access private
        */
        private function writeIndexFileFooter() {
            $str = false;
            $dir = realpath(dirname(__FILE__));
            if($dir !== false) {
                $str = file_get_contents($dir . "/" . $this->indexFooter);
                if($str == false) {
                    return $str;
                }
            }
            return $str;
        }

        /*}}}*/
        /*{{{ private function createJSDir() */

        /** 
        * Create a directory for storing Javascript for the report
        * 
        * @access private
        */
        private function createJSDir() {
            $jsDir = $this->outputDir . "/js";
            if(file_exists($this->outputDir) && !file_exists($jsDir)) {
                mkdir($jsDir);
            }
            $jsSortFile = realpath(dirname(__FILE__)) . "/js/sort_spikesource.js";
            copy($jsSortFile, $jsDir . "/" . "sort_spikesource.js");
            return true;
        }

        /*}}}*/
        /*{{{ private function createImagesDir() */

        /** 
        * Create a directory for storing images for the report 
        * 
        * @access private
        */
        private function createImagesDir() {
            $imagesDir = $this->outputDir . "/images";
            if(file_exists($this->outputDir) && !file_exists($imagesDir)) {
                mkdir($imagesDir);
            }
            $imagesSpikeDir = $imagesDir . "/spikesource";
            if(!file_exists($imagesSpikeDir)) {
                mkdir($imagesSpikeDir);
            }
            $imagesArrowUpFile = realpath(dirname(__FILE__)) . "/images/arrow_up.gif";
            $imagesArrowDownFile = realpath(dirname(__FILE__)) . "/images/arrow_down.gif";
            $imagesPHPCoverageLogoFile = realpath(dirname(__FILE__)) . "/images/spikesource/phpcoverage.gif";
            $imagesSpacerFile = realpath(dirname(__FILE__)) . "/images/spacer.gif";
            copy($imagesArrowUpFile, $imagesDir . "/" . "arrow_up.gif");
            copy($imagesArrowDownFile, $imagesDir . "/" . "arrow_down.gif");
            copy($imagesSpacerFile, $imagesDir . "/" . "spacer.gif");
            copy($imagesPHPCoverageLogoFile, $imagesSpikeDir . "/" . "phpcoverage.gif");
            return true;
        }

        /*}}}*/
        /*{{{ private function createStyleDir() */

        private function createStyleDir() {
            if(isset($this->style)) {
                $this->style = trim($this->style);
            }
            if(empty($this->style)) {
                $this->style = "spikesource.css";
            }
            $styleDir = $this->outputDir . "/css";
            if(file_exists($this->outputDir) && !file_exists($styleDir)) {
                mkdir($styleDir);
            }
            $styleFile = realpath(dirname(__FILE__)) . "/css/" . $this->style;
            copy($styleFile, $styleDir . "/" . $this->style);
            return true;
        }

        /*}}}*/
        /*{{{ protected function writeIndexFileTableHead() */

        /** 
        * Writes the table heading for index.html 
        * 
        * @return string Table heading row code
        * @access protected
        */
        protected function writeIndexFileTableHead() {
            $str = "";
            $str .= '<h1>Details</h1> <table class="spikeDataTable" cellpadding="4" cellspacing="0" border="0" id="table2sort" width="800">';
            $str .= '<thead>';
            $str .= '<tr><td class="spikeDataTableHeadLeft" id="sortCell0" rowspan="2" style="white-space:nowrap" width="52%"><a id="sortCellLink0" class="headerlink" href="javascript:sort(0)" title="Sort Ascending">File Name </a></td>';
            $str .= '<td colspan="4" class="spikeDataTableHeadCenter">Lines</td>';
            $str .= '<td class="spikeDataTableHeadCenterLast" id="sortCell5" rowspan="2"  width="16%" style="white-space:nowrap"><a id="sortCellLink5" class="headerlink" href="javascript:sort(5, \'percentage\')" title="Sort Ascending">Code Coverage </a></td>';
            $str .= '</tr>';

            // Second row - subheadings
            $str .= '<tr>';
            $str .= '<td class="spikeDataTableSubHeadCenter" id="sortCell1" style="white-space:nowrap" width="8%"><a id="sortCellLink1" title="Sort Ascending" class="headerlink" href="javascript:sort(1, \'number\')">Total </a></td>';
            $str .= '<td class="spikeDataTableSubHeadCenter"  id="sortCell2" style="white-space:nowrap" width="9%"><a id="sortCellLink2" title="Sort Ascending" class="headerlink" href="javascript:sort(2, \'number\')">Covered </a></td>';
            $str .= '<td class="spikeDataTableSubHeadCenter" id="sortCell3" style="white-space:nowrap" width="8%"><a id="sortCellLink3" title="Sort Ascending" class="headerlink" href="javascript:sort(3, \'number\')">Missed </a></td>';
            $str .= '<td class="spikeDataTableSubHeadCenter" id="sortCell4" style="white-space:nowrap" width="10%"><a id="sortCellLink4" title="Sort Ascending" class="headerlink" href="javascript:sort(4, \'number\')">Executable </a></td>';
            $str .= '</tr>';
            $str .= '</thead>';
            return $str;
        }

        /*}}}*/
        /*{{{ protected function writeIndexFileTableRow() */

        /** 
        * Writes one row in the index.html table to display filename
        * and coverage recording.
        * 
        * @param $fileLink link to html details file.
        * @param $realFile path to real PHP file.
        * @param $fileCoverage Coverage recording for that file.
        * @return string HTML code for a single row.
        * @access protected
        */
        protected function writeIndexFileTableRow($fileLink, $realFile, $fileCoverage) {

            global $util;
            $fileLink = $this->makeRelative($fileLink);
            $realFileShort = $util->shortenFilename($realFile);
            $str = "";

            $str .= '<tr><td class="spikeDataTableCellLeft">';
            $str .= '<a class="contentlink" href="' . $util->unixifyPath($fileLink) . '" title="' 
            . $realFile .'">' . $realFileShort. '</a>' . '</td>';
            $str .= '<td class="spikeDataTableCellCenter">' . $fileCoverage['total'] . "</td>";
            $str .= '<td class="spikeDataTableCellCenter">' . $fileCoverage['covered'] . "</td>";
            $str .= '<td class="spikeDataTableCellCenter">' . $fileCoverage['uncovered'] . "</td>";
            $str .= '<td class="spikeDataTableCellCenter">' . ($fileCoverage['covered']+$fileCoverage['uncovered']) . "</td>";
            if($fileCoverage['uncovered'] + $fileCoverage['covered'] == 0) {
                // If there are no executable lines, assume coverage to be 100%
                $str .= '<td class="spikeDataTableCellCenter">100%</td></tr>';
            }
            else {
                $str .= '<td class="spikeDataTableCellCenter">' 
                . round(($fileCoverage['covered']/($fileCoverage['uncovered'] 
                + $fileCoverage['covered']))*100.0, 2)
                . '%</td></tr>';
            }
            return $str;
        }

        /*}}}*/
        /*{{{ protected function writeIndexFileGrandTotalPercentage() */

        /** 
        * Writes the grand total for coverage recordings on the index.html 
        * 
        * @return string HTML code for grand total row
        * @access protected
        */
        protected function writeIndexFileGrandTotalPercentage() {
            $str = "";

            $str .= "<br/><h1>" . $this->heading . "</h1><br/>";

            $str .= '<table border="0" cellpadding="0" cellspacing="0" id="contentBox" width="800"> <tr>';
            $str .= '<td align="left" valign="top"><h1>Summary</h1>';
            $str .= '<table class="spikeVerticalTable" cellpadding="4" cellspacing="0" width="800" style="margin-bottom:10px" border="0">';
            $str .= '<td width="380" class="spikeVerticalTableHead" style="font-size:14px">Overall Code Coverage&nbsp;</td>';
            $str .= '<td class="spikeVerticalTableCell" style="font-size:14px" colspan="2"><strong>' . $this->getGrandCodeCoveragePercentage() . '%</td>';

            $str .= '</tr><tr>';

            $str .= '<td class="spikeVerticalTableHead">Total Covered Lines of Code&nbsp;</td>';
            $str .= '<td width="30" class="spikeVerticalTableCell"><span class="emphasis">' . $this->grandTotalCoveredLines.'</span></td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="note">(' . TOTAL_COVERED_LINES_EXPLAIN . ')</span></td>';

            $str .= '</tr><tr>';

            $str .= '<td class="spikeVerticalTableHead">Total Missed Lines of Code&nbsp;</td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="emphasis">' . $this->grandTotalUncoveredLines.'</span></td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="note">(' . TOTAL_UNCOVERED_LINES_EXPLAIN . ')</span></td>';

            $str .= '</tr><tr>';

            $str .= '<td class="spikeVerticalTableHead">Total Lines of Code&nbsp;</td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="emphasis">' . ($this->grandTotalCoveredLines + $this->grandTotalUncoveredLines) .'</span></td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="note">(' . 
            TOTAL_LINES_OF_CODE_EXPLAIN . ')</span></td>';

            $str .= '</tr><tr>';

            $str .= '<td class="spikeVerticalTableHead" >Total Lines&nbsp;</td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="emphasis">' . $this->grandTotalLines.'</span></td>';
            $str .= '<td class="spikeVerticalTableCell"><span class="note">(' . TOTAL_LINES_EXPLAIN . ')</span></td>';

            $str .= '</tr><tr>';

            $str .= '<td class="spikeVerticalTableHeadLast" >Total Files&nbsp;</td>';
            $str .= '<td class="spikeVerticalTableCellLast"><span class="emphasis">' . $this->grandTotalFiles.'</span></td>';
            $str .= '<td class="spikeVerticalTableCellLast"><span class="note">(' . TOTAL_FILES_EXPLAIN . ')</span></td>';

            $str .= '</tr></table>';

            return $str;
        }

        /*}}}*/
        /*{{{ protected function writeIndexFile() */

        /** 
        * Writes index.html file from all coverage recordings. 
        * 
        * @return boolean FALSE on failure
        * @access protected
        */
        protected function writeIndexFile() {
            global $util;
            $str = "";
            $this->createJSDir();
            $this->createImagesDir();
            $this->createStyleDir();
            $this->htmlFile = $this->outputDir . "/index.html";
            $indexFile = fopen($this->htmlFile, "w");
            if(empty($indexFile)) {
                $this->logger->error("Cannot open file for writing: $this->htmlFile",
                    __FILE__, __LINE__);
                return false;
            }

            $strHead = $this->writeIndexFileHeader();
            if($strHead == false) {
                return false;
            }
            $str .= $this->writeIndexFileTableHead();
            $str .= '<tbody>';
            if(!empty($this->coverageData)) {
                foreach($this->coverageData as $filename => &$lines) {
                    $realFile = realpath($filename);
                    $fileLink = $this->outputDir . $util->unixifyPath($realFile). ".html";
                    $fileCoverage = $this->markFile($realFile, $fileLink, $lines);
                    if(empty($fileCoverage)) {
                        return false;
                    }
                    $this->recordFileCoverageInfo($fileCoverage);
                    $this->updateGrandTotals($fileCoverage);

                    $str .= $this->writeIndexFileTableRow($fileLink, $realFile, $fileCoverage);
                    unset($this->coverageData[$filename]);
                }
            }
            $str .= '</tbody>';
            $str .= "</table></td></tr>";

            $str .= "<tr><td><p align=\"right\" class=\"content\">Report Generated On: " . $util->getTimeStamp() . "<br/>";
            $str .= "Generated using Spike PHPCoverage " . $this->recorder->getVersion() . "</p></td></tr></table>";

            // Get the summary
            $strSummary = $this->writeIndexFileGrandTotalPercentage();

            // Merge them - with summary on top
            $str = $strHead . $strSummary . $str;

            $str .= $this->writeIndexFileFooter();
            fwrite($indexFile, $str);
            fclose($indexFile);
            return TRUE;
        }

        /*}}}*/
        /*{{{ private function writePhpFileHeader() */

        /** 
        * Write the header for the source file with mark-up
        * 
        * @param $filename Name of the php file
        * @return string String containing the HTML for PHP file header
        * @access private
        */
        private function writePhpFileHeader($filename, $fileLink) {
            $fileLink = $this->makeRelative($fileLink);
            $str = false;
            $dir = realpath(dirname(__FILE__));
            if($dir !== false) {
                $str = file_get_contents($dir . "/" . $this->header);
                if($str == false) {
                    return $str;
                }
                $str = str_replace("%%filename%%", $filename, $str);
                // Get the path to parent CSS directory
                $relativeCssPath = $this->getRelativeOutputDirPath($fileLink);
                $relativeCssPath .= "/css/" . $this->style;
                $str = str_replace("%%style%%", $relativeCssPath, $str);
            }
            return $str;
        }

        /*}}}*/
        /*{{{ private function writePhpFileFooter() */

        /** 
        * Write the footer for the source file with mark-up 
        * 
        * @return string String containing the HTML for PHP file footer
        * @access private
        */
        private function writePhpFileFooter() {
            $str = false;
            $dir = realpath(dirname(__FILE__));
            if($dir !== false) {
                $str = file_get_contents($dir . "/" . $this->footer);
                if($str == false) {
                    return $str;
                }
            }
            return $str;
        }

        /*}}}*/
        /*{{{ protected function markFile() */

        /** 
        * Mark a source code file based on the coverage data gathered
        * 
        * @param $phpFile Name of the actual source file
        * @param $fileLink Link to the html mark-up file for the $phpFile
        * @param &$coverageLines Coverage recording for $phpFile
        * @return boolean FALSE on failure
        * @access protected
        */
        protected function markFile($phpFile, $fileLink, &$coverageLines) {
            global $util;
            $fileLink = $util->replaceBackslashes($fileLink);
            $parentDir = $util->replaceBackslashes(dirname($fileLink));
            if(!file_exists($parentDir)) {
                //echo "\nCreating dir: $parentDir\n";
                $util->makeDirRecursive($parentDir, 0755);
            }
            $writer = fopen($fileLink, "w");

            if(empty($writer)) {
                $this->logger->error("Could not open file for writing: $fileLink",
                    __FILE__, __LINE__);
                return false;
            }

            // Get the header for file
            $filestr = $this->writePhpFileHeader(basename($phpFile), $fileLink);

            // Add header for table
            $filestr .= '<table width="100%" border="0" cellpadding="2" cellspacing="0">';
            $filestr .= $this->writeFileTableHead();

            $lineCnt = $coveredCnt = $uncoveredCnt = 0;
            $parser = new PHPParser();
            $parser->parse($phpFile);
            $lastLineType = "non-exec";
            $fileLines = array();
            while(($line = $parser->getLine()) !== false) {
                $line = substr($line, 0, strlen($line)-1);
                $lineCnt++;
                $coverageLineNumbers = array_keys($coverageLines);
                if(in_array($lineCnt, $coverageLineNumbers)) {
                    $lineType = $parser->getLineType();
                    if($lineType == LINE_TYPE_EXEC) {
                        $coveredCnt ++;
                        $type = "covered";
                    }
                    else if($lineType == LINE_TYPE_CONT) {
                        // XDebug might return this as covered - when it is
                        // actually merely a continuation of previous line
                        if($lastLineType == "covered") {
                            unset($coverageLines[$lineCnt]);
                            $type = $lastLineType;
                        }
                        else {
                            if($lineCnt-1 >= 0 && isset($fileLines[$lineCnt-1]["type"])) {
                                if($fileLines[$lineCnt-1]["type"] == "uncovered") {
                                    $uncoveredCnt --;
                                }
                                $fileLines[$lineCnt-1]["type"] = $lastLineType = "covered";
                            }
                            $coveredCnt ++;
                            $type = "covered";
                        }
                    }
                    else {
                        $type = "non-exec";
                        $coverageLines[$lineCnt] = 0;
                    }
                }
                else if($parser->getLineType() == LINE_TYPE_EXEC) {
                    $uncoveredCnt ++;
                    $type = "uncovered";
                }
                else if($parser->getLineType() == LINE_TYPE_CONT) {
                    $type = $lastLineType;
                }
                else {
                    $type = "non-exec";
                }
                // Save line type 
                $lastLineType = $type;
                //echo $line . "\t[" . $type . "]\n";

                if(!isset($coverageLines[$lineCnt])) {
                    $coverageLines[$lineCnt] = 0;
                }
                $fileLines[$lineCnt] = array("type" => $type, "lineCnt" => $lineCnt, "line" => $line, "coverageLines" => $coverageLines[$lineCnt]);
            }
            $this->logger->debug("File lines: ". print_r($fileLines, true),
                __FILE__, __LINE__);
            for($i = 1; $i <= count($fileLines); $i++) {
                $filestr .= $this->writeFileTableRow($fileLines[$i]["type"],
                    $fileLines[$i]["lineCnt"], 
                    $fileLines[$i]["line"],
                    $fileLines[$i]["coverageLines"]);
            }
            $filestr .= "</table>";
            $filestr .= $this->writePhpFileFooter();
            fwrite($writer, $filestr);
            fclose($writer);
            return array(
                'filename' => $phpFile,
                'covered' => $coveredCnt,
                'uncovered' => $uncoveredCnt,
                'total' => $lineCnt
            );
        }

        /*}}}*/
        /*{{{ protected function writeFileTableHead() */

        /** 
        * Writes table heading for file details table.
        * 
        * @return string HTML string representing one table row.
        * @access protected
        */
        protected function writeFileTableHead() {
            $filestr = "";

            $filestr .= '<td width="10%"class="coverageDetailsHead" >Line #</td>';
            $filestr .= '<td width="10%" class="coverageDetailsHead">Frequency</td>';
            $filestr .= '<td  width="80%" class="coverageDetailsHead">Source Line</td>';
            return $filestr;
        }

        /*}}}*/
        /*{{{ protected function writeFileTableRow() */

        /** 
        * Write a line for file details table. 
        * 
        * @param $color Text color
        * @param $bgcolor Row bgcolor
        * @param $lineCnt Line number
        * @param $line The source code line
        * @param $coverageLineCnt Number of time the line was executed.
        * @return string HTML code for a table row.
        * @access protected
        */
        protected function writeFileTableRow($type, $lineCnt, $line, $coverageLineCnt) {
            $spanstr = "";
            if($type == "covered") {
                $spanstr .= '<span class="codeExecuted">';
            }
            else if($type == "uncovered") {
                $spanstr .= '<span class="codeMissed">';
            }
            else {
                $spanstr .= '<span>';
            }

            if(empty($coverageLineCnt)) {
                $coverageLineCnt = "";
            }

            $filestr = '<tr>';
            $filestr .= '<td class="coverageDetails">' . $spanstr . $lineCnt . '</span></td>';
            if(empty($coverageLineCnt)) {
                $coverageLineCnt = "&nbsp;";
            }
            $filestr .= '<td class="coverageDetails">' . $spanstr . $coverageLineCnt . '</span></td>';
            $filestr .= '<td class="coverageDetailsCode"><code>' . $spanstr . $this->preserveSpacing($line) . '</span></code></td>';
            $filestr .= "</tr>";
            return $filestr;
        }

        /*}}}*/
        /*{{{ protected function preserveSpacing() */

        /** 
        * Changes all tabs and spaces with HTML non-breakable spaces. 
        * 
        * @param $string String containing spaces and tabs.
        * @return string HTML string with replacements.
        * @access protected
        */
        protected function preserveSpacing($string) {
            $string = htmlspecialchars($string);
            $string = str_replace(" ", "&nbsp;", $string);
            $string = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $string);
            return $string;
        }

        /*}}}*/
    }
?>
