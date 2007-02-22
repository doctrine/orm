<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Plugin rule end renderer for Latex
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Plugin.php,v 1.1 2005/08/17 09:16:35 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders wiki plugins in Latex. (empty)
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Latex_Plugin extends Text_Wiki_Render {

    /**
    *
    * Renders a token into text matching the requested format.
    * Plugins produce wiki markup so are processed by parsing, no tokens produced
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
        return '';
    }
}
?>
