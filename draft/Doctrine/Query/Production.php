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
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
abstract class Doctrine_Query_Production
{
    /**
     * a parser object
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
        $this->_parser->getPrinter()->startProduction($name);
        $retval = $this->_parser->getProduction($method)->execute($args);
        $this->_parser->getPrinter()->endProduction();

        return $retval;
    }

    /**
     * Executes this production using the specified parameters.
     *
     * @param array $params an associative array containing parameter names and
     * their values
     * @return mixed
     */
    abstract public function execute(array $params = array());
}
