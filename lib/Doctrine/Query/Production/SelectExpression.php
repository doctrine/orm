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
 * SelectExpression = (PathExpressionEndingWithAsterisk | Expression | "(" Subselect ")")
 *                    [["AS"] IdentificationVariable]
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
class Doctrine_Query_Production_SelectExpression extends Doctrine_Query_Production
{
    protected $_leftExpression;

    protected $_isSubselect;

    protected $_identificationVariable;

    private $__columnAliasInSql;


    public function syntax($paramHolder)
    {
        // SelectExpression = (PathExpressionEndingWithAsterisk | Expression | "(" Subselect ")")
        //                    [["AS"] IdentificationVariable]
        $this->_isSubselect = false;

        if ($this->_isPathExpressionEndingWithAsterisk()) {
            $this->_leftExpression = $this->AST('PathExpressionEndingWithAsterisk', $paramHolder);
        } elseif(($this->_isSubselect = $this->_isSubselect()) === true) {
            $this->_parser->match('(');
            $this->_leftExpression = $this->AST('Subselect', $paramHolder);
            $this->_parser->match(')');
        } else {
            $this->_leftExpression = $this->AST('Expression', $paramHolder);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->_identificationVariable = $this->AST('IdentificationVariable', $paramHolder);
        }
    }


    public function semantical($paramHolder)
    {
        // Here we inspect for duplicate IdentificationVariable, and if the
        // left expression needs the identification variable. If yes, check
        // its existance.
        if ($this->_leftExpression instanceof Doctrine_Query_Production_PathExpressionEndingWithAsterisk && $this->_identificationVariable !== null) {
            $this->_parser->semanticalError(
                "Cannot assign an identification variable to a path expression with asterisk (ie. foo.bar.* AS foobaz)."
            );
        }

        if ($this->_identificationVariable !== null) {
            if ($this->_leftExpression instanceof Doctrine_Query_Production_PathExpression) {
                // We bring the queryComponent from the class instance
                // $queryComponent = $this->_leftExpression->getQueryComponent();
            } else {
                // We bring the default queryComponent
                // $queryComponent = $parserResult->getQueryComponent(null);
            }

            $idx = count($queryComponent['scalar']);
            $this->__columnAliasInSql .= '__' . $idx;

            $queryComponent['scalar'][$idx] = $this->_identificationVariable;

            //$parserResult->setQueryComponent($componentAlias, $queryComponent);
        }

        // We need to add scalar in queryComponent the item alias if identificationvariable is set.
        echo "SelectExpression:\n";
        echo get_class($this->_leftExpression) . "\n";

        // The check for duplicate IdentificationVariable was already done
    }


    public function buildSql()
    {
        return $this->_leftExpression->buildSql() . ' AS '
             . (($this->_identificationVariable !== null) ? $this->_identificationVariable : '');
    }


    protected function _isPathExpressionEndingWithAsterisk()
    {
        $token = $this->_parser->lookahead;
        $this->_parser->getScanner()->resetPeek();

        while (($token['type'] === Doctrine_Query_Token::T_IDENTIFIER) || ($token['value'] === '.')) {
            $token = $this->_parser->getScanner()->peek();
        }

        return $token['value'] === '*';
    }
}
