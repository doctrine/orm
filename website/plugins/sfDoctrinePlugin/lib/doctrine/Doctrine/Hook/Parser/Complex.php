<?php
/*
 *  $Id: Complex.php 1482 2007-05-26 16:49:58Z zYne $
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
 * Doctrine_Hook_Parser_Complex
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1482 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Hook_Parser_Complex extends Doctrine_Hook_Parser
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
    public function parse($alias, $field, $value)
    {
        $this->condition = $this->parseClause($alias, $field, $value);
    }
    /**
     * parseClause
     *
     * @param string $alias     component alias
     * @param string $field     the field name
     * @param mixed $value      the value of the field
     * @return void
     */
    public function parseClause($alias, $field, $value)
    {
        $parts = Doctrine_Tokenizer::quoteExplode($value, ' AND ');

        if (count($parts) > 1) {
            $ret = array();
            foreach ($parts as $part) {
                $ret[] = $this->parseSingle($alias, $field, $part);
            }

            $r = implode(' AND ', $ret);
        } else {
            $parts = Doctrine_Tokenizer::quoteExplode($value, ' OR ');
            if (count($parts) > 1) {
                $ret = array();
                foreach ($parts as $part) {
                    $ret[] = $this->parseClause($alias, $field, $part);
                }

                $r = implode(' OR ', $ret);
            } else {
                $ret = $this->parseSingle($alias, $field, $parts[0]);
                return $ret;
            }
        }
        return '(' . $r . ')';
    }
    /**
     * parseSingle
     *
     * @param string $alias     component alias
     * @param string $field     the field name
     * @param mixed $value      the value of the field
     * @return void
     */
    abstract public function parseSingle($alias, $field, $value);
}
