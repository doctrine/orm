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
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_PathExpressionEndingWithAsterisk extends Doctrine_Query_Production
{
    protected $_identifiers = array();


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
            $classMetadata = null;

            for ($i = 0; $i < $l; $i++) {
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
            }
        } else {
            // We are dealing with a simple * as our PathExpression.
            // We need to check if there's only one query component.
            $queryComponents = $parserResult->getQueryComponents();

            if (count($queryComponents) != 1) {
                $this->_parser->semanticalError(
                    "Cannot use * as selector expression for multiple components."
                );
            }

            // We simplify our life adding the component alias to our AST,
            // since we have it on hands now.
            $k = array_keys($queryComponents);
            $this->_identifiers[] = $k[0];
        }
    }


    public function buildSql()
    {
        return '';
    }
}
