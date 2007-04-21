<?php
/*
 *  $Id: From.php 1080 2007-02-10 18:17:08Z romanb $
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
 * Doctrine_Tokenizer
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Tokenizer 
{
    public function __construct() 
    {
    	
    }
    public function tokenize() 
    {
    	
    }
    /**
     * bracketExplode
     *
     * example:
     *
     * parameters:
     *      $str = (age < 20 AND age > 18) AND email LIKE 'John@example.com'
     *      $d = ' AND '
     *      $e1 = '('
     *      $e2 = ')'
     *
     * would return an array:
     *      array("(age < 20 AND age > 18)",
     *            "email LIKE 'John@example.com'")
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     */
    public static function bracketExplode($str, $d = ' ', $e1 = '(', $e2 = ')')
    {
        if (is_array($d)) {
            $a = preg_split('/(' . implode('|', $d) . ')/', $str);
            $d = stripslashes($d[0]);
        } else {
            $a = explode($d, $str);
        }
        $i = 0;
        $term = array();
        foreach($a as $key => $val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);
            } else {
                $term[$i] .= $d . trim($val);
            }
            $c1 = substr_count($term[$i], $e1);
            $c2 = substr_count($term[$i], $e2);

            if($c1 == $c2) {
                $i++;
            }
        }
        return $term;
    }
}
