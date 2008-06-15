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

    protected $_fieldIdentificationVariable;

    private $__columnAliasInSql;


    public function syntax($paramHolder)
    {
        // SelectExpression = (PathExpressionEndingWithAsterisk | Expression | "(" Subselect ")")
        //                    [["AS"] IdentificationVariable]
        $this->_isSubselect = false;

        if ($this->_isPathExpressionEndingWithAsterisk()) {
            $this->_leftExpression = $this->AST('PathExpressionEndingWithAsterisk', $paramHolder);

            $fieldName = implode('.', $this->_leftExpression->getIdentifiers()) . '.*';
        } else if (($this->_isSubselect = $this->_isSubselect()) === true) {
            $this->_parser->match('(');
            $this->_leftExpression = $this->AST('Subselect', $paramHolder);
            $this->_parser->match(')');

            // [TODO] Any way to make it more fancy for user error?
            $fieldName = '<Subselect>';
        } else {
            $this->_leftExpression = $this->AST('Expression', $paramHolder);

            // [TODO] Any way to make it more fancy for user error?
            $fieldName = '<Expression>';
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $paramHolder->set('fieldName', $fieldName);

            // Will return an identifier, with the semantical check already applied
            $this->_fieldIdentificationVariable = $this->AST('FieldIdentificationVariable', $paramHolder);

            $paramHolder->remove('fieldName');
        }
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();

        // We cannot have aliases for foo.*
        if ($this->_leftExpression instanceof Doctrine_Query_Production_PathExpressionEndingWithAsterisk
                && $this->_fieldIdentificationVariable !== null) {
            $this->_parser->semanticalError(
                "Cannot assign an identification variable to a path expression ending with asterisk (ie. foo.bar.* AS foobaz)."
            );
        }

        // Also, we cannot have aliases for path expressions: foo.bar
        if ($this->_leftExpression instanceof Doctrine_Query_Production_PathExpressionEndingWithAsterisk
                && $this->_fieldIdentificationVariable !== null) {
            $this->_parser->semanticalError(
                "Cannot assign an identification variable to a path expression (ie. foo.bar AS foobaz)."
            );
        }

        // Make semantical checks
        $this->_leftExpression->semantical($paramHolder);

        if($this->_fieldIdentificationVariable !== null) {
            $this->_fieldIdentificationVariable->semantical($paramHolder);
        }
    }


    public function buildSql()
    {
        return $this->_leftExpression->buildSql() . $this->_buildColumnAliasInSql();
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


    protected function _buildColumnAliasInSql()
    {
        // Retrieving parser result
        $parserResult = $this->_parser->getParserResult();

        // Retrieving connection
        $manager = Doctrine_EntityManagerFactory::getManager(); 
        $conn = $manager->getConnection();

        switch (get_class($this->_leftExpression)) {
            case 'Doctrine_Query_Production_PathExpressionEndingWithAsterisk':
                return '';
            break;

            case 'Doctrine_Query_Production_PathExpression':
                // We bring the queryComponent from the class instance
                $componentAlias = $this->_leftExpression->getComponentAlias();
                $queryComponent = $parserResult->getQueryComponent($componentAlias);
                $fieldName = $this->_leftExpression->getFieldName();

                // Build the column alias now
                $columnAlias = $parserResult->getTableAliasFromComponentAlias($componentAlias)
                             . Doctrine_Query_Production::SQLALIAS_SEPARATOR
                             . $queryComponent['metadata']->getColumnName($fieldName);
            break;

            default:
                // We bring the default queryComponent
                $componentAlias = Doctrine_Query_Production::DEFAULT_QUERYCOMPONENT;
                $queryComponent = $parserResult->getQueryComponent($componentAlias);

                // If we have FieldIdentificationVariable, we have to use the scalar map of it
                if ($this->_fieldIdentificationVariable !== null) {
                    $columnAlias = $this->_fieldIdentificationVariable->getColumnAlias();
                } else {
                    // We have to include the map now, since we don't have the scalar mapped
                    $queryFields = $parserResult->getQueryFields();
                    $itemIndex = 'item' . count(array_filter($queryFields, array($this, "_nonIdentifiedVariable")));
                    $idx = count($queryFields);

                    $queryComponent['scalar'][$idx] = $itemIndex;
                    $parserResult->setQueryComponent($componentAlias, $queryComponent);

                    // And also in field aliases
                    $parserResult->setQueryField($itemIndex, $idx);

                    // Build the column alias
                    $columnAlias = $parserResult->getTableAliasFromComponentAlias($componentAlias)
                                 . Doctrine_Query_Production::SQLALIAS_SEPARATOR . $idx;
                }
            break;
        }

        return ' AS ' . $conn->quoteIdentifier($columnAlias);
    }


    protected function _nonIdentifiedVariable($value)
    {
        return ! is_string($value);
    }
}
