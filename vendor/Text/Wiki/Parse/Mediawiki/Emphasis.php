<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for emphazised text.
 *
 * Text_Wiki rule parser to find source text emphazised
 * as defined by text surrounded by repeated single quotes  ''...'' and more
 * Translated are ''emphasis'' , '''strong''' or '''''both''''' ...
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Emphasis.php,v 1.4 2006/02/15 12:27:40 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Emphazised text rule parser class for Mediawiki. Makes Emphasis, Strong or both
 * This class implements a Text_Wiki_Parse to find source text marked for
 * emphasis, stronger and very as defined by text surrounded by 2,3 or 5 single-quotes.
 * On parsing, the text itself is left in place, but the starting and ending
 * instances of the single-quotes are replaced with tokens.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Emphasis extends Text_Wiki_Parse {

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     * We match '' , ''' or ''''' embeded texts
     *
     * @access public
     * @var string
     * @see Text_Wiki_Parse::parse()
     */
    var $regex = "/(?<!')'('{1,4})(.*?)\\1'(?!')/";

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the emphasized text.
     * Generated tokens are Emphasis (this rule), Strong or Emphasis / Strong
     * The text itself is left in the source but may content bested blocks
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string Delimited by start/end tokens to be used as
     * placeholder in the source text surrounding the text to be emphasized.
     */
    function process(&$matches)
    {
        $embeded = $matches[2];
        switch (strlen($matches[1])) {
            case 1:
                $start = $this->wiki->addToken($this->rule, array('type' => 'start'));
                $end   = $this->wiki->addToken($this->rule, array('type' => 'end'));
            break;
            case 3:
                $embeded = "'" . $embeded . "'";
            case 2:
                $start = $this->wiki->addToken('Strong',    array('type' => 'start'));
                $end   = $this->wiki->addToken('Strong',    array('type' => 'end'));
            break;
            case 4:
                $start = $this->wiki->addToken($this->rule, array('type' => 'start'))
                       . $this->wiki->addToken('Strong',    array('type' => 'start'));
                $end   = $this->wiki->addToken('Strong',    array('type' => 'end'))
                       . $this->wiki->addToken($this->rule, array('type' => 'end'));
            break;
        }
        return $start . $embeded . $end;
    }
}
?>
