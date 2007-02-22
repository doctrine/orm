<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Mediawiki: Parses for links to (inter)wiki pages or images.
 *
 * Text_Wiki rule parser to find links, it groups the 3 rules:
 * # Wikilink: links to internal Wiki pages
 * # Interwiki: links to external Wiki pages (sister projects, interlangage)
 * # Image: Images
 * as defined by text surrounded by double brackets [[]]
 * Translated are the link itself, the section (anchor) and alternate text
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Wikilink.php,v 1.7 2006/02/25 13:34:50 toggg Exp $
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Wikilink, Interwiki and Image rules parser class for Mediawiki.
 * This class implements a Text_Wiki_Parse to find links marked
 * in source by text surrounded by 2 opening/closing brackets as 
 * [[Wiki page name#Section|Alternate text]]
 * On parsing, the link is replaced with a token.
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
class Text_Wiki_Parse_Wikilink extends Text_Wiki_Parse {

    /**
     * Configuration for this rule (Wikilink)
     *
     * @access public
     * @var array
    */
    var $conf = array(
        'spaceUnderscore' => true,
        'project' => array('demo', 'd'),
        'url' => 'http://example.com/en/page=%s',
        'langage' => 'en'
    );

    /**
     * Configuration for the Image rule
     *
     * @access public
     * @var array
    */
    var $imageConf = array(
        'prefix' => array('Image', 'image')
    );

    /**
     * Configuration for the Interwiki rule
     *
     * @access public
     * @var array
    */
    var $interwikiConf = array(
        'sites' => array(
            'manual' => 'http://www.php.net/manual/en/%s',
            'pear'   => 'http://pear.php.net/package/%s',
            'bugs'   => 'http://pear.php.net/package/%s/bugs'
        ),
        'interlangage' => array('en', 'de', 'fr')
    );

    /**
     * The regular expression used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see Text_Wiki_Parse::parse()
     */
    var $regex = '/(?<!\[)\[\[(?!\[)\s*(:?)((?:[^:]+:)+)?([^:]+)(?:#(.*))?\s*(?:\|(((?R))|.*))?]]/msU';

     /**
     * Constructor.
     * We override the constructor to get Image and Interwiki config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Wikilink(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // override config options for image if specified
        if (in_array('Image', $this->wiki->disable)) {
            $this->imageConf['prefix'] = array();
        } else {
            if (isset($this->wiki->parseConf['Image']) &&
                is_array($this->wiki->parseConf['Image'])) {
                $this->imageConf = array_merge(
                    $this->imageConf,
                    $this->wiki->parseConf['Image']
                );
            }
        }

        // override config options for interwiki if specified
        if (in_array('Interwiki', $this->wiki->disable)) {
            $this->interwikiConf['sites'] = array();
            $this->interwikiConf['interlangage'] = array();
        } else {
            if (isset($this->wiki->parseConf['Interwiki']) &&
                is_array($this->wiki->parseConf['Interwiki'])) {
                $this->interwikiConf = array_merge(
                    $this->interwikiConf,
                    $this->wiki->parseConf['Interwiki']
                );
            }
            if (empty($this->conf['langage'])) {
                $this->interwikiConf['interlangage'] = array();
            }
        }
        // convert the list of recognized schemes to a regex OR,
/*        $schemes = $this->getConf('schemes', $default['schemes']);
        $this->url = str_replace( '#delim#', $this->wiki->delim,
           '#(?:' . (is_array($schemes) ? implode('|', $schemes) : $schemes) . ')://'
           . $this->getConf('host_regexp', $default['host_regexp'])
           . $this->getConf('path_regexp', $default['path_regexp']) .'#'); */
    }

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'page' => the name of the target wiki page
     * -'anchor' => the optional section in it
     * - 'text' => the optional alternate link text
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string token to be used as replacement 
     */
    function process(&$matches)
    {
        // Starting colon ?
        $colon = !empty($matches[1]);
        $auto = $interlang = $interwiki = $image = $site = '';
        // Prefix ?
        if (!empty($matches[2])) {
            $prefix = explode(':', substr($matches[2], 0, -1));
            $count = count($prefix);
            $i = -1;
            // Autolink
            if (isset($this->conf['project']) &&
                    in_array(trim($prefix[0]), $this->conf['project'])) {
                $auto = trim($prefix[0]);
                unset($prefix[0]);
                $i = 0;
            }
            while (++$i < $count) {
                $prefix[$i] = trim($prefix[$i]);
                // interlangage
                if (!$interlang &&
                    in_array($prefix[$i], $this->interwikiConf['interlangage'])) {
                    $interlang = $prefix[$i];
                    unset($prefix[$i]);
                    continue;
                }
                // image
                if (!$image && in_array($prefix[$i], $this->imageConf['prefix'])) {
                    $image = $prefix[$i];
                    unset($prefix[$i]);
                    break;
                }
                // interwiki
                if (isset($this->interwikiConf['sites'][$prefix[$i]])) {
                    $interwiki = $this->interwikiConf['sites'][$prefix[$i]];
                    $site = $prefix[$i];
                    unset($prefix[$i]);
                }
                break;
            }
            if ($prefix) {
                $matches[3] = implode(':', $prefix) . ':' . $matches[3];
            }
        }
        $text = empty($matches[5]) ? $matches[3] : $matches[5];
        $matches[3] = trim($matches[3]);
        $matches[4] = empty($matches[4]) ? '' : trim($matches[4]);
        if ($this->conf['spaceUnderscore']) {
            $matches[3] = preg_replace('/\s+/', '_', $matches[3]);
            $matches[4] = preg_replace('/\s+/', '_', $matches[4]);
        }
        if ($image) {
            return $this->image($matches[3] . (empty($matches[4]) ? '' : '#' . $matches[4]),
                                $text, $interlang, $colon);
        }
        if (!$interwiki && $interlang && isset($this->conf['url'])) {
            if ($interlang == $this->conf['langage']) {
                $interlang = '';
            } else {
                $interwiki = $this->conf['url'];
                $site = isset($this->conf['project']) ? $this->conf['project'][0] : '';
            }
        }
        if ($interwiki) {
            return $this->interwiki($site, $interwiki,
                $matches[3] . (empty($matches[4]) ? '' : '#' . $matches[4]),
                $text, $interlang, $colon);
        }
        if ($interlang) {
            $matches[3] = $interlang . ':' . $matches[3];
            $text = (empty($matches[5]) ? $interlang . ':' : '') . $text;
        }
        // set the options
        $options = array(
            'page'   => $matches[3],
            'anchor' => (empty($matches[4]) ? '' : $matches[4]),
            'text'   => $text
        );

        // create and return the replacement token
        return $this->wiki->addToken($this->rule, $options);
    }

    /**
     * Generates an image token.  Token options are:
     * - 'src' => the name of the image file
     * - 'attr' => an array of attributes for the image:
     * | - 'alt' => the optional alternate image text
     * | - 'align => 'left', 'center' or 'right'
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string token to be used as replacement 
     */
    function image($name, $text, $interlang, $colon)
    {
        $attr = array('alt' => '');
        // scan text for supplementary attibutes
        if (strpos($text, '|') !== false) {
            $splits = explode('|', $text);
            $sep = '';
            foreach ($splits as $split) {
                switch (strtolower($split)) {
                    case 'left': case 'center': case 'right':
                        $attr['align'] = strtolower($split);
                        break;
                    default:
                        $attr['alt'] .= $sep . $split;
                        $sep = '|';
                }
            }
        } else {
            $attr['alt'] = $text;
        }
        $options = array(
            'src' => ($interlang ? $interlang . ':' : '') . $name,
            'attr' => $attr);

        // create and return the replacement token
        return $this->wiki->addToken('Image', $options);
    }

    /**
     * Generates an interwiki token.  Token options are:
     * - 'page' => the name of the target wiki page
     * - 'site' => the key for external site
     * - 'url'  => the full target url
     * - 'text' => the optional alternate link text
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string token to be used as replacement 
     */
    function interwiki($site, $interwiki, $page, $text, $interlang, $colon)
    {
        if ($interlang) {
            $interwiki = preg_replace('/\b' . $this->conf['langage'] . '\b/i',
                            $interlang, $interwiki);
        }
        // set the options
        $options = array(
            'page' => $page,
            'site' => $site,
            'url'  => sprintf($interwiki, $page),
            'text' => $text
        );

        // create and return the replacement token
        return $this->wiki->addToken('Interwiki', $options);
    }
}
?>
