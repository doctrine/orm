<?php

/**
* 
* Parse for block-quoted text.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Blockquote.php,v 1.4 2006/10/21 05:56:28 justinpatrin Exp $
* 
*/

/**
* 
* Parse for block-quoted text.
* 
* Find source text marked as a blockquote, identified by any number of
* greater-than signs '>' at the start of the line, followed by a space,
* and then the quote text; each '>' indicates an additional level of
* quoting.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Blockquote extends Text_Wiki_Parse {
    
    
    /**
    * 
    * Regex for parsing the source text.
    * 
    * @access public
    * 
    * @var string
    * 
    * @see parse()
    * 
    */
    
    var $regex = '/\n((\>).*\n)(?!(\>))/Us';
    
    
    /**
    * 
    * Generates a replacement for the matched text.
    * 
    * Token options are:
    * 
    * 'type' =>
    *     'start' : the start of a blockquote
    *     'end'   : the end of a blockquote
    *
    * 'level' => the indent level (0 for the first level, 1 for the
    * second, etc)
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
        $return = "\n";
        
        // the list of post-processing matches
        $list = array();
        
        // $matches[1] is the text matched as a list set by parse();
        // create an array called $list that contains a new set of
        // matches for the various list-item elements.
        preg_match_all(
            '=^(\>+) (.*\n)=Ums',
            $matches[1],
            $list,
            PREG_SET_ORDER
        );
        
        $curLevel = 0;

        // loop through each list-item element.
        foreach ($list as $key => $val) {
            
            // $val[0] is the full matched list-item line
            // $val[1] is the number of initial '>' chars (indent level)
            // $val[2] is the quote text
            
            // we number levels starting at 1, not zero
            $level = strlen($val[1]);
            
            // add a level to the list?
            while ($level > $curLevel) {
                // the current indent level is greater than the number
                // of stack elements, so we must be starting a new
                // level.  push the new level onto the stack with a 
                // dummy value (boolean true)...
                ++$curLevel;
                
                //$return .= "\n";
                
                // ...and add a start token to the return.
                $return .= $this->wiki->addToken(
                    $this->rule, 
                    array(
                        'type' => 'start',
                        'level' => $curLevel
                    )
                );
                
                //$return .= "\n\n";
            }
            
            // remove a level?
            while ($curLevel > $level) {
                
                // as long as the stack count is greater than the
                // current indent level, we need to end list types.
                // continue adding end-list tokens until the stack count
                // and the indent level are the same.
                
                //$return .= "\n\n";
                
                $return .= $this->wiki->addToken(
                    $this->rule, 
                    array (
                        'type' => 'end',
                        'level' => $curLevel
                    )
                );
                
                //$return .= "\n";
                --$curLevel;
            }
            
            // add the line text.
            $return .= $val[2];
        }
        
        // the last char of the matched pattern must be \n but we don't
        // want this to be inside the tokens
        $return = substr($return, 0, -1);
        
        // the last line may have been indented.  go through the stack
        // and create end-tokens until the stack is empty.
        //$return .= "\n";

        while ($curLevel > 0) {
            $return .= $this->wiki->addToken(
                $this->rule, 
                array (
                    'type' => 'end',
                    'level' => $curLevel
                )
            );
            --$curLevel;
        }
        
        // put back the trailing \n
        $return .= "\n";

        // we're done!  send back the replacement text.
        return $return;
    }
}
?>