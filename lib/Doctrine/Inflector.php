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
 * <http://www.phpdoctrine.com>.
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
        return strtr($text, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
                            'AAAAAAACEEEEIIIIDNOOOOOOUUUUYTsaaaaaaaceeeeiiiienoooooouuuuyty');
    }

    /**
     * urlize
     *
     * @param string $text 
     * @return void
     */
    public static function urlize($text)
    {
        return trim(self::underscore(self::unaccent($text)), '-');
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