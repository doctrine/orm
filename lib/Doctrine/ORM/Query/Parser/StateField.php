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
 * StateField ::= {EmbeddedClassStateField "."}* SimpleStateField
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-doctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_StateField extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    
    
    public function syntax()
    {
        // StateField ::= {EmbeddedClassStateField "."}* SimpleStateField
        $this->_AST = $this->AST('StateField');

        while ($this->_parser->lookahead['type'] == Doctrine_ORM_Query_Token::T_IDENTIFIER && 
               $this->_isNextToken(Doctrine_ORM_Query_Token::T_DOT)) {
            $this->_AST->addEmbeddedClassStateField($this->parse('EmbeddedClassStateField'));
            $this->_parser->match(Doctrine_ORM_Query_Token::T_DOT); 
        }
        
        $this->_AST->setSimpleStateField($this->parse('SimpleStateField'));
    }
}