<?php

/**
* 
* "Pre-filter" the source text.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Prefilter.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* "Pre-filter" the source text.
* 
* Convert DOS and Mac line endings to Unix, concat lines ending in a
* backslash \ with the next line, convert tabs to 4-spaces, add newlines
* to the top and end of the source text, compress 3 or more newlines to
* 2 newlines.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Prefilter extends Text_Wiki_Parse {
    
    
    /**
    * 
    * Simple parsing method.
    *
    * @access public
    * 
    */
    
    function parse()
    {
        // convert DOS line endings
        $this->wiki->source = str_replace("\r\n", "\n",
            $this->wiki->source);
        
        // convert Macintosh line endings
        $this->wiki->source = str_replace("\r", "\n",
            $this->wiki->source);
        
        // concat lines ending in a backslash
        $this->wiki->source = str_replace("\\\n", "",
            $this->wiki->source);
        
        // convert tabs to four-spaces
        $this->wiki->source = str_replace("\t", "    ",
            $this->wiki->source);
           
        // add extra newlines at the top and end; this
        // seems to help many rules.
        $this->wiki->source = "\n" . $this->wiki->source . "\n\n";
        
        // finally, compress all instances of 3 or more newlines
        // down to two newlines.
        $find = "/\n{3,}/m";
        $replace = "\n\n";
        $this->wiki->source = preg_replace($find, $replace,
            $this->wiki->source);
    }

}
?>