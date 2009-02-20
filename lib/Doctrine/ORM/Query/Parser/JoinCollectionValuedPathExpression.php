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

namespace Doctrine\ORM\Query\Parser;

/**
 * JoinCollectionValuedPathExpression ::= IdentificationVariable "." CollectionValuedAssociationField
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class JoinCollectionValuedPathExpression extends \Doctrine\ORM\Query\ParserRule
{
    protected $_AST = null;
    
    public function syntax()
    {
        // JoinCollectionValuedPathExpression ::= IdentificationVariable "." CollectionValuedAssociationField
        $this->_AST = $this->AST('JoinCollectionValuedPathExpression');
        
        $this->_AST->setIdentificationVariable($this->parse('IdentificationVariable'));
        $this->_parser->match(\Doctrine\ORM\Query\Token::T_DOT);
        $this->_AST->setCollectionValuedAssociationField($this->parse('CollectionValuedAssociationField'));
    }

    public function semantical()
    {
        $parserResult = $this->_parser->getParserResult();
        $queryComponent = $parserResult->getQueryComponent($this->_AST->setIdentificationVariable());
        $fieldName = $this->_AST->setCollectionValuedAssociationField();

        if ( ! $queryComponent['metadata']->hasField($fieldName)) {
            $componentName = $queryComponent['metadata']->getClassName();

            $message = "Field '" . $fieldName . "' does not exist in component '" . $componentName . "'.";

            $this->_parser->semanticalError($message);
        }

        if ( ! ($queryComponent['metadata']->hasAssociation($fieldName) &&
            $queryComponent['metadata']->getAssociation($fieldName)->isOneToTone())) {
            $componentName = $queryComponent['metadata']->getClassName();

            $message = "Field '" . $fieldName . "' does not map to a collection valued association in component '" 
                     . $componentName . "'.";

            $this->_parser->semanticalError($message);
        }

        return $this->_AST;
    }
}