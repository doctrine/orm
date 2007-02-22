<?php

/**
* 
* Embeds the results of a PHP script at render-time.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Embed.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Embeds the results of a PHP script at render-time.
* 
* This class implements a Text_Wiki_Parse to embed the contents of a URL
* inside the page at render-time.  Typically used to get script output.
* This differs from the 'include' rule, which incorporates results at
* parse-time; 'embed' output does not get parsed by Text_Wiki, while
* 'include' ouput does.
*
* This rule is inherently not secure; it allows cross-site scripting to
* occur if the embedded output has <script> or other similar tags.  Be
* careful.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Embed extends Text_Wiki_Parse {
    
    var $conf = array(
        'base' => '/path/to/scripts/'
    );
    
    var $file = null;

    var $output = null;

    var $vars = null;


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
    
    var $regex = '/(\[\[embed )(.+?)( .+?)?(\]\])/i';
    
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'text' => The full matched text, not including the <code></code> tags.
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
        // save the file location
        $this->file = $this->getConf('base', './') . $matches[2];
        
        // extract attribs as variables in the local space
        $this->vars = $this->getAttrs($matches[3]);
        unset($this->vars['this']);
        extract($this->vars);
        
        // run the script
        ob_start();
        include($this->file);
        $this->output = ob_get_contents();
        ob_end_clean();
        
        // done, place the script output directly in the source
        return $this->wiki->addToken(
            $this->rule,
            array('text' => $this->output)
        );
    }
}
?>