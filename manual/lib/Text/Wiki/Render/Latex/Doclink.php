<?php
class Text_Wiki_Render_Latex_Doclink extends Text_Wiki_Render {
    
    function token($options)
    {
        return '\ref{' . $options['path'] . '}';
    }
}
