<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Titlebar rule end renderer for Latex
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Titlebar.php,v 1.1 2005/08/15 11:36:52 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders a title bar in Latex.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Latex_Titlebar extends Text_Wiki_Render {

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
            return '\framebox[\textwidth]{\textbf{';
        }

        if ($options['type'] == 'end') {
            return "}}\n";
        }
    }
}
?>
