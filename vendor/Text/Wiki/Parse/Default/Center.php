<?php

/**
* 
* Parses for centered lines of text.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Center.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Parses for centered lines of text.
* 
* This class implements a Text_Wiki_Parse to find lines marked for centering.
* The line must start with "= " (i.e., an equal-sign followed by a space).
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Center extends Text_Wiki_Parse {
    
    
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
    
    var $regex = '/\n\= (.*?)\n/';
    
    /**
    * 
    * Generates a token entry for the matched text.
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
        $start = $this->wiki->addToken(
            $this->rule,
            array('type' => 'start')
        );
        
        $end = $this->wiki->addToken(
            $this->rule,
            array('type' => 'end')
        );
        
        return "\n" . $start . $matches[1] . $end . "\n";
    }
}
?>