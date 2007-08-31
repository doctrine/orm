<?php

class Text_Wiki_Render_Latex_Code extends Text_Wiki_Render {
    
    
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
        $text = $options['text'];
        $attr = $options['attr'];
        $type = strtolower($attr['type']);
        
        if ($type == 'php') {
            if (substr($options['text'], 0, 5) != '<?php') {
                // PHP code example:
                // add the PHP tags
                $text = "<?php\n\n" . $options['text'] . "\n\n?>"; // <?php
            }
        }
            
        $text = "\\begin{lstlisting}\n$text\n\\end{lstlisting}\n\n"; 
        
        if ($type != '') {
            $text = "\\lstset{language=$type}\n" . $text;
        } else {
            $text = "\\lstset{language={}}\n" . $text;
        }

        return $text;
    }
}
?>
