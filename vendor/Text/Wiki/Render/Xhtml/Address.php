<?php

/**
 *
 * Address rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 *
 * @package    Text_Wiki
 *
 * @author     Michele Tomaiuolo <tomamic@yahoo.it>
 *
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 * @version    CVS: $Id: Address.php,v 1.1 2007/02/01 09:33:00 mic Exp $
 *
 * @link       http://pear.php.net/package/Text_Wiki
 *
 */

class Text_Wiki_Render_Xhtml_Address extends Text_Wiki_Render {

    var $conf = array(
        'css' => null
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
        if ($options['type'] == 'start') {
            $css = $this->formatConf(' class="%s"', 'css');
            return "<address$css>";
        }

        if ($options['type'] == 'end') {
            return '</address>';
        }
    }
}
?>
