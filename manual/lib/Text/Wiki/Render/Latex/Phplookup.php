<?php

class Text_Wiki_Render_Latex_Phplookup extends Text_Wiki_Render {
    
    /**
    * 
    * Renders a token into text matching the requested format.
    * 
    * @access public
    * 
    * @param array $options The "options" portion of the token (second
    * element).
    * 
    * @return string The text rendered from the token options.
    * 
    */
    
    function token($options)
    {
        $text = trim($options['text']);
        
        // take off the final parens for functions
        if (substr($text, -2) == '()') {
            $q = substr($text, 0, -2);
        } else {
            $q = $text;
        }
        
        $formatObj = $this->wiki->formatObj[$this->format];
        
        // toggg 2006/02/05 page name must be url encoded (e.g. may contain spaces)
        $q = $formatObj->escape_latex($this->urlEncode($q));
        $text = $formatObj->escape_latex($text);
        
        return '\texttt{' . $text . '}\footnote{\url{http://php.net/' . $q . '}}';
    }
}
?>
