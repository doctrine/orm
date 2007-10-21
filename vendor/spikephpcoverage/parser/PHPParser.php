<?php
    /*
    *  $Id: PHPParser.php 14665 2005-03-23 19:37:50Z npac $
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php

    if( ! defined("__PHPCOVERAGE_HOME")) {
        define("__PHPCOVERAGE_HOME", dirname(dirname(__FILE__)));
    }
    require_once __PHPCOVERAGE_HOME . "/parser/Parser.php";

    /** 
    * Parser for PHP files 
    * 
    * @author Nimish Pachapurkar (npac@spikesource.com)
    * @version $Revision: 14665 $
    * @package SpikePHPCoverage_Parser
    */
    class PHPParser extends Parser {
        /*{{{ Members */

        private $inPHP = false;
        private $phpStarters = array('<?php', '<?', '<?=');
        private $phpFinisher = '?>';
        private $inComment = false;
        private $lastLineEndTokenType = "";
        // If one of these tokens occur as the last token of a line
        // then the next line can be treated as a continuation line
        // depending on how it starts.
        public static $contTypes = array(
            "(",
            ",",
            ".",
            "=",
            T_LOGICAL_XOR,
            T_LOGICAL_AND,
            T_LOGICAL_OR,
            T_PLUS_EQUAL,
            T_MINUS_EQUAL,
            T_MUL_EQUAL,
            T_DIV_EQUAL,
            T_CONCAT_EQUAL,
            T_MOD_EQUAL,
            T_AND_EQUAL,
            T_OR_EQUAL,
            T_XOR_EQUAL,
            T_BOOLEAN_AND,
            T_BOOLEAN_OR,
            T_OBJECT_OPERATOR, 
            T_DOUBLE_ARROW, 
            "[", 
            "]",
            T_LOGICAL_OR, 
            T_LOGICAL_XOR, 
            T_LOGICAL_AND
        );

        /*}}}*/
        /*{{{ protected function processLine() */

        /** 
        * Process a line read from the file and determine if it is an
        * executable line or not. 
        *
        * This is the work horse function that does most of the parsing.
        * To parse PHP, get_all_tokens() tokenizer function is used.
        * 
        * @param $line  Line to be parsed.
        * @access protected
        */
        protected function processLine($line) {

            // Default values
            $this->lineType = LINE_TYPE_NOEXEC;
            $line = trim($line);
            $parseLine = $line;
            $artificialStart = false;
            $artificialEnd = false;

            // If we are not inside PHP opening tag
            if( ! $this->inPHP) {
                $pos = -1;

                // Confirm that the line does not have T_OPEN_TAG_WITH_ECHO (< ? =)
                if(strpos($line, $this->phpStarters[2]) === false) {
                    // If the line has PHP start tag of the first kind
                    if(($pos = strpos($line, $this->phpStarters[0])) !== false) {
                        $pos = $pos + strlen($this->phpStarters[0]);
                    }
                    // if the line has PHP start tag of the second kind.
                    else if(($pos = strpos($line, $this->phpStarters[1])) !== false) {
                        $pos = $pos + strlen($this->phpStarters[1]);
                    }
                    // $pos now points to the character after opening tag
                    if($pos > 0) {
                        $this->inPHP = true;
                        //echo "Going in PHP\n";
                        // Remove the part of the line till the PHP opening 
                        // tag and recurse
                        return $this->processLine(trim(substr($line, $pos))); 
                    }
                }
            }
            // If we are already in PHP 
            else if($this->inPHP) {
                // If we are inside a multi-line comment, that is not ending 
                // on the same line
                if((strpos($line, "/*") !== false && 
                strpos($line, "*/") === false) || 
                (strpos($line, "/*") > strpos($line, "*/"))) {
                    $this->inComment = true;
                }
                if($this->inComment) {
                    // Do we need to append an artificial comment start?
                    // (otherwise the tokenizer might throw error.
                    if(strpos($line, "/*") === false) {
                        $line = "/*" . $line;
                        $artificialStart = true;
                    }
                    // Do we need to append an artificial comment end?
                    if(strpos($line, "*/") === false) {
                        $line = $line . "*/";
                        $artificialEnd = true;
                    }
                }
                // Since we are inside php, append php opening and closing tags
                // to prevent tokenizer from mis-interpreting the line
                $parseLine = "<?php " . $line . " ?>";
            }

            // Tokenize
            $tokens = @token_get_all($parseLine);
            $this->logger->debug("inPHP? " . $this->inPHP . "\nLine:" . $parseLine,
                __FILE__, __LINE__);
            $this->logger->debug(print_r($tokens, true), __FILE__, __LINE__);
            $seenEnough = false;
            $seeMore = false;
            $tokenCnt = 0; //tokens in this line
            $phpEnded = false;
            if($this->isContinuation($this->lastLineEndTokenType)) {
                $this->lineType = LINE_TYPE_CONT;
                $this->logger->debug("Continuation !", __FILE__, __LINE__);
            }
            foreach($tokens as $token) {
                $tokenCnt ++;
                if($this->inPHP) {
                    if($tokenCnt == 2) {
                        if($this->isContinuation($token)) {
                            $this->lineType = LINE_TYPE_CONT;
                            $this->logger->debug("Continuation! Token: $token",
                                __FILE__, __LINE__);
                            break;
                        }
                    }
                }

                if(is_string($token)) {
                    // FIXME: Add more cases, if needed
                    switch($token) {
                        // Any of these things, are non-executable.
                    case '{':
                    case '}':
                    case '(':
                    case ')':
                    case ';':
                        if($this->lineType != LINE_TYPE_EXEC) {
                            $this->lineType = LINE_TYPE_NOEXEC;
                        }
                        break; 

                    // Everything else by default is executable.
                    default:
                        $this->lineType = LINE_TYPE_EXEC;
                        break;
                    }
                    $this->logger->debug("Status: " . $this->getLineTypeStr($this->lineType) . "\t\tToken: $token",
                        __FILE__, __LINE__);
                }
                else {
                    // The token is an array
                    list($tokenType, $text) = $token;
                    switch($tokenType) {

                        // If it is a comment end or start, set the correct flag
                        // If we have put the start or end artificially, ignore!
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        if(strpos($text, "/*") !== false && !$artificialStart) {
                            $this->inComment = true;
                        }
                        if(strpos($text, "*/") !== false && !$artificialEnd) {
                            $this->inComment = false;
                        }

                    case T_WHITESPACE:              // white space
                    case T_OPEN_TAG:                // < ?
                    case T_OPEN_TAG_WITH_ECHO:      // < ? =
                    case T_CURLY_OPEN:              // 
                    case T_INLINE_HTML:             // <br/><b>jhsk</b>
                        //case T_STRING:                  // 
                    case T_EXTENDS:                 // extends
                    case T_STATIC:                  // static
                    case T_STRING_VARNAME:          // string varname?
                    case T_CHARACTER:               // character
                    case T_ELSE:                    // else
                    case T_CONSTANT_ENCAPSED_STRING:   // "some str"
                    case T_START_HEREDOC:
                        // Only if decision is not already made
                        // mark this non-executable.
                        if($this->lineType != LINE_TYPE_EXEC) {
                            $this->lineType = LINE_TYPE_NOEXEC;
                        }
                        break;

                    case T_PRIVATE:                 // private
                    case T_PUBLIC:                  // public
                    case T_PROTECTED:               // protected
                    case T_VAR:                     // var
                    case T_FUNCTION:                // function
                    case T_CLASS:                   // class
                    case T_INTERFACE:               // interface
                    case T_REQUIRE:                 // require
                    case T_REQUIRE_ONCE:            // require_once
                    case T_INCLUDE:                 // include
                    case T_INCLUDE_ONCE:            // include_once
                    case T_ARRAY:                   // array
                    case T_SWITCH:                  // switch
                    case T_CONST:                   // const
                    case T_TRY:                     // try
                        $this->lineType = LINE_TYPE_NOEXEC;
                        // No need to see any further
                        $seenEnough = true;
                        break; 

                    case T_VARIABLE:                // $foo
                        $seeMore = true;
                        $this->lineType = LINE_TYPE_EXEC;
                        break;

                    case T_CLOSE_TAG:
                        if($tokenCnt != count($tokens)) {
                            // Token is not last (because we inserted that)
                            $this->logger->debug("T_CLOSE_TAG for tokenCnt " . $tokenCnt . " End of PHP code.");
                            $phpEnded = true; // php end tag found within the line.
                        }
                        if($this->lineType != LINE_TYPE_EXEC) {
                            $this->lineType = LINE_TYPE_NOEXEC;
                        }
                        break;

                    default:
                        $seeMore = false;
                        $this->lineType = LINE_TYPE_EXEC;
                        break;
                    }
                    $this->logger->debug("Status: " . $this->getLineTypeStr($this->lineType) . "\t\tToken type: $tokenType \tText: $text",
                        __FILE__, __LINE__);
                }
                if(($this->lineType == LINE_TYPE_EXEC && !$seeMore) 
                    || $seenEnough) {
                        $this->logger->debug("Made a decision! Exiting. Token Type: $tokenType & Text: $text",
                            __FILE__, __LINE__);
                    if($seenEnough) {
                        $this->logger->debug("Seen enough at Token Type: $tokenType & Text: $text",
                            __FILE__, __LINE__);
                    }
                    break;
                }
            } // end foreach
            $this->logger->debug("Line Type: " . $this->getLineTypeStr($this->lineType),
                __FILE__, __LINE__);
            if($this->inPHP) {
                $this->lastLineEndTokenType = $this->getLastTokenType($tokens);
            }
            $this->logger->debug("Last End Token: " . $this->lastLineEndTokenType,
                __FILE__, __LINE__);

            if($this->inPHP) {
                // Check if PHP block ends on this line
                if($phpEnded) {
                    $this->inPHP = false;
                    // If line is not executable so far, check for the 
                    // remaining part
                    if($this->lineType != LINE_TYPE_EXEC) {
                        //return $this->processLine(trim(substr($line, $pos+2)));
                    }
                }
            }
        }

        /*}}}*/
        /*{{{ public function getLineType() */

        /** 
        * Returns the type of line just read 
        * 
        * @return Line type
        * @access public
        */
        public function getLineType() {
            return $this->lineType;
        }
        /*}}}*/
        /*{{{ protected function isContinuation() */

        /** 
        * Check if a line is a continuation of the previous line 
        * 
        * @param &$token Second token in a line (after PHP start)
        * @return Boolean True if the line is a continuation; false otherwise
        * @access protected
        */
        protected function isContinuation(&$token) {
            if(is_string($token)) {
                switch($token) {
                case ".":
                case ",";
                case "]":
                case "[":
                case "(":
                case ")":
                case "=":
                    return true;
                }
            }
            else {
                list($tokenType, $text) = $token;               
                switch($tokenType) {
                case T_CONSTANT_ENCAPSED_STRING:
                case T_ARRAY:
                case T_DOUBLE_ARROW:
                case T_OBJECT_OPERATOR:
                case T_LOGICAL_XOR:
                case T_LOGICAL_AND:
                case T_LOGICAL_OR:
                case T_PLUS_EQUAL:
                case T_MINUS_EQUAL:
                case T_MUL_EQUAL:
                case T_DIV_EQUAL:
                case T_CONCAT_EQUAL:
                case T_MOD_EQUAL:
                case T_AND_EQUAL:
                case T_OR_EQUAL:
                case T_XOR_EQUAL:
                case T_BOOLEAN_AND:
                case T_BOOLEAN_OR:
                case T_LNUMBER:
                case T_DNUMBER:
                    return true;

                case T_STRING:
                case T_VARIABLE:
                    return in_array($this->lastLineEndTokenType, PHPParser::$contTypes);
                }
            }

            return false;
        }
        /*}}}*/
        /*{{{ protected function getTokenType() */

        /** 
        * Get the token type of a token (if exists) or
        * the token itself.
        * 
        * @param $token Token
        * @return Token type or token itself
        * @access protected
        */
        protected function getTokenType($token) {
            if(is_string($token)) {
                return $token;
            }
            else {
                list($tokenType, $text) = $token;
                return $tokenType;
            }
        }
        /*}}}*/
        /*{{{*/

        /** 
        * Return the type of last non-empty token in a line 
        * 
        * @param &$tokens Array of tokens for a line
        * @return mixed Last non-empty token type (or token) if exists; false otherwise
        * @access protected
        */
        protected function getLastTokenType(&$tokens) {
            for($i = count($tokens)-2; $i > 0; $i--) {
                if(empty($tokens[$i])) {
                    continue;
                }
                if(is_string($tokens[$i])) {
                    return $tokens[$i];
                }
                else {
                    list($tokenType, $text) = $tokens[$i];
                    if($tokenType != T_WHITESPACE && $tokenType != T_EMPTY) {
                        return $tokenType;
                    }
                }
            }
            return false;
        }
        /*}}}*/

        /*
        // Main
        $obj = new PHPParser();
        $obj->parse("test.php");
        while(($line = $obj->getLine()) !== false) {
            echo "#########################\n";
            echo "[" . $line . "] Type: [" . $obj->getLineTypeStr($obj->getLineType()) . "]\n";
            echo "#########################\n";
    }
    */

    }
?>
