<?php
/*
 *  $Id: WordLike.php 1482 2007-05-26 16:49:58Z zYne $
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
Doctrine::autoload('Doctrine_Hook_Parser');
/**
 * Doctrine_Hook_WordLike
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1482 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hook_WordLike extends Doctrine_Hook_Parser_Complex
{
    /**
     * parse
     * Parses given field and field value to DQL condition
     * and parameters. This method should always return
     * prepared statement conditions (conditions that use
     * placeholders instead of literal values).
     *
     * @param string $alias     component alias
     * @param string $field     the field name
     * @param mixed $value      the value of the field
     * @return void
     */
    public function parseSingle($alias, $field, $value)
    {
        if (strpos($value, "'") !== false) {
            $value = Doctrine_Tokenizer::bracketTrim($value, "'", "'");
        
            $a[]   = $alias . '.' . $field . ' LIKE ?';
            $this->params[] = $value . '%';

        } else {
            $e2 = explode(' ',$value);
    
            foreach ($e2 as $v) {
                $v = trim($v);
                $a[] = $alias . '.' . $field . ' LIKE ?';
                $this->params[] = $v . '%';
            }
        }
        return implode(' OR ', $a);
    }
}
