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
 * An abstract base class for the productions of the Doctrine Query Language
 * context-free grammar.
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
abstract class Doctrine_Query_Production
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
     * @var Doctrine_Query_Parser
     */
    protected $_parser;


    /**
     * Creates a new production object.
     *
     * @param Doctrine_Query_Parser $parser a parser object
     */
    public function __construct(Doctrine_Query_Parser $parser)
    {
        $this->_parser = $parser;
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
        return ($la['type'] === Doctrine_Query_Token::T_IDENTIFIER && $next['value'] === '(');
    }


    protected function _isSubselect()
    {
        $la = $this->_parser->lookahead;
        $next = $this->_parser->getScanner()->peek();
        return ($la['value'] === '(' && $next['type'] === Doctrine_Query_Token::T_SELECT);
    }


    /**
     * Executes the production AST using the specified parameters.
     *
     * @param string $AstName Production AST name
     * @param array $paramHolder Production parameter holder
     * @return Doctrine_Query_Production
     */
    public function AST($AstName, $paramHolder)
    {
        $AST = $this->_getProduction($AstName);

        //echo "Processing class: " . get_class($AST) . "...\n";
        //echo "Params: " . var_export($paramHolder, true) . "\n";

        // Syntax check
        if ( ! $paramHolder->has('syntaxCheck') || $paramHolder->get('syntaxCheck') === true) {
            //echo "Processing syntax checks of " . $AstName . "...\n";

            $return = $AST->syntax($paramHolder);

            if ($return !== null) {
                //echo "Returning AST class: " . (is_object($return) ? get_class($return) : $return) . "...\n";

                return $return;
            }
        }

        // Semantical check
        if ( ! $paramHolder->has('semanticalCheck') || $paramHolder->get('semanticalCheck') === true) {
            //echo "Processing semantical checks of " . $AstName . "...\n";

            $return = $AST->semantical($paramHolder);

            if ($return !== null) {
                //echo "Returning AST class: " . (is_object($return) ? get_class($return) : $return) . "...\n";

                return $return;
            }
        }

        //echo "Returning AST class: " . get_class($AST) . "...\n";

        return $AST;
    }


    /**
     * Returns a production object with the given name.
     *
     * @param string $name production name
     * @return Doctrine_Query_Production
     */
    protected function _getProduction($name)
    {
        $class = 'Doctrine_Query_Production_' . $name;

        return new $class($this->_parser);
    }


    /**
     * Executes a production with specified name and parameters.
     *
     * @param string $name production name
     * @param array $params an associative array containing parameter names and
     * their values
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) === 'get') {
            $var = '_' . substr($method, 3);
            $var[1] = strtolower($var[1]);

            return $this->$var;
        }

        return null;
    }


    /**
     * Executes this production using the specified parameters.
     *
     * @param array $paramHolder Production parameter holder
     * @return Doctrine_Query_Production
     */
    public function execute($paramHolder)
    {
        //echo "Processing class: " . get_class($this) . " params: \n" . var_export($paramHolder, true) . "\n";

        // Syntax check
        if ( ! $paramHolder->has('syntaxCheck') || $paramHolder->get('syntaxCheck') === true) {
            //echo "Processing syntax checks of " . get_class($this) . "...\n";

            $return = $this->syntax($paramHolder);

            if ($return !== null) {
                return $return;
            }
        }

        // Semantical check
        if ( ! $paramHolder->has('semanticalCheck') || $paramHolder->get('semanticalCheck') === true) {
            //echo "Processing semantical checks of " . get_class($this) . "...\n";

            $return = $this->semantical($paramHolder);

            if ($return !== null) {
                return $return;
            }
        }

        // Return AST instance
        return $this;
    }


    /**
     * Executes this sql builder using the specified parameters.
     *
     * @return string Sql piece
     */
    public function buildSql()
    {
        $className = get_class($this);
        $methodName = substr($className, strrpos($className, '_'));

        $this->_sqlBuilder->$methodName($this);
    }


    /**
     * @nodoc
     */
    abstract public function syntax($paramHolder);


    /**
     * @nodoc
     */
    public function semantical($paramHolder)
    {
    }
}
