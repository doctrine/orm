<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Blockquote rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Blockquote.php,v 1.9 2007/05/26 18:25:23 mic Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders a blockquote in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Blockquote extends Text_Wiki_Render {

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
        $type = $options['type'];
        $level = $options['level'];

        // set up indenting so that the results look nice; we do this
        // in two steps to avoid str_pad mathematics.  ;-)
        $pad = str_pad('', $level, "\t");
        $pad = str_replace("\t", '    ', $pad);

        // pick the css type
        $css = $this->formatConf(' class="%s"', 'css');

        if (isset($options['css'])) {
            $css = ' class="' . $options['css']. '"';
        }
        // starting
        if ($type == 'start') {
            $output = $pad;
            if ($level > 1) {
                $output .= '</p>';
            }
            $output .= "<blockquote$css><p>";
            
            return $output;
        }

        // ending
        if ($type == 'end') {
            $output = $pad . "</p></blockquote>\n";
            if ($level > 1) {
                $output .= '<p>';
            } 
            return $output;
        }
    }
}
?>
