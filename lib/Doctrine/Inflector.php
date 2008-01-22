<?php
/*
 *  $Id: Inflector.php 3189 2007-11-18 20:37:44Z meus $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Inflector has static methods for inflecting text
 * 
 * The methods in these classes are from several different sources collected
 * across the internet through php development for several years.
 * They have been updated and modified a little bit for Doctrine but are mainly untouched.
 *
 * @package     Doctrine
 * @subpackage  Inflector
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 3189 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Inflector
{
    /**
    * pluralize
    *
    * @param    string $word English noun to pluralize
    * @return   string Plural noun
    */
    public static function pluralize($word)
    {
        $plural = array('/(quiz)$/i' => '\1zes',
                        '/^(ox)$/i' => '\1en',
                        '/([m|l])ouse$/i' => '\1ice',
                        '/(matr|vert|ind)ix|ex$/i' => '\1ices',
                        '/(x|ch|ss|sh)$/i' => '\1es',
                        '/([^aeiouy]|qu)ies$/i' => '\1y',
                        '/([^aeiouy]|qu)y$/i' => '\1ies',
                        '/(hive)$/i' => '\1s',
                        '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
                        '/sis$/i' => 'ses',
                        '/([ti])um$/i' => '\1a',
                        '/(buffal|tomat)o$/i' => '\1oes',
                        '/(bu)s$/i' => '\1ses',
                        '/(alias|status)/i' => '\1es',
                        '/(octop|vir)us$/i' => '\1i',
                        '/(ax|test)is$/i' => '\1es',
                        '/s$/i' => 's',
                        '/$/' => 's');

        $uncountable = array('equipment',
                             'information',
                             'rice',
                             'money',
                             'species',
                             'series',
                             'fish',
                             'sheep');

        $irregular = array('person' => 'people',
                           'man'    => 'men',
                           'child'  => 'children',
                           'sex'    => 'sexes',
                           'move'   => 'moves');

        $lowercasedWord = strtolower($word);

        foreach ($uncountable as $_uncountable) {
            if(substr($lowercasedWord, (-1 * strlen($_uncountable))) == $_uncountable) {
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1) . substr($_singular,1), $word);
            }
        }

        foreach ($plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return false;
    }

    /**
    * singularize
    *
    * @param    string    $word    English noun to singularize
    * @return   string Singular noun.
    */
    public static function singularize($word)
    {
        $singular = array('/(quiz)zes$/i' => '\\1',
                          '/(matr)ices$/i' => '\\1ix',
                          '/(vert|ind)ices$/i' => '\\1ex',
                          '/^(ox)en/i' => '\\1',
                          '/(alias|status)es$/i' => '\\1',
                          '/([octop|vir])i$/i' => '\\1us',
                          '/(cris|ax|test)es$/i' => '\\1is',
                          '/(shoe)s$/i' => '\\1',
                          '/(o)es$/i' => '\\1',
                          '/(bus)es$/i' => '\\1',
                          '/([m|l])ice$/i' => '\\1ouse',
                          '/(x|ch|ss|sh)es$/i' => '\\1',
                          '/(m)ovies$/i' => '\\1ovie',
                          '/(s)eries$/i' => '\\1eries',
                          '/([^aeiouy]|qu)ies$/i' => '\\1y',
                          '/([lr])ves$/i' => '\\1f',
                          '/(tive)s$/i' => '\\1',
                          '/(hive)s$/i' => '\\1',
                          '/([^f])ves$/i' => '\\1fe',
                          '/(^analy)ses$/i' => '\\1sis',
                          '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
                          '/([ti])a$/i' => '\\1um',
                          '/(n)ews$/i' => '\\1ews',
                          '/s$/i' => '');

        $uncountable = array('equipment',
                             'information',
                             'rice',
                             'money',
                             'species',
                             'series',
                             'fish',
                             'sheep',
                             'sms');

        $irregular = array('person' => 'people',
                           'man'    => 'men',
                           'child'  => 'children',
                           'sex'    => 'sexes',
                           'move'   => 'moves');

        $lowercasedWord = strtolower($word);
        foreach ($uncountable as $_uncountable){
            if(substr($lowercasedWord, ( -1 * strlen($_uncountable))) == $_uncountable){
                return $word;
            }
        }

        foreach ($irregular as $_singular => $_plural) {
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
            }
        }

        foreach ($singular as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }

    /**
     * variablize
     * 
     * @param string $word 
     * @return void
     */
    public static function variablize($word)
    {
        $word = self::camelize($word);

        return strtolower($word[0]) . substr($word, 1);
    }

    /**
     * tableize
     *
     * @param string $name
     * @return void
     */
    public static function tableize($name)
    {
        // Would prefer this but it breaks unit tests. Forces the table underscore pattern
        // return self::pluralize(self::underscore($name));
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $name));
    }

    /**
     * classify
     *
     * @param string $word
     */
    public static function classify($word)
    {
        return preg_replace_callback('~(_?)(_)([\w])~', array("Doctrine_Inflector", "classifyCallback"), ucfirst(strtolower($word)));
    }

    /**
     * classifyCallback
     *
     * Callback function to classify a classname properly.
     *
     * @param array $matches An array of matches from a pcre_replace call
     * @return string A string with matches 1 and mathces 3 in upper case.
     */
    public static function classifyCallback($matches)
    {
        return $matches[1] . strtoupper($matches[3]);
    }

    /**
     * camelize
     *
     * @param string $word 
     * @return void
     */
    public static function camelize($word)
    {
        if (preg_match_all('/\/(.?)/', $word, $got)) {
            foreach ($got[1] as $k => $v){
                $got[1][$k] = '::' . strtoupper($v);
            }

            $word = str_replace($got[0], $got[1], $word);
        }

        return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/', ' ', $word)));
    }

    /**
     * unaccent
     *
     * @param string $text 
     * @return void
     */
    public static function unaccent($text)
    {
        $chars = array('À' => 'A',
                      'Á' => 'A',
                      'Â' => 'A',
                      'Ã' => 'A',
                      'Ä' => 'A',
                      'Å' => 'A',
                      'Æ' => 'AE',
                      'Ā' => 'A',
                      'Ą' => 'A',
                      'Ă' => 'A',
                      'Ç' => 'C',
                      'Ć' => 'C',
                      'Č' => 'C',
                      'Ĉ' => 'C',
                      'Ċ' => 'C',
                      'Ď' => 'D',
                      'Đ' => 'D',
                      'È' => 'E',
                      'É' => 'E',
                      'Ê' => 'E',
                      'Ë' => 'E',
                      'Ē' => 'E',
                      'Ę' => 'E',
                      'Ě' => 'E',
                      'Ĕ' => 'E',
                      'Ė' => 'E',
                      'Ĝ' => 'G',
                      'Ğ' => 'G',
                      'Ġ' => 'G',
                      'Ģ' => 'G',
                      'Ĥ' => 'H',
                      'Ħ' => 'H',
                      'Ì' => 'I',
                      'Í' => 'I',
                      'Î' => 'I',
                      'Ï' => 'I',
                      'Ī' => 'I',
                      'Ĩ' => 'I',
                      'Ĭ' => 'I',
                      'Į' => 'I',
                      'İ' => 'I',
                      'Ĳ' => 'IJ',
                      'Ĵ' => 'J',
                      'Ķ' => 'K',
                      'Ľ' => 'L',
                      'Ĺ' => 'L',
                      'Ļ' => 'K',
                      'Ŀ' => 'K',
                      'Ł' => 'L',
                      'Ñ' => 'N',
                      'Ń' => 'N',
                      'Ň' => 'N',
                      'Ņ' => 'N',
                      'Ŋ' => 'N',
                      'Ò' => 'O',
                      'Ó' => 'O',
                      'Ô' => 'O',
                      'Õ' => 'O',
                      'Ö' => 'O',
                      'Ø' => 'O',
                      'Ō' => 'O',
                      'Ő' => 'O',
                      'Ŏ' => 'O',
                      'Œ' => 'OE',
                      'Ŕ' => 'R',
                      'Ř' => 'R',
                      'Ŗ' => 'R',
                      'Ś' => 'S',
                      'Ş' => 'S',
                      'Ŝ' => 'S',
                      'Ș' => 'S',
                      'Š' => 'S',
                      'Ť' => 'T',
                      'Ţ' => 'T',
                      'Ŧ' => 'T',
                      'Ț' => 'T',
                      'Ù' => 'U',
                      'Ú' => 'U',
                      'Û' => 'U',
                      'Ü' => 'Ue',
                      'Ū' => 'U',
                      'Ů' => 'U',
                      'Ű' => 'U',
                      'Ŭ' => 'U',
                      'Ũ' => 'U',
                      'Ų' => 'U',
                      'Ŵ' => 'W',
                      'Ŷ' => 'Y',
                      'Ÿ' => 'Y',
                      'Ý' => 'Y',
                      'Ź' => 'Z',
                      'Ż' => 'Z',
                      'Ž' => 'Z',
                      'à' => 'a',
                      'á' => 'a',
                      'â' => 'a',
                      'ã' => 'a',
                      'ä' => 'a',
                      'ā' => 'a',
                      'ą' => 'a',
                      'ă' => 'a',
                      'å' => 'a',
                      'æ' => 'ae',
                      'ç' => 'c',
                      'ć' => 'c',
                      'č' => 'c',
                      'ĉ' => 'c',
                      'ċ' => 'c',
                      'ď' => 'd',
                      'đ' => 'd',
                      'è' => 'e',
                      'é' => 'e',
                      'ê' => 'e',
                      'ë' => 'e',
                      'ē' => 'e',
                      'ę' => 'e',
                      'ě' => 'e',
                      'ĕ' => 'e',
                      'ė' => 'e',
                      'ƒ' => 'f',
                      'ĝ' => 'g',
                      'ğ' => 'g',
                      'ġ' => 'g',
                      'ģ' => 'g',
                      'ĥ' => 'h',
                      'ħ' => 'h',
                      'ì' => 'i',
                      'í' => 'i',
                      'î' => 'i',
                      'ï' => 'i',
                      'ī' => 'i',
                      'ĩ' => 'i',
                      'ĭ' => 'i',
                      'į' => 'i',
                      'ı' => 'i',
                      'ĳ' => 'ij',
                      'ĵ' => 'j',
                      'ķ' => 'k',
                      'ĸ' => 'k',
                      'ł' => 'l',
                      'ľ' => 'l',
                      'ĺ' => 'l',
                      'ļ' => 'l',
                      'ŀ' => 'l',
                      'ñ' => 'n',
                      'ń' => 'n',
                      'ň' => 'n',
                      'ņ' => 'n',
                      'ŉ' => 'n',
                      'ŋ' => 'n',
                      'ò' => 'o',
                      'ó' => 'o',
                      'ô' => 'o',
                      'õ' => 'o',
                      'ö' => 'o',
                      'ø' => 'o',
                      'ō' => 'o',
                      'ő' => 'o',
                      'ŏ' => 'o',
                      'œ' => 'oe',
                      'ŕ' => 'r',
                      'ř' => 'r',
                      'ŗ' => 'r',
                      'ś' => 's',
                      'š' => 's',
                      'ť' => 't',
                      'ù' => 'u',
                      'ú' => 'u',
                      'û' => 'u',
                      'ü' => 'u',
                      'ū' => 'u',
                      'ů' => 'u',
                      'ű' => 'u',
                      'ŭ' => 'u',
                      'ũ' => 'u',
                      'ų' => 'u',
                      'ŵ' => 'w',
                      'ÿ' => 'y',
                      'ý' => 'y',
                      'ŷ' => 'y',
                      'ż' => 'z',
                      'ź' => 'z',
                      'ž' => 'z',
                      'ß' => 'ss',
                      'ſ' => 'ss',
                      'Α' => 'A',
                      'Ά' => 'A',
                      'Ἀ' => 'A',
                      'Ἁ' => 'A',
                      'Ἂ' => 'A',
                      'Ἃ' => 'A',
                      'Ἄ' => 'A',
                      'Ἅ' => 'A',
                      'Ἆ' => 'A',
                      'Ἇ' => 'A',
                      'ᾈ' => 'A',
                      'ᾉ' => 'A',
                      'ᾊ' => 'A',
                      'ᾋ' => 'A',
                      'ᾌ' => 'A',
                      'ᾍ' => 'A',
                      'ᾎ' => 'A',
                      'ᾏ' => 'A',
                      'Ᾰ' => 'A',
                      'Ᾱ' => 'A',
                      'Ὰ' => 'A',
                      'Ά' => 'A',
                      'ᾼ' => 'A',
                      'Β' => 'B',
                      'Γ' => 'G',
                      'Δ' => 'D',
                      'Ε' => 'E',
                      'Έ' => 'E',
                      'Ἐ' => 'E',
                      'Ἑ' => 'E',
                      'Ἒ' => 'E',
                      'Ἓ' => 'E',
                      'Ἔ' => 'E',
                      'Ἕ' => 'E',
                      'Έ' => 'E',
                      'Ὲ' => 'E',
                      'Ζ' => 'Z',
                      'Η' => 'I',
                      'Ή' => 'I',
                      'Ἠ' => 'I',
                      'Ἡ' => 'I',
                      'Ἢ' => 'I',
                      'Ἣ' => 'I',
                      'Ἤ' => 'I',
                      'Ἥ' => 'I',
                      'Ἦ' => 'I',
                      'Ἧ' => 'I',
                      'ᾘ' => 'I',
                      'ᾙ' => 'I',
                      'ᾚ' => 'I',
                      'ᾛ' => 'I',
                      'ᾜ' => 'I',
                      'ᾝ' => 'I',
                      'ᾞ' => 'I',
                      'ᾟ' => 'I',
                      'Ὴ' => 'I',
                      'Ή' => 'I',
                      'ῌ' => 'I',
                      'Θ' => 'TH',
                      'Ι' => 'I',
                      'Ί' => 'I',
                      'Ϊ' => 'I',
                      'Ἰ' => 'I',
                      'Ἱ' => 'I',
                      'Ἲ' => 'I',
                      'Ἳ' => 'I',
                      'Ἴ' => 'I',
                      'Ἵ' => 'I',
                      'Ἶ' => 'I',
                      'Ἷ' => 'I',
                      'Ῐ' => 'I',
                      'Ῑ' => 'I',
                      'Ὶ' => 'I',
                      'Ί' => 'I',
                      'Κ' => 'K',
                      'Λ' => 'L',
                      'Μ' => 'M',
                      'Ν' => 'N',
                      'Ξ' => 'KS',
                      'Ο' => 'O',
                      'Ό' => 'O',
                      'Ὀ' => 'O',
                      'Ὁ' => 'O',
                      'Ὂ' => 'O',
                      'Ὃ' => 'O',
                      'Ὄ' => 'O',
                      'Ὅ' => 'O',
                      'Ὸ' => 'O',
                      'Ό' => 'O',
                      'Π' => 'P',
                      'Ρ' => 'R',
                      'Ῥ' => 'R',
                      'Σ' => 'S',
                      'Τ' => 'T',
                      'Υ' => 'Y',
                      'Ύ' => 'Y',
                      'Ϋ' => 'Y',
                      'Ὑ' => 'Y',
                      'Ὓ' => 'Y',
                      'Ὕ' => 'Y',
                      'Ὗ' => 'Y',
                      'Ῠ' => 'Y',
                      'Ῡ' => 'Y',
                      'Ὺ' => 'Y',
                      'Ύ' => 'Y',
                      'Φ' => 'F',
                      'Χ' => 'X',
                      'Ψ' => 'PS',
                      'Ω' => 'O',
                      'Ώ' => 'O',
                      'Ὠ' => 'O',
                      'Ὡ' => 'O',
                      'Ὢ' => 'O',
                      'Ὣ' => 'O',
                      'Ὤ' => 'O',
                      'Ὥ' => 'O',
                      'Ὦ' => 'O',
                      'Ὧ' => 'O',
                      'ᾨ' => 'O',
                      'ᾩ' => 'O',
                      'ᾪ' => 'O',
                      'ᾫ' => 'O',
                      'ᾬ' => 'O',
                      'ᾭ' => 'O',
                      'ᾮ' => 'O',
                      'ᾯ' => 'O',
                      'Ὼ' => 'O',
                      'Ώ' => 'O',
                      'ῼ' => 'O',
                      'α' => 'a',
                      'ά' => 'a',
                      'ἀ' => 'a',
                      'ἁ' => 'a',
                      'ἂ' => 'a',
                      'ἃ' => 'a',
                      'ἄ' => 'a',
                      'ἅ' => 'a',
                      'ἆ' => 'a',
                      'ἇ' => 'a',
                      'ᾀ' => 'a',
                      'ᾁ' => 'a',
                      'ᾂ' => 'a',
                      'ᾃ' => 'a',
                      'ᾄ' => 'a',
                      'ᾅ' => 'a',
                      'ᾆ' => 'a',
                      'ᾇ' => 'a',
                      'ὰ' => 'a',
                      'ά' => 'a',
                      'ᾰ' => 'a',
                      'ᾱ' => 'a',
                      'ᾲ' => 'a',
                      'ᾳ' => 'a',
                      'ᾴ' => 'a',
                      'ᾶ' => 'a',
                      'ᾷ' => 'a',
                      'β' => 'b',
                      'γ' => 'g',
                      'δ' => 'd',
                      'ε' => 'e',
                      'έ' => 'e',
                      'ἐ' => 'e',
                      'ἑ' => 'e',
                      'ἒ' => 'e',
                      'ἓ' => 'e',
                      'ἔ' => 'e',
                      'ἕ' => 'e',
                      'ὲ' => 'e',
                      'έ' => 'e',
                      'ζ' => 'z',
                      'η' => 'i',
                      'ή' => 'i',
                      'ἠ' => 'i',
                      'ἡ' => 'i',
                      'ἢ' => 'i',
                      'ἣ' => 'i',
                      'ἤ' => 'i',
                      'ἥ' => 'i',
                      'ἦ' => 'i',
                      'ἧ' => 'i',
                      'ᾐ' => 'i',
                      'ᾑ' => 'i',
                      'ᾒ' => 'i',
                      'ᾓ' => 'i',
                      'ᾔ' => 'i',
                      'ᾕ' => 'i',
                      'ᾖ' => 'i',
                      'ᾗ' => 'i',
                      'ὴ' => 'i',
                      'ή' => 'i',
                      'ῂ' => 'i',
                      'ῃ' => 'i',
                      'ῄ' => 'i',
                      'ῆ' => 'i',
                      'ῇ' => 'i',
                      'θ' => 'th',
                      'ι' => 'i',
                      'ί' => 'i',
                      'ϊ' => 'i',
                      'ΐ' => 'i',
                      'ἰ' => 'i',
                      'ἱ' => 'i',
                      'ἲ' => 'i',
                      'ἳ' => 'i',
                      'ἴ' => 'i',
                      'ἵ' => 'i',
                      'ἶ' => 'i',
                      'ἷ' => 'i',
                      'ὶ' => 'i',
                      'ί' => 'i',
                      'ῐ' => 'i',
                      'ῑ' => 'i',
                      'ῒ' => 'i',
                      'ΐ' => 'i',
                      'ῖ' => 'i',
                      'ῗ' => 'i',
                      'κ' => 'k',
                      'λ' => 'l',
                      'μ' => 'm',
                      'ν' => 'n',
                      'ξ' => 'ks',
                      'ο' => 'o',
                      'ό' => 'o',
                      'ὀ' => 'o',
                      'ὁ' => 'o',
                      'ὂ' => 'o',
                      'ὃ' => 'o',
                      'ὄ' => 'o',
                      'ὅ' => 'o',
                      'ὸ' => 'o',
                      'ό' => 'o',
                      'π' => 'p',
                      'ρ' => 'r',
                      'ῤ' => 'r',
                      'ῥ' => 'r',
                      'σ' => 's',
                      'ς' => 's',
                      'τ' => 't',
                      'υ' => 'y',
                      'ύ' => 'y',
                      'ϋ' => 'y',
                      'ΰ' => 'y',
                      'ὐ' => 'y',
                      'ὑ' => 'y',
                      'ὒ' => 'y',
                      'ὓ' => 'y',
                      'ὔ' => 'y',
                      'ὕ' => 'y',
                      'ὖ' => 'y',
                      'ὗ' => 'y',
                      'ὺ' => 'y',
                      'ύ' => 'y',
                      'ῠ' => 'y',
                      'ῡ' => 'y',
                      'ῢ' => 'y',
                      'ΰ' => 'y',
                      'ῦ' => 'y',
                      'ῧ' => 'y',
                      'φ' => 'f',
                      'χ' => 'x',
                      'ψ' => 'ps',
                      'ω' => 'o',
                      'ώ' => 'o',
                      'ὠ' => 'o',
                      'ὡ' => 'o',
                      'ὢ' => 'o',
                      'ὣ' => 'o',
                      'ὤ' => 'o',
                      'ὥ' => 'o',
                      'ὦ' => 'o',
                      'ὧ' => 'o',
                      'ᾠ' => 'o',
                      'ᾡ' => 'o',
                      'ᾢ' => 'o',
                      'ᾣ' => 'o',
                      'ᾤ' => 'o',
                      'ᾥ' => 'o',
                      'ᾦ' => 'o',
                      'ᾧ' => 'o',
                      'ὼ' => 'o',
                      'ώ' => 'o',
                      'ῲ' => 'o',
                      'ῳ' => 'o',
                      'ῴ' => 'o',
                      'ῶ' => 'o',
                      'ῷ' => 'o',
                      '¨' => '',
                      '΅' => '',
                      '᾿' => '',
                      '῾' => '',
                      '῍' => '',
                      '῝' => '',
                      '῎' => '',
                      '῞' => '',
                      '῏' => '',
                      '῟' => '',
                      '῀' => '',
                      '῁' => '',
                      '΄' => '',
                      '΅' => '',
                      '`' => '',
                      '῭' => '',
                      'ͺ' => '',
                      '᾽' => '',
                      'А' => 'A',
                      'Б' => 'B',
                      'В' => 'V',
                      'Г' => 'G',
                      'Д' => 'D',
                      'Е' => 'E',
                      'Ё' => 'E',
                      'Ж' => 'ZH',
                      'З' => 'Z',
                      'И' => 'I',
                      'Й' => 'I',
                      'К' => 'K',
                      'Л' => 'L',
                      'М' => 'M',
                      'Н' => 'N',
                      'О' => 'O',
                      'П' => 'P',
                      'Р' => 'R',
                      'С' => 'S',
                      'Т' => 'T',
                      'У' => 'U',
                      'Ф' => 'F',
                      'Х' => 'KH',
                      'Ц' => 'TS',
                      'Ч' => 'CH',
                      'Ш' => 'SH',
                      'Щ' => 'SHCH',
                      'Ы' => 'Y',
                      'Э' => 'E',
                      'Ю' => 'YU',
                      'Я' => 'YA',
                      'а' => 'A',
                      'б' => 'B',
                      'в' => 'V',
                      'г' => 'G',
                      'д' => 'D',
                      'е' => 'E',
                      'ё' => 'E',
                      'ж' => 'ZH',
                      'з' => 'Z',
                      'и' => 'I',
                      'й' => 'I',
                      'к' => 'K',
                      'л' => 'L',
                      'м' => 'M',
                      'н' => 'N',
                      'о' => 'O',
                      'п' => 'P',
                      'р' => 'R',
                      'с' => 'S',
                      'т' => 'T',
                      'у' => 'U',
                      'ф' => 'F',
                      'х' => 'KH',
                      'ц' => 'TS',
                      'ч' => 'CH',
                      'ш' => 'SH',
                      'щ' => 'SHCH',
                      'ы' => 'Y',
                      'э' => 'E',
                      'ю' => 'YU',
                      'я' => 'YA',
                      'Ъ' => '',
                      'ъ' => '',
                      'Ь' => '',
                      'ь' => '',
                      'ð' => 'd',
                      'Ð' => 'D',
                      'þ' => 'th',
                      'Þ' => 'TH',
                      'ა' => 'a',
                      'ბ' => 'b',
                      'გ' => 'g',
                      'დ' => 'd',
                      'ე' => 'e',
                      'ვ' => 'v',
                      'ზ' => 'z',
                      'თ' => 't',
                      'ი' => 'i',
                      'კ' => 'k',
                      'ლ' => 'l',
                      'მ' => 'm',
                      'ნ' => 'n',
                      'ო' => 'o',
                      'პ' => 'p',
                      'ჟ' => 'zh',
                      'რ' => 'r',
                      'ს' => 's',
                      'ტ' => 't',
                      'უ' => 'u',
                      'ფ' => 'p',
                      'ქ' => 'k',
                      'ღ' => 'gh',
                      'ყ' => 'q',
                      'შ' => 'sh',
                      'ჩ' => 'ch',
                      'ც' => 'ts',
                      'ძ' => 'dz',
                      'წ' => 'ts',
                      'ჭ' => 'ch',
                      'ხ' => 'kh',
                      'ჯ' => 'j',
                      'ჰ' => 'h');
        
        return strtr($text, $chars);
    }

    /**
     * urlize
     *
     * @param string $text 
     * @return void
     */
    public static function urlize($text)
    {
        // Remove all non url friendly characters with the unaccent function
        $text = self::unaccent($text);
        
        // Remove all none word characters
        $text = preg_replace('/\W/', ' ', $text);
        
        // More stripping. Replace spaces with dashes
        $text = strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/', '-',
                           preg_replace('/([a-z\d])([A-Z])/', '\1_\2',
                           preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2',
                           preg_replace('/::/', '/', $text)))));
        
        return trim($text);
    }

    /**
     * underscore
     *
     * @param string $word 
     * @return void
     */
    public static function underscore($word)
    {
        return strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/', '_',
               preg_replace('/([a-z\d])([A-Z])/', '\1_\2',
               preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2',
               preg_replace('/::/', '/', $word)))));
    }
}