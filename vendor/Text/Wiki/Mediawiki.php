<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Parse structured wiki text and render into arbitrary formats such as XHTML.
 * This is the Text_Wiki extension for Mediawiki markup
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Mediawiki.php,v 1.8 2006/02/25 09:59:34 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * "master" class for handling the management and convenience
 */
require_once('Text/Wiki.php');

/**
 * Base Text_Wiki handler class extension for Mediawiki markup
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki()
 */
class Text_Wiki_Mediawiki extends Text_Wiki {
    var $rules = array(
        'Prefilter',
        'Delimiter',
//        'Code',
//        'Plugin',
//        'Function',
//        'Html',
        'Raw',
//        'Preformatted',
//        'Include',
//        'Embed',
//        'Page',
//        'Anchor',
        'Heading',
        'Toc',
//        'Titlebar',
        'Horiz',
        'Break',
//        'Blockquote',
        'List',
        'Deflist',
        'Table',
//        'Box',
//        'Image', // done by Wikilink but still possible to disable/configure
//        'Phplookup',
//        'Center',
        'Newline',
        'Paragraph',
        'Url',
//        'Freelink',
//        'Colortext',
        'Wikilink',
//        'Strong',  ** will be only fake inserted by Emphasis if needed for render
//        'Bold',
        'Emphasis',
//        'Italic',
//        'Underline',
//        'Tt',
//        'Superscript',
//        'Subscript',
//        'Specialchar',
//        'Revise',
//        'Interwiki', // done by Wikilink but still possible to disable/configure
        'Tighten'
    );

	/**
     * Constructor: just adds the path to Mediawiki rules
     *
     * @access public
     * @param array $rules The set of rules to load for this object.
     */
    function Text_Wiki_Mediawiki($rules = null) {
        parent::Text_Wiki($rules);
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)).'Parse/Mediawiki');
    }
}

?>
