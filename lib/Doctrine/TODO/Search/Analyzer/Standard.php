<?php
/*
 *  $Id$
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
 * Doctrine_Search_Analyzer_Standard
 *
 * @package     Doctrine
 * @subpackage  Search
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Search_Analyzer_Standard implements Doctrine_Search_Analyzer_Interface
{
    protected static $_stopwords = array(
                            '0',
                            '1',
                            '2',
                            '3',
                            '4',
                            '5',
                            '6',
                            '7',
                            '8',
                            '9',
                            '10',
                            'a',
                            'about',
                            'after',
                            'all',
                            'almost',
                            'along',
                            'also',
                            'although',
                            'amp',
                            'an',
                            'and',
                            'another',
                            'any',
                            'are',
                            'area',
                            'arent',
                            'around',
                            'as',
                            'at',
                            'available',
                            'back',
                            'be',
                            'because',
                            'been',
                            'before',
                            'being',
                            'best',
                            'better',
                            'big',
                            'bit',
                            'both',
                            'but',
                            'by',
                            'c',
                            'came',
                            'can',
                            'capable',
                            'control',
                            'could',
                            'course',
                            'd',
                            'dan',
                            'day',
                            'decided',
                            'did',
                            'didn',
                            'different',
                            'div',
                            'do',
                            'doesn',
                            'don',
                            'down',
                            'drive',
                            'e',
                            'each',
                            'easily',
                            'easy',
                            'edition',
                            'either',
                            'end',
                            'enough',
                            'even',
                            'every',
                            'example',
                            'few',
                            'find',
                            'first',
                            'for',
                            'found',
                            'from',
                            'get',
                            'go',
                            'going',
                            'good',
                            'got',
                            'gt',
                            'had',
                            'hard',
                            'has',
                            'have',
                            'he',
                            'her',
                            'here',
                            'how',
                            'i',
                            'if',
                            'in',
                            'into',
                            'is',
                            'isn',
                            'it',
                            'just',
                            'know',
                            'last',
                            'left',
                            'li',
                            'like',
                            'little',
                            'll',
                            'long',
                            'look',
                            'lot',
                            'lt',
                            'm',
                            'made',
                            'make',
                            'many',
                            'mb',
                            'me',
                            'menu',
                            'might',
                            'mm',
                            'more',
                            'most',
                            'much',
                            'my',
                            'name',
                            'nbsp',
                            'need',
                            'new',
                            'no',
                            'not',
                            'now',
                            'number',
                            'of',
                            'off',
                            'old',
                            'on',
                            'one',
                            'only',
                            'or',
                            'original',
                            'other',
                            'our',
                            'out',
                            'over',
                            'part',
                            'place',
                            'point',
                            'pretty',
                            'probably',
                            'problem',
                            'put',
                            'quite',
                            'quot',
                            'r',
                            're',
                            'really',
                            'results',
                            'right',
                            's',
                            'same',
                            'saw',
                            'see',
                            'set',
                            'several',
                            'she',
                            'sherree',
                            'should',
                            'since',
                            'size',
                            'small',
                            'so',
                            'some',
                            'something',
                            'special',
                            'still',
                            'stuff',
                            'such',
                            'sure',
                            'system',
                            't',
                            'take',
                            'than',
                            'that',
                            'the',
                            'their',
                            'them',
                            'then',
                            'there',
                            'these',
                            'they',
                            'thing',
                            'things',
                            'think',
                            'this',
                            'those',
                            'though',
                            'through',
                            'time',
                            'to',
                            'today',
                            'together',
                            'too',
                            'took',
                            'two',
                            'up',
                            'us',
                            'use',
                            'used',
                            'using',
                            've',
                            'very',
                            'want',
                            'was',
                            'way',
                            'we',
                            'well',
                            'went',
                            'were',
                            'what',
                            'when',
                            'where',
                            'which',
                            'while',
                            'white',
                            'who',
                            'will',
                            'with',
                            'would',
                            'yet',
                            'you',
                            'your',
                            'yours'
                            );

    public function analyze($text)
    {
    	$text = preg_replace('/[\'`´"]/', '', $text);
        $text = preg_replace('/[^A-Za-z0-9]/', ' ', $text);
        $text = str_replace('  ', ' ', $text);

        $terms = explode(' ', $text);
        
        $ret = array();
        if ( ! empty($terms)) {
            foreach ($terms as $i => $term) {
                if (empty($term)) {
                    continue;
                }
                $lower = strtolower(trim($term));

                if (in_array($lower, self::$_stopwords)) {
                    continue;
                }

                $ret[$i] = $lower;
            }
        }
        return $ret;
    }
}
