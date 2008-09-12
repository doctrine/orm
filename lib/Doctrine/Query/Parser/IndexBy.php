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
 * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
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
class Doctrine_Query_Parser_IndexBy extends Doctrine_Query_ParserRule
{
    protected $_AST = null;
    

    public function syntax($paramHolder)
    {
        // IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
        $this->_AST = $this->AST('IndexBy');
        
        $this->_parser->match(Doctrine_Query_Token::T_INDEX);
        $this->_parser->match(Doctrine_Query_Token::T_BY);

        $this->_AST->setSimpleStateFieldPathExpression($this->parse('SimpleStateFieldPathExpression', $paramHolder));
    }
    
    
    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();
        $componentAlias = $this->_AST->getSimpleStateFieldPathExpression()
            ->getIdentificationVariable()->getComponentAlias();
        $componentFieldName = $this->_AST->getSimpleStateFieldPathExpression()
            ->getSimpleStateField()->getFieldName();
            
        // Check if we have same component being used in index
        if ($componentAlias !== $paramHolder->get('componentAlias')) {
            $message = "Invalid alising. Cannot index by '" . $paramHolder->get('componentAlias')
                     . "' inside '" . $componentAlias . "' scope.";

            $this->_parser->semanticalError($message);
        }

        // Retrieving required information
        try {
            $queryComponent = $parserResult->getQueryComponent($componentAlias);
            $classMetadata = $queryComponent['metadata'];
        } catch (Doctrine_Exception $e) {
            $this->_parser->semanticalError($e->getMessage());

            return;
        }
        
        // The INDEXBY field must be either the (primary && not part of composite pk) || (unique && notnull)
        $columnMapping = $classMetadata->getFieldMapping($componentFieldName);

        if ( 
            ! $classMetadata->isIdentifier($componentFieldName) && 
            ! $classMetadata->isUniqueField($componentFieldName) && 
            ! $classMetadata->isNotNull($componentFieldName)
        ) {
            $this->_parser->semanticalError(
                "Field '" . $componentFieldName . "' of component  '" . $classMetadata->getClassName() .
                "' must be unique and notnull to be used as index.",
                $this->_parser->token
            );
        }

        if ($classMetadata->isIdentifier($componentFieldName) && $classMetadata->isIdentifierComposite()) {
            $this->_parser->semanticalError(
                "Field '" . $componentFieldName . "' of component  '" . $classMetadata->getClassName() .
                "' must be primary and not part of a composite primary key to be used as index.",
                $this->_parser->token
            );
        }

        $queryComponent['map'] = $componentFieldName;
        $parserResult->setQueryComponent($componentAlias, $queryComponent);

        // Return AST node
        return $this->_AST;
    }
}