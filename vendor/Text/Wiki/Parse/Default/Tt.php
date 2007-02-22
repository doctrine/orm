<?php

/**
* 
* Find source text marked for teletype (monospace).
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Tt.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Find source text marked for teletype (monospace).
* 
* Defined by text surrounded by two curly braces. On parsing, the text
* itself is left in place, but the starting and ending instances of
* curly braces are replaced with tokens.
* 
* Token options are:
* 
* 'type' => ['start'|'end'] The starting or ending point of the
* teletype text.  The text itself is left in the source.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Tt extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to parse the source text.
    * 
    * @access public
    * 
    * @var string
    * 
    * @see parse()
    * 
    */
    
    var $regex = "/{{({*?.*}*?)}}/U";
    
    
    /**
    * 
    * Generates a replacement for the matched text. 
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the teletype text.
    *
    */
    
    function process(&$matches)
    {
        $start = $this->wiki->addToken(
            $this->rule, array('type' => 'start')
        );
        
        $end = $this->wiki->addToken(
            $this->rule, array('type' => 'end')
        );
        
        return $start . $matches[1] . $end;
    }
}
?>