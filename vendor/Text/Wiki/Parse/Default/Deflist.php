<?php

/**
* 
* Parses for definition lists.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Deflist.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Parses for definition lists.
* 
* This class implements a Text_Wiki_Parse to find source text marked as a
* definition list.  In short, if a line starts with ':' then it is a
* definition list item; another ':' on the same line indicates the end
* of the definition term and the beginning of the definition narrative.
* The list items must be on sequential lines (no blank lines between
* them) -- a blank line indicates the beginning of a new list.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Deflist extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * 
    * @var string
    * 
    * @see parse()
    * 
    */
    
    var $regex = '/\n((: ).*\n)(?!(: |\n))/Us';
    
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'type' =>
    *     'list_start'    : the start of a definition list
    *     'list_end'      : the end of a definition list
    *     'term_start'    : the start of a definition term
    *     'term_end'      : the end of a definition term
    *     'narr_start'    : the start of definition narrative
    *     'narr_end'      : the end of definition narrative
    *     'unknown'       : unknown type of definition portion
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A series of text and delimited tokens marking the different
    * list text and list elements.
    *
    */
    
    function process(&$matches)
    {
        // the replacement text we will return to parse()
        $return = '';
        
        // the list of post-processing matches
        $list = array();
        
        // start the deflist
        $options = array('type' => 'list_start');
        $return .= $this->wiki->addToken($this->rule, $options);
        
        // $matches[1] is the text matched as a list set by parse();
        // create an array called $list that contains a new set of
        // matches for the various definition-list elements.
        preg_match_all(
            '/^(: )(.*)?( : )(.*)?$/Ums',
            $matches[1],
            $list,
            PREG_SET_ORDER
        );
        
        // add each term and narrative
        foreach ($list as $key => $val) {
            $return .= (
                $this->wiki->addToken($this->rule, array('type' => 'term_start')) .
                trim($val[2]) .
                $this->wiki->addToken($this->rule, array('type' => 'term_end')) .
                $this->wiki->addToken($this->rule, array('type' => 'narr_start')) .
                trim($val[4]) . 
                $this->wiki->addToken($this->rule, array('type' => 'narr_end'))
            );
        }
        
        
        // end the deflist
        $options = array('type' => 'list_end');
        $return .= $this->wiki->addToken($this->rule, $options);
        
        // done!
        return "\n" . $return . "\n\n";
    }
}
?>