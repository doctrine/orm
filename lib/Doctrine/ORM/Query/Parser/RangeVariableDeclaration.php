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
 * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_RangeVariableDeclaration extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    

    public function syntax()
    {
        // RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
        $this->_AST = $this->AST('RangeVariableDeclaration');
        
        $this->_AST->setAbstractSchemaName($this->parse('AbstractSchemaName'));
        
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_ORM_Query_Token::T_AS);
        }

        $this->_AST->setAliasIdentificationVariable($this->parse('AliasIdentificationVariable'));
    }
    
    
    public function semantical()
    {
        $parserResult = $this->_parser->getParserResult();
        $componentName = $this->_AST->getAbstractSchemaName()->getComponentName();
        $componentAlias = $this->_AST->getAliasIdentificationVariable()->getComponentAlias();

        // Check if we already have a component defined without an alias
        if ($componentAlias === null && $parserResult->hasQueryComponent($componentName)) {
            $this->_parser->semanticalError(
                "Cannot re-declare component '{$componentName}'. Please assign an alias to it."
            );
        // Define new queryComponent since it does not exist yet
        } else {
            // Retrieving ClassMetadata and Mapper
            try {
                $classMetadata = $this->_em->getClassMetadata($componentName);
    
                // Building queryComponent
                $queryComponent = array(
                    'metadata' => $classMetadata,
                    'parent'   => null,
                    'relation' => null,
                    'map'      => null,
                    'scalar'   => null,
                );
            } catch (Doctrine_Exception $e) {
                $this->_parser->semanticalError($e->getMessage());
            }

            // Inspect for possible non-aliasing
            if ($componentAlias === null) {
                $componentAlias = $componentName;
            }
    
            $tableAlias = $parserResult->generateTableAlias($classMetadata->getClassName());
            $parserResult->setQueryComponent($componentAlias, $queryComponent);
            $parserResult->setTableAlias($tableAlias, $componentAlias);
        }
        
        // Return AST node
        return $this->_AST;
    }
}