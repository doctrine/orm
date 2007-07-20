<?php
/**
 * Parse structured wiki text and render into arbitrary formats such as XHTML.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Justin Patrin <justinpatrin@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Default.php,v 1.1 2006/03/01 16:58:17 justinpatrin Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

require_once('Text/Wiki.php');

/**
 * This is the parser for the documentation
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author     Justin Patrin <justinpatrin@php.net>
 */
class Text_Wiki_Doc extends Text_Wiki {

    var $rules = array(
        'Prefilter',
        'Delimiter',
        'Code',
        'Raw',
        'Horiz',
        'Break',
        'Blockquote',
        'List',
        'Deflist',
        'Table',
        'Image',
        'Phplookup',
        'Newline',
        'Paragraph',
        'Url',
        'Doclink',
        'Colortext',
        'Strong',
        'Bold',
        'Emphasis',
        'Italic',
        'Underline',
        'Tt',
        'Superscript',
        'Subscript',
        'Revise',
        'Tighten'
    );

    function Text_Wiki_Doc($rules = null) {
        parent::Text_Wiki($rules);
        
        $this->addPath('parse', $this->fixPath(dirname(__FILE__)) . 'Parse/Doc');
        $this->addPath('render', $this->fixPath(dirname(__FILE__)) . 'Render/');
        
        $this->setRenderConf('Xhtml', 'Url', 'target', '');
        $this->setRenderConf('Xhtml', 'charset', 'UTF-8');
    }
    
}
