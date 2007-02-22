<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Smiley rule Latex renderer
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Smiley.php,v 1.2 2005/08/10 11:42:08 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Smiley rule Latex render class
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki_Render()
 */
class Text_Wiki_Render_Latex_Smiley extends Text_Wiki_Render {

    /**
      * Renders a token into text matching the requested format.
      * process the Smileys
      *
      * @access public
      * @param array $options The "options" portion of the token (second element).
      * @return string The text rendered from the token options.
      */
    function token($options)
    {
        return $options['symbol'];
    }
}
?>
