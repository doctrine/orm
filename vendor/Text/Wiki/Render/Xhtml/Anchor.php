<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Anchor rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Anchor.php,v 1.10 2006/02/10 23:07:03 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders an anchor target name in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Anchor extends Text_Wiki_Render {

    var $conf = array(
        'css' => null
    );

    function token($options)
    {
        extract($options); // $type, $name

        if ($type == 'start') {
            $css = $this->formatConf(' class="%s"', 'css');
            $format = "<a$css id=\"%s\">";
            return sprintf($format, $this->textEncode($name));
        }

        if ($type == 'end') {
            return '</a>';
        }
    }
}

?>
