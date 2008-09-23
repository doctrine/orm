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
 * JoinVariableDeclaration = Join [IndexBy]
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_JoinVariableDeclaration extends Doctrine_Query_Production
{
    protected $_join;

    protected $_indexBy;


    public function syntax($paramHolder)
    {
        $this->_join = $this->AST('Join', $paramHolder);

        if ($this->_isNextToken(Doctrine_Query_Token::T_INDEX)) {
            $paramHolder->set('componentAlias', $this->_join->getRangeVariableDeclaration()->getIdentificationVariable());
            $this->_indexBy = $this->AST('IndexBy', $paramHolder);
            $paramHolder->remove('componentAlias');
        }
    }


    public function buildSql()
    {
        return $this->_join->buildSql() . (isset($this->_indexby) ? $this->_indexby->buildSql() . ' ' : '');
    }


    /* Getters */
    public function getJoin()
    {
        return $this->_join;
    }


    public function getIndexBy()
    {
        return $this->_indexBy;
    }
}