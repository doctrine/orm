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
 * PathExpressionEndingWithAsterisk = {identifier "."} "*"
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_PathExpressionEndingWithAsterisk extends Doctrine_Query_Production
{
    protected $_identifiers = array();

    protected $_queryComponent;


    public function syntax($paramHolder)
    {
        // PathExpressionEndingWithAsterisk = {identifier "."} "*"
        while ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
            $this->_identifiers[] = $this->_parser->token['value'];

            $this->_parser->match('.');
        }

        $this->_parser->match('*');
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();

        if (($l = count($this->_identifiers)) > 0) {
            // We are dealing with component{.component}.*
            $path = $this->_identifiers[0];
            $this->_queryComponent = $parserResult->getQueryComponent($path);

            // We should have a semantical error if the queryComponent does not exists yet
            if ($this->_queryComponent === null) {
                $this->_parser->semanticalError("Undefined component alias for '{$path}'", $this->_parser->token);
            }

            // Initializing ClassMetadata
            $classMetadata = $this->_queryComponent['metadata'];

            // Looping through relations
            for ($i = 1; $i < $l; $i++) {
                $relationName = $this->_identifiers[$i];
                $path .= '.' . $relationName;

                if ( ! $classMetadata->hasRelation($relationName)) {
                    $className = $classMetadata->getClassName();

                    $this->_parser->semanticalError(
                        "Relation '{$relationName}' does not exist in component '{$className}' when trying to get the path '{$path}'",
                        $this->_parser->token
                    );
                }

                // We inspect for queryComponent of relations, since we are using them
                if ( ! $parserResult->hasQueryComponent($path)) {
                    $this->_parser->semanticalError("Cannot use the path '{$path}' without defining it in FROM.", $this->_parser->token);
                }

                // Assigning new queryComponent and classMetadata
                $this->_queryComponent = $parserResult->getQueryComponent($path);

                $classMetadata = $this->_queryComponent['metadata'];
            }
        } else {
            // We are dealing with a simple * as our PathExpression.
            // We need to check if there's only one query component.
            $queryComponents = $parserResult->getQueryComponents();

            if (count($queryComponents) != 2) {
                $this->_parser->semanticalError(
                    "Cannot use * as selector expression for multiple components."
                );
            }

            // We simplify our life adding the component alias to our AST,
            // since we have it on hands now.
            $k = array_keys($queryComponents);
            $componentAlias = $k[1];

            $this->_queryComponent = $queryComponents[$componentAlias];
        }
    }


    public function buildSql()
    {
        // Basic handy variables
        $parserResult = $this->_parser->getParserResult();

        // Retrieving connection
        $conn = $this->_em->getConnection();

        // Looking for componentAlias to fetch
        $componentAlias = implode('.', $this->_identifiers);

        if (count($this->_identifiers) == 0) {
            $queryComponents = $parserResult->getQueryComponents();

            // Retrieve ClassMetadata
            $k = array_keys($queryComponents);
            $componentAlias = $k[1];
        }

        // Generating the SQL piece
        $fields = $this->_queryComponent['metadata']->getFieldMappings();
        $tableAlias = $parserResult->getTableAliasFromComponentAlias($componentAlias);
        $str = '';

        foreach ($fields as $fieldName => $fieldMap) {
            $str .= ($str != '') ? ', ' : '';

            // DB Field name
            $column = $tableAlias . '.' . $this->_queryComponent['metadata']->getColumnName($fieldName);
            $column = $conn->quoteIdentifier($column);

            // DB Field alias
            $columnAlias = $tableAlias . '__' . $this->_queryComponent['metadata']->getColumnName($fieldName);
            $columnAlias = $conn->quoteIdentifier($columnAlias);

            $str .= $column . ' AS ' . $columnAlias;
        }

        return $str;
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $visitor->visitPathExpressionEndingWithAsterisk($this);
    }
    
    /* Getters */
    
    public function getIdentifiers()
    {
        return $this->_identifiers;
    }
    
    public function getQueryComponent()
    {
        return  $this->_queryComponent;
    }
}
