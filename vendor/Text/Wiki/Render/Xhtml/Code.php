<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Code rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Code.php,v 1.13 2006/02/10 23:07:03 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class renders code blocks in XHTML.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Code extends Text_Wiki_Render {

    var $conf = array(
        'css'      => null, // class for <pre>
        'css_code' => null, // class for generic <code>
        'css_php'  => null, // class for PHP <code>
        'css_html' => null, // class for HTML <code>
        'css_filename' => null // class for optional filename <div>
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
        $text = $options['text'];
        $attr = $options['attr'];
        $type = strtolower($attr['type']);

        $css      = $this->formatConf(' class="%s"', 'css');
        $css_code = $this->formatConf(' class="%s"', 'css_code');
        $css_php  = $this->formatConf(' class="%s"', 'css_php');
        $css_html = $this->formatConf(' class="%s"', 'css_html');
        $css_filename = $this->formatConf(' class="%s"', 'css_filename');

        if ($type == 'php' || true) {
            if (substr($options['text'], 0, 5) != '<?php') {
                // PHP code example:
                // add the PHP tags
                $text = "<?php 
                " .  $options['text'] . "\n?>"; // <?php
            }

            // convert tabs to four spaces
            $text = str_replace("\t", "&nbsp;&nbsp;%nbsp;&nbsp;", $text);

            // colorize the code block (also converts HTML entities and adds
            // <code>...</code> tags)
            $h = new PHP_Highlight;
            $h->loadString($text);
            $text = $h->toHtml(true);

            // replace <br /> tags with simple newlines.
            // replace non-breaking space with simple spaces.
            // translate HTML <font> and color to XHTML <span> and style.
            // courtesy of research by A. Kalin :-).
            /**
            $map = array(
                '<br />'  => "\n",
                '&nbsp;'  => ' ',
                "\n"      => "<br \>",
                '<font'   => '<span',
                '</font>' => '</span>',
                'color="' => 'style="color:'
            );

            $text = strtr($text, $map);
            */

            // get rid of the last newline inside the code block
            // (becuase higlight_string puts one there)
            if (substr($text, -8) == "\n</code>") {
                $text = substr($text, 0, -8) . "</code>";
            }

            // replace all <code> tags with classed tags
            if ($css_php) {
                $text = str_replace('<code>', "<code$css_php>", $text);
            }

            // done
            $text = "<table border=1 class='dashed' cellpadding=0 cellspacing=0>
                     <tr><td><b>$text
                     </b></td></tr>
                     </table>";

        } elseif ($type == 'html' || $type == 'xhtml') {

            // HTML code example:
            // add <html> opening and closing tags,
            // convert tabs to four spaces,
            // convert entities.
            $text = str_replace("\t", "    ", $text);
            $text = "<html>\n$text\n</html>";
            $text = $this->textEncode($text);
            $text = "<pre$css><code$css_html>$text</code></pre>";

        } else {
            // generic code example:
            // convert tabs to four spaces,
            // convert entities.
            $text = str_replace("\t", "    ", $text);
            $text = $this->textEncode($text);
            $text = "<pre$css><code$css_code>$text</code></pre>";
        }

        if ($css_filename && isset($attr['filename'])) {
            $text = "<div$css_filename>" .
                $attr['filename'] . '</div>' . $text;
        }

        return "\n$text\n\n";
    }
}
?>
