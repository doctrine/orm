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

namespace Doctrine\ORM\Query;

/**
 * An abstract base class for the productions of the Doctrine Query Language
 * context-free grammar.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
abstract class ParserRule
{
    /**
     * @nodoc
     */
    const SQLALIAS_SEPARATOR = '__';

    /**
     * @nodoc
     */
    const DEFAULT_QUERYCOMPONENT = 'dctrn';

    /**
     * Parser object
     *
     * @var Doctrine_ORM_Query_Parser
     */
    protected $_parser;
    
    /**
     * The EntityManager.
     *
     * @var EntityManager
     */
    protected $_em;
    
    /**
     * The Parser Data Holder.
     *
     * @var ParserDataHolder
     */
    protected $_dataHolder;

    /**
     * Creates a new production object.
     *
     * @param Doctrine_ORM_Query_Parser $parser a parser object
     */
    public function __construct(Doctrine_ORM_Query_Parser $parser)
    {
        $this->_parser = $parser;
        $this->_em = $this->_parser->getEntityManager();
        $this->_dataHolder = Doctrine_ORM_Query_ParserDataHolder::create();
    }

    protected function _isNextToken($token)
    {
        $la = $this->_parser->lookahead;
        return ($la['type'] === $token || $la['value'] === $token);
    }

    protected function _isFunction()
    {
        $la = $this->_parser->lookahead;
        $next = $this->_parser->getScanner()->peek();
        return ($la['type'] === Doctrine_ORM_Query_Token::T_IDENTIFIER && $next['value'] === '(');
    }

    protected function _isSubselect()
    {
        $la = $this->_parser->lookahead;
        $next = $this->_parser->getScanner()->peek();
        return ($la['value'] === '(' && $next['type'] === Doctrine_ORM_Query_Token::T_SELECT);
    }

    /**
     * Executes the grammar rule using the specified parameters.
     *
     * @param string $RuleName BNF Grammar Rule name
     * @param array $paramHolder Production parameter holder
     * @return Doctrine_ORM_Query_AST The constructed subtree during parsing.
     */
    public function parse($ruleName)
    {
        echo $ruleName . PHP_EOL;
        return $this->_getGrammarRule($ruleName)->syntax();

        // Syntax check
        /*if ( ! $this->_dataHolder->has('syntaxCheck') || $this->_dataHolder->get('syntaxCheck') === true) {
            //echo "Processing syntax checks of " . $RuleName . "...\n";
            $ASTNode = $BNFGrammarRule->syntax();
            if ($ASTNode !== null) {
                //echo "Returning Grammar Rule class: " . (is_object($ASTNode) ? get_class($ASTNode) : $ASTNode) . "...\n";
                return $ASTNode;
            }
        }*/

        // Semantical check
        /*if ( ! $this->_dataHolder->has('semanticalCheck') || $this->_dataHolder->get('semanticalCheck') === true) {
            echo "Processing semantical checks of " . $RuleName . "...\n";

            $return = $BNFGrammarRule->semantical();

            if ($return !== null) {
                echo "Returning Grammar Rule class: " . (is_object($return) ? get_class($return) : $return) . "...\n";

                return $return;
            }
        }*/

        return $BNFGrammarRule;
    }

    /**
     * Returns a grammar rule object with the given name.
     *
     * @param string $name grammar rule name
     * @return Doctrine_ORM_Query_ParserRule
     */
    protected function _getGrammarRule($name)
    {
        $class = 'Doctrine_ORM_Query_Parser_' . $name;

        //echo $class . "\r\n";
        //TODO: This expensive check is not necessary. Should be removed at the end.
        //      "new $class" will throw an error anyway if the class is not found.
        if ( ! class_exists($class)) {
            throw \Doctrine\Common\DoctrineException::updateMe(
                "Unknown Grammar Rule '$name'. Could not find related compiler class."
            );
        }

        return new $class($this->_parser);
    }
        
    /**
     * Creates an AST node with the given name.
     *
     * @param string $AstName AST node name
     * @return Doctrine_ORM_Query_AST
     */
    public function AST($AstName)
    {
        $class = 'Doctrine_ORM_Query_AST_' . $AstName;
        return new $class($this->_parser->getParserResult());
    }

    /**
     * @nodoc
     */
    abstract public function syntax();

    /**
     * @nodoc
     */
    public function semantical()
    {
    }

    public function getParser()
    {
        return $this->_parser;
    }
    
    public function getDataHolder()
    {
        return $this->_dataHolder;
    }
}