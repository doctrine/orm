<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Prefilter rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Prefilter.php,v 1.7 2005/07/30 08:03:29 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class implements a Text_Wiki_Render_Xhtml to "pre-filter" source text so
 * that line endings are consistently \n, lines ending in a backslash \
 * are concatenated with the next line, and tabs are converted to spaces.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Render_Xhtml_Prefilter extends Text_Wiki_Render {
    function token()
    {
        return '';
    }
}
?>
