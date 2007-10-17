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
require_once('highlight.php');
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

        if ($type == 'php') {
            if (substr($options['text'], 0, 5) != '<?php') {
                // PHP code example:
                // add the PHP tags
                $text = "<?php\n\n" . $options['text'] . "\n\n?>"; // <?php
            }

            // convert tabs to four spaces
            $text = str_replace("\t", "    ", $text);

            // colorize the code block (also converts HTML entities and adds
            // <pre>...</pre> tags)
            $h = new PHP_Highlight(true);
            $h->loadString($text);
            $text = @$h->toHtml(true);

            $text = str_replace('&nbsp;', ' ', $text);

            $text = str_replace('<pre>', "<pre><code$css_php>", $text);
            $text = str_replace('</pre>', "</code></pre>", $text);

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
