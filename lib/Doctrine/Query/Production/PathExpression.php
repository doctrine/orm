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


    public function syntax($paramHolder)
    {
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        $this->_identifiers[] = $this->_parser->token['value'];

        while ($this->_isNextToken('.')) {
            $this->_parser->match('.');
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

            $this->_identifiers[] = $this->_parser->token['value'];
        }
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();
        $classMetadata = null;

        for ($i = 0, $l = count($this->_identifiers); $i < $l; $i++) {
            if ($i < $l - 1) {
                $relationName = $this->_identifiers[$i];

                // We are still checking for relations
                if ( $classMetadata !== null && ! $classMetadata->hasRelation($relationName)) {
                    $className = $classMetadata->getClassName();

                    $this->_parser->semanticalError("Relation '{$relationName}' does not exist in component '{$className}'");

                    // Assigning new ClassMetadata
                    $classMetadata = $classMetadata->getRelation($relationName)->getClassMetadata();
                } elseif ( $classMetadata === null ) {
                    $queryComponent = $parserResult->getQueryComponent($relationName);

                    // We should have a semantical error if the queryComponent does not exists yet
                    if ($queryComponent === null) {
                        $this->_parser->semanticalError("Undefined component alias for relation '{$relationName}'");
                    }

                    // Initializing ClassMetadata
                    $classMetadata = $queryComponent['metadata'];
                }
            } else {
                $fieldName = $this->_identifiers[$i];

                // We are checking for fields
                if ($classMetadata === null) {
                    // No metadata selection until now. We might need to deal with:
                    // DELETE FROM Obj alias WHERE field = X
                    $queryComponents = $parserResult->getQueryComponents();

                    // Check if we have more than one queryComponent defined
                    if (count($queryComponents) != 1) {
                        $this->_parser->semanticalError("Undefined component alias for field '{$fieldName}'");
                    }

                    // Retrieve ClassMetadata
                    $k = array_keys($queryComponents);
                    $componentAlias = $k[0];

                    $classMetadata = $queryComponents[$componentAlias]['metadata'];
                    array_unshift($this->_identifiers, $componentAlias);
                }

                // Check if field exists in ClassMetadata
                if ( ! $classMetadata->hasField($fieldName)) {
                    $className = $classMetadata->getClassName();

                    $this->_parser->semanticalError("Field '{$fieldName}' does not exist in component '{$className}'");
                }
            }
        }
    }


    public function buildSql()
    {
        // Basic handy variables
        $parserResult = $this->_parser->getParserResult();

        // Retrieving connection
        $conn = $this->_parser->getSqlBuilder()->getConnection();
        $manager = Doctrine_Manager::getInstance();

        // _identifiers are always >= 2
        if ($manager->hasConnectionForComponent($this->_identifiers[0])) {
            $conn = $manager->getConnectionForComponent($this->_identifiers[0]);
        }

        $str = '';

        for ($i = 0, $l = count($this->_identifiers); $i < $l; $i++) {
            if ($i < $l - 1) {
                // [TODO] We are assuming we never define relations in SELECT
                // and WHERE clauses. So, do not bother about table alias that
                // may not be previously added. At a later stage, we should
                // deal with it too.
                $str .= $parserResult->getTableAliasFromComponentAlias($this->_identifiers[$i]) . '.';
            } else {
                // Retrieving last ClassMetadata
                $queryComponent = $parserResult->getQueryComponent($this->_identifiers[$i - 1]);
                $classMetadata = $queryComponent['metadata'];

                $str .= $classMetadata->getColumnName($this->_identifiers[$i]);
            }
        }

        return $conn->quoteIdentifier($str);
    }
}
