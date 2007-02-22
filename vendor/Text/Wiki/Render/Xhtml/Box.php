<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Box rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Box.php,v 1.2 2005/07/30 08:03:28 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders a box drawn in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Box extends Text_Wiki_Render {

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
            return "<div class='simplebox'$css>";
        }

        if ($options['type'] == 'end') {
            return '</div>';
        }
    }
}
?>
