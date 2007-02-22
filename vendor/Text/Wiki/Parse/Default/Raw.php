<?php

/**
* 
* Parses for text marked as "raw" (i.e., to be rendered as-is).
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Raw.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Parses for text marked as "raw" (i.e., to be rendered as-is).
* 
* This class implements a Text_Wiki rule to find sections of the source
* text that are not to be processed by Text_Wiki.  These blocks of "raw"
* text will be rendered as they were found.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Raw extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to find source text matching this
    * rule.
    * 
    * @access public
    * 
    * @var string
    * 
    */
    
    var $regex = "/``(.*)``/U";
    
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'text' => The full matched text.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */
    
    function process(&$matches)
    {
        $options = array('text' => $matches[1]);
        return $this->wiki->addToken($this->rule, $options);
    }
}
?>