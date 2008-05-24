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
 * PathExpression = identifier { "." identifier }
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
class Doctrine_Query_Production_PathExpression extends Doctrine_Query_Production
{
    protected $_identifiers = array();

    protected $_fieldName;

    private $_queryComponent;


    public function syntax($paramHolder)
    {
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        $this->_identifiers[] = $this->_parser->token['value'];

        while ($this->_isNextToken('.')) {
            $this->_parser->match('.');
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

            $this->_identifiers[] = $this->_parser->token['value'];
        }

        $this->_fieldName = array_pop($this->_identifiers);
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();
        $classMetadata = null;

        if (($l = count($this->_identifiers)) == 0) {
            // No metadata selection until now. We might need to deal with:
            // DELETE FROM Obj alias WHERE field = X
            $queryComponents = $parserResult->getQueryComponents();

            // Check if we have more than one queryComponent defined
            if (count($queryComponents) != 1) {
                $this->_parser->semanticalError("Undefined component alias for field '{$this->_fieldName}'", $this->_parser->token);
            }

            // Retrieve ClassMetadata
            $k = array_keys($queryComponents);
            $componentAlias = $k[0];

            $this->_queryComponent = $queryComponents[$componentAlias];
            $classMetadata = $this->_queryComponent['metadata'];
        } else {
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
        }

        // Now we inspect for field existance
        if ( ! $classMetadata->hasField($this->_fieldName)) {
            $className = $classMetadata->getClassName();

            $this->_parser->semanticalError("Field '{$this->_fieldName}' does not exist in component '{$className}'", $this->_parser->token);
        }
    }


    public function buildSql()
    {
        // Basic handy variables
        $parserResult = $this->_parser->getParserResult();

        // Retrieving connection
        $manager = Doctrine_EntityManager::getManager(); 
        $conn = $manager->getConnection();

        // Looking for componentAlias to fetch
        $componentAlias = implode('.', $this->_identifiers);

        if (count($this->_identifiers) == 0) {
            $queryComponents = $parserResult->getQueryComponents();

            // Retrieve ClassMetadata
            $k = array_keys($queryComponents);
            $componentAlias = $k[0];
        }

        // Generating the SQL piece
        $str = $parserResult->getTableAliasFromComponentAlias($componentAlias) . '.'
             . $this->_queryComponent['metadata']->getColumnName($this->_fieldName);

        return $conn->quoteIdentifier($str);
    }
}
