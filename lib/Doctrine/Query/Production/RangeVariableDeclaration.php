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
 * RangeVariableDeclaration = identifier {"." identifier} [["AS"] IdentificationVariable]
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
class Doctrine_Query_Production_RangeVariableDeclaration extends Doctrine_Query_Production
{
    protected $_identifiers = array();

    protected $_identificationVariable;


    public function syntax($paramHolder)
    {
        // RangeVariableDeclaration = identifier {"." identifier} [["AS"] IdentificationVariable]
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        $this->_identifiers[] = $this->_parser->token['value'];

        while ($this->_isNextToken('.')) {
            $this->_parser->match('.');
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

            $this->_identifiers[] = $this->_parser->token['value'];
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $paramHolder->set('componentName', implode('.', $this->_identifiers));

            // Will return an identifier, with the semantical check already applied
            $this->_identificationVariable = $this->AST('IdentificationVariable', $paramHolder);

            $paramHolder->remove('componentName');
        }
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();
        $componentName = implode('.', $this->_identifiers);

        if ($parserResult->hasQueryComponent($componentName)) {
            //echo "Entered in if of hasQueryComponent(".$componentName."): true\n";

            // As long as name != alias, try to bring the queryComponent from name (already processed)
            $queryComponent = $parserResult->getQueryComponent($componentName);

            // Check if we defined _identificationVariable. We throw semantical error if not
            if ($this->_identificationVariable === null) {
                $componentName = $queryComponent['metadata']->getClassName();

                $this->_parser->semanticalError(
                    "Cannot re-declare component '{$componentName}'. Please assign an alias to it."
                );

                return;
            }
        } else {
            //echo "Entered in if hasQueryComponent(".$componentName."), alias ".var_export($this->_identificationVariable, true).": false\n";

            // No queryComponent was found. We will have to build it for the first time
            if (count($this->_identifiers) > 1) {
                // We are in a multiple identifier declaration; we are dealing with relations here
                $this->_semanticalWithMultipleIdentifier();
            } else {
                // We are in a single identifier declaration; our identifier is the class name
                $this->_semanticalWithSingleIdentifier();
            }
        }

        return $this->_identificationVariable;
    }


    public function buildSql()
    {
        return '';
    }


    private function _semanticalWithSingleIdentifier()
    {
        $parserResult = $this->_parser->getParserResult();

        // Get the connection for the component
        $conn = $this->_parser->getSqlBuilder()->getConnection();
        $manager = Doctrine_EntityManager::getManager();
        $componentName = $this->_identifiers[0];

        // Retrieving ClassMetadata and Mapper
        try {
            $classMetadata = $manager->getClassMetadata($componentName);

            // Building queryComponent
            $queryComponent = array(
                'metadata' => $classMetadata,
                'parent'   => null,
                'relation' => null,
                'map'      => null,
                'scalar'   => null,
            );
        } catch (Doctrine_Exception $e) {
            //echo "Tried to load class metadata from '".$componentName."': " . $e->getMessage() . "\n";
            $this->_parser->semanticalError($e->getMessage());

            return;
        }

        if ($this->_identificationVariable === null) {
            $this->_identificationVariable = $componentName;
        }

        //echo "Identification Variable: " .$this->_identificationVariable . "\n";

        $tableAlias = $parserResult->generateTableAlias($classMetadata->getClassName());
        $parserResult->setQueryComponent($this->_identificationVariable, $queryComponent);
        $parserResult->setTableAlias($tableAlias, $this->_identificationVariable);
    }


    private function _semanticalWithMultipleIdentifier()
    {
        $parserResult = $this->_parser->getParserResult();

        // Get the connection for the component
        $conn = $this->_parser->getSqlBuilder()->getConnection();
        $manager = Doctrine_EntityManager::getManager();

        // Retrieve the base component
        try {
            $queryComponent = $parserResult->getQueryComponent($this->_identifiers[0]);
            $classMetadata = $queryComponent['metadata'];
            $className = $classMetadata->getClassName();
            $parent = $path = $this->_identifiers[0];
        } catch (Doctrine_Exception $e) {
            $this->_parser->semanticalError($e->getMessage());

            return;
        }

        // We loop into others identifier to build query components
        for ($i = 1, $l = count($this->_identifiers); $i < $l; $i++) {
            $relationName = $this->_identifiers[$i];
            $path .= '.' . $relationName;

            if ($parserResult->hasQueryComponent($path)) {
                // We already have the query component on hands, get it
                $queryComponent = $parserResult->getQueryComponent($path);
                $classMetadata = $queryComponent['metadata'];

                // If we are in our last check and identification variable is null, we throw semantical error
                if ($i == $l - 1 && $this->_identificationVariable === null) {
                    $componentName = $classMetadata->getClassName();

                    $this->_parser->semanticalError(
                        "Cannot re-declare component '{$componentName}' in path '{$path}'. " .
                        "Please assign an alias to it."
                    );

                    return;
                }
            } else {
                // We don't have the query component yet
                if ( ! $classMetadata->hasRelation($relationName)) {
                    $className = $classMetadata->getClassName();

                    $this->_parser->semanticalError("Relation '{$relationName}' does not exist in component '{$className}'");

                    return;
                }

                // Retrieving ClassMetadata and Mapper
                try {
                    $relation = $classMetadata->getRelation($relationName);
                    $classMetadata = $relation->getClassMetadata();

                    $queryComponent = array(
                        'metadata' => $classMetadata,
                        'parent'   => $parent,
                        'relation' => $relation,
                        'map'      => null,
                        'scalar'   => null,
                    );

                    $parent = $path;
                } catch (Doctrine_Exception $e) {
                    echo "Tried to load class metadata from '".$relationName."'\n";
                    $this->_parser->semanticalError($e->getMessage());

                   return;
                }
            }
        }

        if ($this->_identificationVariable === null) {
            $this->_identificationVariable = $path;
        }

        $tableAlias = $parserResult->generateTableAlias($classMetadata->getClassName());

	//echo "Table alias: " . $tableAlias . "\n";

        $parserResult->setQueryComponent($this->_identificationVariable, $queryComponent);
        $parserResult->setTableAlias($tableAlias, $this->_identificationVariable);
    }
}
