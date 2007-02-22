<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: extra Font rules renderer to size the text
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Font.php,v 1.2 2006/03/11 11:14:23 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Font rule render class (used for BBCode)
 * 
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki::Text_Wiki_Render()
 */
class Text_Wiki_Render_Latex_Font extends Text_Wiki_Render {
    
    /**
     * A table to translate the sizes
     * 
     * @access public
     * @var array
     */
    var $sizes = array(
        'tiny' => 5,
        'scriptsize' => 7,
        'footnotesize' => 8,
        'small' => 9,
        'normalsize' => 11,
        'large' => 13,
        'Large' => 16,
        'LARGE' => 19,
        'huge' => 22,
        'Huge' => 9999);
    
    /**
      * Renders a token into text matching the requested format.
      * process the font size option 
      *
      * @access public
      * @param array $options The "options" portion of the token (second element).
      * @return string The text rendered from the token options.
      */
    function token($options)
    {
        if ($options['type'] == 'start') {
            foreach ($this->sizes as $key => $lim) {
                if ($options['size'] < $lim) {
                    break;
                }
            }
            return '\{' . $key . '}{';
        }
        
        if ($options['type'] == 'end') {
            return '}';
        }
    }
}
?>
