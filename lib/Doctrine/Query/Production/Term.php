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
 * Term = Factor {("*" | "/") Factor}
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_Term extends Doctrine_Query_Production
{
    protected $_factors = array();


    public function syntax($paramHolder)
    {
        // Term = Factor {("*" | "/") Factor}
        $this->_factors[] = $this->AST('Factor', $paramHolder);

        while ($this->_isNextToken('*') || $this->_isNextToken('/')) {
            if ($this->_isNextToken('*')) {
                $this->_parser->match('*');
                $this->_factors[] = '*';
            } else {
                $this->_parser->match('/');
                $this->_factors[] = '/';
            }

            $this->_factors[] = $this->AST('Factor', $paramHolder);
        }

        // Optimize depth instances in AST
        if (count($this->_factors) == 1) {
            return $this->_factors[0];
        }
    }


    public function semantical($paramHolder)
    {
        for ($i = 0, $l = count($this->_factors); $i < $l; $i++) {
            if ($this->_factors[$i] != '*' && $this->_factors[$i] != '/') {
                $this->_factors[$i]->semantical($paramHolder);
            }
        }
    }


    public function buildSql()
    {
        return implode(' ', $this->_mapFactors());
    }


    protected function _mapFactors()
    {
        return array_map(array(&$this, '_mapFactor'), $this->_factors);
    }


    protected function _mapFactor($value)
    {
        return (is_string($value) ? $value : $value->buildSql());
    }
}
