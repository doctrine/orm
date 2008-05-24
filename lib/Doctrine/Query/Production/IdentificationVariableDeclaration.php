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
 * IdentificationVariableDeclaration = RangeVariableDeclaration [IndexBy] {Join [IndexBy]}
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
class Doctrine_Query_Production_IdentificationVariableDeclaration extends Doctrine_Query_Production
{
    protected $_rangeVariableDeclaration;

    protected $_indexBy;

    protected $_relations = array();


    public function syntax($paramHolder)
    {
        $this->_rangeVariableDeclaration = $this->AST('RangeVariableDeclaration', $paramHolder);

        if ($this->_isNextToken(Doctrine_Query_Token::T_INDEX)) {
            $paramHolder->set('componentAlias', $this->_rangeVariableDeclaration);
            $this->_indexBy = $this->AST('IndexBy', $paramHolder);
            $paramHolder->remove('componentAlias');
        }

        while (
            $this->_isNextToken(Doctrine_Query_Token::T_LEFT) ||
            $this->_isNextToken(Doctrine_Query_Token::T_INNER) ||
            $this->_isNextToken(Doctrine_Query_Token::T_JOIN)
        ) {
            $i = count($this->_relations);

            $this->_relations[$i]['join'] = $this->AST('Join', $paramHolder);

            if ($this->_isNextToken(Doctrine_Query_Token::T_INDEX)) {
                $paramHolder->set('componentAlias', $this->_relations[$i]['join']->getRangeVariableDeclaration());
                $this->_relations[$i]['indexBy'] = $this->AST('IndexBy', $paramHolder);
                $paramHolder->remove('componentAlias');
            }
        }
    }


    public function buildSql()
    {
        // We need to bring the queryComponent and get things from there.
        $parserResult = $this->_parser->getParserResult();
        $queryComponent = $parserResult->getQueryComponent($this->_rangeVariableDeclaration);

        // Retrieving connection
        $conn = $this->_parser->getSqlBuilder()->getConnection();
        $manager = Doctrine_Manager::getInstance();

        if ($manager->hasConnectionForComponent($queryComponent['metadata']->getClassName())) {
            $conn = $manager->getConnectionForComponent($queryComponent['metadata']->getClassName());
        }

        $str = $conn->quoteIdentifier($queryComponent['metadata']->getTableName()) . ' '
             . $conn->quoteIdentifier($parserResult->getTableAliasFromComponentAlias($this->_rangeVariableDeclaration));

        for ($i = 0, $l = count($this->_relations); $i < $l; $i++) {
            $str .= $this->_relations[$i]['join']->buildSql() . ' '
                  . ((isset($this->_relations[$i]['indexby'])) ? $this->_relations[$i]['indexby']->buildSql() . ' ' : '');
        }

        return $str;
    }
}
