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

    require_once("XML/Parser.php");

    if( ! defined("ATTRIBUTES")) {
        define("ATTRIBUTES", "__ATTRIBUTES__");
    }

    /** 
    * An XML parser that extends the functionality of PEAR XML_Parser
    * module. 
    * 
    * @author Nimish Pachapurkar <npac@spikesource.com>
    * @version $Revision: $
    * @package SpikePHPCoverage_Parser
    */
    class BasicXmlParser extends XML_Parser {
        /*{{{ Members */

        protected $openTags;
        protected $docroot;

        /*}}}*/
        /*{{{ Constructor*/

        /** 
        * Constructor 
        * @access public
        */
        public function BasicXmlParser() {
            parent::XML_Parser();
        }

        /*}}}*/
        /*{{{ public function handleAttrTag() */

        /** 
        * Function that handles an element with attributes. 
        * 
        * @param $name Name of the element
        * @param $attrs Attributes array (name, value pairs)
        * @return Array An element
        * @access public
        */
        public function handleAttrTag($name, $attrs) {
            $tag = array();
            foreach($attrs as $attr_name => $value) {
                $tag[$attr_name] = $value;
            }
            return $tag;
        }

        /*}}}*/
        /*{{{ public function startHandler() */

        /** 
        * Function to handle start of an element
        * 
        * @param $xp XMLParser handle
        * @param $name Element name
        * @param $attributes Attribute array
        * @access public
        */
        function startHandler($xp, $name, $attributes) {
            $this->openTags[] = $name;
        }

        /*}}}*/
        /*{{{ public function endHandler()*/

        /**
        * Function to handle end of an element 
        * 
        * @param $xp XML_Parser handle
        * @param $name Name of the element
        * @access public
        */
        public function endHandler($xp, $name) {
            // Handle error tags
            $lastTag = $this->getLastOpenTag($name);
            switch($name) {
            case "MESSAGE":
                if($lastTag == "ERROR") {
                    $this->docroot["ERROR"]["MESSAGE"] = $this->getCData();
                }
                break;
            }
            // Empty CData
            $this->lastCData = "";

            // Close tag
            if($this->openTags[count($this->openTags)-1] == $name) {
                array_pop($this->openTags);
            }
        }

        /*}}}*/
        /*{{{ public function cdataHandler() */

        /** 
        * Function to handle character data 
        * 
        * @param $xp XMLParser handle
        * @param $cdata Character data
        * @access public
        */
        public function cdataHandler($xp, $cdata) {
            $this->lastCData .= $cdata;
        }

        /*}}}*/
        /*{{{ public function getCData() */

        /** 
        * Returns the CData collected so far. 
        * 
        * @return String Character data collected.
        * @access public
        */
        public function getCData() {
            return $this->lastCData;
        }

        /*}}}*/
        /*{{{ public function getLastOpenTag() */

        /** 
        * Returns the name of parent tag of give tag 
        * 
        * @param $tag Name of a child tag
        * @return String Name of the parent tag of $tag
        * @access public
        */
        public function getLastOpenTag($tag) {
            $lastTagIndex = count($this->openTags)-1;
            if($this->openTags[$lastTagIndex] == $tag) {
                if($lastTagIndex > 0) {
                    return $this->openTags[$lastTagIndex-1];
                }
            }
            return false;
        }

        /*}}}*/
        /*{{{ public function getDocumentArray() */

        /** 
        * Return the document array gathered during parsing.
        * Document array is a data structure that mimics the XML
        * contents.
        * 
        * @return Array Document array
        * @access public
        */
        public function getDocumentArray() {
            return $this->docroot;
        }

        /*}}}*/
    }

?>
