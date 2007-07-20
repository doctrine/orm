<?php


class Text_Wiki_Render_Latex_Url extends Text_Wiki_Render {


    var $conf = array(
        'target' => false,
        'images' => true,
        'img_ext' => array('jpg', 'jpeg', 'gif', 'png')
    );

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
        // create local variables from the options array (text,
        // href, type)
        extract($options);

        if ($options['type'] == 'start') {
            return '';
        } else if ($options['type'] == 'end') {
            return '\footnote{' . $href . '}';
        } else {
            return $text . '\footnote{' . $href . '}';
        }
    }
}
?>
