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
 * ConditionalTerm = ConditionalFactor {"AND" ConditionalFactor}
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
class Doctrine_Query_Production_ConditionalTerm extends Doctrine_Query_Production
{
    protected $_conditionalFactors = array();


    public function syntax($paramHolder)
    {
        // ConditionalTerm = ConditionalFactor {"AND" ConditionalFactor}
        $this->_conditionalFactors[] = $this->AST('ConditionalFactor', $paramHolder);

        while ($this->_isNextToken(Doctrine_Query_Token::T_AND)) {
            $this->_parser->match(Doctrine_Query_Token::T_AND);
            $this->_conditionalFactors[] = $this->AST('ConditionalFactor', $paramHolder);
        }

        // Optimize depth instances in AST
        if (count($this->_conditionalFactors) == 1) {
            return $this->_conditionalFactors[0];
        }
    }


    public function buildSql()
    {
        return implode(' AND ', $this->_mapConditionalFactors());
    }


    protected function _mapConditionalFactors()
    {
        return array_map(array(&$this, '_mapConditionalFactor'), $this->_conditionalFactors);
    }


    protected function _mapConditionalFactor($value)
    {
        return $value->buildSql();
    }
}
