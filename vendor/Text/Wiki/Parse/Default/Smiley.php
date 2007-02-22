<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Default: Parses for smileys / emoticons tags
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * smileys defined by symbols as ':)' , ':-)' or ':smile:'
 * The symbol is replaced with a token.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Smiley.php,v 1.6 2005/10/04 08:17:51 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Smiley rule parser class for Default.
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
class Text_Wiki_Parse_Smiley extends Text_Wiki_Parse {

    /**
     * Configuration keys for this rule
     * 'smileys' => array Smileys recognized by this rule, symbols key definitions:
     *              'symbol' => array ( 'name', 'description' [, 'variante', ...] ) as
     *                  ':)'  => array('smile', 'Smile'),
     *                  ':D'  => array('biggrin', 'Very Happy',':grin:'),
     *              the eventual elements after symbol and description are variantes
     *
     * 'auto_nose' => boolean enabling the auto nose feature:
     *                auto build a variante for 2 chars symbols by inserting a '-' as ':)' <=> ':-)'
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'smileys' => array(
            ':D'        => array('biggrin', 'Very Happy', ':grin:'),
            ':)'        => array('smile', 'Smile', '(:'),
            ':('        => array('sad', 'Sad', '):'),
            ':o'        => array('surprised', 'Surprised', ':eek:', 'o:'),
            ':shock:'   => array('eek', 'Shocked'),
            ':?'        => array('confused', 'Confused', ':???:'),
            '8)'        => array('cool', 'Cool', '(8'),
            ':lol:'     => array('lol', 'Laughing'),
            ':x'        => array('mad', 'Mad'),
            ':P'        => array('razz', 'Razz'),
            ':oops:'    => array('redface', 'Embarassed'),
            ':cry:'     => array('cry', 'Crying or Very sad'),
            ':evil:'    => array('evil', 'Evil or Very Mad'),
            ':twisted:' => array('twisted', 'Twisted Evil'),
            ':roll:'    => array('rolleyes', 'Rolling Eyes'),
            ';)'        => array('wink', 'Wink', '(;'),
            ':!:'       => array('exclaim', 'Exclamation'),
            ':?:'       => array('question', 'Question'),
            ':idea:'    => array('idea', 'Idea'),
            ':arrow:'   => array('arrow', 'Arrow'),
            ':|'        => array('neutral', 'Neutral', '|:'),
            ':mrgreen:' => array('mrgreen', 'Mr. Green'),
        ),
        'auto_nose' => true
    );

    /**
     * Definition array of smileys, variantes references their model
     * 'symbol' => array ( 'name', 'description')
     *
     * @access private
     * @var array 'config-key' => mixed config-value
     */
    var $_smileys = array();

     /**
     * Constructor.
     * We override the constructor to build up the regex from config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Smiley(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // read the list of smileys to sort out variantes and :xxx: while building the regexp
        $this->_smileys = $this->getConf('smileys', $default['smileys']);
        $autoNose = $this->getConf('auto_nose', $default['auto_nose']);
        $reg1 = $reg2 = '';
        $sep1 = ':(?:';
        $sep2 = '';
        foreach ($this->_smileys as $smiley => $def) {
            for ($i = 1; $i < count($def); $i++) {
                if ($i > 1) {
                    $cur = $def[$i];
                    $this->_smileys[$cur] = &$this->_smileys[$smiley];
                } else {
                    $cur = $smiley;
                }
                $len = strlen($cur);
                if (($cur{0} == ':') && ($len > 2) && ($cur{$len - 1} == ':')) {
                    $reg1 .= $sep1 . preg_quote(substr($cur, 1, -1), '#');
                    $sep1 = '|';
                    continue;
                }
                if ($autoNose && ($len === 2)) {
                    $variante = $cur{0} . '-' . $cur{1};
                    $this->_smileys[$variante] = &$this->_smileys[$smiley];
                    $cur = preg_quote($cur{0}, '#') . '-?' . preg_quote($cur{1}, '#');
                } else {
                    $cur = preg_quote($cur, '#');
                }
                $reg2 .= $sep2 . $cur;
                $sep2 = '|';
            }
        }
        $delim = '[\n\r\s' . $this->wiki->delim . '$^]';
        $this->regex = '#(?<=' . $delim .
             ')(' . ($reg1 ? $reg1 . '):' . ($reg2 ? '|' : '') : '') . $reg2 .
             ')(?=' . $delim . ')#i';
    }

    /**
     * Generates a replacement token for the matched text.  Token options are:
     *     'symbol' => the original marker
     *     'name' => the name of the smiley
     *     'desc' => the description of the smiley
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited token representing the smiley
     * @access public
     */
    function process(&$matches)
    {
        // tokenize
        return $this->wiki->addToken($this->rule,
            array(
                'symbol' => $matches[1],
                'name'   => $this->_smileys[$matches[1]][0],
                'desc'   => $this->_smileys[$matches[1]][1]
            ));
    }
}
?>
