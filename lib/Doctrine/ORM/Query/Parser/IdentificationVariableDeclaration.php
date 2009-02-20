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
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class IdentificationVariableDeclaration extends \Doctrine\ORM\Query\ParserRule
{
    protected $_AST = null;
    

    public function syntax()
    {
        // IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
        $this->_AST = $this->AST('IdentificationVariableDeclaration');
        
        $this->_AST->setRangeVariableDeclaration($this->parse('RangeVariableDeclaration'));

        if ($this->_isNextToken(\Doctrine\ORM\Query\Token::T_INDEX)) {
            $this->_AST->setIndexBy($this->parse('IndexBy'));
        }

        while (
            $this->_isNextToken(\Doctrine\ORM\Query\Token::T_LEFT) ||
            $this->_isNextToken(\Doctrine\ORM\Query\Token::T_INNER) ||
            $this->_isNextToken(\Doctrine\ORM\Query\Token::T_JOIN)
        ) {
            $this->_AST->addJoinVariableDeclaration($this->parse('JoinVariableDeclaration'));
        }
    }
    
    
    public function semantical()
    {
        // If we have an INDEX BY RangeVariableDeclaration
        if ($this->_AST->getIndexby() !== null) {
            // Grab Range component alias
            $rangeComponentAlias = $this->_AST->getRangeVariableDeclaration()->getAliasIdentificationVariable();

            // Grab IndexBy component alias
            $indexComponentAlias = $this->_AST->getIndexBy()->getSimpleStateFieldPathExpression()
                ->getIdentificationVariable();

            // Check if we have same component being used in index
            if ($rangeComponentAlias !== $indexComponentAlias) {
                $message = "Invalid aliasing. Cannot index by '" . $indexComponentAlias
                         . "' inside '" . $rangeComponentAlias . "' scope.";

                $this->_parser->semanticalError($message);
            }
        }

        // Return AST node
        return $this->_AST;
    }
}