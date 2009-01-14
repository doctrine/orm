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
 * SimpleStateFieldPathExpression ::= IdentificationVariable "." SimpleStateField
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_SimpleStateFieldPathExpression extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    
 
    public function syntax()
    {
        // SimpleStateFieldPathExpression ::= IdentificationVariable "." SimpleStateField
        $this->_AST = $this->AST('SimpleStateFieldPathExpression');
        
        $this->_AST->setIdentificationVariable($this->parse('IdentificationVariable'));

        $this->_parser->match('.');
        
        $this->_AST->setSimpleStateField($this->parse('SimpleStateField'));
    }

    
    public function semantical()
    {
        $parserResult = $this->_parser->getParserResult();
        $componentAlias = $this->_AST->getIdentificationVariable()->getComponentAlias();
        $componentFieldName = $this->_AST->getSimpleStateField()->getFieldName();

        // We need to make sure field exists
        try {
            $queryComponent = $parserResult->getQueryComponent($componentAlias);
            $classMetadata = $queryComponent['metadata'];
        } catch (Doctrine_Exception $e) {
            $this->_parser->semanticalError($e->getMessage());

            return;
        }

        if ($classMetadata instanceof Doctrine_ClassMetadata && ! $classMetadata->hasField($componentFieldName)) {
            $this->_parser->semanticalError(
                "Cannot use key mapping. Field '" . $componentFieldName . "' " .
                "does not exist in component '" . $classMetadata->getClassName() . "'.",
                $this->_parser->token
            );
        }

        // Return AST node
        return $this->_AST;
    }
}